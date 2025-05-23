<?php
$page_title = 'Create Topic';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$error = '';
$title = '';
$content = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form inputs
    $title = sanitize_input($_POST['title'] ?? '');
    $content = sanitize_input($_POST['content'] ?? '');
    
    if (empty($title)) {
        $error = 'Title is required.';
    } elseif (strlen($title) < 5) {
        $error = 'Title must be at least 5 characters long.';
    } elseif (empty($content)) {
        $error = 'Content is required.';
    } elseif (strlen($content) < 20) {
        $error = 'Content must be at least 20 characters long.';
    } else {
        try {
            // Insert new topic
            $stmt = $pdo->prepare("INSERT INTO topics (user_id, title, content, created_at) VALUES (?, ?, ?, NOW())");
            $result = $stmt->execute([$_SESSION['user_id'], $title, $content]);
            
            if ($result) {
                $topic_id = $pdo->lastInsertId();
                redirect("topic.php?id=$topic_id");
            } else {
                $error = 'Failed to create topic.';
            }
        } catch(PDOException $e) {
            $error = 'Failed to create topic: ' . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2>Create New Topic</h2>
    </div>
    
    <?php if ($error): ?>
        <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form action="create_topic.php" method="POST" class="validate-form">
        <div class="form-group">
            <label for="title" class="form-label">Title</label>
            <input type="text" id="title" name="title" class="form-control validate-input" required value="<?php echo htmlspecialchars($title); ?>">
            <small>Be specific and imagine you're asking a question to another person.</small>
        </div>
        
        <div class="form-group">
            <label for="content" class="form-label">Content</label>
            <textarea id="content" name="content" class="form-control validate-input auto-resize" rows="10" required><?php echo htmlspecialchars($content); ?></textarea>
            <small>Include all the information someone would need to answer your question.</small>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn">Create Topic</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
