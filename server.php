<?php

session_start();
include 'connect.php';

require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

define('ADMIN_USERNAME', 'ashley01');
define('ADMIN_PASSWORD', 'admin01');

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

function checkValidRoute() {
    // Allow admin routes without user session
    $allowedPaths = ['index.php', 'register.html', 'forgot.html', 'admin-login.php', 'admin-dashboard.php'];
    $currentPath = basename($_SERVER['PHP_SELF']);
    
    // Skip check for admin pages if admin is logged in
    if (isset($_SESSION['admin']) && $_SESSION['admin'] === true) {
        return;
    }
    
    // Check regular user routes
    if (!isset($_SESSION['user_id']) && !in_array($currentPath, $allowedPaths)) {
        header("Location: index.php");
        exit;
    }
}

if (isset($_POST['register'])) {
    $fullName = $_POST['fname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];

    // Validate full name (only alphabetical characters and spaces)
    if (!preg_match("/^[a-zA-Z ]*$/", $fullName)) {
        echo "<script>
                alert('Full name should contain only alphabetical characters!');
                window.location.href = 'register.html';
              </script>";
        exit;
    }

    // Validate email domain
    if (!preg_match("/^[a-zA-Z0-9._%+-]+@(gmail\.com|yahoo\.com)$/", $email)) {
        echo "<script>
                alert('Only @google.com and @yahoo.com email addresses are allowed!');
                window.location.href = 'register.html';
              </script>";
        exit;
    }

    // Check if passwords match
    if ($password != $confirmPassword) {
        echo "<script>
                alert('Password does not match!');
                window.location.href = 'register.html';
              </script>";
        exit;
    }

    // Check if email already exists
    $checkEmail = $conn->query("SELECT * FROM users WHERE email = '$email'");

    if ($checkEmail->num_rows > 0) {
        echo "<script>
                alert('Email Address Already exists!');
                window.location.href = 'register.html';
              </script>";
        exit;
    } else {
        // Hash the password before storing it
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert user into the database
        $stmt = $conn->prepare("INSERT INTO users (fullName, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $fullName, $email, $hashedPassword);

        if ($stmt->execute()) {
            echo "<script>
                    alert('Registration successful! Please log in.');
                    window.location.href = 'index.php';
                  </script>";
        } else {
            echo "<script>
                    alert('Error: Could not register user.');
                    window.location.href = 'register.html';
                  </script>";
        }

        $stmt->close();
    }
}


if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check if the user exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if ($user['is_banned'] == 1) {
            echo "<script>
                    alert('Your account has been banned. Please contact administrator.');
                    window.location.href = 'index.php';
                  </script>";
            exit;
        }

        // Verify the hashed password
        if (password_verify($password, $user['password'])) {
            // Store user_id in the session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fullName'] = $user['fullName']; // Optional: Store full name for personalized greetings
            $_SESSION['is_admin'] = $user['is_admin'] ?? false;
            header("Location: dashboard.php");
            exit;
        } else {
            echo "<script>
                    alert('Invalid Password!');
                    window.location.href = 'index.php';
                  </script>";
        }
    } else {
        echo "<script>
                alert('Email Address not found!');
                window.location.href = 'index.php';
              </script>";
    }

    $stmt->close();
}

if (isset($_POST['admin_login'])) {
    $username = $_POST['admin_username'];
    $password = $_POST['admin_password'];

    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        $_SESSION['admin'] = true;
        header('Location: admin-dashboard.php');
        exit;
    } else {
        echo "<script>
                alert('Invalid admin credentials!');
                window.location.href = 'admin-login.php';
              </script>";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin-login.php');
    exit;
}

function calculateSimilarity($str1, $str2) {
    $leven = levenshtein(strtolower($str1), strtolower($str2));
    $maxLen = max(strlen($str1), strlen($str2));
    return (1 - ($leven / $maxLen)) * 100;
}

if (isset($_POST["reset"])) {
    $email = $_POST["email"];
    $attempt = $_POST["attempt"]; // User's attempt at full name
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Check similarity with fullname only
        $nameSimility = calculateSimilarity($attempt, $user['fullName']);
        
        if ($nameSimility >= 90) {
            $_SESSION['reset_email'] = $email;
            header("Location: reset-password.php");
            exit;
        } else {
            echo "<script>
                    alert('Verification failed. Please enter your correct full name.');
                    window.location.href = 'forgot.html';
                  </script>";
        }
    } else {
        echo "<script>
                alert('Email address not found!');
                window.location.href = 'forgot.html';
              </script>";
    }
}

if (isset($_POST['new_password'])) {
    if (!isset($_SESSION['reset_email'])) {
        header("Location: forgot.html");
        exit;
    }

    $password = $_POST['password'];
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $hashedPassword, $_SESSION['reset_email']);
    
    if ($stmt->execute()) {
        unset($_SESSION['reset_email']);
        echo "<script>
                alert('Password updated successfully!');
                window.location.href = 'index.php';
              </script>";
    } else {
        echo "<script>
                alert('Password update failed. Please try again.');
                window.location.href = 'forgot.html';
              </script>";
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add-expense'])) {
    // Check if the user is logged in - only for expense operations
    if (!isset($_SESSION['user_id'])) {
        echo "<script>
                alert('You must be logged in to add expenses!');
                window.location.href = 'index.php';
              </script>";
        exit;
    }
    
    $userId = $_SESSION['user_id']; // Get the logged-in user's ID
    $category = $_POST['category-option'];
    $amount = $_POST['amount'];
    $date = $_POST['input-date'];

    // Validate inputs
    if (empty($category) || empty($amount) || empty($date) || $amount <= 0) {

        echo "<script>
            alert('Invalid input. Please provide valid category, amount, and date.');
            window.location.href = 'dashboard.php';
          </script>";
    exit;
    }

    // Insert expense into the database
    $stmt = $conn->prepare("INSERT INTO expenses (user_id, category, amount, date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isds", $userId, $category, $amount, $date);

    if ($stmt->execute()) {
        echo "<script>
            alert('Expense added successfully!');
            window.location.href = 'dashboard.php';
          </script>";
    exit;
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

// Function to fetch expenses for the logged-in user
function getExpenses($conn, $userId) {
    $stmt = $conn->prepare("SELECT category, amount, date FROM expenses WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $expenses = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $expenses;
}

// Function to calculate the total expenses for the logged-in user
function getTotalExpenses($conn, $userId) {
    $stmt = $conn->prepare("SELECT SUM(amount) AS total FROM expenses WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['total'] ?? 0; // Return 0 if no expenses are found
}

function getUserTotalExpenses($conn, $userId) {
    $stmt = $conn->prepare("SELECT SUM(amount) as total FROM expenses WHERE user_id = ?");
    if (!$stmt) {
        return 0;
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

function getLastActive($conn, $userId) {
    $stmt = $conn->prepare("SELECT MAX(date) as last_active FROM expenses WHERE user_id = ?");
    if (!$stmt) {
        return 'Never';
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['last_active'] ? date('Y-m-d', strtotime($row['last_active'])) : 'Never';
}

function banUser($conn, $userId) {
    if (!isAdmin()) {
        return false;
    }
    $stmt = $conn->prepare("UPDATE users SET is_banned = 1 WHERE id = ?");
    $stmt->bind_param("i", $userId);
    return $stmt->execute();
}

function unbanUser($conn, $userId) {
    if (!isAdmin()) {
        return false;
    }
    $stmt = $conn->prepare("UPDATE users SET is_banned = 0 WHERE id = ?");
    $stmt->bind_param("i", $userId);
    return $stmt->execute();
}

function deleteUser($conn, $userId) {
    if (!isAdmin()) {
        return false;
    }
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    return $stmt->execute();
}

function getAllUsers($conn) {
    try {
        $stmt = $conn->prepare("SELECT id, fullName, email, is_banned FROM users WHERE 1");
        if (!$stmt) {
            error_log("Error preparing statement: " . $conn->error);
            return [];
        }
        if (!$stmt->execute()) {
            error_log("Error executing statement: " . $stmt->error);
            return [];
        }
        $result = $stmt->get_result();
        $users = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $users;
    } catch (Exception $e) {
        error_log("Error in getAllUsers: " . $e->getMessage());
        return [];
    }
}

if (isset($_POST['admin_action'])) {
    $userId = $_POST['user_id'] ?? null;
    $action = $_POST['admin_action'];

    if ($userId) {
        try {
            switch ($action) {
                case 'ban':
                    $stmt = $conn->prepare("UPDATE users SET is_banned = 1 WHERE id = ?");
                    $message = "User banned successfully";
                    break;
                case 'unban':
                    $stmt = $conn->prepare("UPDATE users SET is_banned = 0 WHERE id = ?");
                    $message = "User unbanned successfully";
                    break;
                case 'delete':
                    // First delete user's expenses
                    $stmt = $conn->prepare("DELETE FROM expenses WHERE user_id = ?");
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    
                    // Then delete the user
                    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                    $message = "User deleted successfully";
                    break;
            }
            
            if (isset($stmt)) {
                $stmt->bind_param("i", $userId);
                if ($stmt->execute()) {
                    $_SESSION['admin_message'] = $message;
                } else {
                    $_SESSION['admin_message'] = "Error: Operation failed";
                }
                $stmt->close();
            }
        } catch (Exception $e) {
            $_SESSION['admin_message'] = "Error: " . $e->getMessage();
        }
    }
    header("Location: admin-dashboard.php");
    exit;
}

// Move the route check to the end and modify it
if (!isset($_SESSION['admin']) && basename($_SERVER['PHP_SELF']) === 'admin-dashboard.php') {
    header("Location: admin-login.php");
    exit;
}

// Move the route check to only apply for non-admin pages
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    checkValidRoute();
}

?>
