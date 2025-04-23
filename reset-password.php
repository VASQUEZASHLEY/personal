<?php
session_start();
if (!isset($_SESSION['reset_email'])) {
    header('Location: forgot.html');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <link rel="stylesheet" href="forgot.css">
</head>
<body>
    <div class="forgot-container">
        <h2>Enter New Password</h2>
        <form method="post" action="server.php">
            <div class="input-container">
                <input type="password" class="input-field" name="password" placeholder="New Password" required>
            </div>
            <div class="submit">
                <button type="submit" name="new_password" class="submit-button">Update Password</button>
            </div>
        </form>
    </div>
</body>
</html>
