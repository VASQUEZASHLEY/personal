<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Admin Login</h1>
        <form method="post" action="server.php">
            <div class="input-container">
                <input type="text" name="admin_username" placeholder="Username" required>
            </div>
            <div class="input-container">
                <input type="password" name="admin_password" placeholder="Password" required>
            </div>
            <button type="submit" name="admin_login" class="submit-button">Login</button>
        </form>
    </div>
</body>
</html>
