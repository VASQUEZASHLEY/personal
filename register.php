<!DOCTYPE html>

<html>

<head>
    <title>Register</title>
    <link rel="stylesheet" href="register.css">

</head>

<body>
    
    <div class="register-container">
        <h1>Your Personal Budget Tracker!</h1>
        <hr />
        <h2>Register</h2>

        <?php
        session_start();
        ?>

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
        </div>

        <form method="post" action="server.php">

            <div class="input-container">
                <input type="text" class="input-field" placeholder="Full Name" name="fname" required>
            </div>

            <div class="input-container">
                <input type="email" class="input-field" placeholder="Email" name="email" required>
            </div>

            <div class="input-container">
                <input type="password" class="input-field" placeholder="Password" name="password" required>
            </div>

            <div class="input-container">
                <input type="password" class="input-field" placeholder="Confirm Password" name="confirmPassword" required>
            </div>

            <div class="submit">
                <button class="submit-button" name="register">Register</button>
            </div>

            <div class="login-link">
                <p>You have already an account? <a href="index.php">Login</a></p>
            </div>
        
        </form>

    </div>

</body>

</html>
