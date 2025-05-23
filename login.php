<?php
$page_title = 'Login';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Check if user is already logged in
if (is_logged_in()) {
    redirect('index.php');
}

$error = '';
$username = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form inputs
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Attempt to login
        $result = login_user($username, $password);
        
        if ($result['success']) {
            // Redirect to home page after successful login
            redirect('index.php');
        } else {
            $error = $result['message'];
        }
    }
}

include 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2>Login to Your Account</h2>
    </div>
    
    <?php if ($error): ?>
        <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form action="login.php" method="POST" class="validate-form">
        <div class="form-group">
            <label for="username" class="form-label">Username</label>
            <input type="text" id="username" name="username" class="form-control validate-input" required value="<?php echo htmlspecialchars($username); ?>">
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
            <button type="submit" class="btn btn-block">Login</button>
        </div>
    </form>
    
    <div style="text-align: center; margin-top: 20px;">
        <p>Don't have an account? <a href="register.php">Register now</a></p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
