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

          <div class="forgot-password">
            <a href="forgot.html">Forgot Password?</a>
          </div>
        </div>
        
          <hr />

          <div class="register">
            <p>Don't have an account? <a href="register.html">Register</a></p>
          </div>

          <div class="admin-login">
            <a href="admin-login.php" class="admin-btn">Log-in as Admin</a>
          </div>

        </form>
      </div>

</body>

</html>
