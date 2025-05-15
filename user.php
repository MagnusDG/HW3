<?php
// user.php
$pageTitle = "My Account";
require_once __DIR__ . '/includes/header.php'; // Đã bao gồm config/db.php

// User must be logged in to access this page
if (!isLoggedIn()) {
    $_SESSION['error_message'] = "You need to log in to access this page.";
    header("Location: login.php");
    exit;
}

// Get current user's full data from database
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :user_id");
$stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$userData) {
    // Should not happen if session is valid, but good to check
    session_destroy();
    $_SESSION['error_message'] = "Error: User information not found. Please log in again.";
    header("Location: login.php");
    exit;
}

// Determine which section to display
// Default to 'my_account' (Basic Information)
$section = isset($_GET['section']) ? $_GET['section'] : 'my_account';

// Define path for user avatar
$userAvatar = SITE_URL . '/uploads/users_avatars/' . htmlspecialchars($userData['user_picture'] ?: DEFAULT_AVATAR);
$defaultAvatarPath = SITE_URL . '/uploads/users_avatars/' . DEFAULT_AVATAR;

?>

<div class="user-page-layout">
    <aside class="user-sidebar">
        <div class="user-avatar">
            <img src="<?php echo $userAvatar; ?>" alt="<?php echo htmlspecialchars($userData['user_name']); ?>"
                 onerror="this.onerror=null; this.src='<?php echo $defaultAvatarPath; ?>';">
        </div>
        <p class="user-name"><?php echo htmlspecialchars($userData['user_name']); ?></p>
        <ul>
            <li>
                <a href="?section=my_account" class="<?php echo ($section === 'my_account') ? 'active' : ''; ?>">Basic Information</a>
            </li>
            <li>
                <a href="?section=change_password" class="<?php echo ($section === 'change_password') ? 'active' : ''; ?>">Change Password</a>
            </li>
            <li>
                <a href="?section=my_products" class="<?php echo ($section === 'my_products') ? 'active' : ''; ?>">My Products</a>
            </li>
            <li>
                <a href="?section=my_purchases" class="<?php echo ($section === 'my_purchases') ? 'active' : ''; ?>">My Orders</a>
            </li>
            <li>
                <a href="?section=delete_account" class="<?php echo ($section === 'delete_account') ? 'active' : ''; ?>">Delete Account</a>
            </li>
            <li><a href="<?php echo SITE_URL; ?>/logout.php">Logout</a></li>
        </ul>
    </aside>

    <main class="user-content">
        <?php
        // Include section content based on $section variable
        // We will create these files in subsequent steps
        switch ($section) {
            case 'my_account':
                include __DIR__ . '/includes/user_sections/basic_info.php';
                break;
            case 'edit_basic_info': // Add a case for editing form
                 include __DIR__ . '/includes/user_sections/edit_basic_info_form.php';
                 break;
            case 'change_password':
                include __DIR__ . '/includes/user_sections/change_password_form.php';
                break;
            case 'my_products':
                include __DIR__ . '/includes/user_sections/my_products_list.php';
                break;
            case 'add_product': // For add product form
                include __DIR__ . '/includes/user_sections/add_product_form.php';
                break;
            case 'edit_product': // For edit product form
                include __DIR__ . '/includes/user_sections/edit_product_form.php';
                break;
            case 'my_purchases':
                include __DIR__ . '/includes/user_sections/my_purchases_list.php';
                break;
            case 'delete_account':
                include __DIR__ . '/includes/user_sections/delete_account_form.php';
                break;
            default:
                echo "<h2>Welcome to your account management page!</h2>";
                echo "<p>Select an item from the menu on the left to continue.</p>";
                break;
        }
        ?>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
