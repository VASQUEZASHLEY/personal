<?php
session_start();
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: admin-login.php');
    exit;
}
require 'connect.php';
require 'server.php';

try {
    $users = getAllUsers($conn);
    if ($users === false) {
        throw new Exception("Failed to retrieve users");
    }
    $totalUsers = count($users);
    $bannedUsers = array_filter($users, function($user) { return $user['is_banned']; });
    $totalBanned = count($bannedUsers);
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Display admin message if exists
if (isset($_SESSION['admin_message'])) {
    echo "<script>alert('" . $_SESSION['admin_message'] . "');</script>";
    unset($_SESSION['admin_message']);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <h2>Admin Panel</h2>
            <div class="admin-stats">
                <div class="stat-box">
                    <h3>Total Users</h3>
                    <p><?php echo $totalUsers; ?></p>
                </div>
                <div class="stat-box">
                    <h3>Banned Users</h3>
                    <p><?php echo $totalBanned; ?></p>
                </div>
            </div>
            <div class="admin-search">
                <input type="text" id="userSearch" placeholder="Search users...">
            </div>
            <a href="server.php?logout=1" class="logout-btn">Logout</a>
        </div>

        <div class="admin-main">
            <h1>User Management</h1>
            <div class="table-container">
                <table class="user-table" id="userTable">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo $user['fullName']; ?></td>
                        <td><?php echo $user['email']; ?></td>
                        <td>
                            <span class="status-badge <?php echo $user['is_banned'] ? 'banned' : 'active'; ?>">
                                <?php echo $user['is_banned'] ? 'Banned' : 'Active'; ?>
                            </span>
                        </td>
                        <td class="action-buttons">
                            <form method="post" action="server.php">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="admin_action" value="<?php echo $user['is_banned'] ? 'unban' : 'ban'; ?>" 
                                        class="<?php echo $user['is_banned'] ? 'unban-btn' : 'ban-btn'; ?>">
                                    <?php echo $user['is_banned'] ? 'Unban' : 'Ban'; ?>
                                </button>
                                <button type="submit" name="admin_action" value="delete" class="delete-btn" 
                                        onclick="return confirm('Are you sure you want to delete this user?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('userSearch').addEventListener('keyup', function() {
            let search = this.value.toLowerCase();
            let rows = document.getElementById('userTable').getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                let name = rows[i].getElementsByTagName('td')[1].textContent.toLowerCase();
                let email = rows[i].getElementsByTagName('td')[2].textContent.toLowerCase();
                rows[i].style.display = 
                    name.includes(search) || email.includes(search) ? '' : 'none';
            }
        });

        function viewUserDetails(userId) {
            window.location.href = `user-details.php?id=${userId}`;
        }
    </script>
</body>
</html>
