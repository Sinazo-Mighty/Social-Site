<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$search_results = [];
$search_query = '';

if (isset($_GET['query'])) {
    $search_query = trim($_GET['query']);
    
    if (!empty($search_query)) {
        $stmt = $pdo->prepare("
            SELECT user_id, username, profile_picture
            FROM users 
            WHERE username LIKE ? 
            AND user_id != ?
            LIMIT 20
        ");
        $stmt->execute(["%$search_query%", $_SESSION['user_id']]);
        $search_results = $stmt->fetchAll();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Search Users</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .search-container {
            max-width: 600px;
            margin: 20px auto;
        }

        .search-box {
            position: relative;
            margin-bottom: 20px;
        }

        .search-input {
            width: 100%;
            padding: 15px 20px;
            font-size: 16px;
            border: 2px solid var(--border-color);
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .live-search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: none;
        }

        .user-card {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            transition: background-color 0.2s;
            text-decoration: none;
            color: var(--text-primary);
        }

        .user-card:last-child {
            border-bottom: none;
        }

        .user-card:hover {
            background-color: rgba(79, 70, 229, 0.05);
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
        }

        .user-info {
            flex: 1;
        }

        .username {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 4px;
        }

        .no-results {
            padding: 20px;
            text-align: center;
            color: var(--text-secondary);
        }

        .search-spinner {
            display: none;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
        }

        .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid var(--border-color);
            border-top-color: var(--primary-color);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .search-highlight {
            background-color: rgba(79, 70, 229, 0.1);
            padding: 0 2px;
            border-radius: 2px;
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
                <a href="search.php" class="active">Search</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="search-container">
            <div class="search-box">
                <input type="text" 
                       id="searchInput" 
                       class="search-input" 
                       placeholder="Search users..." 
                       value="<?php echo htmlspecialchars($search_query); ?>"
                       autocomplete="off">
                <div class="search-spinner">
                    <div class="spinner"></div>
                </div>
                <div class="live-search-results" id="searchResults"></div>
            </div>

            <div class="search-results">
                <?php if (!empty($search_query) && empty($search_results)): ?>
                    <div class="no-results">No users found matching "<?php echo htmlspecialchars($search_query); ?>"</div>
                <?php endif; ?>

                <?php foreach ($search_results as $user): ?>
                    <a href="profile.php?user_id=<?php echo $user['user_id']; ?>" class="user-card">
                        <img src="uploads/<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                             class="user-avatar" 
                             alt="<?php echo htmlspecialchars($user['username']); ?>">
                        <div class="user-info">
                            <div class="username"><?php echo htmlspecialchars($user['username']); ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        const searchInput = document.getElementById('searchInput');
        const searchResults = document.getElementById('searchResults');
        const spinner = document.querySelector('.search-spinner');
        let searchTimeout;

        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            // Clear previous timeout
            clearTimeout(searchTimeout);
            
            if (query.length < 2) {
                searchResults.style.display = 'none';
                return;
            }

            // Show spinner
            spinner.style.display = 'block';
            
            // Set new timeout for search
            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, 300);
        });

        async function performSearch(query) {
            try {
                const response = await fetch(`search_ajax.php?query=${encodeURIComponent(query)}`);
                const data = await response.json();
                
                // Hide spinner
                spinner.style.display = 'none';

                if (data.length === 0) {
                    searchResults.innerHTML = '<div class="no-results">No users found</div>';
                } else {
                    searchResults.innerHTML = data.map(user => `
                        <a href="profile.php?user_id=${user.user_id}" class="user-card">
                            <img src="uploads/${user.profile_picture}" class="user-avatar" alt="${user.username}">
                            <div class="user-info">
                                <div class="username">${highlightMatch(user.username, query)}</div>
                            </div>
                        </a>
                    `).join('');
                }
                
                searchResults.style.display = 'block';
            } catch (error) {
                console.error('Search error:', error);
                spinner.style.display = 'none';
                searchResults.innerHTML = '<div class="no-results">An error occurred while searching</div>';
                searchResults.style.display = 'block';
            }
        }

        function highlightMatch(text, query) {
            const regex = new RegExp(`(${query})`, 'gi');
            return text.replace(regex, '<span class="search-highlight">$1</span>');
        }

        // Hide search results when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchResults.contains(e.target) && e.target !== searchInput) {
                searchResults.style.display = 'none';
            }
        });

        // Show results again when focusing on input
        searchInput.addEventListener('focus', function() {
            if (this.value.trim().length >= 2) {
                searchResults.style.display = 'block';
            }
        });
    </script>
</body>
</html>