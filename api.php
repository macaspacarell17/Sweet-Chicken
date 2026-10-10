<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
require 'config/database.php';
require_once 'audit_log.php';
require_once 'auth.php';

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Unauthorized. Please log in.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$role = $_SESSION['role'];

// Re-check the account on every request so a deactivation or role change takes effect immediately.
$accountState = getAccountState($conn, $userId);
if ($accountState === null || $accountState['status'] !== 'Active') {
    endSessionWithMessage('This account has been deactivated. Please contact the administrator.');
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'This account has been deactivated.']);
    exit;
}
$role = $accountState['role'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}
function requireRole($roles) {
    global $role;
    if (!in_array($role, $roles, true)) respond(['success'=>false,'message'=>'Access denied.'], 403);
}
function bodyJson() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $_POST;
}

try {
    switch ($action) {
        case 'create_walkin_order':
            requireRole(['cashier','admin']);
            $data = bodyJson();
            $customerName = trim((string)($data['customer_name'] ?? 'Walk-in Customer'));
            $orderType = trim((string)($data['order_type'] ?? 'Walk-In'));
            $tableNumber = trim((string)($data['table_number'] ?? ''));
            $paymentMethod = (string)($data['payment_method'] ?? 'Cash');
            $paymentStatus = (string)($data['payment_status'] ?? 'Paid');
            $items = $data['items'] ?? [];
            $discount = max(0.0, min(100.0, (float)($data['discount'] ?? 0)));
            $total = (float)($data['total'] ?? 0);

            if ($customerName === '') $customerName = 'Walk-in Customer';
            if (!in_array($orderType, ['Walk-In','Dine-In','Take-Out'], true)) $orderType = 'Walk-In';
            if ($orderType === 'Dine-In' && $tableNumber === '') respond(['success'=>false,'message'=>'Please enter a table number for Dine-In orders.'],400);
            if (strlen($tableNumber) > 20) respond(['success'=>false,'message'=>'Table number is too long.'],400);
            if ($orderType !== 'Dine-In') $tableNumber = '';
            if (!in_array($paymentMethod, ['Cash','GCash'], true)) respond(['success'=>false,'message'=>'Invalid payment method. Only Cash and GCash are accepted.'],400);
            if (!in_array($paymentStatus, ['Paid','Unpaid'], true)) $paymentStatus = 'Paid';
            if (!is_array($items) || count($items) === 0) respond(['success'=>false,'message'=>'No items in the order.'],400);

            $conn->begin_transaction();
            try {
                // Recalculate the total from the actual menu prices.
                $calculatedTotal = 0.0;
                $resolvedItems = [];
                foreach ($items as $item) {
                    $code = trim((string)($item['id'] ?? $item['code'] ?? ''));
                    $qty = max(1, (int)($item['quantity'] ?? 1));
                    if ($code === '') throw new Exception('Invalid menu item.');
                    $stmt = $conn->prepare("SELECT id,name,price,stock,status FROM menu WHERE code=? LIMIT 1");
                    $stmt->bind_param('s',$code); $stmt->execute();
                    $menu = $stmt->get_result()->fetch_assoc();
                    if (!$menu) throw new Exception('Menu item not found: '.$code);
                    if (($menu['status'] ?? 'Available') !== 'Available') throw new Exception($menu['name'].' is unavailable.');
                    if ((int)$menu['stock'] < $qty) throw new Exception('Not enough stock for '.$menu['name'].'.');
                    $price = (float)$menu['price'];
                    $calculatedTotal += $price * $qty;
                    $resolvedItems[] = ['menu_id'=>(int)$menu['id'],'quantity'=>$qty,'price'=>$price];
                }

                $discountedTotal = round($calculatedTotal * (1 - ($discount / 100)), 2);
                $stmt = $conn->prepare("INSERT INTO orders (customer_id,customer_name,phone,order_type,table_number,total,payment_method,status,created_at) VALUES (NULL,?,NULL,?,?,?,?,'Preparing',NOW())");
                $stmt->bind_param('sssds', $customerName, $orderType, $tableNumber, $discountedTotal, $paymentMethod);
                if (!$stmt->execute()) throw new Exception($stmt->error);
                $orderId = $conn->insert_id;

                foreach ($resolvedItems as $item) {
                    $stmt = $conn->prepare("INSERT INTO order_items(order_id,menu_id,quantity,price) VALUES(?,?,?,?)");
                    $stmt->bind_param('iiid',$orderId,$item['menu_id'],$item['quantity'],$item['price']);
                    if (!$stmt->execute()) throw new Exception($stmt->error);

                    $stmt = $conn->prepare("UPDATE menu SET stock=stock-? WHERE id=? AND stock>=?");
                    $stmt->bind_param('iii',$item['quantity'],$item['menu_id'],$item['quantity']);
                    if (!$stmt->execute() || $stmt->affected_rows !== 1) throw new Exception('Unable to update stock.');
                }

                $stmt = $conn->prepare("INSERT INTO payments(order_id,amount,payment_method,payment_status,payment_date) VALUES(?,?,?,?,NOW())");
                $stmt->bind_param('idss',$orderId,$discountedTotal,$paymentMethod,$paymentStatus);
                if (!$stmt->execute()) throw new Exception($stmt->error);

                $conn->commit();
                createAuditLog($conn, 'Walk-in Order Created', 'Order #'.$orderId, $orderType.' | '.$paymentMethod.' ('.$paymentStatus.') | PHP '.number_format($discountedTotal,2));
                $orderNumber = 'SC-WALKIN-' . str_pad($orderId, 6, '0', STR_PAD_LEFT);
                respond(['success'=>true,'order_id'=>$orderId,'order_number'=>$orderNumber,'table_number'=>$tableNumber,'status'=>'Preparing','total'=>$discountedTotal,'message'=>'Walk-in order sent to the kitchen.']);
            } catch (Throwable $e) {
                $conn->rollback();
                respond(['success'=>false,'message'=>$e->getMessage()],400);
            }

        case 'orders':

            requireRole(['admin','cashier','kitchen','customer']);
            $sql = "SELECT o.id, o.customer_id, o.customer_name, o.phone, o.order_type, o.table_number,
                           o.total, o.payment_method, o.status, o.created_at,
                           COALESCE(p.payment_status,'Unpaid') AS payment_status,
                           u.fullname AS account_name,
                           COALESCE(GROUP_CONCAT(CONCAT(m.name,' x',oi.quantity) ORDER BY oi.id SEPARATOR ', '),'') AS items
                    FROM orders o
                    LEFT JOIN users u ON u.id=o.customer_id
                    LEFT JOIN payments p ON p.order_id=o.id
                    LEFT JOIN order_items oi ON oi.order_id=o.id
                    LEFT JOIN menu m ON m.id=oi.menu_id";
            $params = []; $types = '';
            if ($role === 'customer') {
                $sql .= " WHERE o.customer_id=?";
                $params[] = $userId; $types .= 'i';
            }
            $sql .= " GROUP BY o.id ORDER BY o.created_at DESC";
            $stmt = $conn->prepare($sql);
            if ($types) $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $orders = [];
            while ($row = $result->fetch_assoc()) {
                $row['id'] = (int)$row['id'];
                $row['total'] = (float)$row['total'];
                $prefix = (strtolower((string)$row['order_type']) === 'walk-in') ? 'SC-WALKIN-' : 'SC-ONLINE-';
                $row['order_number'] = $prefix . str_pad($row['id'], 6, '0', STR_PAD_LEFT);
                $orders[] = $row;
            }
            respond(['success'=>true,'orders'=>$orders]);

        case 'order_details':
            requireRole(['admin','cashier','kitchen','customer']);
            $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
            $stmt = $conn->prepare("SELECT o.*, u.fullname AS account_name, COALESCE(p.payment_status,'Unpaid') payment_status
                                    FROM orders o LEFT JOIN users u ON u.id=o.customer_id
                                    LEFT JOIN payments p ON p.order_id=o.id WHERE o.id=?");
            $stmt->bind_param('i', $id); $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();
            if (!$order || ($role === 'customer' && (int)$order['customer_id'] !== $userId)) respond(['success'=>false,'message'=>'Order not found.'],404);
            $stmt = $conn->prepare("SELECT oi.menu_id, oi.quantity, oi.price, m.name FROM order_items oi JOIN menu m ON m.id=oi.menu_id WHERE oi.order_id=? ORDER BY oi.id");
            $stmt->bind_param('i',$id); $stmt->execute();
            $items=[]; $r=$stmt->get_result();
            while($x=$r->fetch_assoc()){ $x['quantity']=(int)$x['quantity']; $x['price']=(float)$x['price']; $items[]=$x; }
            $order['total']=(float)$order['total'];
            $order['items']=$items;
            $prefix = (strtolower((string)$order['order_type']) === 'walk-in') ? 'SC-WALKIN-' : 'SC-ONLINE-';
            $order['order_number']=$prefix.str_pad($id,6,'0',STR_PAD_LEFT);
            respond(['success'=>true,'order'=>$order]);

        case 'update_order_status':
            $data = bodyJson();
            $id = (int)($data['order_id'] ?? 0);
            $newStatus = $data['status'] ?? '';
            $valid = ['Pending','Preparing','Ready','Completed','Cancelled'];
            if (!$id || !in_array($newStatus,$valid,true)) respond(['success'=>false,'message'=>'Invalid order or status.'],400);
            requireRole(['admin','cashier','kitchen']);
            $stmt=$conn->prepare("SELECT status,total FROM orders WHERE id=?");
            $stmt->bind_param('i',$id); $stmt->execute(); $current=$stmt->get_result()->fetch_assoc();
            if(!$current) respond(['success'=>false,'message'=>'Order not found.'],404);
            $old=$current['status'];
            if ($role==='cashier' && !in_array($newStatus,['Preparing','Completed','Cancelled'],true)) respond(['success'=>false,'message'=>'Cashier can confirm, complete, or cancel orders.'],403);
            if ($role==='kitchen' && !in_array($newStatus,['Ready'],true)) respond(['success'=>false,'message'=>'Kitchen can mark confirmed orders as Ready.'],403);
            if ($role==='kitchen' && $newStatus==='Ready' && $old!=='Preparing') respond(['success'=>false,'message'=>'Order must be Preparing before it is Ready.'],400);
            if ($role==='cashier' && $newStatus==='Completed' && $old!=='Ready') respond(['success'=>false,'message'=>'Order must be Ready before completion.'],400);
            if ($role==='cashier' && $newStatus==='Preparing' && !in_array($old,['Pending','Preparing'],true)) respond(['success'=>false,'message'=>'Order is not pending.'],400);

            $stmt=$conn->prepare("UPDATE orders SET status=? WHERE id=?");
            $stmt->bind_param('si',$newStatus,$id); $stmt->execute();
            createAuditLog($conn, 'Order Status Updated', 'Order #'.$id, $old.' -> '.$newStatus);
            if ($newStatus==='Completed') {
                $stmt=$conn->prepare("INSERT INTO sales(order_id,total,created_at) SELECT id,total,NOW() FROM orders WHERE id=? AND NOT EXISTS(SELECT 1 FROM sales WHERE order_id=?)");
                $stmt->bind_param('ii',$id,$id); $stmt->execute();
            }
            respond(['success'=>true,'message'=>"Order #$id updated to $newStatus.",'status'=>$newStatus]);

        case 'update_payment':
            requireRole(['admin','cashier']);
            $data=bodyJson(); $id=(int)($data['order_id']??0); $status=$data['payment_status']??'';
            if(!in_array($status,['Paid','Unpaid'],true)) respond(['success'=>false,'message'=>'Invalid payment status.'],400);
            $stmt=$conn->prepare("UPDATE payments SET payment_status=?, payment_date=NOW() WHERE order_id=?");
            $stmt->bind_param('si',$status,$id); $stmt->execute();
            createAuditLog($conn, 'Payment Status Updated', 'Order #'.$id, 'Payment marked '.$status);
            respond(['success'=>true]);

        case 'menu':
            $stmt=$conn->prepare("SELECT id,code,name,category,description,price,image,stock,status FROM menu WHERE status='Available' ORDER BY id");
            $stmt->execute(); $r=$stmt->get_result(); $menu=[];
            while($m=$r->fetch_assoc()){ $m['id']=(int)$m['id']; $m['price']=(float)$m['price']; $m['stock']=(int)$m['stock']; $menu[]=$m; }
            respond(['success'=>true,'menu'=>$menu]);

        case 'admin_menu':
            requireRole(['admin']);
            $stmt=$conn->prepare("SELECT id,code,name,category,description,price,image,stock,status FROM menu ORDER BY id");
            $stmt->execute(); $r=$stmt->get_result(); $menu=[];
            while($m=$r->fetch_assoc()){ $m['id']=(int)$m['id']; $m['price']=(float)$m['price']; $m['stock']=(int)$m['stock']; $menu[]=$m; }
            respond(['success'=>true,'menu'=>$menu]);

        case 'save_menu':
            requireRole(['admin']);
            $data=bodyJson();
            $id=(int)($data['id']??0); $code=trim($data['code']??''); $name=trim($data['name']??'');
            $category=trim($data['category']??'Other'); $description=trim($data['description']??'');
            $price=(float)($data['price']??0); $stock=(int)($data['stock']??0); $status=($data['status']??'Available')==='Available'?'Available':'Unavailable';
            if($code===''||$name===''||$price<0) respond(['success'=>false,'message'=>'Please provide valid menu data.'],400);
            if($id){
                $stmt=$conn->prepare("UPDATE menu SET code=?,name=?,category=?,description=?,price=?,stock=?,status=? WHERE id=?");
                $stmt->bind_param('ssssdisi',$code,$name,$category,$description,$price,$stock,$status,$id);
            } else {
                $stmt=$conn->prepare("INSERT INTO menu(code,name,category,description,price,stock,status) VALUES(?,?,?,?,?,?,?)");
                $stmt->bind_param('ssssdis',$code,$name,$category,$description,$price,$stock,$status);
            }
            $ok=$stmt->execute();
            if(!$ok) respond(['success'=>false,'message'=>'Could not save menu item: '.$stmt->error],400);
            $savedId = $id ?: $conn->insert_id;
            createAuditLog($conn, $id ? 'Menu Item Updated' : 'Menu Item Created', $name.' (#'.$savedId.')', 'Price PHP '.number_format($price,2).' | Stock '.$stock.' | '.$status);
            respond(['success'=>true,'id'=>$savedId]);

        case 'delete_menu':
            requireRole(['admin']); $data=bodyJson(); $id=(int)($data['id']??0);
            $stmt=$conn->prepare("UPDATE menu SET status='Unavailable' WHERE id=?"); $stmt->bind_param('i',$id); $stmt->execute();
            createAuditLog($conn, 'Menu Item Removed', 'Menu item #'.$id, 'Marked Unavailable');
            respond(['success'=>true]);

        case 'sales':
            requireRole(['admin','cashier']);
            // Sales are generated from completed orders and linked to the real order/payment records.
            // The item count is calculated from order_items so the report reflects the actual transaction.
            $stmt=$conn->prepare("SELECT s.id,s.order_id,s.total,s.created_at,
                                         COALESCE(NULLIF(o.customer_name,''),u.fullname,'Walk-in Customer') AS customer_name,
                                         o.order_type,o.payment_method,o.status,
                                         COALESCE(p.payment_status,'Unpaid') AS payment_status,
                                         COALESCE(SUM(oi.quantity),0) AS items_count
                                  FROM sales s
                                  JOIN orders o ON o.id=s.order_id
                                  LEFT JOIN users u ON u.id=o.customer_id
                                  LEFT JOIN payments p ON p.order_id=o.id
                                  LEFT JOIN order_items oi ON oi.order_id=o.id
                                  WHERE o.status='Completed'
                                  GROUP BY s.id,s.order_id,s.total,s.created_at,o.customer_name,u.fullname,o.order_type,o.payment_method,o.status,p.payment_status
                                  ORDER BY s.created_at DESC");
            $stmt->execute(); $r=$stmt->get_result(); $sales=[];
            while($s=$r->fetch_assoc()){
                $s['total']=(float)$s['total'];
                $s['items_count']=(int)$s['items_count'];
                $sales[]=$s;
            }
            respond(['success'=>true,'sales'=>$sales]);

        case 'users':
            requireRole(['admin']);
            $r=$conn->query("SELECT id,fullname,email,role,status,created_at FROM users ORDER BY id DESC"); $users=[];
            while($u=$r->fetch_assoc()) $users[]=$u;
            respond(['success'=>true,'users'=>$users]);

        case 'create_user':
            requireRole(['admin']); $data=bodyJson();
            $fullname=trim($data['fullname']??''); $email=trim($data['email']??''); $password=$data['password']??''; $newRole=$data['role']??'';
            // The Administrator creates employee accounts only; customers register themselves.
            if($fullname===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||!in_array($newRole,['cashier','kitchen'],true)) respond(['success'=>false,'message'=>'Invalid account data. Role must be Cashier or Kitchen.'],400);
            if(!preg_match('/^(?=.*[A-Za-z])(?=.*\d).{9,}$/',$password)) respond(['success'=>false,'message'=>'Password must be at least 9 characters and include a letter and a number.'],400);
            $hash=password_hash($password,PASSWORD_DEFAULT);
            $stmt=$conn->prepare("INSERT INTO users(fullname,email,password,role) VALUES(?,?,?,?)"); $stmt->bind_param('ssss',$fullname,$email,$hash,$newRole);
            try { $created=$stmt->execute(); } catch (Throwable $e) { $created=false; } // PHP 8.1+ throws on duplicate keys
            if(!$created) respond(['success'=>false,'message'=>'Could not create account. The email may already be registered.'],400);
            $newId=$conn->insert_id;
            createAuditLog($conn, 'Employee Account Created', $fullname.' ('.$newRole.')', 'User #'.$newId.' | '.$email);
            respond(['success'=>true,'id'=>$newId]);

        case 'update_user':
            requireRole(['admin']); $data=bodyJson();
            $id=(int)($data['id']??0); $fullname=trim($data['fullname']??''); $newRole=$data['role']??null;
            if(!$id||$fullname==='') respond(['success'=>false,'message'=>'A valid user and name are required.'],400);
            $stmt=$conn->prepare("SELECT fullname,role FROM users WHERE id=?"); $stmt->bind_param('i',$id); $stmt->execute();
            $target=$stmt->get_result()->fetch_assoc();
            if(!$target) respond(['success'=>false,'message'=>'User not found.'],404);
            if(!in_array($target['role'],['cashier','kitchen'],true)) respond(['success'=>false,'message'=>'Only Cashier and Kitchen accounts can be edited here.'],403);
            if($newRole===null||$newRole==='') $newRole=$target['role'];
            if(!in_array($newRole,['cashier','kitchen'],true)) respond(['success'=>false,'message'=>'Role must be Cashier or Kitchen.'],400);
            $stmt=$conn->prepare("UPDATE users SET fullname=?, role=? WHERE id=?"); $stmt->bind_param('ssi',$fullname,$newRole,$id); $stmt->execute();
            $changes=[];
            if($fullname!==$target['fullname']) $changes[]='name: '.$target['fullname'].' -> '.$fullname;
            if($newRole!==$target['role']) $changes[]='role: '.$target['role'].' -> '.$newRole;
            createAuditLog($conn, 'Employee Account Updated', $fullname.' (User #'.$id.')', $changes?implode('; ',$changes):'No changes');
            respond(['success'=>true]);

        case 'set_user_status':
            requireRole(['admin']); $data=bodyJson();
            $id=(int)($data['id']??0); $newStatus=$data['status']??'';
            if(!$id||!in_array($newStatus,['Active','Deactivated'],true)) respond(['success'=>false,'message'=>'Invalid user or status.'],400);
            if($id===$userId) respond(['success'=>false,'message'=>'You cannot change the status of your own account.'],400);
            $stmt=$conn->prepare("SELECT fullname,role FROM users WHERE id=?"); $stmt->bind_param('i',$id); $stmt->execute();
            $target=$stmt->get_result()->fetch_assoc();
            if(!$target) respond(['success'=>false,'message'=>'User not found.'],404);
            if(!in_array($target['role'],['cashier','kitchen'],true)) respond(['success'=>false,'message'=>'Only Cashier and Kitchen accounts can be activated or deactivated.'],403);
            $stmt=$conn->prepare("UPDATE users SET status=? WHERE id=?"); $stmt->bind_param('si',$newStatus,$id); $stmt->execute();
            createAuditLog($conn, $newStatus==='Deactivated'?'Account Deactivated':'Account Reactivated', $target['fullname'].' ('.$target['role'].', User #'.$id.')');
            respond(['success'=>true,'status'=>$newStatus]);

        case 'next_order_number':
            requireRole(['admin','cashier']);
            $r = $conn->query("SHOW TABLE STATUS LIKE 'orders'");
            $status = $r->fetch_assoc();
            $nextId = (int)($status['Auto_increment'] ?? 1);
            if ($nextId < 1) $nextId = 1;
            respond(['success'=>true,'next_order_number'=>'SC-' . str_pad($nextId, 6, '0', STR_PAD_LEFT)]);

        case 'metrics':
            requireRole(['admin','cashier']);
            $r=$conn->query("SELECT COUNT(*) total_orders, COALESCE(SUM(total),0) total_sales FROM orders WHERE status='Completed'");
            $metrics=$r->fetch_assoc();
            $r=$conn->query("SELECT COUNT(*) pending_orders FROM orders WHERE status IN('Pending','Preparing','Ready')"); $metrics=array_merge($metrics,$r->fetch_assoc());
            respond(['success'=>true,'metrics'=>$metrics]);

        default:
            respond(['success'=>false,'message'=>'Unknown API action.'],400);
    }
} catch (Throwable $e) {
    respond(['success'=>false,'message'=>$e->getMessage()],500);
}