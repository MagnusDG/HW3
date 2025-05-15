<?php
// checkout.php
$pageTitle = "Checkout Order";
require_once __DIR__ . '/includes/header.php';

if (!isLoggedIn()) {
    $_SESSION['error_message'] = "Please log in to proceed to checkout.";
    header("Location: login.php");
    exit;
}

$seller_id_to_checkout = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : 0;
$cart = $_SESSION['cart'] ?? []; // Corrected variable name

if ($seller_id_to_checkout <= 0 || !isset($cart[$seller_id_to_checkout]) || empty($cart[$seller_id_to_checkout])) {
    $_SESSION['message_cart_page'] = "No valid order from this seller to checkout or cart is empty.";
    $_SESSION['message_cart_page_type'] = "warning";
    header("Location: cart.php");
    exit;
}

// Lấy thông tin người bán
$stmt_seller_checkout = $pdo->prepare("SELECT user_name FROM users WHERE user_id = :seller_id");
$stmt_seller_checkout->bindParam(':seller_id', $seller_id_to_checkout, PDO::PARAM_INT);
$stmt_seller_checkout->execute();
$seller_checkout_info = $stmt_seller_checkout->fetch(PDO::FETCH_ASSOC);
$seller_checkout_name = $seller_checkout_info ? htmlspecialchars($seller_checkout_info['user_name']) : "Unknown Seller";

// Lấy thông tin người mua hiện tại để điền form
$buyer_id = $_SESSION['user_id'];
$stmt_buyer = $pdo->prepare("SELECT fullname, address, phone FROM users WHERE user_id = :buyer_id");
$stmt_buyer->bindParam(':buyer_id', $buyer_id, PDO::PARAM_INT);
$stmt_buyer->execute();
$buyer_info = $stmt_buyer->fetch(PDO::FETCH_ASSOC);

$buyer_fullname_default = $buyer_info['fullname'] ?? '';
$buyer_address_default = $buyer_info['address'] ?? '';
$buyer_phone_default = $buyer_info['phone'] ?? '';

// Repopulate form data and display errors if redirected from place_order_action.php
$checkout_form_data = $_SESSION['checkout_form_data'][$seller_id_to_checkout] ?? [];
$buyer_fullname = htmlspecialchars($checkout_form_data['buyer_fullname'] ?? $buyer_fullname_default);
$buyer_address = htmlspecialchars($checkout_form_data['buyer_address'] ?? $buyer_address_default);
$buyer_phone = htmlspecialchars($checkout_form_data['buyer_phone'] ?? $buyer_phone_default);
$selected_payment_method = $checkout_form_data['payment_method'] ?? '';

$checkout_errors = $_SESSION['checkout_errors'][$seller_id_to_checkout] ?? [];
$fullname_err = htmlspecialchars($checkout_errors['buyer_fullname'] ?? '');
$address_err = htmlspecialchars($checkout_errors['buyer_address'] ?? '');
$phone_err = htmlspecialchars($checkout_errors['buyer_phone'] ?? '');
$payment_err = htmlspecialchars($checkout_errors['payment_method'] ?? '');
$general_checkout_err = htmlspecialchars($checkout_errors['general'] ?? '');

unset($_SESSION['checkout_form_data'][$seller_id_to_checkout]);
unset($_SESSION['checkout_errors'][$seller_id_to_checkout]);


$products_for_this_seller = $cart[$seller_id_to_checkout];
$order_total_amount = 0;
$order_product_details_for_db = []; // Mảng để lưu chi tiết sản phẩm cho CSDL

?>

<h1>Checkout Order from Seller: <?php echo $seller_checkout_name; ?></h1>

<div class="checkout-layout">
    <div class="checkout-order-summary">
        <h3>Order Details</h3>
        <?php foreach ($products_for_this_seller as $product_id => $cart_item): ?>
            <?php
            $item = $cart_item['item_details'];
            $quantity = $cart_item['quantity'];
            $subtotal = $item['price'] * $quantity;
            $order_total_amount += $subtotal;

            // Chuẩn bị dữ liệu sản phẩm cho việc lưu vào CSDL orders.order_products
            $order_product_details_for_db[] = [
                'product_id' => $item['product_id'],
                'name' => $item['name'],
                'quantity' => $quantity, // Corrected variable name
                'price_at_purchase' => $item['price'],
                'image' => $item['image']
            ];

            $item_image_path_checkout = SITE_URL . '/uploads/products_images/' . htmlspecialchars($item['image']);
            $default_item_image_path_checkout = SITE_URL . '/uploads/products_images/default_product.png';
            ?>
            <div class="checkout-item">
                <div class="checkout-item-image">
                    <img src="<?php echo $item_image_path_checkout; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>"
                         onerror="this.onerror=null; this.src='<?php echo $default_item_image_path_checkout; ?>';">
                </div>
                <div class="checkout-item-details">
                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                    <p>Quantity: <?php echo $quantity; ?></p> <!-- Corrected variable name -->
                    <p>Price: NT$<?php echo number_format($item['price'], 2, '.', ','); ?></p>
                </div>
                <div class="checkout-item-price">
                    NT$<?php echo number_format($subtotal, 2, '.', ','); ?>
                </div>
            </div>
        <?php endforeach; ?>
        <hr>
        <div class="order-total-amount">
            <h4>Total: <strong>NT$<?php echo number_format($order_total_amount, 2, '.', ','); ?></strong></h4>
        </div>
    </div> <!-- Corrected closing tag -->

    <div class="checkout-form-section">
        <h3>Shipping and Payment Information</h3>
        <?php if ($general_checkout_err): ?>
            <div class="alert alert-danger"><?php echo $general_checkout_err; ?></div>
        <?php endif; ?>

        <form action="<?php echo SITE_URL; ?>/actions/place_order_action.php" method="post">
            <input type="hidden" name="seller_id" value="<?php echo $seller_id_to_checkout; ?>">
            <input type="hidden" name="order_total_price" value="<?php echo $order_total_amount; ?>">
            <input type="hidden" name="order_products_json" value="<?php echo htmlspecialchars(json_encode($order_product_details_for_db)); ?>">

            <div class="form-group">
                <label for="buyer_fullname">Recipient's Full Name:</label>
                <input type="text" name="buyer_fullname" id="buyer_fullname" class="form-control" value="<?php echo $buyer_fullname; ?>" required>
                <?php if ($fullname_err): ?><small class="alert-danger"><?php echo $fullname_err; ?></small><?php endif; ?>
            </div>

            <div class="form-group">
                <label for="buyer_address">Shipping Address:</label>
                <textarea name="buyer_address" id="buyer_address" class="form-control" rows="3" required><?php echo $buyer_address; ?></textarea>
                <?php if ($address_err): ?><small class="alert-danger"><?php echo $address_err; ?></small><?php endif; ?>
            </div>

            <div class="form-group">
                <label for="buyer_phone">Recipient's Phone Number:</label>
                <input type="tel" name="buyer_phone" id="buyer_phone" class="form-control" value="<?php echo $buyer_phone; ?>" required>
                <?php if ($phone_err): ?><small class="alert-danger"><?php echo $phone_err; ?></small><?php endif; ?>
            </div>

            <div class="form-group">
                <label>Payment Method:</label>
                <?php
                $payment_methods = [
                    'credit_card' => 'Credit/Debit Card',
                    'cod' => 'Cash on Delivery (COD)',
                    'e_wallet' => 'E-wallet',
                    'bank_transfer' => 'Bank Transfer'
                ];
                ?>
                <?php foreach ($payment_methods as $value => $label): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="payment_method" id="payment_<?php echo $value; ?>" value="<?php echo $value; ?>" <?php echo ($selected_payment_method === $value) ? 'checked' : ''; ?> required>
                        <label class="form-check-label" for="payment_<?php echo $value; ?>">
                            <?php echo $label; ?>
                        </label>
                    </div>
                <?php endforeach; ?>
                <?php if ($payment_err): ?><small class="alert-danger" style="display:block;"><?php echo $payment_err; ?></small><?php endif; ?>
            </div>

            <div class="form-group">
                <button type="submit" name="place_order" class="btn btn-primary btn-lg btn-block">Place Order</button>
            </div>
        </form>
        <a href="<?php echo SITE_URL; ?>/cart.php" class="btn btn-secondary">Back to Cart</a>
    </div>
</div>

<style>
    .checkout-layout { display: flex; gap: 30px; flex-wrap: wrap; }
    .checkout-order-summary { flex: 1; min-width: 300px; background-color:#f9f9f9; padding:20px; border-radius:5px; }
    .checkout-form-section { flex: 2; min-width: 400px; }
    .checkout-item { display: flex; gap: 15px; align-items: center; padding: 10px 0; border-bottom: 1px solid #eee; }
    .checkout-item:last-child { border-bottom: none; }
    .checkout-item-image img { width: 70px; height: 70px; object-fit: cover; border-radius: 3px; }
    .checkout-item-details { flex-grow: 1; }
    .checkout-item-details h4 { margin:0 0 5px 0; font-size: 1em;}
    .checkout-item-price { font-weight: bold; min-width: 100px; text-align: right; }
    .order-total-amount { text-align: right; margin-top: 15px; font-size: 1.2em; }
    .form-check { margin-bottom: 0.5rem; }
    .form-check-input { margin-right: 0.5rem; }
    @media (max-width: 768px) {
        .checkout-layout { flex-direction: column; }
    }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>