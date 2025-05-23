<?php
$page_title = 'Profile';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Check if user ID is provided, otherwise use logged-in user's ID
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $profile_id = (int)$_GET['id'];
} else if (is_logged_in()) {
    $profile_id = $_SESSION['user_id'];
} else {
    // Redirect to login if not logged in and no profile ID provided
    redirect('login.php');
}

// Get user data
$user = get_user_data($profile_id, $pdo);

if (!$user) {
    redirect('index.php');
}

// Get user's topics
try {
    $stmt = $pdo->prepare("SELECT t.id, t.title, t.created_at, 
                          (SELECT COUNT(*) FROM comments WHERE topic_id = t.id) as comment_count 
                          FROM topics t 
                          WHERE t.user_id = ? 
                          ORDER BY t.created_at DESC 
                          LIMIT 5");
    $stmt->execute([$profile_id]);
    $user_topics = $stmt->fetchAll();
} catch(PDOException $e) {
    $user_topics = [];
}

// Get user's comments
try {
    $stmt = $pdo->prepare("SELECT c.id, c.content, c.created_at, t.id as topic_id, t.title as topic_title 
                          FROM comments c 
                          JOIN topics t ON c.topic_id = t.id 
                          WHERE c.user_id = ? 
                          ORDER BY c.created_at DESC 
                          LIMIT 5");
    $stmt->execute([$profile_id]);
    $user_comments = $stmt->fetchAll();
} catch(PDOException $e) {
    $user_comments = [];
}

// Get user's stats
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as topic_count FROM topics WHERE user_id = ?");
    $stmt->execute([$profile_id]);
    $topic_count = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as comment_count FROM comments WHERE user_id = ?");
    $stmt->execute([$profile_id]);
    $comment_count = $stmt->fetchColumn();
} catch(PDOException $e) {
    $topic_count = 0;
    $comment_count = 0;
}

// Handle profile update
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_logged_in() && $profile_id === $_SESSION['user_id']) {
    $email = sanitize_input($_POST['email'] ?? '');
    $bio = sanitize_input($_POST['bio'] ?? '');
    $avatar = sanitize_input($_POST['avatar'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate data
    if (empty($email)) {
        $error_message = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } elseif (!empty($new_password) && strlen($new_password) < 6) {
        $error_message = 'New password must be at least 6 characters.';
    } elseif (!empty($new_password) && $new_password !== $confirm_password) {
        $error_message = 'New passwords do not match.';
    } else {
        // Check current password if changing password
        if (!empty($new_password)) {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$profile_id]);
            $current_hash = $stmt->fetchColumn();
            
            if (!password_verify($current_password, $current_hash)) {
                $error_message = 'Current password is incorrect.';
            } else {
                // Update profile with new password
                $result = update_profile($profile_id, [
                    'email' => $email,
                    'bio' => $bio,
                    'avatar' => $avatar,
                    'password' => $new_password
                ]);
                
                if ($result['success']) {
                    $success_message = $result['message'];
                    $user = get_user_data($profile_id, $pdo); // Refresh user data
                } else {
                    $error_message = $result['message'];
                }
            }
        } else {
            // Update profile without changing password
            $result = update_profile($profile_id, [
                'email' => $email,
                'bio' => $bio,
                'avatar' => $avatar
            ]);
            
            if ($result['success']) {
                $success_message = $result['message'];
                $user = get_user_data($profile_id, $pdo); // Refresh user data
            } else {
                $error_message = $result['message'];
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2><?php echo htmlspecialchars($user['username']); ?>'s Profile</h2>
    </div>
    
    <div class="profile-header">
        <div class="profile-avatar">
            <img src="<?php echo get_avatar_url($user['avatar'] ?? ''); ?>" alt="User Avatar">
        </div>
        <div class="profile-details">
            <h2><?php echo htmlspecialchars($user['username']); ?></h2>
            <div>Member since <?php echo format_date($user['date_joined']); ?></div>
            
            <div class="profile-stats">
                <div>
                    <div class="profile-stat-value"><?php echo $topic_count; ?></div>
                    <div class="profile-stat-label">Topics</div>
                </div>
                <div>
                    <div class="profile-stat-value"><?php echo $comment_count; ?></div>
                    <div class="profile-stat-label">Comments</div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($user['bio'])): ?>
        <div class="profile-bio">
            <h3>About</h3>
            <p><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (is_logged_in() && $profile_id === $_SESSION['user_id']): ?>
        <div class="profile-actions">
            <button id="edit-profile-btn" class="btn">Edit Profile</button>
        </div>
        
        <div id="edit-profile-form" style="display: none;">
            <h3>Edit Profile</h3>
            
            <?php if ($success_message): ?>
                <div class="success-message"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <form action="profile.php" method="POST" class="validate-form">
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-control validate-input" required value="<?php echo htmlspecialchars($user['email']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="bio" class="form-label">Bio</label>
                    <textarea id="bio" name="bio" class="form-control auto-resize" rows="4"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="avatar" class="form-label">Avatar URL</label>
                    <input type="url" id="avatar" name="avatar" class="form-control" value="<?php echo htmlspecialchars($user['avatar'] ?? ''); ?>">
                    <small>Leave empty to use a default avatar</small>
                </div>
                
                <h4>Change Password</h4>
                <p><small>Fill this section only if you want to change your password</small></p>
                
                <div class="form-group">
                    <label for="current_password" class="form-label">Current Password</label>
                    <div style="position: relative;">
                        <input type="password" id="current_password" name="current_password" class="form-control">
                        <span class="password-toggle" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="new_password" class="form-label">New Password</label>
                    <div style="position: relative;">
                        <input type="password" id="new_password" name="new_password" class="form-control validate-input" data-min-length="6">
                        <span class="password-toggle" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <div style="position: relative;">
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control validate-input">
                        <span class="password-toggle" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn">Update Profile</button>
                    <button type="button" id="cancel-edit" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    <?php endif; ?>
    
    <div class="user-topics">
        <h3>Recent Topics</h3>
        
        <?php if (count($user_topics) > 0): ?>
            <div class="topic-list">
                <?php foreach ($user_topics as $topic): ?>
                    <div class="topic-item">
                        <div class="topic-content">
                            <h4 class="topic-title"><a href="topic.php?id=<?php echo $topic['id']; ?>"><?php echo htmlspecialchars($topic['title']); ?></a></h4>
                            <div class="topic-meta">
                                <span><i class="fas fa-clock"></i> <?php echo format_date($topic['created_at']); ?></span>
                                <span><i class="fas fa-comments"></i> <?php echo $topic['comment_count']; ?> comments</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No topics created yet.</p>
        <?php endif; ?>
    </div>
    
    <div class="user-comments">
        <h3>Recent Comments</h3>
        
        <?php if (count($user_comments) > 0): ?>
            <div class="comment-list">
                <?php foreach ($user_comments as $comment): ?>
                    <div class="comment">
                        <div class="comment-content">
                            <p><?php echo get_excerpt($comment['content'], 100); ?></p>
                        </div>
                        <div class="comment-meta">
                            <span>on <a href="topic.php?id=<?php echo $comment['topic_id']; ?>"><?php echo htmlspecialchars($comment['topic_title']); ?></a></span>
                            <span><?php echo format_date($comment['created_at']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No comments made yet.</p>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editProfileBtn = document.getElementById('edit-profile-btn');
    const editProfileForm = document.getElementById('edit-profile-form');
    const cancelEditBtn = document.getElementById('cancel-edit');
    
    if (editProfileBtn && editProfileForm) {
        editProfileBtn.addEventListener('click', function() {
            editProfileForm.style.display = 'block';
            editProfileBtn.style.display = 'none';
        });
        
        if (cancelEditBtn) {
            cancelEditBtn.addEventListener('click', function() {
                editProfileForm.style.display = 'none';
                editProfileBtn.style.display = 'inline-block';
            });
        }
        
        // Show form if there was an error
        if (document.querySelector('.error-message')) {
            editProfileForm.style.display = 'block';
            editProfileBtn.style.display = 'none';
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
