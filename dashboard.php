<!-- filepath: d:\xampp\htdocs\personal\dashboard.html -->
<?php
include 'connect.php';
include 'server.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit;
}

$userId = $_SESSION['user_id'];
$expenses = getExpenses($conn, $userId);
$totalExpenses = getTotalExpenses($conn, $userId); // Fetch the total expenses

// Fetch total expenses for each category
$stmt = $conn->prepare("SELECT category, SUM(amount) AS total FROM expenses WHERE user_id = ? GROUP BY category");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$categories = [];
$totals = [];

while ($row = $result->fetch_assoc()) {
    $categories[] = $row['category'];
    $totals[] = $row['total'];
}

?>

<!DOCTYPE html>

<html>

    <head>

        <title>Dashboard</title>
        <link rel="stylesheet" href="dashboard.css">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Include Chart.js -->


    </head>

    <body>

        <div class="dashboard-container">

            <div class="dashboard-left">

                <h1>Personal Budget Tracker</h1>
                <hr />

                <div class="circle-parent">
                    <div class="circle">
                        <img src="images/user.png" alt="User Image" class="user-image">
                    </div>
                </div>

                <h1>Welcome, <?php echo htmlspecialchars($_SESSION['fullName']); ?>!</h1>

                <div class="dashboard-parent">
                    <div class="dashboard-interface">
                    
                        <h2 id = "dashboard">Dashboard</h2>
                        <h2 id = "history">History</h2>
                        <h2 id = "settings">Settings</h2>
                        <h2><a href="logout.php">Logout</a></h2>
                    </div>
                </div>

            </div>

            <div class="dashboard-right">

                <div class="dashboard-content" id="dashboard-content">
                    <h2>Expense Tracker</h2>
                    <hr />

                    <div class="category-section">

                        <div class = input-section>
                            
                            <form method="post" action="server.php">
                                <label for="category">Select Category:</label>
                            
                                    <select name="category-option" class = "input" required>
                                        <option value="food">Food & Beverage</option>
                                        <option value="transport">Transport</option>
                                        <option value="entertainment">Entertainment</option>
                                        <option value="utilities">Utilities</option>
                                        <option value="other">Other</option>
                                    </select>
                        </div>

                                <div class ="input-section">
                                        <label for = "input-amount">Amount:</label>
                                        <input type ="number" name="amount" class = "input" placeholder = "Enter Amount">
                                </div>

                                <div class ="input-section">
                                        <label for ="input-date">Date:</label>
                                        <input type ="date" name="input-date" class = "input">
                                </div>
                            
                                <div class ="expense-button">
                                        <button class ="submit" name="add-expense">Add Expense</button>
                                </div>

                            </form>

                        

                    </div>

                </div>

                    <div class="annual-container" id="annual-container">

                        <h2>Chart Expenses</h2>
                        <hr />
                            <canvas id="expenseChart"></canvas> <!-- Canvas for the chart -->

                            <script>
                                // Pass PHP data to JavaScript
                                const categories = <?php echo json_encode($categories); ?>;
                                const totals = <?php echo json_encode($totals); ?>;
                                
                                // Calculate total for percentages
                                const total = totals.reduce((a, b) => a + b, 0);
                                
                                // Create the pie chart
                                const ctx = document.getElementById('expenseChart').getContext('2d');
                                const expenseChart = new Chart(ctx, {
                                    type: 'doughnut', // Changed to doughnut for better appearance
                                    data: {
                                        labels: categories,
                                        datasets: [{
                                            label: 'Annual Expenses',
                                            data: totals,
                                            backgroundColor: [
                                                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'
                                            ],
                                            borderWidth: 2,
                                            borderColor: '#ffffff'
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: true,
                                        plugins: {
                                            legend: {
                                                position: 'right',
                                                labels: {
                                                    padding: 20,
                                                    font: {
                                                        size: 12
                                                    }
                                                }
                                            },
                                            title: {
                                                display: true,
                                                text: 'Expenses by Category',
                                                font: {
                                                    size: 16,
                                                    weight: 'bold'
                                                },
                                                padding: 20
                                            },
                                            tooltip: {
                                                callbacks: {
                                                    label: function(context) {
                                                        const value = context.raw;
                                                        const percentage = ((value / total) * 100).toFixed(1);
                                                        return `${context.label}: $${value} (${percentage}%)`;
                                                    }
                                                }
                                            }
                                        },
                                        animation: {
                                            animateScale: true,
                                            animateRotate: true
                                        },
                                        cutout: '60%' // Makes the doughnut hole
                                    }
                                });
                            </script>
                        
                    </div>
                    
                    <div class="history-container">
                        <h3 id = "h3-history" style="display: none">History</h3>
                
                    </div>

                    <div class ="expense-container" id="expense-container" style="display: none;">
                        <h2>Expense History</h2>
                        <table class="expense-table">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                </tr>
                            </thead>

                            <tbody id="table-body">

                                                    <?php if (!empty($expenses)): ?>
                                    <?php foreach ($expenses as $expense): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($expense['category']); ?></td>
                                            <td><?php echo htmlspecialchars($expense['amount']); ?></td>
                                            <td><?php echo htmlspecialchars($expense['date']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3">No expenses found.</td>
                                    </tr>
                                <?php endif; ?>
                            
                            
                            </tbody>

                            <tfoot>
                                <tr>
                                    <td><strong>Total:</strong></td>
                                    <td id="total-expense"><?php echo number_format($totalExpenses, 2); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>  
        </div>

        <script src="script.js"></script>
    </body>

</html>

