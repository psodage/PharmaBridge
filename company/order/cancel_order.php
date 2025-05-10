<?php 
session_start();
if (!isset($_SESSION['user_name']) || !isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'company') { 
    header("Location: ../signin.php");
    exit();
}
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pharma";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Get company ID from session
$company_id = $_SESSION['user_id'];
// Message variables
$message = "";
$messageType = "";
// Process form submission for cancellation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancel_order'])) {
    $order_id = $_POST['order_id'];
    $cancellation_reason = $_POST['cancellation_reason'];

    // Validate that this company has products in this order
    $check_sql = "SELECT COUNT(*) as count
                 FROM order_items oi
                 JOIN products p ON oi.product_id = p.id
                 WHERE oi.order_id = ? AND p.company_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $order_id, $company_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $check_row = $check_result->fetch_assoc();

    if ($check_row['count'] > 0) {
        // Check if order can be cancelled (not delivered or already cancelled)
        $status_check_sql = "SELECT order_status FROM orders WHERE id = ?";
        $status_check_stmt = $conn->prepare($status_check_sql);
        $status_check_stmt->bind_param("i", $order_id);
        $status_check_stmt->execute();
        $status_result = $status_check_stmt->get_result();
        $status_row = $status_result->fetch_assoc();

        if ($status_row && $status_row['order_status'] != 'Delivered' && $status_row['order_status'] != 'Cancelled') {
            // Update order status to Cancelled
            $update_sql = "UPDATE orders SET order_status = 'Cancelled', cancellation_reason = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $cancellation_reason, $order_id);
            
            if ($update_stmt->execute()) {
                // Insert into order_history table
                $history_sql = "INSERT INTO order_history (order_id, status, notes, updated_by, updated_by_id) 
                                VALUES (?, 'Cancelled', ?, 'Company', ?)";
                $history_stmt = $conn->prepare($history_sql);
                $history_stmt->bind_param("isi", $order_id, $cancellation_reason, $company_id);
                $history_stmt->execute();
                
                $message = "Order #$order_id has been cancelled successfully.";
                $messageType = "success";
            } else {
                $message = "Error cancelling order: " . $conn->error;
                $messageType = "danger";
            }
        } else {
            $message = "Order cannot be cancelled. It may already be delivered or cancelled.";
            $messageType = "warning";
        }
    } else {
        $message = "You don't have permission to cancel this order.";
        $messageType = "danger";
    }
}

// Fetch orders that have products from this company
$sql = "SELECT DISTINCT o.id, o.order_number, o.order_date, o.order_status, 
        o.total_amount, o.customer_id, o.shipping_address, o.billing_address,
        o.payment_method, o.cancellation_reason, c.first_name, c.last_name, c.email, c.phone
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        JOIN customers c ON o.customer_id = c.id
        WHERE p.company_id = ?
        ORDER BY o.order_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - BridgeRX Vendor Portal</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-4">
        <h2>Manage Orders</h2>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>" role="alert">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5>Your Orders</h5>
            </div>
            <div class="card-body">
                <?php if ($result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['order_number']); ?></td>
                                        <td><?php echo date("M d, Y", strtotime($row['order_date'])); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?><br>
                                            <small><?php echo htmlspecialchars($row['email']); ?></small>
                                        </td>
                                        <td>$<?php echo number_format($row['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="badge <?php 
                                                if ($row['order_status'] == 'Processing') echo 'bg-info';
                                                elseif ($row['order_status'] == 'Shipped') echo 'bg-primary';
                                                elseif ($row['order_status'] == 'Delivered') echo 'bg-success';
                                                elseif ($row['order_status'] == 'Cancelled') echo 'bg-danger';
                                                else echo 'bg-secondary';
                                            ?>">
                                                <?php echo htmlspecialchars($row['order_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="order_details.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <?php if ($row['order_status'] != 'Delivered' && $row['order_status'] != 'Cancelled'): ?>
                                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#cancelOrderModal<?php echo $row['id']; ?>">
                                                    <i class="fas fa-times"></i> Cancel
                                                </button>
                                                
                                                <!-- Cancel Order Modal -->
                                                <div class="modal fade" id="cancelOrderModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="cancelOrderModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="cancelOrderModalLabel<?php echo $row['id']; ?>">Cancel Order #<?php echo $row['order_number']; ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <form method="post" action="">
                                                                <div class="modal-body">
                                                                    <p>Are you sure you want to cancel this order? This action cannot be undone.</p>
                                                                    <div class="mb-3">
                                                                        <label for="cancellation_reason" class="form-label">Reason for Cancellation</label>
                                                                        <textarea class="form-control" id="cancellation_reason" name="cancellation_reason" rows="3" required></textarea>
                                                                    </div>
                                                                    <input type="hidden" name="order_id" value="<?php echo $row['id']; ?>">
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                    <button type="submit" name="cancel_order" class="btn btn-danger">Cancel Order</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No orders found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // You can add custom JavaScript here
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>