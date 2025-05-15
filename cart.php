<?php
// cart.php
$pageTitle = "Your Shopping Cart";
require_once __DIR__ . '/includes/header.php';

if (!isLoggedIn()) {
    $_SESSION['error_message'] = "Please log in to view your cart.";
    header("Location: login.php");
    exit;
}

// Xử lý thông báo từ các action (update, remove)
if (isset($_SESSION['message_cart_page'])) {
    $cart_page_message_type = $_SESSION['message_cart_page_type'] ?? 'info';
    echo '<div class="alert alert-' . $cart_page_message_type . '">' . htmlspecialchars($_SESSION['message_cart_page']) . '</div>';
    unset($_SESSION['message_cart_page']);
    unset($_SESSION['message_cart_page_type']);
}

$cart = $_SESSION['cart'] ?? [];
$grand_total = 0;
?>

<h1><?php echo $pageTitle; ?></h1>

<?php if (empty($cart)): ?>
    <p class="alert alert-info">Your cart is currently empty. <a href="<?php echo SITE_URL; ?>/index.php">Get back to NoW!</a>.</p>
<?php else: ?>
    <div class="cart-page-container">
        <?php foreach ($cart as $seller_id => $products_by_seller): ?>
            <?php
            // Lấy tên người bán
            $stmt_seller = $pdo->prepare("SELECT user_name FROM users WHERE user_id = :seller_id");
            $stmt_seller->bindParam(':seller_id', $seller_id, PDO::PARAM_INT);
            $stmt_seller->execute();
            $seller_info = $stmt_seller->fetch(PDO::FETCH_ASSOC); // Corrected variable name
            $seller_name = $seller_info ? htmlspecialchars($seller_info['user_name']) : "Unknown Seller";
            $order_subtotal_by_seller = 0;
            ?>
            <div class="cart-seller-group">
                <h3>Order from Seller: <?php echo $seller_name; ?></h3>
                <form action="<?php echo SITE_URL; ?>/actions/update_cart_action.php" method="post">
                    <input type="hidden" name="seller_id_for_update" value="<?php echo $seller_id; ?>">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th colspan="2">Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th>Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products_by_seller as $product_id => $cart_item): ?>
                                <?php
                                $item = $cart_item['item_details'];
                                $quantity = $cart_item['quantity'];
                                $subtotal = $item['price'] * $quantity;
                                $order_subtotal_by_seller += $subtotal;
                                $grand_total += $subtotal;
                                $item_image_path = SITE_URL . '/uploads/products_images/' . htmlspecialchars($item['image']);
                                $default_item_image_path = SITE_URL . '/uploads/products_images/default_product.png';
                                ?>
                                <tr>
                                    <td class="cart-item-image">
                                        <a href="<?php echo SITE_URL . '/product_page.php?id=' . $item['product_id']; ?>">
                                            <img src="<?php echo $item_image_path; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                 onerror="this.onerror=null; this.src='<?php echo $default_item_image_path; ?>';">
                                        </a>
                                    </td>
                                    <td class="cart-item-details">
                                        <a href="<?php echo SITE_URL . '/product_page.php?id=' . $item['product_id']; ?>">
                                            <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                        </a>
                                        </td>
                                    <td><?php echo number_format($item['price'], 0, ',', '.'); ?>₫</td>
                                    <td class="cart-item-quantity">
                                        <input type="number" name="quantities[<?php echo $product_id; ?>]"
                                               value="<?php echo $quantity; ?>" min="1"
                                               max="<?php echo $item['max_stock']; ?>" class="form-control quantity-input-cart"
                                               style="width: 70px; text-align: center;">
                                    </td>
                                    <td class="cart-item-price"><?php echo number_format($subtotal, 0, ',', '.'); ?>₫</td>
                                    <td class="cart-actions">
                                        <a href="<?php echo SITE_URL; ?>/actions/remove_from_cart_action.php?product_id=<?php echo $product_id; ?>&seller_id=<?php echo $seller_id; ?>"
                                           class="btn btn-danger btn-sm" title="Remove product"
                                           onclick="return confirm('Are you sure you want to remove this product from your cart?');">&times;</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="cart-seller-actions">
                        <button type="submit" name="update_cart_specific_seller" class="btn btn-info">Update Cart (This Seller)</button>
                        <a href="<?php echo SITE_URL; ?>/checkout.php?seller_id=<?php echo $seller_id; ?>" class="btn btn-success">Checkout (This Order)</a>
                    </div>
                </form>
                <p class="cart-seller-subtotal"><strong>Order Subtotal (Seller: <?php echo $seller_name; ?>): <?php echo number_format($order_subtotal_by_seller, 0, ',', '.'); ?>₫</strong></p>
            </div> <?php endforeach; ?>

        <div class="cart-summary">
            <h3>Total</h3>
            <p class="total">
                <span>Total Price:</span>
                <strong><?php echo number_format($grand_total, 0, ',', '.'); ?>₫</strong>
            </p>
            <a href="<?php echo SITE_URL; ?>/index.php" class="btn btn-secondary">Continue Shopping</a>
        </div>
    </div> <?php endif; ?>

<style>
    .cart-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
    .cart-table th, .cart-table td { padding: 10px; border: 1px solid #ddd; text-align: left; vertical-align: middle;}
    .cart-table th { background-color: #f9f9f9; }
    .cart-item-image img { width: 60px; height: 60px; object-fit: cover; border-radius: 3px; }
    .quantity-input-cart { display: inline-block; }
    .cart-seller-group { border: 1px solid #eee; padding: 15px; margin-bottom: 20px; border-radius: 5px; background-color: #fdfdfd;}
    .cart-seller-group h3 { margin-top: 0; border-bottom: 1px dashed #ccc; padding-bottom: 10px; margin-bottom: 15px;}
    .cart-seller-actions { margin-top: 15px; text-align: right; }
    .cart-seller-actions .btn { margin-left: 10px; }
    .cart-seller-subtotal { text-align: right; font-size: 1.1em; margin-top: 10px; }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
