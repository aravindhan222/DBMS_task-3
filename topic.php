<?php
$page_title = 'Topic';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Check if topic ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('index.php');
}

$topic_id = (int)$_GET['id'];

// Get topic data
$topic = get_topic($pdo, $topic_id);

if (!$topic) {
    redirect('index.php');
}

$page_title = $topic['title']; // Update page title with topic title

// Handle adding a new comment
$comment_error = '';
$comment_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_logged_in()) {
    $content = sanitize_input($_POST['content'] ?? '');
    $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;
    
    if (empty($content)) {
        $comment_error = 'Comment content is required.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO comments (topic_id, user_id, parent_id, content, created_at) VALUES (?, ?, ?, ?, NOW())");
            $result = $stmt->execute([$topic_id, $_SESSION['user_id'], $parent_id, $content]);
            
            if ($result) {
                $comment_success = 'Comment added successfully.';
                // Redirect to avoid form resubmission
                redirect("topic.php?id=$topic_id#comments");
            } else {
                $comment_error = 'Failed to add comment.';
            }
        } catch(PDOException $e) {
            $comment_error = 'Failed to add comment: ' . $e->getMessage();
        }
    }
}

// Get comments for this topic
$comments = get_comments($pdo, $topic_id);

include 'includes/header.php';
?>

<div class="topic-detail card">
    <div class="card-header">
        <h2><?php echo htmlspecialchars($topic['title']); ?></h2>
        <div>
            <?php if (is_logged_in() && $topic['user_id'] == $_SESSION['user_id']): ?>
                <a href="edit_topic.php?id=<?php echo $topic_id; ?>" class="btn btn-secondary">Edit</a>
            <?php endif; ?>
            <a href="index.php" class="btn btn-secondary">Back to Topics</a>
        </div>
    </div>
    
    <div class="topic-author">
        <div class="topic-author-avatar">
            <img src="<?php echo get_avatar_url($topic['avatar'] ?? ''); ?>" alt="User Avatar">
        </div>
        <div class="topic-author-info">
            <div class="topic-author-name">Posted by <a href="profile.php?id=<?php echo $topic['user_id']; ?>"><?php echo htmlspecialchars($topic['username']); ?></a></div>
            <div class="topic-date"><?php echo format_date($topic['created_at']); ?></div>
        </div>
    </div>
    
    <div class="topic-content">
        <?php echo nl2br(htmlspecialchars($topic['content'])); ?>
    </div>
    
    <div id="comments" class="comments-section">
        <h3><?php echo count($comments); ?> Comments</h3>
        
        <?php if ($comment_error): ?>
            <div class="error-message"><?php echo $comment_error; ?></div>
        <?php endif; ?>
        
        <?php if ($comment_success): ?>
            <div class="success-message"><?php echo $comment_success; ?></div>
        <?php endif; ?>
        
        <?php if (is_logged_in()): ?>
            <div class="comment-form">
                <h4>Add a Comment</h4>
                <form action="topic.php?id=<?php echo $topic_id; ?>" method="POST" class="validate-form">
                    <div class="form-group">
                        <textarea name="content" class="form-control validate-input auto-resize" rows="4" required placeholder="Write your comment here..."></textarea>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn">Post Comment</button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="login-prompt">
                <p>Please <a href="login.php">login</a> to post a comment.</p>
            </div>
        <?php endif; ?>
        
        <div class="comment-list">
            <?php 
            // Function to recursively display comments with threading
            function display_comments($comments, $parent_id = 0) {
                global $pdo, $topic_id;
                
                foreach ($comments as $comment) {
                    if ($comment['parent_id'] == $parent_id) {
                        ?>
                        <div class="comment" id="comment-<?php echo $comment['id']; ?>">
                            <div class="comment-header">
                                <div class="comment-author">
                                    <div class="comment-avatar">
                                        <img src="<?php echo get_avatar_url($comment['avatar'] ?? ''); ?>" alt="User Avatar">
                                    </div>
                                    <div>
                                        <div class="comment-author-name"><a href="profile.php?id=<?php echo $comment['user_id']; ?>"><?php echo htmlspecialchars($comment['username']); ?></a></div>
                                        <div class="comment-date"><?php echo format_date($comment['created_at']); ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="comment-content">
                                <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                            </div>
                            <div class="comment-actions">
                                <?php if (is_logged_in()): ?>
                                    <a href="#" class="reply-link" data-comment-id="<?php echo $comment['id']; ?>">Reply</a>
                                    
                                    <?php if ($comment['user_id'] == $_SESSION['user_id']): ?>
                                        <a href="edit_comment.php?id=<?php echo $comment['id']; ?>" class="edit-link">Edit</a>
                                        <a href="delete_comment.php?id=<?php echo $comment['id']; ?>" class="delete-button">Delete</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (is_logged_in()): ?>
                                <div id="replyForm-<?php echo $comment['id']; ?>" style="display: none; margin-top: 10px;">
                                    <form action="topic.php?id=<?php echo $topic_id; ?>" method="POST" class="validate-form">
                                        <input type="hidden" name="parent_id" value="<?php echo $comment['id']; ?>">
                                        <div class="form-group">
                                            <textarea name="content" class="form-control validate-input auto-resize" rows="3" required placeholder="Write your reply here..."></textarea>
                                        </div>
                                        <div class="form-group">
                                            <button type="submit" class="btn">Post Reply</button>
                                            <button type="button" class="btn btn-secondary cancel-reply" data-comment-id="<?php echo $comment['id']; ?>">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            <?php endif; ?>
                            
                            <?php
                            // Get child comments
                            $child_comments = get_comments($pdo, $topic_id, $comment['id']);
                            
                            if ($child_comments && count($child_comments) > 0): ?>
                                <div class="nested-comments">
                                    <?php display_comments($child_comments, $comment['id']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php
                    }
                }
            }
            
            // Display all top-level comments
            if ($comments && count($comments) > 0) {
                display_comments($comments);
            } else {
                echo '<div class="empty-state"><p>No comments yet. Be the first to comment!</p></div>';
            }
            ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle reply cancel buttons
    const cancelButtons = document.querySelectorAll('.cancel-reply');
    
    cancelButtons.forEach(button => {
        button.addEventListener('click', function() {
            const commentId = this.getAttribute('data-comment-id');
            const replyForm = document.getElementById('replyForm-' + commentId);
            
            if (replyForm) {
                replyForm.style.display = 'none';
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
