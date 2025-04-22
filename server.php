<?php

session_start();
include 'connect.php';

if (isset($_POST['register'])) {
    $fullName = $_POST['fname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];

    if ($password != $confirmPassword) {
        echo "Password does not match!";
        exit;
    }

    $checkEmail = $conn->query("SELECT * FROM users WHERE email = '$email'");

    if ($checkEmail->num_rows > 0) {
        echo "Email Address Already exists!";
        exit;
    } else {
        $conn->query("INSERT INTO users (fullName, email, password) VALUES ('$fullName', '$email', '$password')");
    }
    
    header("Location: index.html");
    exit;
}


if (isset($_POST['login'])) {

    $email = $_POST['email'];
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE email = '$email'");

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if ($password == $user['password']) {
            // Store user_id in the session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fullName'] = $user['fullName']; // Optional: Store full name for personalized greetings
            header("Location: dashboard.php");
            exit;
        } else {
            echo "Invalid Password!";
        }
    } else {
        echo "Email Address not found!";
    }


}


if (isset($_POST["reset"])) {
    $email = $_POST["email"];

    $result = $conn->query("SELECT * FROM users WHERE email = '$email'");

    if ($result->num_rows > 0) {
        // Send reset password link to the email
        echo "Reset password link sent to your email!";
    } else {
        echo "Email Address not found!";
    }
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "You must be logged in to add expenses!";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = $_SESSION['user_id']; // Get the logged-in user's ID
    $category = $_POST['category-option'];
    $amount = $_POST['amount'];
    $date = $_POST['input-date'];

    // Validate inputs
    if (empty($category) || empty($amount) || empty($date) || $amount <= 0) {
        echo "Invalid input. Please provide valid category, amount, and date.";
        exit;
    }

    // Insert expense into the database
    $stmt = $conn->prepare("INSERT INTO expenses (user_id, category, amount, date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isds", $userId, $category, $amount, $date);

    if ($stmt->execute()) {
        echo "Expense added successfully!";
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

?>
 