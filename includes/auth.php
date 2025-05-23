<?php
require_once 'db.php';
require_once 'functions.php';

// Start session
session_start_safe();

// Register user
function register_user($username, $email, $password) {
    global $pdo;
    
    // Check if username or email already exists
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            return ['success' => false, 'message' => 'Username or email already exists'];
        }
        
        // Hash the password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, date_joined) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$username, $email, $password_hash]);
        
        return ['success' => true, 'message' => 'Registration successful. You can now login.'];
    } catch(PDOException $e) {
        return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
    }
}

// Login user
function login_user($username, $password) {
    global $pdo;
    
    try {
        // Get user by username
        $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid username or password'];
        }
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Password is correct, start a new session
            session_start_safe();
            
            // Store user data in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['logged_in'] = true;
            
            return ['success' => true, 'message' => 'Login successful'];
        } else {
            return ['success' => false, 'message' => 'Invalid username or password'];
        }
    } catch(PDOException $e) {
        return ['success' => false, 'message' => 'Login failed: ' . $e->getMessage()];
    }
}

// Logout user
function logout_user() {
    session_start_safe();
    
    // Unset all session variables
    $_SESSION = [];
    
    // Destroy the session
    session_destroy();
    
    return true;
}

// Update user profile
function update_profile($user_id, $data) {
    global $pdo;
    
    try {
        $query = "UPDATE users SET ";
        $params = [];
        $updates = [];
        
        // Add each field to update
        if (isset($data['email'])) {
            $updates[] = "email = ?";
            $params[] = $data['email'];
        }
        
        if (isset($data['bio'])) {
            $updates[] = "bio = ?";
            $params[] = $data['bio'];
        }
        
        if (isset($data['avatar'])) {
            $updates[] = "avatar = ?";
            $params[] = $data['avatar'];
        }
        
        // If password is being updated
        if (!empty($data['password'])) {
            $updates[] = "password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        // If no updates, return success
        if (empty($updates)) {
            return ['success' => true, 'message' => 'No changes made.'];
        }
        
        $query .= implode(", ", $updates);
        $query .= " WHERE id = ?";
        $params[] = $user_id;
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        return ['success' => true, 'message' => 'Profile updated successfully'];
    } catch(PDOException $e) {
        return ['success' => false, 'message' => 'Profile update failed: ' . $e->getMessage()];
    }
}
?>
