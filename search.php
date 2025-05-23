<?php
$page_title = 'Search Results';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Get search query
$search_query = isset($_GET['q']) ? sanitize_input($_GET['q']) : '';

// Get current page for pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 10;

// Get total topics count for pagination
$total_topics = count_topics($pdo, $search_query);
$total_pages = ceil($total_topics / $limit);

// Get topics for current page with search filter
$topics = get_topics($pdo, $page, $limit, $search_query);

include 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2>Search Results: <?php echo htmlspecialchars($search_query); ?></h2>
    </div>
    
    <?php if (empty($search_query)): ?>
        <div class="empty-state">
            <p>Please enter a search term to find topics.</p>
            <form action="search.php" method="GET" class="search-form">
                <div class="form-group">
                    <input type="text" name="q" class="form-control" placeholder="Search for topics...">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn">Search</button>
                </div>
            </form>
        </div>
    <?php elseif ($topics && count($topics) > 0): ?>
        <div class="search-summary">
            <p>Found <?php echo $total_topics; ?> result<?php echo $total_topics != 1 ? 's' : ''; ?> for "<?php echo htmlspecialchars($search_query); ?>"</p>
        </div>
        
        <div class="topic-list">
            <?php foreach ($topics as $topic): ?>
                <div class="topic-item">
                    <div class="topic-avatar">
                        <img src="<?php echo get_avatar_url($topic['avatar'] ?? ''); ?>" alt="User Avatar">
                    </div>
                    <div class="topic-content">
                        <div class="topic-header">
                            <h3 class="topic-title"><a href="topic.php?id=<?php echo $topic['id']; ?>"><?php echo htmlspecialchars($topic['title']); ?></a></h3>
                        </div>
                        <div class="topic-meta">
                            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($topic['username']); ?></span>
                            <span><i class="fas fa-clock"></i> <?php echo format_date($topic['created_at']); ?></span>
                            <span><i class="fas fa-comments"></i> <?php echo $topic['comment_count']; ?> comments</span>
                        </div>
                        <div class="topic-excerpt">
                            <?php echo get_excerpt($topic['content']); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php 
        // Generate pagination with search query preserved
        $pagination_url = "?q=" . urlencode($search_query) . "&page=";
        echo pagination($total_pages, $page, $pagination_url); 
        ?>
        
    <?php else: ?>
        <div class="empty-state">
            <p>No results found for "<?php echo htmlspecialchars($search_query); ?>".</p>
            <p>Try different keywords or check your spelling.</p>
            <form action="search.php" method="GET" class="search-form">
                <div class="form-group">
                    <input type="text" name="q" class="form-control" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search for topics...">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn">Search Again</button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
