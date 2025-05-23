<?php
$page_title = 'Register';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Check if user is already logged in
if (is_logged_in()) {
    redirect('index.php');
}

$error = '';
$success = '';
$username = '';
$email = '';

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form inputs
    $username = sanitize_input($_POST['username'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif (strlen($username) < 3 || strlen($username) > 30) {
        $error = 'Username must be between 3 and 30 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Attempt to register
        $result = register_user($username, $email, $password);
        
        if ($result['success']) {
            $success = $result['message'];
            // Clear form
            $username = '';
            $email = '';
        } else {
            $error = $result['message'];
        }
    }
}

include 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2>Create an Account</h2>
    </div>
    
    <?php if ($error): ?>
        <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="success-message">
            <?php echo $success; ?>
            <p>You can now <a href="login.php">login to your account</a>.</p>
        </div>
    <?php else: ?>
        <form action="register.php" method="POST" class="validate-form">
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input type="text" id="username" name="username" class="form-control validate-input" required value="<?php echo htmlspecialchars($username); ?>">
            </div>
            
            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-control validate-input" required value="<?php echo htmlspecialchars($email); ?>">
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div style="position: relative;">
                    <input type="password" id="password" name="password" class="form-control validate-input" required data-min-length="6">
                    <span class="password-toggle" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <div style="position: relative;">
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control validate-input" required>
                    <span class="password-toggle" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-block">Register</button>
            </div>
        </form>
        
        <div style="text-align: center; margin-top: 20px;">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
