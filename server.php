<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'connect.php';

define('ADMIN_USERNAME', 'ashley01');
define('ADMIN_PASSWORD', 'admin01');

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

function setError($message) {
    $_SESSION['error'] = $message;
}

function setSuccess($message) {
    $_SESSION['success'] = $message;
}

function checkValidRoute() {
    // Allow admin routes without user session
    $allowedPaths = ['index.php', 'register.php', 'admin-login.php', 'admin-dashboard.php'];
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
        setError('Full name can only contain letters and spaces');
        header("Location: register.php");
        exit;
    }

    // Validate email domain
    if (!preg_match("/^[a-zA-Z0-9._%+-]+@((gmail\.com|yahoo\.com|outlook\.com|hotmail\.com)|(.*\.edu(\.[a-z]{2})?))$/", $email)) {
        setError('Invalid email domain. Please use an educational (.edu) or common email provider');
        header("Location: register.php");
        exit;
    }

    // Validate password length first
    if (strlen($password) < 8) {
        setError('Password must be at least 8 characters long');
        header("Location: register.php");
        exit;
    }

    // Check if passwords match using strict comparison
    if ($password !== $confirmPassword) {
        setError('Passwords do not match. Please try again.');
        header("Location: register.php");
        exit;
    }

    // Check if email already exists
    $checkEmail = $conn->query("SELECT * FROM users WHERE email = '$email'");

    if ($checkEmail->num_rows > 0) {
        setError('This email address is already registered');
        header("Location: register.php");
        exit;
    } else {
        // Hash the password before storing it
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert user into the database
        $stmt = $conn->prepare("INSERT INTO users (fullName, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $fullName, $email, $hashedPassword);

        if ($stmt->execute()) {
            setSuccess('Registration complete! Please log in to continue.');
            header("Location: register.php");
        } else {
            setError('Registration failed. Please try again.');
            header("Location: register.php");
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
            setError('Access Denied: Your account has been suspended. Please contact the administrator.');
            header("Location: index.php");
            exit;
        }

        // Verify the hashed password
        if (!password_verify($password, $user['password'])) {
            setError('Invalid password. Please try again.');
            header("Location: index.php"); // Ensure this redirects to index.php
            exit;
        }

        // If password is correct, proceed with login
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['fullName'] = $user['fullName'];
        $_SESSION['is_admin'] = $user['is_admin'] ?? false;
        header("Location: dashboard.php");
        exit;
    } else {
        setError('Email address not found. Please check and try again.');
        $stmt->close();
        header("Location: index.php"); // Ensure this redirects to index.php
        exit;
    }
}

if (isset($_POST['admin_login'])) {
    $username = $_POST['admin_username'];
    $password = $_POST['admin_password'];

    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        $_SESSION['admin'] = true;
        header('Location: admin-dashboard.php');
        exit;
    } else {
        setError('Invalid administrator credentials');
        header("Location: admin-login.php");
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin-login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add-expense'])) {
    // Check if the user is logged in - only for expense operations
    if (!isset($_SESSION['user_id'])) {
        setError('You must be logged in to add expenses!');
        header("Location: index.php");
        exit;
    }
    
    $userId = $_SESSION['user_id']; // Get the logged-in user's ID
    $category = $_POST['category-option'];
    $amount = $_POST['amount'];
    $date = $_POST['input-date'];

    // Validate inputs
    if (empty($category) || empty($amount) || empty($date) || $amount <= 0) {
        setError('Please provide valid category, amount, and date for the expense.');
        header("Location: dashboard.php");
        exit;
    }

    // Insert expense into the database
    $stmt = $conn->prepare("INSERT INTO expenses (user_id, category, amount, date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isds", $userId, $category, $amount, $date);

    if ($stmt->execute()) {
        setSuccess('Expense recorded successfully!');
        header("Location: dashboard.php");
        exit;
    } else {
        setError('Could not save expense. Please try again.');
        header("Location: dashboard.php");
        exit;
    }
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
        $stmt = $conn->prepare("SELECT id, fullName, email, is_banned, is_admin FROM users");
        if (!$stmt) {
            error_log("Database error in getAllUsers: " . $conn->error);
            return [];
        }
        
        if (!$stmt->execute()) {
            error_log("Execute error in getAllUsers: " . $stmt->error);
            return [];
        }
        
        $result = $stmt->get_result();
        if (!$result) {
            error_log("Result error in getAllUsers: " . $stmt->error);
            return [];
        }
        
        $users = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        error_log("Users found: " . count($users)); // Debug log
        return $users;
    } catch (Exception $e) {
        error_log("Exception in getAllUsers: " . $e->getMessage());
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

if (isset($_SESSION['admin_message'])) {
    echo "<script>
            alert('" . addslashes($_SESSION['admin_message']) . "');
          </script>";
    unset($_SESSION['admin_message']);
}

?>
