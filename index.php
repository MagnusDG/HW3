<?php
// index.php
$pageTitle = "Home"; // Đã đổi sang tiếng Anh
require_once __DIR__ . '/includes/header.php';

if (isset($_GET['message']) && $_GET['message'] === 'account_deleted_successfully') {
    echo '<div class="alert alert-success modern-alert">Your account has been successfully deleted. Your username and email can now be used for new registrations.</div>';
}


$categories = [
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

// Pagination settings
$productsPerPage = 8;
$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) {
    $currentPage = 1;
}
$offset = ($currentPage - 1) * $productsPerPage;

// Search and filter conditions
$searchKeyword = isset($_GET['search']) ? trim($_GET['search']) : '';
$selectedCategory = isset($_GET['category']) ? trim($_GET['category']) : ''; // Đây sẽ là key tiếng Anh

// --- Build base SQL and parameters for WHERE clauses ---
// KHỞI TẠO $baseSqlWhere và $whereParams Ở ĐÂY
$baseSqlWhere = " FROM products p JOIN users u ON p.seller_id = u.user_id WHERE 1=1";
$whereParams = []; // Parameters for the WHERE clauses, sẽ dùng cho cả count và data query

if (!empty($searchKeyword)) {
    $baseSqlWhere .= " AND p.product_name LIKE :keyword";
    $whereParams[':keyword'] = "%" . $searchKeyword . "%";
}

// Xử lý $selectedCategory (đã là key tiếng Anh nếu bạn đã đổi $categories)
// Dòng 49, 50 bạn báo lỗi có thể nằm trong khối if này
if (!empty($selectedCategory) && $selectedCategory !== 'All Categories') { // "All Categories" là giá trị mới
    if (array_key_exists($selectedCategory, $categories)) { // Kiểm tra xem $selectedCategory có phải là key hợp lệ không
        // Điều kiện category sẽ được thêm vào $baseSqlWhere
        // Không cần strpos ở đây nữa nếu $baseSqlWhere được xây dựng tuần tự
        $baseSqlWhere .= " AND p.category = :category_where"; // Sử dụng placeholder thống nhất
        $whereParams[':category_where'] = $selectedCategory; // Dùng trực tiếp key tiếng Anh
    } else {
        // Optional: Ghi log nếu category không hợp lệ
        // error_log("Invalid category selected: " . $selectedCategory);
    }
}


if (isLoggedIn()) {
    $baseSqlWhere .= " AND p.seller_id != :logged_in_user_id";
    $whereParams[':logged_in_user_id'] = (int)$_SESSION['user_id'];
}

// --- Get total number of products for pagination ---
// $countSqlFinal được xây dựng từ $baseSqlWhere đã có đủ điều kiện
$countSqlFinal = "SELECT COUNT(*) " . $baseSqlWhere;
$stmtCount = $pdo->prepare($countSqlFinal);

foreach ($whereParams as $key => &$val) {
    $paramType = ($key === ':logged_in_user_id' || $key === ':seller_id') ? PDO::PARAM_INT : PDO::PARAM_STR;
    // Nếu key là ':category_where', nó sẽ được bind như PDO::PARAM_STR (mặc định)
    $stmtCount->bindParam($key, $val, $paramType);
}
unset($val);
$stmtCount->execute();
$totalProducts = $stmtCount->fetchColumn();
$totalPages = ceil($totalProducts / $productsPerPage);


// --- Fetch products for the current page ---
$dataSql = "SELECT p.*, u.user_name AS seller_name " . $baseSqlWhere; // Sử dụng lại $baseSqlWhere
$dataSql .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";

$stmtData = $pdo->prepare($dataSql);

foreach ($whereParams as $key => &$val) { // Sử dụng lại $whereParams
    $paramType = ($key === ':logged_in_user_id' || $key === ':seller_id') ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmtData->bindParam($key, $val, $paramType);
}
unset($val);
$stmtData->bindValue(':limit', (int)$productsPerPage, PDO::PARAM_INT);
$stmtData->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

$stmtData->execute();
$products = $stmtData->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="category-buttons">
    <a href="<?php echo SITE_URL; ?>/index.php?search=<?php echo urlencode($searchKeyword); ?>" class="btn <?php echo (empty($selectedCategory) || $selectedCategory === 'All Categories') ? 'btn-secondary' : ''; ?>">All Categories</a>
    <?php foreach ($categories as $key => $displayName): ?>
        <a href="<?php echo SITE_URL; ?>/index.php?category=<?php echo urlencode($displayName); ?>&search=<?php echo urlencode($searchKeyword); ?>"
           class="btn <?php echo ($selectedCategory === $displayName) ? 'btn-secondary' : ''; ?>">
            <?php echo htmlspecialchars($displayName); ?>
        </a>
    <?php endforeach; ?>
</div>

<h2><?php echo htmlspecialchars($selectedCategory ?: 'All Products'); ?> <?php if($searchKeyword) echo " for '".htmlspecialchars($searchKeyword)."'"; ?></h2>

<div class="product-grid">
    <?php if (count($products) > 0): ?>
        <?php foreach ($products as $product): ?>
            <?php
                $productImage = 'default_product.png';
                if (!empty($product['product_images'])) {
                    $images = json_decode($product['product_images'], true);
                    if (is_array($images) && !empty($images[0])) {
                        $productImage = $images[0];
                    } elseif (!is_array($images) && !empty($product['product_images'])) {
                        $productImage = $product['product_images'];
                    }
                }
                $imagePath = SITE_URL . '/uploads/products_images/' . htmlspecialchars($productImage);
                $defaultProductImage = SITE_URL . '/uploads/products_images/default_product.png';
            ?>
            <a href="<?php echo SITE_URL; ?>/product_page.php?id=<?php echo $product['product_id']; ?>" class="product-card">
                <div class="product-image-container">
                    <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                         onerror="this.onerror=null; this.src='<?php echo $defaultProductImage; ?>';">
                </div>
                <div class="product-info">
                    <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                    <p class="product-category">Category: <?php echo htmlspecialchars($product['category']); ?></p>
                    <p class="product-price">NT$<?php echo number_format($product['price'], 2, '.', ','); ?></p>
                    <p class="product-seller">Seller: <?php echo htmlspecialchars($product['seller_name']); ?></p>
                </div>
            </a>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Sorry, the item you're looking for is currently not available on NoW.</p>
    <?php endif; ?>
</div>

<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php if ($currentPage > 1): ?>
        <a href="?page=<?php echo $currentPage - 1; ?>&search=<?php echo urlencode($searchKeyword); ?>&category=<?php echo urlencode($selectedCategory); ?>">&laquo; Previous</a>
    <?php else: ?>
        <span class="disabled">&laquo; Forward</span>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <?php if ($i == $currentPage): ?>
            <span class="current-page"><?php echo $i; ?></span>
        <?php else: ?>
            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($searchKeyword); ?>&category=<?php echo urlencode($selectedCategory); ?>"><?php echo $i; ?></a>
        <?php endif; ?>
    <?php endfor; ?>

    <?php if ($currentPage < $totalPages): ?>
        <a href="?page=<?php echo $currentPage + 1; ?>&search=<?php echo urlencode($searchKeyword); ?>&category=<?php echo urlencode($selectedCategory); ?>">Next &raquo;</a>
    <?php else: ?>
        <span class="disabled">Back &raquo;</span>
    <?php endif; ?>
</div>
<?php endif; ?>


<?php require_once __DIR__ . '/includes/footer.php'; ?>
