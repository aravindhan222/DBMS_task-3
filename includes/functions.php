<?php
// Start session if not already started
function session_start_safe() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// Sanitize user input
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Check if user is logged in
function is_logged_in() {
    session_start_safe();
    return isset($_SESSION['user_id']);
}

// Redirect helper
function redirect($location) {
    header("Location: $location");
    exit;
}

// Display error message HTML
function display_error($message) {
    return "<div class='error-message'>$message</div>";
}

// Display success message HTML
function display_success($message) {
    return "<div class='success-message'>$message</div>";
}

// Get user data by ID
function get_user_data($user_id, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT id, username, email, avatar, bio, date_joined FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return false;
    }
}

// Get topics with optional search, pagination
function get_topics($pdo, $page = 1, $limit = 10, $search = '') {
    $offset = ($page - 1) * $limit;

    try {
        $query = "SELECT t.id, t.title, t.content, t.created_at, t.user_id, u.username, u.avatar,
                  (SELECT COUNT(*) FROM comments WHERE topic_id = t.id) AS comment_count
                  FROM topics t
                  JOIN users u ON t.user_id = u.id";

        $params = [];

        if (!empty($search)) {
            $query .= " WHERE t.title LIKE ? OR t.content LIKE ?";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $query .= " ORDER BY t.created_at DESC LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return false;
    }
}

// Count total topics (for pagination)
function count_topics($pdo, $search = '') {
    try {
        $query = "SELECT COUNT(*) as total FROM topics";
        $params = [];

        if (!empty($search)) {
            $query .= " WHERE title LIKE ? OR content LIKE ?";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch();

        return $result['total'] ?? 0;
    } catch (PDOException $e) {
        return 0;
    }
}

// Get one topic by ID
function get_topic($pdo, $topic_id) {
    try {
        $stmt = $pdo->prepare("SELECT t.*, u.username, u.avatar
                               FROM topics t
                               JOIN users u ON t.user_id = u.id
                               WHERE t.id = ?");
        $stmt->execute([$topic_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return false;
    }
}

// Get comments by topic and parent_id (for threading)
function get_comments($pdo, $topic_id, $parent_id = 0) {
    try {
        $stmt = $pdo->prepare("SELECT c.*, u.username, u.avatar
                               FROM comments c
                               JOIN users u ON c.user_id = u.id
                               WHERE c.topic_id = ? AND c.parent_id = ?
                               ORDER BY c.created_at ASC");
        $stmt->execute([$topic_id, $parent_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

// Format a datetime string nicely
function format_date($date) {
    return date('F j, Y \a\t g:i a', strtotime($date));
}

// Get a shortened excerpt from content
function get_excerpt($content, $length = 150) {
    return strlen($content) <= $length
        ? htmlspecialchars($content)
        : htmlspecialchars(substr($content, 0, $length)) . '...';
}

// Get avatar URL or random placeholder if empty
function get_avatar_url($avatar) {
    $default_avatars = [
        "https://pixabay.com/get/g72625ae205a8c231fe8f03053d4a18cd2a3b1c1eeeb16871039124e17770ed97db69f5b03f6d30f672b89be6d9e54dbd5631ea9f314c23df50527330e60fd73f_1280.jpg",
        "https://pixabay.com/get/gd26d9bc36bcb2b13c2dce79d687a97e97e3094eddcf166ea918271034c572a600fb757a14b86c2a2deb50915cbc209f8cdfaaa2b07f274b302d09d20c6b9c309_1280.jpg",
        "https://pixabay.com/get/g61ddd1d5adecff3fc0d6b697c3ad005005d0cd397d95c46d9d4f9f019b88dde1aead1cf4be96bb3a869e43ee9b0ef56bb9f11bd8229d72f5d4d05044f4c3e451_1280.jpg",
        "https://pixabay.com/get/g3d40ed6de0c8da96299d089bffdf0cebf834da3dd33bb9993ee7a70655cd880d82c673c523a5ff831c572d0247243a7317ca6e1b32b5d4ac0e97f5d9d670d23f_1280.jpg"
    ];

    return empty($avatar) ? $default_avatars[array_rand($default_avatars)] : htmlspecialchars($avatar);
}

// Generate pagination links HTML
function pagination($total_pages, $current_page, $url = '?page=') {
    if ($total_pages <= 1) return '';

    $html = '<div class="pagination">';

    if ($current_page > 1) {
        $html .= '<a href="' . $url . ($current_page - 1) . '" class="pagination-prev">&laquo; Previous</a>';
    }

    for ($i = 1; $i <= $total_pages; $i++) {
        if ($i == $current_page) {
            $html .= '<span class="pagination-current">' . $i . '</span>';
        } else {
            $html .= '<a href="' . $url . $i . '">' . $i . '</a>';
        }
    }

    if ($current_page < $total_pages) {
        $html .= '<a href="' . $url . ($current_page + 1) . '" class="pagination-next">Next &raquo;</a>';
    }

    $html .= '</div>';
    return $html;
}
?>
