<?php
// product_page.php
$pageTitle = "Product Details"; // Will be updated with the product name
require_once __DIR__ . '/includes/header.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    echo "<div class='alert alert-danger'>Invalid product or product not found.</div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Fetch product details
$stmt = $pdo->prepare(
    "SELECT p.*, u.user_name AS seller_username
     FROM products p
     JOIN users u ON p.seller_id = u.user_id
     WHERE p.product_id = :product_id"
);
$stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
$stmt->execute();
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo "<div class='alert alert-danger'>This product was not found.</div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Cập nhật pageTitle với tên sản phẩm thật
$pageTitle = htmlspecialchars($product['product_name']); // Cập nhật lại tiêu đề trang
// Để tiêu đề này có hiệu lực, bạn cần một cách để header.php đọc nó *sau khi* nó được set.
// Một cách đơn giản là echo lại phần head hoặc có 1 hàm để render header.
// Hoặc, chấp nhận tiêu đề chung cho trang này.
// Vì header đã được include, chúng ta không thể dễ dàng thay đổi <title> đã được in ra.
// Chúng ta sẽ hiển thị tên sản phẩm trong H1.

$product_images_array = json_decode($product['product_images'], true) ?: [];
if (!is_array($product_images_array) && !empty($product['product_images'])) { // Nếu chỉ là một chuỗi ảnh đơn
    $product_images_array = [$product['product_images']];
}
$main_image = !empty($product_images_array[0]) ? $product_images_array[0] : 'default_product.png';
$main_image_path = SITE_URL . '/uploads/products_images/' . htmlspecialchars($main_image);
$default_product_image_path = SITE_URL . '/uploads/products_images/default_product.png';

// Lấy danh mục sản phẩm (để hiển thị tên tiếng Việt)
$categories_for_display = [
    "Electronics & Accessories" => "Electronics & Accessories",
    "Home Appliances & Living Essentials" => "Home Appliances & Living Essentials",
    "Clothing & Accessories" => "Clothing & Accessories",
    "Beauty & Personal Care" => "Beauty & Personal Care",
    "Food & Beverages" => "Food & Beverages",
    "Home & Furniture" => "Home & Furniture",
    "Sports & Outdoor Equipment" => "Sports & Outdoor Equipment",
    "Automotive & Motorcycle Accessories" => "Automotive & Motorcycle Accessories",
    "Baby & Maternity Products" => "Baby & Maternity Products",
    "Books & Office Supplies" => "Books & Office Supplies",
    "Other" => "Other"
];
$category_display_name = $categories_for_display[$product['category']] ?? htmlspecialchars($product['category']);

// Thông báo từ add_to_cart_action
if (isset($_SESSION['message_cart'])) {
    $cart_message_type = $_SESSION['message_cart_type'] ?? 'info'; // 'success' or 'danger'
    echo '<div class="alert alert-' . $cart_message_type . '">' . htmlspecialchars($_SESSION['message_cart']) . '</div>';
    unset($_SESSION['message_cart']);
    unset($_SESSION['message_cart_type']);
}
?>

<div class="product-detail-layout">
    <div class="product-detail-images">
        <div class="main-image">
            <img id="mainProductImage" src="<?php echo $main_image_path; ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                 onerror="this.onerror=null; this.src='<?php echo $default_product_image_path; ?>';">
        </div>
        <?php if (count($product_images_array) > 1): ?>
            <div class="thumbnail-gallery">
                <?php foreach ($product_images_array as $index => $img_file): ?>
                    <?php $thumb_path = SITE_URL . '/uploads/products_images/' . htmlspecialchars($img_file); ?>
                    <img src="<?php echo $thumb_path; ?>" alt="Thumbnail <?php echo $index + 1; ?>"
                         class="thumbnail-item <?php echo ($index == 0) ? 'active-thumb' : ''; ?>"
                         data-image-src="<?php echo $thumb_path; ?>"
                         onerror="this.style.display='none';">
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="product-detail-info">
        <h1><?php echo htmlspecialchars($product['product_name']); ?></h1>
        <p class="seller-name">Seller: <?php echo htmlspecialchars($product['seller_username']); ?></p>
        <p class="product-category-detail">Category: <?php echo htmlspecialchars($category_display_name); ?></p>
        <p class="price">NT$<?php echo number_format($product['price'], 2, '.', ','); ?></p>
        <div class="description">
            <h4>Product Description:</h4>
            <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
        </div>
        <p>Stock available: <span id="stockAvailable"><?php echo $product['in_stock']; ?></span></p>

        <?php if ($product['in_stock'] > 0): ?>
            <form action="<?php echo SITE_URL; ?>/actions/add_to_cart_action.php" method="post">
                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                <div class="form-group quantity-selector-group">
                    <label for="quantity">Quantity:</label>
                    <div class="quantity-selector">
                        <button type="button" class="qty-decrease" aria-label="Decrease quantity">-</button>
                        <input type="text" name="quantity" id="quantity" class="qty-input" value="1" data-min="1" data-max="<?php echo $product['in_stock']; ?>">
                        <button type="button" class="qty-increase" aria-label="Increase quantity">+</button>
                        <span class="stock-info" data-stock="<?php echo $product['in_stock']; ?>"> (Max: <?php echo $product['in_stock']; ?>)</span>
                    </div>
                    <small id="quantityWarning" class="qty-warning" style="color:red; display:block; margin-top:5px;"></small>
                </div>
                <button type="submit" name="add_to_cart" class="btn btn-primary btn-lg">Add to Cart</button>
            </form>
        <?php else: ?>
            <p class="alert alert-warning">This product is currently out of stock.</p>
        <?php endif; ?>
         <?php if (isLoggedIn() && $product['seller_id'] == $_SESSION['user_id']): ?>
            <p class="alert alert-info" style="margin-top:15px;">This is your product. <a href="<?php echo SITE_URL; ?>/user.php?section=edit_product&id=<?php echo $product['product_id']; ?>">Edit product</a>.</p>
        <?php endif; ?> <!-- Corrected closing tag -->
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Image thumbnail switcher
    const mainImage = document.getElementById('mainProductImage');
    const thumbnails = document.querySelectorAll('.thumbnail-item');

    thumbnails.forEach(thumb => {
        thumb.addEventListener('click', function() {
            if (mainImage) {
                mainImage.src = this.dataset.imageSrc;
            }
            // Update active thumbnail
            thumbnails.forEach(t => t.classList.remove('active-thumb'));
            this.classList.add('active-thumb');
        });
    });

    // Quantity selector logic (đã có trong js/main.js, nhưng có thể tùy chỉnh ở đây nếu cần)
    const quantityInput = document.getElementById('quantity');
    const decreaseBtn = document.querySelector('.qty-decrease');
    const increaseBtn = document.querySelector('.qty-increase');
    const stockAvailable = parseInt(document.getElementById('stockAvailable').textContent, 10);
    const quantityWarning = document.getElementById('quantityWarning');

    function updateQuantity(value) {
        let currentVal = parseInt(quantityInput.value, 10);
        if (isNaN(currentVal)) currentVal = 1;

        let newValue = currentVal + value;

        if (newValue < 1) {
            newValue = 1;
            if (quantityWarning) quantityWarning.textContent = '';
        } else if (newValue > stockAvailable) { // Corrected variable name
            newValue = stockAvailable; // Corrected from stockAvailable to stockAvailable
            if (quantityWarning) quantityWarning.textContent = 'Maximum quantity available is ' + stockAvailable + '.';
        } else {
            if (quantityWarning) quantityWarning.textContent = '';
        }
        quantityInput.value = newValue;
    }

    if (decreaseBtn) {
        decreaseBtn.addEventListener('click', function() {
            updateQuantity(-1);
        });
    }

    if (increaseBtn) {
        increaseBtn.addEventListener('click', function() {
            updateQuantity(1);
        });
    }

    if (quantityInput) {
        quantityInput.addEventListener('input', function() {
            let val = parseInt(this.value, 10);
            if (isNaN(val) || val < 1) {
                this.value = 1;
                if (quantityWarning) quantityWarning.textContent = '';
            } else if (val > stockAvailable) {
                this.value = stockAvailable;
                if (quantityWarning) quantityWarning.textContent = 'Maximum quantity available is ' + stockAvailable + '.'; // Corrected variable name
            } else {
                 if (quantityWarning) quantityWarning.textContent = '';
            }
        });
        // Ensure initial value is valid
        let initialVal = parseInt(quantityInput.value, 10);
        if (isNaN(initialVal) || initialVal < 1) quantityInput.value = 1;
        if (initialVal > stockAvailable) quantityInput.value = stockAvailable;

    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
