<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$upload_success = $upload_error = '';


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_picture'])) {
    $file = $_FILES['profile_picture'];
    
  
    $upload_dir = "uploads/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

   
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; 
    
    if ($file['error'] === 0) {
        
        $file_type = mime_content_type($file['tmp_name']);
        if (!in_array($file_type, $allowed_types)) {
            $upload_error = "Only JPG, PNG & GIF files are allowed.";
        }
        
        else if ($file['size'] > $max_size) {
            $upload_error = "File size must be less than 5MB.";
        }
        else {
            
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
           
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                try {
                    
                    $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $old_picture = $stmt->fetchColumn();
                    
                    
                    $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
                    $stmt->execute([$new_filename, $user_id]);
                    
                    
                    if ($old_picture && $old_picture != 'default.jpg' && file_exists($upload_dir . $old_picture)) {
                        unlink($upload_dir . $old_picture);
                    }
                    
                    $upload_success = "Profile picture updated successfully!";
                } catch (PDOException $e) {
                    $upload_error = "Database error occurred. Please try again.";
                    
                    if (file_exists($upload_path)) {
                        unlink($upload_path);
                    }
                }
            } else {
                $upload_error = "Failed to upload file. Please try again.";
            }
        }
    } else {
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $upload_error = "File size is too large.";
                break;
            case UPLOAD_ERR_PARTIAL:
                $upload_error = "File was only partially uploaded.";
                break;
            case UPLOAD_ERR_NO_FILE:
                $upload_error = "No file was uploaded.";
                break;
            default:
                $upload_error = "Unknown error occurred.";
        }
    }
}


$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();


$stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$posts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Profile</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .upload-container {
            margin: 20px 0;
            text-align: center;
        }
        .preview-container {
            margin: 10px 0;
            display: none;
        }
        #imagePreview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 50%;
        }
        .alert {
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="profile-header card">
            <img src="uploads/<?php echo htmlspecialchars($user['profile_picture'] ?: 'default.jpg'); ?>" 
                 class="profile-picture" 
                 alt="Profile Picture">
            <h2><?php echo htmlspecialchars($user['username']); ?></h2>
            
            <!-- Upload Form -->
            <div class="upload-container">
                <form method="POST" action="" enctype="multipart/form-data" id="uploadForm">
                    <div class="form-group">
                        <label for="profile_picture">Update Profile Picture:</label>
                        <input type="file" 
                               name="profile_picture" 
                               id="profile_picture" 
                               accept="image/*" 
                               required>
                    </div>
                    
                 
                    <div class="preview-container" id="previewContainer">
                        <img id="imagePreview" src="#" alt="Preview">
                    </div>
                    
                    <button type="submit">Upload</button>
                </form>
                
                <?php if ($upload_success): ?>
                    <div class="alert alert-success"><?php echo $upload_success; ?></div>
                <?php endif; ?>
                
                <?php if ($upload_error): ?>
                    <div class="alert alert-error"><?php echo $upload_error; ?></div>
                <?php endif; ?>
            </div>
        </div>

        
        <div class="posts-section">
            <h3>Your Posts</h3>
            <?php foreach ($posts as $post): ?>
                <div class="post card">
                    <p><?php echo htmlspecialchars($post['content']); ?></p>
                    <?php if ($post['image']): ?>
                        <img src="uploads/<?php echo htmlspecialchars($post['image']); ?>" 
                             class="post-image" 
                             alt="Post Image">
                    <?php endif; ?>
                    <span class="timestamp"><?php echo date('F j, Y g:i a', strtotime($post['created_at'])); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        
        document.getElementById('profile_picture').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                const previewContainer = document.getElementById('previewContainer');
                const imagePreview = document.getElementById('imagePreview');

                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    previewContainer.style.display = 'block';
                }

                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>