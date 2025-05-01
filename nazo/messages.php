<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message']) && isset($_POST['receiver_id'])) {
    $message = trim($_POST['message']);
    $receiver_id = $_POST['receiver_id'];
    
    if (!empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message_text) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $receiver_id, $message]);
    }
}

$stmt = $pdo->prepare("
    SELECT DISTINCT u.user_id, u.username, u.profile_picture
    FROM users u
    LEFT JOIN messages m ON (m.sender_id = u.user_id OR m.receiver_id = u.user_id)
    WHERE u.user_id != ?
    ORDER BY u.username
");
$stmt->execute([$user_id]);
$users = $stmt->fetchAll();

$selected_user = null;
$messages = array();
if (isset($_GET['user_id'])) {
    $chat_user_id = $_GET['user_id'];
    
    $stmt = $pdo->prepare("SELECT user_id, username, profile_picture FROM users WHERE user_id = ?");
    $stmt->execute([$chat_user_id]);
    $selected_user = $stmt->fetch();
    
    if ($selected_user) {
        $stmt = $pdo->prepare("
            SELECT m.*, u.username, u.profile_picture
            FROM messages m
            JOIN users u ON m.sender_id = u.user_id
            WHERE (m.sender_id = ? AND m.receiver_id = ?)
            OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$user_id, $chat_user_id, $chat_user_id, $user_id]);
        $messages = $stmt->fetchAll();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Messages</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .messages-container {
            display: flex;
            height: calc(100vh - 100px);
        }
        
        .users-sidebar {
            width: 300px;
            border-right: 1px solid #ddd;
            overflow-y: auto;
        }
        
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .messages-list {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }
        
        .message-form {
            padding: 20px;
            border-top: 1px solid #ddd;
        }
        
        .user-item {
            padding: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        
        .user-item:hover {
            background-color: #f5f5f5;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="timeline.php" class="nav-logo">Social Media</a>
            <div class="nav-links">
                <a href="profile.php">Profile</a>
                <a href="messages.php">Messages</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="messages-container">
           
            <div class="users-sidebar">
                <div class="search-box">
                    <input type="text" id="userSearch" placeholder="Search users..." onkeyup="searchUsers()">
                </div>
                <div class="users-list">
                    <?php foreach ($users as $user): ?>
                        <a href="?user_id=<?php echo $user['user_id']; ?>" class="user-item">
                            <img src="uploads/<?php echo htmlspecialchars($user['profile_picture']); ?>" class="user-avatar">
                            <span><?php echo htmlspecialchars($user['username']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            
            <div class="chat-area">
                <?php if ($selected_user): ?>
                    <div class="chat-header">
                        <img src="uploads/<?php echo htmlspecialchars($selected_user['profile_picture']); ?>" class="user-avatar">
                        <h3><?php echo htmlspecialchars($selected_user['username']); ?></h3>
                    </div>
                    
                    <div class="messages-list">
                        <?php foreach ($messages as $message): ?>
                            <div class="message <?php echo $message['sender_id'] == $user_id ? 'sent' : 'received'; ?>">
                                <p><?php echo nl2br(htmlspecialchars($message['message_text'])); ?></p>
                                <span class="timestamp">
                                    <?php echo date('g:i a', strtotime($message['created_at'])); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <form method="POST" action="" class="message-form">
                        <input type="hidden" name="receiver_id" value="<?php echo $selected_user['user_id']; ?>">
                        <div class="form-group">
                            <textarea name="message" placeholder="Type your message..." required></textarea>
                        </div>
                        <button type="submit">Send</button>
                    </form>
                <?php else: ?>
                    <div class="no-chat-selected">
                        <p>Select a user to start chatting</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    function searchUsers() {
        var input = document.getElementById("userSearch");
        var filter = input.value.toLowerCase();
        var users = document.getElementsByClassName("user-item");

        for (var i = 0; i < users.length; i++) {
            var username = users[i].getElementsByTagName("span")[0];
            var txtValue = username.textContent || username.innerText;
            if (txtValue.toLowerCase().indexOf(filter) > -1) {
                users[i].style.display = "";
            } else {
                users[i].style.display = "none";
            }
        }
    }

   
    function scrollToBottom() {
        var messagesList = document.querySelector('.messages-list');
        messagesList.scrollTop = messagesList.scrollHeight;
    }

    
    window.onload = function() {
        scrollToBottom();
    }
    </script>
</body>
</html>