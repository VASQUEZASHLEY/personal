<?php
session_start();
?>
<!DOCTYPE html>

<html>

<head>

    <title>Personal Budget Tracker!</title>
    <link rel="stylesheet" href="styles.css">

</head>

<body>

    <div class="login-container">
        <h1>Personal Budget Tracker!</h1>
        <hr />

        <div class="container">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="login-form">
                <h2>Login</h2>

                <form method="post" action="server.php">
                <div class="input-container">
                    <input type="email" class="input-field" placeholder="Email" name="email" required>
                </div>

                <div class="input-container">
                    <input type="password" class="input-field" placeholder="Password" name="password" required>
                </div>

                <div class="submit">
                    <button class="submit-button" name="login">Login</button>
                </div>

            </div>
        

                <div class="register">
                    <p>Don't have an account? <a href="register.php">Register</a></p>
                </div>

                <div class="admin-login">
                    <a href="admin-login.php" class="admin-btn">Log-in as Admin</a>
                </div>

                </form>
            </div>
        </div>

</body>

</html>
