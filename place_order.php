<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require 'config/database.php';
require_once 'audit_log.php';
require_once 'auth.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
    echo json_encode(['success'=>false,'message'=>'Please log in as a customer to place an order.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['items']) || !is_array($input['items'])) {
    echo json_encode(['success'=>false,'message'=>'Invalid order data.']);
    exit;
}

$customerId = (int)$_SESSION['user_id'];

// A deactivated customer account cannot place orders.
$accountState = getAccountState($conn, $customerId);
if ($accountState === null || $accountState['status'] !== 'Active') {
    endSessionWithMessage('This account has been deactivated. Please contact the administrator.');
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'This account has been deactivated.']); exit;
}

$orderType = $input['orderType'] ?? '';
$paymentMethod = $input['paymentMethod'] ?? '';
$customerName = trim($input['customerName'] ?? ($_SESSION['fullname'] ?? ''));
$phone = trim($input['phone'] ?? '');
$gcashReference = trim($input['gcashReference'] ?? '');

// The restaurant serves dine-in and take-out only (no delivery), and accepts Cash and GCash only.
if (!in_array($orderType, ['Dine-In','Take-Out'], true)) {
    echo json_encode(['success'=>false,'message'=>'Please choose Dine-In or Take-Out.']); exit;
}
if (!in_array($paymentMethod, ['Cash','GCash'], true)) {
    echo json_encode(['success'=>false,'message'=>'Invalid payment method. Only Cash and GCash are accepted.']); exit;
}
if ($paymentMethod === 'GCash' && ($gcashReference === '' || $gcashReference === 'N/A')) {
    echo json_encode(['success'=>false,'message'=>'Please enter your GCash reference number.']); exit;
}
if ($paymentMethod === 'Cash') {
    $gcashReference = '';
}
if ($customerName === '' || $phone === '') {
    echo json_encode(['success'=>false,'message'=>'Customer name and phone are required.']); exit;
}

try {
    $conn->begin_transaction();
    $total = 0;
    $validatedItems = [];

    foreach ($input['items'] as $item) {
        $code = trim((string)($item['productId'] ?? ''));
        $qty = max(1, (int)($item['quantity'] ?? 0));
        if ($code === '') throw new Exception('A menu item is missing its product code.');

        $stmt = $conn->prepare("SELECT id,code,name,price,stock,status FROM menu WHERE code=? LIMIT 1");
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $menu = $stmt->get_result()->fetch_assoc();
        if (!$menu) throw new Exception("Menu item '$code' was not found.");
        if ($menu['status'] !== 'Available') throw new Exception($menu['name'].' is currently unavailable.');
        if ((int)$menu['stock'] < $qty) throw new Exception('Not enough stock for '.$menu['name'].'.');

        $price = (float)$menu['price'];
        $total += $price * $qty;
        $validatedItems[] = ['menu_id'=>(int)$menu['id'],'name'=>$menu['name'],'price'=>$price,'quantity'=>$qty];
    }

    $stmt = $conn->prepare("INSERT INTO orders (customer_id,customer_name,phone,order_type,total,payment_method,gcash_reference,status,created_at) VALUES (?,?,?,?,?,?,?,'Pending',NOW())");
    $stmt->bind_param('isssdss', $customerId, $customerName, $phone, $orderType, $total, $paymentMethod, $gcashReference);
    $stmt->execute();
    $orderId = $conn->insert_id;

    foreach ($validatedItems as $item) {
        $stmt = $conn->prepare("INSERT INTO order_items(order_id,menu_id,quantity,price) VALUES(?,?,?,?)");
        $stmt->bind_param('iiid',$orderId,$item['menu_id'],$item['quantity'],$item['price']);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE menu SET stock=stock-? WHERE id=? AND stock>=?");
        $stmt->bind_param('iii',$item['quantity'],$item['menu_id'],$item['quantity']);
        $stmt->execute();
        if ($stmt->affected_rows !== 1) throw new Exception('Stock changed while placing the order. Please try again.');
    }

    $paymentStatus = ($paymentMethod === 'GCash' && $gcashReference !== '') ? 'Paid' : 'Unpaid';
    $stmt = $conn->prepare("INSERT INTO payments(order_id,amount,payment_method,payment_status,payment_date) VALUES(?,?,?,?,NOW())");
    $stmt->bind_param('idss',$orderId,$total,$paymentMethod,$paymentStatus);
    $stmt->execute();

    // Sales are created only when the cashier completes the order.
    $conn->commit();
    createAuditLog($conn, 'Customer Order Placed', 'Order #'.$orderId, $orderType.' | '.$paymentMethod.' | PHP '.number_format($total,2));

    echo json_encode([
        'success'=>true,
        'orderId'=>$orderId,
        'orderNumber'=>'SC-ONLINE-'.str_pad($orderId,6,'0',STR_PAD_LEFT),
        'status'=>'Pending',
        'total'=>$total,
        'message'=>'Order placed successfully! It is now visible to the cashier and kitchen.'
    ]);
} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
