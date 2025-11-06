<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Fetch dynamic settings
$store_name = getSetting('store_name');
$xmr_address = getSetting('xmr_address');

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please login to proceed to checkout.";
    redirect('index.php');
}

// Redirect to cart if cart is empty
if (empty($_SESSION['cart'])) {
    $_SESSION['error'] = "Your cart is empty.";
    redirect('cart.php');
}

// Fetch the total cart price in USD
$total_usd = 0;
foreach ($_SESSION['cart'] as $product_id => $item) {
    $total_usd += $item['price'] * $item['quantity'];
}

// Fetch the current XMR exchange rate using CoinGecko API
$xmr_rate = null;
try {
    $api_url = "https://api.coingecko.com/api/v3/simple/price?ids=monero&vs_currencies=usd";
    $response = file_get_contents($api_url);
    if ($response) {
        $data = json_decode($response, true);
        $xmr_rate = $data['monero']['usd'];
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Failed to fetch Monero exchange rate. Please try again later.";
    redirect('cart.php');
}

// Convert the total USD amount to XMR
if ($xmr_rate) {
    $total_xmr = $total_usd / $xmr_rate;
} else {
    $_SESSION['error'] = "Failed to fetch Monero exchange rate. Please try again later.";
    redirect('cart.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = 'xmr';
    $payment_address = $xmr_address;

    // Save order to database
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total_price, payment_method, payment_address, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([$user_id, $total_usd, $payment_method, $payment_address]);

    // Clear the cart
    unset($_SESSION['cart']);

    // Redirect to a success page
    $_SESSION['success'] = "Order placed successfully! Please complete your Monero (XMR) payment.";
    redirect('orders.php');
}

// Count cart items
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?php echo htmlspecialchars($store_name); ?></title>
    <link rel="stylesheet" href="assets/modern-styles.css">
</head>
<body>
    <div class="container">
        <div class="xp-window">
            <!-- Title Bar -->
            <div class="xp-titlebar">
                <div class="title-content">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                        <line x1="1" y1="10" x2="23" y2="10"/>
                    </svg>
                    <span class="title-text">💳 Checkout - <?php echo htmlspecialchars($store_name); ?></span>
                </div>
            </div>
            
            <!-- Navigation -->
            <div class="navigation">
                <a href="cart.php" class="xp-button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m12 19-7-7 7-7"/>
                        <path d="M19 12H5"/>
                    </svg>
                    Back to Cart
                </a>
            </div>
            
            <div class="content">
                <!-- Notifications -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="notification error">
                        <span class="icon">✕</span>
                        <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <!-- Order Summary -->
                <h3 style="font-size: 1.5rem; margin-bottom: 20px; color: var(--text-primary);">📦 Order Summary</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($_SESSION['cart'] as $product_id => $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td>$<?php echo htmlspecialchars(number_format($item['price'], 2)); ?></td>
                            <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                            <td><strong>$<?php echo htmlspecialchars(number_format($item['price'] * $item['quantity'], 2)); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background: var(--bg-primary);">
                            <td colspan="3" style="text-align: right; font-weight: 700; font-size: 1.125rem;">Grand Total:</td>
                            <td style="font-weight: 700; font-size: 1.25rem; background: linear-gradient(135deg, var(--primary), var(--secondary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                                $<?php echo htmlspecialchars(number_format($total_usd, 2)); ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>

                <!-- Payment Form -->
                <h3 style="font-size: 1.5rem; margin: 40px 0 20px; color: var(--text-primary);">💰 Payment Details</h3>
                <p style="color: var(--text-secondary); margin-bottom: 24px;">
                    Please send the exact amount of <strong style="color: var(--primary); font-size: 1.125rem;"><?php echo htmlspecialchars(number_format($total_xmr, 8)); ?> XMR</strong> to the following Monero address:
                </p>
                
                <div class="xmr-payment">
                    <p style="font-weight: 600; margin-bottom: 12px; color: var(--text-primary);">Monero (XMR) Address:</p>
                    <code><?php echo htmlspecialchars($xmr_address); ?></code>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo urlencode($xmr_address); ?>" alt="Monero QR Code" class="qr-code">
                    <p style="color: var(--text-muted); font-size: 0.875rem; margin-top: 16px;">
                        💡 Scan the QR code or copy the address above to complete your payment.
                    </p>
                </div>
                
                <form method="POST" action="" style="margin-top: 30px; text-align: center;">
                    <button type="submit" class="button success" style="font-size: 1.125rem; padding: 16px 32px;">
                        ✓ Confirm Order & Complete Payment
                    </button>
                </form>
                
                <!-- Footer -->
                <div class="footer">
                    <p>© 2025 Market-X - KEY TO O | Powered by Modern Design ✨</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>