<?php
// login.php
$pageTitle = "Login";
require_once __DIR__ . '/includes/header.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: user.php");
    exit;
}

// Define variables and initialize with empty values
$account = ""; // Email
$account_err = $password_err = $login_err = "";

// Store form data in session to repopulate if there's an error
if (isset($_SESSION['form_data_login'])) {
    $account = htmlspecialchars($_SESSION['form_data_login']['account'] ?? '');
    unset($_SESSION['form_data_login']);
}

// Display errors from session if redirected from login_action.php
if (isset($_SESSION['errors_login'])) {
    $errors = $_SESSION['errors_login'];
    $account_err = htmlspecialchars($errors['account'] ?? '');
    $password_err = htmlspecialchars($errors['password'] ?? '');
    $login_err = htmlspecialchars($errors['general'] ?? '');
    unset($_SESSION['errors_login']);
}

?>

<h2>Login</h2>
<p>Please fill in your credentials to login.</p>

<?php if ($login_err): ?>
    <div class="alert alert-danger"><?php echo $login_err; ?></div>
<?php endif; ?>

<form action="<?php echo SITE_URL; ?>/actions/login_action.php" method="post">
    <div class="form-group">
        <label for="account">User Account (Email):</label>
        <input type="email" name="account" id="account" value="<?php echo $account; ?>" required>
        <?php if ($account_err): ?><small class="alert-danger"><?php echo $account_err; ?></small><?php endif; ?>
    </div>

    <div class="form-group">
        <label for="password">Password:</label>
        <div class="password-wrapper">
            <input type="password" name="password" id="password" required>
            <button type="button" class="toggle-password">Show</button>
        </div>
        <?php if ($password_err): ?><small class="alert-danger"><?php echo $password_err; ?></small><?php endif; ?>
    </div>

    <div class="form-group">
        <button type="submit" class="btn">Login</button>
    </div>
    <p>New customer? <a href="<?php echo SITE_URL; ?>/register.php">Sign up here</a>.</p>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
