<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle new post creation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $content = $_POST['content'];
    $image = null;
    
    // Handle image upload if present
    if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] == 0) {
        $target_dir = "uploads/";
        $file_extension = strtolower(pathinfo($_FILES["post_image"]["name"], PATHINFO_EXTENSION));
        $new_filename = "post_" . time() . "_" . $_SESSION['user_id'] . "." . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        $valid_types = array("jpg", "jpeg", "png", "gif");
        if (in_array($file_extension, $valid_types)) {
            if (move_uploaded_file($_FILES["post_image"]["tmp_name"], $target_file)) {
                $image = $new_filename;
            }
        }
    }
    
    // Insert new post
    $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, image) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $content, $image]);
    
    // Redirect to prevent form resubmission
    header("Location: timeline.php");
    exit();
}

// Fetch all posts with user information
$stmt = $pdo->prepare("
    SELECT posts.*, users.username, users.profile_picture 
    FROM posts 
    JOIN users ON posts.user_id = users.user_id 
    ORDER BY posts.created_at DESC
");
$stmt->execute();
$posts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Timeline</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="timeline.php" class="nav-logo">Welcome Sinazo Maseko</a>
            <div class="nav-links">
                <a href="profile.php">Profile</a>
                <a href="messages.php">Messages</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Create Post Form -->
        <div class="create-post">
            <h3>Create a Post</h3>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <textarea name="content" placeholder="What's on your mind?" required></textarea>
                </div>
                <div class="form-group">
                    <label>Add Image (optional):</label>
                    <input type="file" name="post_image" accept="image/*">
                </div>
                <button type="submit">Post</button>
            </form>
        </div>

        <!-- Posts Feed -->
        <div class="posts-feed">
            <?php foreach ($posts as $post): ?>
                <div class="post">
                    <div class="post-header">
                        <img src="uploads/<?php echo htmlspecialchars($post['profile_picture']); ?>" class="post-avatar">
                        <div class="post-info">
                            <a href="profile.php?user_id=<?php echo $post['user_id']; ?>" class="username">
                                <?php echo htmlspecialchars($post['username']); ?>
                            </a>
                            <span class="timestamp"><?php echo date('F j, Y g:i a', strtotime($post['created_at'])); ?></span>
                        </div>
                    </div>
                    <div class="post-content">
                        <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                        <?php if ($post['image']): ?>
                            <img src="uploads/<?php echo htmlspecialchars($post['image']); ?>" class="post-image">
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>