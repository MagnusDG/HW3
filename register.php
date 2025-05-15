<?php
// register.php
$pageTitle = "Register";
require_once __DIR__ . '/includes/header.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: user.php");
    exit;
}

// Define variables and initialize with empty values
$username = $email = $fullname = $address = $phone = "";
$username_err = $email_err = $password_err = $confirm_password_err = $fullname_err = $address_err = $phone_err = $photo_err = $general_err = "";

// Store form data in session to repopulate if there's an error, except passwords
if (isset($_SESSION['form_data'])) {
    $username = htmlspecialchars($_SESSION['form_data']['username'] ?? '');
    $email = htmlspecialchars($_SESSION['form_data']['email'] ?? '');
    $fullname = htmlspecialchars($_SESSION['form_data']['fullname'] ?? '');
    $address = htmlspecialchars($_SESSION['form_data']['address'] ?? '');
    $phone = htmlspecialchars($_SESSION['form_data']['phone'] ?? '');
    unset($_SESSION['form_data']); // Clear after use
}

// Display errors from session if redirected from register_action.php
if (isset($_SESSION['errors'])) {
    $errors = $_SESSION['errors'];
    $username_err = htmlspecialchars($errors['username'] ?? '');
    $email_err = htmlspecialchars($errors['email'] ?? '');
    $password_err = htmlspecialchars($errors['password'] ?? '');
    $confirm_password_err = htmlspecialchars($errors['confirm_password'] ?? '');
    $fullname_err = htmlspecialchars($errors['fullname'] ?? '');
    $address_err = htmlspecialchars($errors['address'] ?? '');
    $phone_err = htmlspecialchars($errors['phone'] ?? '');
    $photo_err = htmlspecialchars($errors['photo'] ?? '');
    $general_err = htmlspecialchars($errors['general'] ?? '');
    unset($_SESSION['errors']); // Clear after use
}
?>

<h2>Register New Account</h2>
<p>Please fill out this form to create an account. All fields are required.</p>

<?php if ($general_err): ?>
    <div class="alert alert-danger"><?php echo $general_err; ?></div>
<?php endif; ?>

<form action="<?php echo SITE_URL; ?>/actions/register_action.php" method="post" enctype="multipart/form-data">
    <div class="form-group">
        <label for="username">Username:</label>
        <input type="text" name="username" id="username" value="<?php echo $username; ?>" required>
        <?php if ($username_err): ?><small class="alert-danger"><?php echo $username_err; ?></small><?php endif; ?>
    </div>

    <div class="form-group">
        <label for="email">Email (Login Account):</label>
        <input type="email" name="email" id="email" value="<?php echo $email; ?>" required>
        <?php if ($email_err): ?><small class="alert-danger"><?php echo $email_err; ?></small><?php endif; ?>
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
        <label for="confirm_password">Confirm Password:</label>
        <div class="password-wrapper">
            <input type="password" name="confirm_password" id="confirm_password" required>
            <button type="button" class="toggle-password">Show</button>
        </div>
        <?php if ($confirm_password_err): ?><small class="alert-danger"><?php echo $confirm_password_err; ?></small><?php endif; ?>
    </div>

    <div class="form-group">
        <label for="fullname">Full Name:</label>
        <input type="text" name="fullname" id="fullname" value="<?php echo $fullname; ?>" required>
        <?php if ($fullname_err): ?><small class="alert-danger"><?php echo $fullname_err; ?></small><?php endif; ?>
    </div>

    <div class="form-group">
        <label for="address">Address:</label>
        <textarea name="address" id="address" required><?php echo $address; ?></textarea>
        <?php if ($address_err): ?><small class="alert-danger"><?php echo $address_err; ?></small><?php endif; ?>
    </div>

    <div class="form-group">
        <label for="phone">Phone Number:</label>
        <input type="text" name="phone" id="phone" value="<?php echo $phone; ?>" required>
        <?php if ($phone_err): ?><small class="alert-danger"><?php echo $phone_err; ?></small><?php endif; ?>
    </div>

    <div class="form-group">
        <label for="user_picture">Profile Picture (JPG, JPEG, PNG, GIF):</label>
        <input type="file" name="user_picture" id="user_picture" accept="image/jpeg, image/png, image/gif" required>
        <div class="thumbnails-container" id="photo-thumbnail-preview">
            </div>
        <?php if ($photo_err): ?><small class="alert-danger"><?php echo $photo_err; ?></small><?php endif; ?>
    </div>

    <div class="form-group">
        <button type="submit" class="btn">Register</button>
    </div>
</form>

<p>Already have an account? <a href="<?php echo SITE_URL; ?>/login.php">Login here</a>.</p>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
