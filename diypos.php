<?php

session_start();

// ─── Data ────────────────────────────────────────────────────────────────────

$categories = [
    ['id' => 'all',     'name' => 'All',     'icon' => '🍽️'],
    ['id' => 'chicken', 'name' => 'Chicken', 'icon' => '🍗'],
    ['id' => 'pasta',   'name' => 'Pasta',   'icon' => '🍝'],
    ['id' => 'sides',   'name' => 'Sides',   'icon' => '🥗'],
    ['id' => 'drinks',  'name' => 'Drinks',  'icon' => '🥤'],
];

$menuItems = [
    ['id'=>'7pc-combo',       'categoryId'=>'chicken','name'=>'7 pcs Combo',      'description'=>'with rice & 2 sides','price'=>289,'hasFlavors'=>true, 'image'=>'https://images.unsplash.com/photo-1626645738196-c2a7c87a8f58?w=300&h=200&fit=crop&auto=format'],
    ['id'=>'4pc-combo',       'categoryId'=>'chicken','name'=>'4 pcs Combo',      'description'=>'with rice & 1 side', 'price'=>199,'hasFlavors'=>true, 'image'=>'https://images.unsplash.com/photo-1527477396000-e27163b481c2?w=300&h=200&fit=crop&auto=format'],
    ['id'=>'3pc-combo',       'categoryId'=>'chicken','name'=>'3 pcs Combo',      'description'=>'with rice & 1 side', 'price'=>159,'hasFlavors'=>true, 'image'=>'https://images.unsplash.com/photo-1598103442097-8b74394b95c3?w=300&h=200&fit=crop&auto=format'],
    ['id'=>'solo-meal',       'categoryId'=>'chicken','name'=>'Solo Meal',        'description'=>'1 pc + rice + drink','price'=>125,'hasFlavors'=>true, 'image'=>'https://images.unsplash.com/photo-1562967914-608f82629710?w=300&h=200&fit=crop&auto=format'],
    ['id'=>'whole-chicken',   'categoryId'=>'chicken','name'=>'Whole Chicken',    'description'=>'with rice & 3 sides','price'=>499,'hasFlavors'=>true, 'image'=>'https://images.unsplash.com/photo-1574672280600-4accfa5b6f98?w=300&h=200&fit=crop&auto=format'],
    ['id'=>'half-chicken',    'categoryId'=>'chicken','name'=>'Half Chicken',     'description'=>'with rice & 2 sides','price'=>279,'hasFlavors'=>true, 'image'=>'https://images.unsplash.com/photo-1604908176997-125f25cc6f3d?w=300&h=200&fit=crop&auto=format'],
    ['id'=>'special-spaghetti','categoryId'=>'pasta', 'name'=>'Special Spaghetti','description'=>'',                   'price'=>89, 'hasFlavors'=>false,'image'=>'https://images.unsplash.com/photo-1555949258-eb67b1ef0ceb?w=300&h=200&fit=crop&auto=format'],
    ['id'=>'chicken-carbonara','categoryId'=>'pasta', 'name'=>'Chicken Carbonara','description'=>'',                   'price'=>99, 'hasFlavors'=>false,'image'=>'https://images.unsplash.com/photo-1612874742237-6526221588e3?w=300&h=200&fit=crop&auto=format'],
    ['id'=>'chicken-spaghetti','categoryId'=>'pasta', 'name'=>'Chicken Spaghetti','description'=>'',                   'price'=>89, 'hasFlavors'=>false,'image'=>'https://images.unsplash.com/photo-1621996346565-e3dbc353d2e5?w=300&h=200&fit=crop&auto=format'],
    ['id'=>'garden-salad',    'categoryId'=>'sides',  'name'=>'Garden Salad',     'description'=>'',                   'price'=>59, 'hasFlavors'=>false,'image'=>'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=300&h=200&fit=crop&auto=format'],
    ['id'=>'coleslaw',        'categoryId'=>'sides',  'name'=>'Coleslaw',         'description'=>'',                   'price'=>49, 'hasFlavors'=>false,'image'=>'https://images.unsplash.com/photo-1621908012027-f81be5dd4b43?w=300&h=200&fit=crop&auto=format'],
    ['id'=>'corn',            'categoryId'=>'sides',  'name'=>'Corn on the Cob',  'description'=>'',                   'price'=>39, 'hasFlavors'=>false,'image'=>'https://images.unsplash.com/photo-1471193945509-9ad0617afabf?w=300&h=200&fit=crop&auto=format'],
    ['id'=>'softdrink',       'categoryId'=>'drinks', 'name'=>'Softdrink',        'description'=>'',                   'price'=>39, 'hasFlavors'=>false,'image'=>'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?w=300&h=200&fit=crop&auto=format'],
    ['id'=>'iced-tea',        'categoryId'=>'drinks', 'name'=>'Iced Tea',         'description'=>'',                   'price'=>45, 'hasFlavors'=>false,'image'=>'https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=300&h=200&fit=crop&auto=format'],
    ['id'=>'water',           'categoryId'=>'drinks', 'name'=>'Bottled Water',    'description'=>'',                   'price'=>25, 'hasFlavors'=>false,'image'=>'https://images.unsplash.com/photo-1548839140-29a749e1cf4d?w=300&h=200&fit=crop&auto=format'],
];

$flavors = [
    ['id'=>'original',    'label'=>'ORIGINAL'],
    ['id'=>'sweet-garlic','label'=>'SWEET GARLIC'],
    ['id'=>'buffalo',     'label'=>'BUFFALO'],
    ['id'=>'sweet-chili', 'label'=>'SWEET CHILI'],
    ['id'=>'teriyaki',    'label'=>'TERIYAKI'],
    ['id'=>'korean-chili','label'=>'KOREAN CHILI'],
];

$gcashSteps = [
    'Go to your GCash app and login.',
    'On the bottom of your GCash application, select the scan icon.',
    'Scan the QR code in the screen.',
    'Put the amount for your order and send it.',
    'Take a screenshot of the receipt and show it to the cashier.',
];

$mayaSteps = [
    'Go to your Maya app and login.',
    'On the bottom of your Maya application, select the scan icon.',
    'Scan the QR code in the screen.',
    'Put the amount for your order and send it.',
    'Take a screenshot of the receipt and show it to the cashier.',
];

// ─── Session init ─────────────────────────────────────────────────────────────

if (!isset($_SESSION['screen']))      $_SESSION['screen']      = 'splash';
if (!isset($_SESSION['cart']))        $_SESSION['cart']        = [];
if (!isset($_SESSION['eatType']))     $_SESSION['eatType']     = 'dine-in';
if (!isset($_SESSION['payment']))     $_SESSION['payment']     = null;
if (!isset($_SESSION['pendingItem'])) $_SESSION['pendingItem'] = null;
if (!isset($_SESSION['orderNum']))    $_SESSION['orderNum']    = 'SC-' . rand(1000, 9999);

// ─── Helpers ─────────────────────────────────────────────────────────────────

function getMenuItem(string $id): ?array {
    global $menuItems;
    foreach ($menuItems as $item) {
        if ($item['id'] === $id) return $item;
    }
    return null;
}

function cartAdd(array $item, ?string $flavor = null): void {
    $flavorLabel = $flavor ? ucwords(str_replace('-', ' ', $flavor)) : null;
    if (!$flavor) {
        foreach ($_SESSION['cart'] as &$c) {
            if ($c['menuItemId'] === $item['id'] && $c['flavor'] === null) {
                $c['quantity']++;
                return;
            }
        }
        unset($c);
    }
    $_SESSION['cart'][] = [
        'cartId'     => $item['id'] . '-' . uniqid(),
        'menuItemId' => $item['id'],
        'name'       => $item['name'],
        'price'      => $item['price'],
        'quantity'   => 1,
        'flavor'     => $flavorLabel,
    ];
}

function cartRemove(string $cartId): void {
    $_SESSION['cart'] = array_values(
        array_filter($_SESSION['cart'], fn($c) => $c['cartId'] !== $cartId)
    );
}

function cartSubtotal(): float {
    return array_reduce($_SESSION['cart'], fn($s, $i) => $s + $i['price'] * $i['quantity'], 0);
}

function cartTotal(): float {
    return cartSubtotal() * 1.14;
}

function cartCount(): int {
    return array_reduce($_SESSION['cart'], fn($s, $i) => $s + $i['quantity'], 0);
}

function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES);
}

// ─── POST handler (PRG pattern) ───────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'start':
            $_SESSION['screen'] = 'eat-type';
            break;

        case 'select-eat-type':
            $_SESSION['eatType'] = $_POST['eatType'] === 'take-out' ? 'take-out' : 'dine-in';
            $_SESSION['screen']  = 'menu';
            break;

        case 'add-item':
            $item = getMenuItem($_POST['itemId'] ?? '');
            if ($item) {
                if ($item['hasFlavors']) {
                    $_SESSION['pendingItem'] = $item;
                    $_SESSION['screen']      = 'choose-flavors';
                } else {
                    cartAdd($item);
                }
            }
            break;

        case 'remove-item':
            cartRemove($_POST['cartId'] ?? '');
            break;

        case 'confirm-flavor':
            $item = $_SESSION['pendingItem'];
            if ($item) {
                cartAdd($item, $_POST['flavor'] ?? 'original');
                $_SESSION['pendingItem'] = null;
            }
            $_SESSION['screen'] = 'menu';
            break;

        case 'back-flavors':
            $_SESSION['pendingItem'] = null;
            $_SESSION['screen']      = 'menu';
            break;

        case 'void':
            $_SESSION['cart'] = [];
            break;

        case 'pay':
            if (!empty($_SESSION['cart'])) {
                $_SESSION['screen'] = 'choose-payment';
            }
            break;

        case 'select-cash':
            $_SESSION['payment'] = 'cash';
            $_SESSION['screen']  = 'receipt';
            break;

        case 'select-online':
            $_SESSION['screen'] = 'online-payment';
            break;

        case 'select-gcash':
            $_SESSION['payment'] = 'gcash';
            $_SESSION['screen']  = 'payment-instructions-gcash';
            break;

        case 'select-maya':
            $_SESSION['payment'] = 'maya';
            $_SESSION['screen']  = 'payment-instructions-maya';
            break;

        case 'payment-done':
            $_SESSION['screen']   = 'receipt';
            $_SESSION['orderNum'] = 'SC-' . rand(1000, 9999);
            break;

        case 'order-more':
            $_SESSION['cart']    = [];
            $_SESSION['payment'] = null;
            $_SESSION['screen']  = 'eat-type';
            break;

        case 'go-back':
            $from = $_POST['from'] ?? '';
            $map  = [
                'eat-type'                    => 'splash',
                'menu'                        => 'eat-type',
                'choose-flavors'              => 'menu',
                'choose-payment'              => 'menu',
                'online-payment'              => 'choose-payment',
                'payment-instructions-gcash'  => 'online-payment',
                'payment-instructions-maya'   => 'online-payment',
            ];
            $_SESSION['screen'] = $map[$from] ?? 'splash';
            break;
    }

    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

$screen  = $_SESSION['screen'];
$eatType = $_SESSION['eatType'];
$cart    = $_SESSION['cart'];
$payment = $_SESSION['payment'];

// ─── Partials ─────────────────────────────────────────────────────────────────

function renderMascotSvg(int $size = 180): void { ?>
<svg width="<?= $size ?>" height="<?= $size ?>" viewBox="0 0 180 180" fill="none" xmlns="http://www.w3.org/2000/svg">
<ellipse cx="90" cy="110" rx="52" ry="48" fill="#F5C842"/>
<circle cx="90" cy="62" r="32" fill="#F5C842"/>
<ellipse cx="90" cy="30" rx="8" ry="12" fill="#E05050"/>
<ellipse cx="79" cy="33" rx="6" ry="9" fill="#E05050"/>
<ellipse cx="101" cy="33" rx="6" ry="9" fill="#E05050"/>
<ellipse cx="90" cy="86" rx="7" ry="9" fill="#E05050"/>
<ellipse cx="90" cy="70" rx="9" ry="7" fill="#F0A030"/>
<path d="M81 72 Q90 80 99 72" fill="#E08020"/>
<circle cx="78" cy="58" r="7" fill="white"/><circle cx="102" cy="58" r="7" fill="white"/>
<circle cx="80" cy="58" r="4" fill="#1a1a1a"/><circle cx="104" cy="58" r="4" fill="#1a1a1a"/>
<circle cx="81" cy="56" r="1.5" fill="white"/><circle cx="105" cy="56" r="1.5" fill="white"/>
<ellipse cx="45" cy="108" rx="22" ry="30" fill="#E8B030" transform="rotate(-20 45 108)"/>
<ellipse cx="135" cy="108" rx="22" ry="30" fill="#E8B030" transform="rotate(20 135 108)"/>
<rect x="30" y="90" width="14" height="36" rx="7" fill="#E8B030" transform="rotate(-30 30 90)"/>
<ellipse cx="20" cy="122" rx="10" ry="8" fill="#C89020" transform="rotate(-30 20 122)"/>
<rect x="136" y="90" width="14" height="36" rx="7" fill="#E8B030" transform="rotate(30 136 90)"/>
<ellipse cx="160" cy="122" rx="10" ry="8" fill="#C89020" transform="rotate(30 160 122)"/>
<rect x="72" y="152" width="12" height="22" rx="4" fill="#F0A030"/>
<rect x="96" y="152" width="12" height="22" rx="4" fill="#F0A030"/>
<rect x="62" y="170" width="28" height="7" rx="3" fill="#E08020"/>
<rect x="90" y="170" width="28" height="7" rx="3" fill="#E08020"/>
<rect x="68" y="30" width="44" height="20" rx="4" fill="white"/>
<ellipse cx="90" cy="30" rx="28" ry="10" fill="white"/>
<ellipse cx="90" cy="20" rx="20" ry="14" fill="white"/>
</svg>
<?php }

function renderBadgeSvg(int $size = 80): void { ?>
<div style="width:<?= $size ?>px;height:<?= $size ?>px;border-radius:50%;border:4px solid #c8991a;background:#2a5418;display:flex;align-items:center;justify-content:center;overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,.4)">
<svg width="<?= round($size*.75) ?>" height="<?= round($size*.75) ?>" viewBox="0 0 60 60" fill="none">
<ellipse cx="30" cy="38" rx="17" ry="15" fill="#F5C842"/>
<circle cx="30" cy="22" r="12" fill="#F5C842"/>
<ellipse cx="30" cy="10" rx="4" ry="5" fill="#E05050"/>
<ellipse cx="25" cy="12" rx="3" ry="4" fill="#E05050"/>
<ellipse cx="35" cy="12" rx="3" ry="4" fill="#E05050"/>
<ellipse cx="30" cy="30" rx="3" ry="4" fill="#E05050"/>
<ellipse cx="30" cy="25" rx="4" ry="3" fill="#F0A030"/>
<circle cx="24" cy="20" r="3" fill="white"/><circle cx="36" cy="20" r="3" fill="white"/>
<circle cx="24.8" cy="20" r="1.8" fill="#1a1a1a"/><circle cx="36.8" cy="20" r="1.8" fill="#1a1a1a"/>
<ellipse cx="14" cy="37" rx="8" ry="11" fill="#E8B030" transform="rotate(-15 14 37)"/>
<ellipse cx="46" cy="37" rx="8" ry="11" fill="#E8B030" transform="rotate(15 46 37)"/>
<rect x="24" y="50" width="5" height="8" rx="2" fill="#F0A030"/>
<rect x="31" y="50" width="5" height="8" rx="2" fill="#F0A030"/>
</svg>
</div>
<?php }

function renderHeader(string $step): void {
    global $eatType;
    $steps   = [['id'=>'order','label'=>'ORDER'],['id'=>'payment','label'=>'PAYMENT'],['id'=>'receipt','label'=>'RECEIPT']];
    $stepMap = ['order'=>0,'payment'=>1,'receipt'=>2];
    $current = $stepMap[$step] ?? 0;
    $time    = date('h:i A');
    $total   = cartTotal();
    $eatLbl  = $eatType === 'dine-in' ? 'DINE IN' : 'TAKE OUT';
?>
<header class="kiosk-header" style="background:#1d3c10;color:white;display:flex;align-items:center;justify-content:space-between;padding:8px 16px;border-bottom:2px solid #c8991a;flex-shrink:0;gap:6px">
<div style="display:flex;align-items:center;gap:8px;width:25%;min-width:0">
<div class="kiosk-header-logo-circle" style="width:32px;height:32px;border-radius:50%;background:#c8991a;display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0">
    <img src="images/734733230_122196035888731309_491023812011076473_n.jpg" alt="Logo" style="width:100%;height:100%;object-fit:cover;">
</div>
<div style="min-width:0">
<div class="kiosk-header-brand" style="font-family:Oswald,sans-serif;color:#c8991a;font-weight:700;font-size:13px;letter-spacing:.1em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">SWEET CHICKEN</div>
<div class="kiosk-header-sub" style="font-size:9px;letter-spacing:.2em;opacity:.6;white-space:nowrap">ORIGINAL TASTE</div>
</div>
</div>
<div style="display:flex;align-items:center;gap:6px;width:50%;justify-content:center">
<?php foreach($steps as $i => $s):
        $active = $i === $current;
        $done   = $i < $current;
        $circleBg = $active ? '#c8991a' : ($done ? '#3a7020' : 'transparent');
        $circleBorder = $active ? '#c8991a' : ($done ? '#3a7020' : '#3a7020');
        $circleColor  = $active ? '#1d3c10' : 'white';
        $labelColor   = $active ? '#c8991a' : '#3a7020';
    ?>
<div class="kiosk-header-circle" style="width:22px;height:22px;border-radius:50%;background:<?= $circleBg ?>;border:2px solid <?= $circleBorder ?>;color:<?= $circleColor ?>;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;font-family:Oswald,sans-serif;flex-shrink:0"><?= $done ? '✓' : ($i+1) ?></div>
<span class="kiosk-header-steplabel" style="font-size:9px;font-weight:600;letter-spacing:.1em;color:<?= $labelColor ?>;font-family:Oswald,sans-serif;white-space:nowrap"><?= $s['label'] ?></span>
<?php if ($i < count($steps)-1): ?>
<div class="kiosk-header-step-connector" style="width:20px;height:1px;background:<?= $done ? '#3a7020' : '#2a5418' ?>;flex-shrink:0"></div>
<?php endif ?>
<?php endforeach ?>
</div>
<div style="display:flex;flex-direction:column;align-items:flex-end;gap:2px;width:25%;min-width:0">
<div style="display:flex;align-items:center;gap:6px">
<span class="kiosk-header-eatbadge" style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:4px;background:#2a5418;color:#c8991a;letter-spacing:.1em;font-family:Oswald,sans-serif;white-space:nowrap"><?= h($eatLbl) ?></span>
<span class="kiosk-header-time" style="font-size:9px;opacity:.6;white-space:nowrap"><?= h($time) ?></span>
</div>
<div class="kiosk-header-total" style="color:#c8991a;font-weight:700;font-size:13px;font-family:Oswald,sans-serif;white-space:nowrap">₱<?= number_format($total, 2) ?></div>
</div>
</header>
<?php }

function backForm(string $from, string $label = 'BACK', string $extra = ''): void { ?>
<form method="post" style="display:block">
<input type="hidden" name="action" value="go-back">
<input type="hidden" name="from" value="<?= h($from) ?>">
<button type="submit" <?= $extra ?>><?= h($label) ?></button>
</form>
<?php }

// ─── Screens ──────────────────────────────────────────────────────────────────

function renderSplash(): void { ?>
<div class="kiosk-screen" data-screen="splash" style="background:linear-gradient(180deg,#0a1506 0%,#111a0a 60%,#1a2c0c 100%);cursor:pointer;position:relative;overflow:hidden">
<div style="position:absolute;top:0;left:0;right:0;height:6px;background:#c8991a"></div>
<div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:16px;padding:24px;position:relative;z-index:1;min-height:0">
<div style="display:flex;align-items:center;gap:12px;margin-bottom:8px">
<div style="height:1px;width:60px;background:#c8991a;opacity:.5"></div>
<span style="font-family:Oswald,sans-serif;color:#c8991a;font-size:11px;letter-spacing:.3em;font-weight:600">EST. 2021</span>
<div style="height:1px;width:60px;background:#c8991a;opacity:.5"></div>
</div>
<div class="kiosk-mascot-wrap">
<?php renderMascotSvg(190) ?>
</div>
<div style="text-align:center;margin-top:8px;min-height:0">
<h1 style="font-family:Oswald,sans-serif;color:#c8991a;font-weight:700;letter-spacing:.15em;font-size:clamp(2rem,8vw,4rem);text-shadow:0 2px 20px rgba(200,153,26,.4);margin:0">SWEET CHICKEN</h1>
<div style="font-family:Oswald,sans-serif;color:#f5f0e8;letter-spacing:.4em;font-size:13px;margin-top:4px;opacity:.65">ORIGINAL TASTE</div>
</div>
<div style="display:flex;align-items:center;gap:10px;margin-top:8px">
<div style="height:1px;width:60px;background:#c8991a;opacity:.35"></div>
<div style="width:8px;height:8px;transform:rotate(45deg);background:#c8991a;opacity:.6"></div>
<div style="height:1px;width:60px;background:#c8991a;opacity:.35"></div>
</div>
</div>
<form method="post" style="flex-shrink:0">
<input type="hidden" name="action" value="start">
<button type="submit" style="width:100%;background:#c8991a;border:none;padding:22px;cursor:pointer;font-family:Oswald,sans-serif;font-weight:700;letter-spacing:.4em;font-size:18px;color:#1d3c10;transition:background .2s">TAP TO START</button>
</form>
</div>
<?php }

function renderEatType(): void { ?>
<div class="kiosk-screen" style="background:linear-gradient(160deg,#1d3c10 0%,#2a5418 100%);align-items:center;justify-content:center;gap:32px;padding:32px;position:relative;overflow-y:auto;-webkit-overflow-scrolling:touch">
<div style="position:absolute;top:0;left:0;right:0;height:6px;background:#c8991a"></div>
<div style="position:absolute;bottom:0;left:0;right:0;height:6px;background:#c8991a"></div>
<?php renderBadgeSvg(88) ?>
<div style="text-align:center">
<h2 style="font-family:Oswald,sans-serif;color:#c8991a;font-size:clamp(1.8rem,6vw,2.8rem);font-weight:600;letter-spacing:.1em;margin:0">Eat type</h2>
<p style="color:#f5f0e8;opacity:.6;font-size:13px;margin:6px 0 0">Please select how you'll be dining</p>
</div>
<div class="two-col-row" style="display:flex;gap:24px;width:100%;max-width:380px">
<form method="post" style="flex:1">
<input type="hidden" name="action" value="select-eat-type">
<input type="hidden" name="eatType" value="dine-in">
<button type="submit" class="two-col-btn" style="width:100%;background:#f5f0e8;border:none;border-radius:18px;padding:36px 12px;display:flex;flex-direction:column;align-items:center;gap:12px;cursor:pointer;box-shadow:0 8px 24px rgba(0,0,0,.3);transition:transform .15s,background .15s" onmouseover="this.style.background='white'" onmouseout="this.style.background='#f5f0e8'">
<span style="font-size:3rem">🪑</span>
<span style="font-family:Oswald,sans-serif;color:#1d3c10;font-weight:700;font-size:1.2rem;letter-spacing:.05em">Dine-In</span>
</button>
</form>
<form method="post" style="flex:1">
<input type="hidden" name="action" value="select-eat-type">
<input type="hidden" name="eatType" value="take-out">
<button type="submit" class="two-col-btn" style="width:100%;background:#f5f0e8;border:none;border-radius:18px;padding:36px 12px;display:flex;flex-direction:column;align-items:center;gap:12px;cursor:pointer;box-shadow:0 8px 24px rgba(0,0,0,.3);transition:transform .15s,background .15s" onmouseover="this.style.background='white'" onmouseout="this.style.background='#f5f0e8'">
<span style="font-size:3rem">🛍️</span>
<span style="font-family:Oswald,sans-serif;color:#1d3c10;font-weight:700;font-size:1.2rem;letter-spacing:.05em">Take-Out</span>
</button>
</form>
</div>
</div>
<?php }

function renderMenu(): void {
    global $categories, $menuItems;
    $cart      = $_SESSION['cart'];
    $subtotal  = cartSubtotal();
    $tax       = $subtotal * 0.12;
    $svc       = $subtotal * 0.02;
    $total     = $subtotal + $tax + $svc;
    $count     = cartCount();
?>
<div class="kiosk-screen" style="background:#f0ede8">
<?php renderHeader('order') ?>
<div style="display:flex;flex:1;min-height:0">
<!-- Sidebar -->
<aside class="kiosk-sidebar" style="width:72px;background:#1d3c10;display:flex;flex-direction:column;gap:2px;padding:6px 0;overflow-y:auto;overflow-x:hidden;flex-shrink:0">
<?php foreach($categories as $cat): ?>
<button type="button"
          onclick="filterCategory('<?= h($cat['id']) ?>')"
          id="cat-btn-<?= h($cat['id']) ?>"
          style="background:transparent;border:none;display:flex;flex-direction:column;align-items:center;gap:4px;padding:10px 4px;cursor:pointer;transition:background .15s;width:100%;<?= $cat['id']==='all' ? 'background:#c8991a' : '' ?>"
          onmouseover="if(!this.classList.contains('active-cat'))this.style.background='#2a5418'"
          onmouseout="if(!this.classList.contains('active-cat'))this.style.background='transparent'">
<span class="kiosk-sidebar-icon" style="font-size:22px;line-height:1"><?= $cat['icon'] ?></span>
<span class="kiosk-sidebar-label" style="font-family:Oswald,sans-serif;font-size:9px;letter-spacing:.05em;font-weight:600;text-align:center;line-height:1.2;color:<?= $cat['id']==='all' ? '#1d3c10' : '#f5f0e8' ?>" id="cat-lbl-<?= h($cat['id']) ?>"><?= h($cat['name']) ?></span>
</button>
<?php endforeach ?>
</aside>

    <!-- Content -->
<div style="flex:1;display:flex;flex-direction:column;min-height:0;min-width:0">
<!-- Food grid -->
<div id="food-grid" style="flex:1;overflow-y:auto;-webkit-overflow-scrolling:touch;padding:10px;scrollbar-width:none">
<div class="kiosk-food-grid-inner" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px">
<?php foreach($menuItems as $item):
              $inCart = array_reduce($cart, fn($s,$c) => $s + ($c['menuItemId']===$item['id'] ? $c['quantity'] : 0), 0);
          ?>
<div class="food-card" data-cat="<?= h($item['categoryId']) ?>">
<form method="post">
<input type="hidden" name="action" value="add-item">
<input type="hidden" name="itemId" value="<?= h($item['id']) ?>">
<button type="submit" style="width:100%;background:white;border:none;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1);cursor:pointer;text-align:left;transition:box-shadow .15s,transform .1s;padding:0" onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,.18)'" onmouseout="this.style.boxShadow='0 2px 8px rgba(0,0,0,.1)'">
<div style="position:relative">
<img src="<?= h($item['image']) ?>" alt="<?= h($item['name']) ?>" style="width:100%;height:80px;object-fit:cover;display:block">
<?php if($inCart > 0): ?>
<div style="position:absolute;top:4px;right:4px;background:#c8991a;color:#1d3c10;border-radius:50%;width:22px;height:22px;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;font-family:Oswald,sans-serif"><?= $inCart ?></div>
<?php endif ?>
</div>
<div style="padding:7px 8px">
<div style="font-family:Oswald,sans-serif;color:#1d3c10;font-weight:600;font-size:12px;line-height:1.3"><?= h($item['name']) ?></div>
<?php if($item['description']): ?>
<div style="color:#888;font-size:9px;margin-top:2px;line-height:1.3"><?= h($item['description']) ?></div>
<?php endif ?>
<div style="color:#c8991a;font-weight:700;font-size:13px;margin-top:4px;font-family:Oswald,sans-serif">₱<?= $item['price'] ?></div>
</div>
</button>
</form>
</div>
<?php endforeach ?>
</div>
</div>

      <!-- Cart -->
<div style="background:white;border-top:2px solid #e0d8cc;flex-shrink:0">
<?php if(empty($cart)): ?>
<div style="padding:12px;text-align:center;color:#999;font-size:11px">No items added yet. Select items above.</div>
<?php else: ?>
<div style="overflow-y:auto;-webkit-overflow-scrolling:touch;max-height:110px;padding:6px 12px;scrollbar-width:none">
<?php foreach($cart as $ci): ?>
<div style="display:flex;align-items:center;justify-content:space-between;padding:4px 0;border-bottom:1px solid #f0ede8">
<div style="flex:1;min-width:0">
<span style="font-family:Oswald,sans-serif;font-size:12px;font-weight:600;color:#1d3c10"><?= h($ci['quantity'].'× '.$ci['name']) ?></span>
<?php if($ci['flavor']): ?>
<span style="color:#c8991a;font-size:10px;margin-left:4px">(<?= h($ci['flavor']) ?>)</span>
<?php endif ?>
</div>
<div style="display:flex;align-items:center;gap:8px;flex-shrink:0;margin-left:8px">
<span style="font-family:Oswald,sans-serif;font-size:12px;font-weight:700;color:#1d3c10">₱<?= number_format($ci['price']*$ci['quantity'],2) ?></span>
<form method="post" style="display:inline">
<input type="hidden" name="action" value="remove-item">
<input type="hidden" name="cartId" value="<?= h($ci['cartId']) ?>">
<button type="submit" style="width:26px;height:26px;min-width:26px;border-radius:50%;background:#b01c1c;color:white;border:none;font-size:15px;cursor:pointer;display:flex;align-items:center;justify-content:center;line-height:1">×</button>
</form>
</div>
</div>
<?php endforeach ?>
</div>
<?php endif ?>
<!-- Totals -->
<div style="padding:8px 12px;background:#f8f5f0;border-top:1px solid #e0d8cc">
<div style="display:flex;justify-content:space-between;font-size:10px;color:#666;margin-bottom:3px">
<span>Selected Items (<?= $count ?>)</span><span>₱<?= number_format($subtotal,2) ?></span>
</div>
<div style="display:flex;justify-content:space-between;font-size:10px;color:#666;margin-bottom:3px">
<span>VAT (12%)</span><span>₱<?= number_format($tax,2) ?></span>
</div>
<div style="display:flex;justify-content:space-between;font-size:10px;color:#666;margin-bottom:6px">
<span>Service Charge</span><span>₱<?= number_format($svc,2) ?></span>
</div>
<div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid #e0d8cc;padding-top:6px">
<span style="font-family:Oswald,sans-serif;color:#1d3c10;font-weight:700;font-size:14px">TOTAL</span>
<span style="font-family:Oswald,sans-serif;color:#c8991a;font-weight:700;font-size:15px">₱<?= number_format($total,2) ?></span>
</div>
</div>
</div>
</div>
</div>

  <!-- Footer buttons -->
<div class="kiosk-footer-buttons" style="display:grid;grid-template-columns:1fr 1fr 1fr;flex-shrink:0">
<?php backForm('menu', 'BACK', 'style="width:100%;background:#c8991a;border:none;color:#1d3c10;font-family:Oswald,sans-serif;font-weight:700;font-size:16px;letter-spacing:.15em;padding:16px;cursor:pointer"') ?>
<form method="post">
<input type="hidden" name="action" value="void">
<button type="submit" style="width:100%;background:#b01c1c;border:none;color:white;font-family:Oswald,sans-serif;font-weight:700;font-size:16px;letter-spacing:.15em;padding:16px;cursor:pointer">VOID</button>
</form>
<form method="post">
<input type="hidden" name="action" value="pay">
<button type="submit" <?= empty($cart) ? 'disabled' : '' ?> style="width:100%;background:<?= empty($cart) ? '#555' : '#1e7a1e' ?>;border:none;color:white;font-family:Oswald,sans-serif;font-weight:700;font-size:16px;letter-spacing:.15em;padding:16px;cursor:<?= empty($cart) ? 'not-allowed' : 'pointer' ?>">PAY</button>
</form>
</div>
</div>
<script>
function filterCategory(id) {
    document.querySelectorAll('.food-card').forEach(el => {
        el.style.display = (id === 'all' || el.dataset.cat === id) ? '' : 'none';
    });
    document.querySelectorAll('[id^="cat-btn-"]').forEach(btn => {
        const isActive = btn.id === 'cat-btn-' + id;
        btn.style.background = isActive ? '#c8991a' : 'transparent';
        const lbl = document.getElementById(btn.id.replace('cat-btn-', 'cat-lbl-'));
        if (lbl) lbl.style.color = isActive ? '#1d3c10' : '#f5f0e8';
    });
}
</script>
<?php }

function renderChooseFlavors(): void {
    global $flavors;
    $item = $_SESSION['pendingItem'];
    if (!$item) { $_SESSION['screen'] = 'menu'; header('Location: ./'); exit; }
?>
<div class="kiosk-screen" style="background:linear-gradient(160deg,#1d3c10 0%,#2a5418 100%)">
<?php renderHeader('order') ?>
<div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:24px;gap:24px;overflow-y:auto;-webkit-overflow-scrolling:touch;min-height:0">
<div style="text-align:center">
<div style="color:#f5f0e8;opacity:.6;font-size:13px;margin-bottom:4px">Choosing flavor for</div>
<h2 style="font-family:Oswald,sans-serif;color:#c8991a;font-size:clamp(1.4rem,5vw,2rem);font-weight:700;letter-spacing:.1em;margin:0"><?= h($item['name']) ?></h2>
</div>
<div style="text-align:center">
<h1 style="font-family:Oswald,sans-serif;color:#f5f0e8;font-size:clamp(1.5rem,5vw,2.2rem);font-weight:700;letter-spacing:.2em;margin:0">CHOOSE YOUR FLAVORS</h1>
<div style="height:2px;background:#c8991a;margin-top:8px;border-radius:2px;opacity:.6"></div>
</div>
<!-- Flavor grid — radios linked to confirm-form via form="confirm-form" -->
<div style="width:100%;max-width:360px">
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:20px">
<?php foreach($flavors as $f): ?>
<div id="flavor-<?= h($f['id']) ?>" onclick="document.getElementById('r-<?= h($f['id']) ?>').checked=true;updateSelected('<?= h($f['id']) ?>','<?= h($f['label']) ?>')"
      style="border-radius:12px;padding:16px 6px;text-align:center;font-family:Oswald,sans-serif;font-weight:700;font-size:11px;letter-spacing:.08em;cursor:pointer;transition:all .15s;background:<?= $f['id']==='original'?'#c8991a':'#2a5418' ?>;color:<?= $f['id']==='original'?'#1d3c10':'#f5f0e8' ?>;border:1px solid <?= $f['id']==='original'?'#c8991a':'#3a7020' ?>;<?= $f['id']==='original'?'transform:scale(1.05);box-shadow:0 4px 12px rgba(0,0,0,.3)':'' ?>">
<?= h($f['label']) ?>
</div>
<?php endforeach ?>
</div>
<div style="display:none">
<?php foreach($flavors as $f): ?>
<input type="radio" id="r-<?= h($f['id']) ?>" name="flavor" form="confirm-form" value="<?= h($f['id']) ?>" <?= $f['id']==='original'?'checked':'' ?>>
<?php endforeach ?>
</div>
<div style="background:#2a5418;border:1px solid #3a7020;border-radius:12px;padding:12px;text-align:center;margin-bottom:4px">
<div style="color:#f5f0e8;opacity:.6;font-size:11px;margin-bottom:2px">Selected Flavor</div>
<div id="selected-label" style="font-family:Oswald,sans-serif;color:#c8991a;font-weight:700;font-size:18px;letter-spacing:.15em">ORIGINAL</div>
</div>
</div>
<!-- BACK + CONFIRM forms side-by-side outside any parent form -->
<div style="display:grid;grid-template-columns:1fr 1fr;width:100%;max-width:360px">
<?php backForm('choose-flavors', 'BACK', 'style="width:100%;background:#b01c1c;border:none;color:white;font-family:Oswald,sans-serif;font-weight:700;font-size:16px;letter-spacing:.15em;padding:16px;cursor:pointer"') ?>
<form id="confirm-form" method="post">
<input type="hidden" name="action" value="confirm-flavor">
<button type="submit" style="width:100%;background:#1e7a1e;border:none;color:white;font-family:Oswald,sans-serif;font-weight:700;font-size:16px;letter-spacing:.15em;padding:16px;cursor:pointer">CONFIRM</button>
</form>
</div>
</div>
</div>
<script>
function updateSelected(id, label) {
    document.getElementById('selected-label').textContent = label;
    var r = document.getElementById('r-' + id);
    if (r) r.checked = true;
<?php foreach($flavors as $f): ?>
    (function(){
        var el = document.getElementById('flavor-<?= h($f['id']) ?>');
        if (!el) return;
        var isActive = id === '<?= h($f['id']) ?>';
        el.style.background = isActive ? '#c8991a' : '#2a5418';
        el.style.color = isActive ? '#1d3c10' : '#f5f0e8';
        el.style.transform = isActive ? 'scale(1.05)' : 'scale(1)';
        el.style.boxShadow = isActive ? '0 4px 12px rgba(0,0,0,.3)' : 'none';
        el.style.border = '1px solid ' + (isActive ? '#c8991a' : '#3a7020');
    })();
<?php endforeach ?>
}
</script>
<?php }

function renderChoosePayment(): void { ?>
<div class="kiosk-screen" style="background:linear-gradient(160deg,#1d3c10 0%,#2a5418 100%);align-items:center;justify-content:center;gap:32px;padding:32px;position:relative;overflow-y:auto;-webkit-overflow-scrolling:touch">
<div style="position:absolute;top:0;left:0;right:0;height:6px;background:#c8991a"></div>
<div style="position:absolute;bottom:0;left:0;right:0;height:6px;background:#c8991a"></div>
<?php renderBadgeSvg(88) ?>
<div style="text-align:center">
<h1 style="font-family:Oswald,sans-serif;color:#c8991a;font-size:clamp(1.8rem,7vw,3rem);font-weight:700;letter-spacing:.2em;margin:0">CHOOSE PAYMENT</h1>
<div style="height:2px;background:#c8991a;margin-top:8px;opacity:.4;border-radius:2px"></div>
</div>
<div class="two-col-row" style="display:flex;gap:24px;width:100%;max-width:360px">
<form method="post" style="flex:1">
<input type="hidden" name="action" value="select-cash">
<button type="submit" class="two-col-btn" style="width:100%;background:#2a5418;border:2px solid #3a7020;border-radius:18px;padding:36px 12px;display:flex;flex-direction:column;align-items:center;gap:12px;cursor:pointer;box-shadow:0 8px 24px rgba(0,0,0,.3);transition:all .15s" onmouseover="this.style.borderColor='#c8991a'" onmouseout="this.style.borderColor='#3a7020'">
<span style="font-size:3rem">💵</span>
<span style="font-family:Oswald,sans-serif;color:#f5f0e8;font-weight:700;font-size:1.1rem;letter-spacing:.15em">CASH</span>
</button>
</form>
<form method="post" style="flex:1">
<input type="hidden" name="action" value="select-online">
<button type="submit" class="two-col-btn" style="width:100%;background:#2a5418;border:2px solid #3a7020;border-radius:18px;padding:36px 12px;display:flex;flex-direction:column;align-items:center;gap:12px;cursor:pointer;box-shadow:0 8px 24px rgba(0,0,0,.3);transition:all .15s" onmouseover="this.style.borderColor='#c8991a'" onmouseout="this.style.borderColor='#3a7020'">
<span style="font-size:3rem">📱</span>
<span style="font-family:Oswald,sans-serif;color:#f5f0e8;font-weight:700;font-size:1.1rem;letter-spacing:.1em;text-align:center;line-height:1.3">ONLINE<br>PAYMENT</span>
</button>
</form>
</div>
<?php backForm('choose-payment', 'BACK', 'style="background:#b01c1c;border:none;color:white;font-family:Oswald,sans-serif;font-weight:700;font-size:16px;letter-spacing:.2em;padding:12px 48px;border-radius:12px;cursor:pointer;margin-top:8px"') ?>
</div>
<?php }

function renderOnlinePayment(): void { ?>
<div class="kiosk-screen" style="background:linear-gradient(160deg,#1d3c10 0%,#2a5418 100%);align-items:center;justify-content:center;gap:32px;padding:32px;position:relative;overflow-y:auto;-webkit-overflow-scrolling:touch">
<div style="position:absolute;top:0;left:0;right:0;height:6px;background:#c8991a"></div>
<div style="position:absolute;bottom:0;left:0;right:0;height:6px;background:#c8991a"></div>
<?php renderBadgeSvg(88) ?>
<div style="text-align:center">
<h1 style="font-family:Oswald,sans-serif;color:#c8991a;font-size:clamp(1.4rem,5vw,2.4rem);font-weight:700;letter-spacing:.15em;margin:0">CHOOSE ONLINE PAYMENT</h1>
<div style="height:2px;background:#c8991a;margin-top:8px;opacity:.4;border-radius:2px"></div>
</div>
<div class="two-col-row" style="display:flex;gap:24px;width:100%;max-width:300px">
<form method="post" style="flex:1">
<input type="hidden" name="action" value="select-gcash">
<button type="submit" class="two-col-btn" style="width:100%;background:#007AFF;border:none;border-radius:18px;overflow:hidden;box-shadow:0 8px 24px rgba(0,0,0,.3);cursor:pointer;padding:24px 12px 20px;display:flex;flex-direction:column;align-items:center;gap:10px;transition:all .15s" onmouseover="this.style.outline='3px solid #c8991a'" onmouseout="this.style.outline='none'">
<div style="width:48px;height:48px;background:white;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,.2)">
<span style="font-family:Oswald,sans-serif;color:#007AFF;font-weight:900;font-size:20px">G</span>
</div>
<span style="font-family:Oswald,sans-serif;color:white;font-weight:700;letter-spacing:.2em;font-size:14px">GCASH</span>
</button>
</form>
<form method="post" style="flex:1">
<input type="hidden" name="action" value="select-maya">
<button type="submit" class="two-col-btn" style="width:100%;background:#00A878;border:none;border-radius:18px;overflow:hidden;box-shadow:0 8px 24px rgba(0,0,0,.3);cursor:pointer;padding:24px 12px 20px;display:flex;flex-direction:column;align-items:center;gap:10px;transition:all .15s" onmouseover="this.style.outline='3px solid #c8991a'" onmouseout="this.style.outline='none'">
<div style="width:48px;height:48px;background:white;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,.2)">
<span style="font-family:Oswald,sans-serif;color:#00A878;font-weight:900;font-size:20px">M</span>
</div>
<span style="font-family:Oswald,sans-serif;color:white;font-weight:700;letter-spacing:.2em;font-size:14px">MAYA</span>
</button>
</form>
</div>
<?php backForm('online-payment', 'BACK', 'style="background:#b01c1c;border:none;color:white;font-family:Oswald,sans-serif;font-weight:700;font-size:16px;letter-spacing:.2em;padding:12px 48px;border-radius:12px;cursor:pointer;margin-top:8px"') ?>
</div>
<?php }

function renderPaymentInstructions(string $method): void {
    global $gcashSteps, $mayaSteps;
    $steps  = $method === 'gcash' ? $gcashSteps : $mayaSteps;
    $color  = $method === 'gcash' ? '#007AFF' : '#00A878';
    $label  = $method === 'gcash' ? 'GCASH' : 'MAYA';
    $total  = cartTotal();
?>
<div class="kiosk-screen" style="background:#f0ede8">
<?php renderHeader('payment') ?>
<div class="pay-instructions-body" style="display:flex;flex:1;min-height:0">
<!-- QR side -->
<div class="pay-instructions-qr" style="width:42%;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;padding:20px;background:white;border-right:1px solid #e0d8cc">
<div style="width:52px;height:52px;border-radius:50%;background:<?= $color ?>;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(0,0,0,.2)">
<span style="font-family:Oswald,sans-serif;color:white;font-weight:900;font-size:22px"><?= $label[0] ?></span>
</div>
<div style="font-family:Oswald,sans-serif;font-weight:700;letter-spacing:.2em;font-size:16px;color:<?= $color ?>"><?= $label ?></div>
<!-- QR placeholder -->
<div style="width:140px;height:140px;background:white;border-radius:12px;overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,.15);border:3px solid #e0d8cc;padding:8px">
<svg viewBox="0 0 100 100" width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
<rect width="100" height="100" fill="white"/>
<rect x="5" y="5" width="30" height="30" rx="3" fill="<?= $color ?>"/>
<rect x="10" y="10" width="20" height="20" rx="2" fill="white"/>
<rect x="14" y="14" width="12" height="12" rx="1" fill="<?= $color ?>"/>
<rect x="65" y="5" width="30" height="30" rx="3" fill="<?= $color ?>"/>
<rect x="70" y="10" width="20" height="20" rx="2" fill="white"/>
<rect x="74" y="14" width="12" height="12" rx="1" fill="<?= $color ?>"/>
<rect x="5" y="65" width="30" height="30" rx="3" fill="<?= $color ?>"/>
<rect x="10" y="70" width="20" height="20" rx="2" fill="white"/>
<rect x="14" y="74" width="12" height="12" rx="1" fill="<?= $color ?>"/>
<?php
          $modules = [[42,5],[50,5],[58,5],[42,12],[58,12],[42,19],[50,19],[58,19],
                      [42,26],[5,42],[12,42],[19,42],[26,42],[42,42],[50,42],[58,42],[65,42],[72,42],[79,42],[86,42],
                      [5,50],[19,50],[42,50],[65,50],[79,50],[5,58],[12,58],[26,58],[42,58],[50,58],[72,58],[86,58],
                      [5,65],[19,65],[26,65],[42,65],[50,65],[58,65],[72,65],[5,79],[12,79],[26,79],[50,79],[58,79],[65,79],[79,79],
                      [5,86],[19,86],[42,86],[50,86],[58,86],[65,86],[79,86],[86,86]];
          foreach($modules as $m): ?>
<rect x="<?= $m[0] ?>" y="<?= $m[1] ?>" width="5" height="5" rx="0.5" fill="<?= $color ?>"/>
<?php endforeach ?>
</svg>
</div>
<div style="text-align:center">
<div style="color:#666;font-size:11px;margin-bottom:3px">Scan to pay</div>
<div style="font-family:Oswald,sans-serif;font-weight:700;font-size:22px;color:#1d3c10">₱<?= number_format($total,2) ?></div>
</div>
</div>
<!-- Steps side -->
<div class="pay-instructions-steps" style="flex:1;display:flex;flex-direction:column;justify-content:center;padding:20px;gap:14px;overflow-y:auto;-webkit-overflow-scrolling:touch">
<h2 style="font-family:Oswald,sans-serif;color:#1d3c10;font-weight:700;font-size:clamp(.85rem,2.5vw,1.05rem);letter-spacing:.05em;margin:0;line-height:1.4">STEPS ON HOW TO PAY USING ONLINE PAYMENT</h2>
<div style="height:2px;background:#c8991a;opacity:.4;border-radius:2px"></div>
<ol style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:12px">
<?php foreach($steps as $i => $step): ?>
<li style="display:flex;gap:10px;align-items:flex-start">
<div style="width:22px;height:22px;min-width:22px;border-radius:50%;background:<?= $color ?>;color:white;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;font-family:Oswald,sans-serif"><?= $i+1 ?></div>
<p style="color:#333;font-size:11px;line-height:1.6;margin:2px 0 0"><?= h($step) ?></p>
</li>
<?php endforeach ?>
</ol>
</div>
</div>
<div style="display:grid;grid-template-columns:1fr 1fr;flex-shrink:0">
<?php backForm('payment-instructions-'.$method, 'BACK', 'style="width:100%;background:#b01c1c;border:none;color:white;font-family:Oswald,sans-serif;font-weight:700;font-size:16px;letter-spacing:.15em;padding:16px;cursor:pointer"') ?>
<form method="post">
<input type="hidden" name="action" value="payment-done">
<button type="submit" style="width:100%;background:<?= $color ?>;border:none;color:white;font-family:Oswald,sans-serif;font-weight:700;font-size:16px;letter-spacing:.15em;padding:16px;cursor:pointer">DONE</button>
</form>
</div>
</div>
<?php }

function renderReceipt(): void {
    $cart    = $_SESSION['cart'];
    $payment = $_SESSION['payment'];
    $eatType = $_SESSION['eatType'];
    $orderNum= $_SESSION['orderNum'];

    $subtotal = cartSubtotal();
    $tax      = $subtotal * 0.12;
    $svc      = $subtotal * 0.02;
    $total    = $subtotal + $tax + $svc;

    $payLabel = match($payment) {
        'gcash' => 'GCash', 'maya' => 'Maya', default => 'Cash'
    };
    $eatLabel = $eatType === 'dine-in' ? 'Dine-In' : 'Take-Out';
    $date = date('F j, Y');
    $time = date('h:i:s A');
?>
<div class="kiosk-screen" style="background:#f0ede8">
<?php renderHeader('receipt') ?>
<div style="flex:1;overflow-y:auto;padding:16px;scrollbar-width:none">
<div style="background:white;border-radius:16px;box-shadow:0 8px 32px rgba(0,0,0,.12);max-width:440px;margin:0 auto;overflow:hidden">
<!-- Header -->
<div style="background:#1d3c10;text-align:center;padding:24px 16px">
<div style="font-family:Oswald,sans-serif;color:#c8991a;font-weight:700;letter-spacing:.3em;font-size:20px">SWEET CHICKEN</div>
<div style="color:#f5f0e8;font-size:10px;opacity:.6;letter-spacing:.3em;margin-top:4px;font-family:Oswald,sans-serif">ORIGINAL TASTE · EST. 2021</div>
<div style="color:#f5f0e8;font-size:11px;opacity:.5;margin-top:10px"><?= $date ?> · <?= $time ?></div>
</div>
<!-- Order info -->
<div style="padding:10px 20px;background:#f8f5f0;display:flex;justify-content:space-between;border-bottom:1px dashed #e0d8cc">
<div><div style="color:#666;font-size:9px">Order Number</div><div style="font-family:Oswald,sans-serif;color:#1d3c10;font-weight:700"><?= h($orderNum) ?></div></div>
<div style="text-align:center"><div style="color:#666;font-size:9px">Type</div><div style="font-family:Oswald,sans-serif;color:#1d3c10;font-weight:700"><?= h($eatLabel) ?></div></div>
<div style="text-align:right"><div style="color:#666;font-size:9px">Payment</div><div style="font-family:Oswald,sans-serif;color:#1d3c10;font-weight:700"><?= h($payLabel) ?></div></div>
</div>
<!-- Items -->
<div style="padding:14px 20px">
<div style="font-family:Oswald,sans-serif;color:#1d3c10;font-weight:700;font-size:11px;letter-spacing:.1em;margin-bottom:10px">ORDER DETAILS</div>
<?php foreach($cart as $ci): ?>
<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px">
<div>
<div style="font-family:Oswald,sans-serif;color:#1d3c10;font-size:13px;font-weight:600"><?= h($ci['quantity'].'× '.$ci['name']) ?></div>
<?php if($ci['flavor']): ?>
<div style="color:#c8991a;font-size:10px">Flavor: <?= h($ci['flavor']) ?></div>
<?php endif ?>
</div>
<div style="font-family:Oswald,sans-serif;color:#1d3c10;font-weight:700;font-size:13px">₱<?= number_format($ci['price']*$ci['quantity'],2) ?></div>
</div>
<?php endforeach ?>
</div>
<!-- Totals -->
<div style="padding:10px 20px 14px;background:#f8f5f0;border-top:1px dashed #e0d8cc">
<div style="display:flex;justify-content:space-between;font-size:11px;color:#666;margin-bottom:5px"><span>Subtotal</span><span>₱<?= number_format($subtotal,2) ?></span></div>
<div style="display:flex;justify-content:space-between;font-size:11px;color:#666;margin-bottom:5px"><span>VAT (12%)</span><span>₱<?= number_format($tax,2) ?></span></div>
<div style="display:flex;justify-content:space-between;font-size:11px;color:#666;margin-bottom:10px"><span>Service Charge</span><span>₱<?= number_format($svc,2) ?></span></div>
<div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid #e0d8cc;padding-top:8px">
<span style="font-family:Oswald,sans-serif;color:#1d3c10;font-weight:700;font-size:15px">TOTAL</span>
<span style="font-family:Oswald,sans-serif;color:#c8991a;font-weight:700;font-size:20px">₱<?= number_format($total,2) ?></span>
</div>
</div>
<!-- Footer -->
<div style="padding:14px;text-align:center;border-top:1px dashed #e0d8cc">
<div style="color:#666;font-size:11px">Thank you for dining with us!</div>
<div style="font-family:Oswald,sans-serif;color:#c8991a;font-size:11px;font-weight:600;letter-spacing:.15em;margin-top:4px">SWEET CHICKEN · EST. 2021</div>
</div>
</div>
</div>
<div style="padding:12px 16px;background:white;border-top:1px solid #e0d8cc;flex-shrink:0">
<form method="post">
<input type="hidden" name="action" value="order-more">
<button type="submit" style="width:100%;background:#1d3c10;border:none;color:#c8991a;font-family:Oswald,sans-serif;font-weight:700;font-size:18px;letter-spacing:.3em;padding:16px;border-radius:12px;cursor:pointer;box-shadow:0 4px 16px rgba(0,0,0,.15)">ORDER MORE</button>
</form>
</div>
</div>
<?php }

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<title>Sweet Chicken Kiosk</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    :root { color-scheme: light; }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; -webkit-tap-highlight-color: transparent; }
    html, body {
        width: 100%; height: 100%; overflow: hidden; background: #111a0a;
        font-family: 'Poppins', sans-serif; touch-action: manipulation;
        -webkit-text-size-adjust: 100%; text-size-adjust: 100%;
        overscroll-behavior: none;
    }
    button, input { font-family: inherit; -webkit-appearance: none; appearance: none; }
    button { touch-action: manipulation; }
    button:active { filter: brightness(.93); }
    img { -webkit-user-drag: none; user-select: none; }

    .kiosk-screen {
        width: 100vw; width: 100dvw; height: 100dvh; min-height: 100vh;
        display: flex; flex-direction: column; overflow: hidden; animation: fadeIn .25s ease;
        padding-top: env(safe-area-inset-top, 0px);
        padding-bottom: env(safe-area-inset-bottom, 0px);
        padding-left: env(safe-area-inset-left, 0px);
        padding-right: env(safe-area-inset-right, 0px);
    }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
    ::-webkit-scrollbar { display: none; }

    /* ─────────────── iPad / tablet (portrait & smaller landscape) ─────────────── */
    @media (max-width: 1024px) {
        .kiosk-sidebar { width: 64px !important; }
    }

    /* ─────────────── Below iPad width: stack the GCash/Maya payment screen ─────────────── */
    @media (max-width: 820px) {
        .pay-instructions-body { flex-direction: column !important; overflow-y: auto !important; -webkit-overflow-scrolling: touch; }
        .pay-instructions-qr { width: 100% !important; border-right: none !important; border-bottom: 1px solid #e0d8cc; padding: 14px !important; flex-shrink: 0; }
        .pay-instructions-steps { padding: 14px 18px !important; overflow-y: visible !important; }
    }

    /* ─────────────── Phones ─────────────── */
    @media (max-width: 600px) {
        .kiosk-header { padding: 6px 8px !important; }
        .kiosk-header-logo-circle { width: 26px !important; height: 26px !important; font-size: 12px !important; }
        .kiosk-header-brand { font-size: 10.5px !important; letter-spacing: .06em !important; }
        .kiosk-header-sub { display: none !important; }
        .kiosk-header-steplabel { display: none !important; }
        .kiosk-header-step-connector { width: 8px !important; }
        .kiosk-header-circle { width: 18px !important; height: 18px !important; font-size: 9px !important; }
        .kiosk-header-time { display: none !important; }
        .kiosk-header-eatbadge { font-size: 8px !important; padding: 2px 5px !important; }
        .kiosk-header-total { font-size: 12px !important; }

        .kiosk-sidebar { width: 54px !important; }
        .kiosk-sidebar-icon { font-size: 18px !important; }
        .kiosk-sidebar-label { font-size: 7.5px !important; }

        .kiosk-food-grid-inner { grid-template-columns: repeat(auto-fill, minmax(102px, 1fr)) !important; gap: 8px !important; }

        .two-col-row { flex-direction: column !important; gap: 12px !important; max-width: 320px !important; }
        .two-col-btn { padding: 20px 12px !important; }

        .kiosk-footer-buttons button { font-size: 12.5px !important; letter-spacing: .06em !important; padding: 14px 6px !important; }

        .kiosk-screen h1 { letter-spacing: .1em !important; }
    }

    @media (max-width: 380px) {
        .kiosk-header-brand { font-size: 9.5px !important; }
        .kiosk-header-total { font-size: 10.5px !important; }
        .kiosk-sidebar { width: 46px !important; }
        .kiosk-sidebar-icon { font-size: 15px !important; }
        .kiosk-sidebar-label { font-size: 6.5px !important; }
        .kiosk-footer-buttons button { font-size: 11px !important; }
    }

    /* Short landscape phones (e.g. iPhone SE/mini rotated): keep choice buttons side-by-side */
    @media (max-width: 900px) and (orientation: landscape) and (max-height: 430px) {
        .two-col-row { flex-direction: row !important; max-width: 460px !important; }
        .two-col-btn { padding: 14px 10px !important; }
        .kiosk-screen[data-screen="splash"] .kiosk-mascot-wrap { transform: scale(.75); }
    }
</style>
</head>
<body>
<?php
switch ($screen) {
    case 'splash':                          renderSplash();                     break;
    case 'eat-type':                        renderEatType();                    break;
    case 'menu':                            renderMenu();                       break;
    case 'choose-flavors':                  renderChooseFlavors();              break;
    case 'choose-payment':                  renderChoosePayment();              break;
    case 'online-payment':                  renderOnlinePayment();              break;
    case 'payment-instructions-gcash':      renderPaymentInstructions('gcash'); break;
    case 'payment-instructions-maya':       renderPaymentInstructions('maya');  break;
    case 'receipt':                         renderReceipt();                    break;
    default:
        $_SESSION['screen'] = 'splash';
        header('Location: ./');
        exit;
}
?>
</body>
</html>