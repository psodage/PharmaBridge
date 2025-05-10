<?php
// Include database connection
require_once '../api/db.php';
session_start();



// Function to get order status class for styling
function getStatusClass($status) {
    switch ($status) {
        case 'Pending':
            return 'status-pending';
        case 'Processing':
            return 'status-processing';
        case 'Shipped':
            return 'status-shipped';
        case 'Delivered':
            return 'status-delivered';
        case 'Cancelled':
            return 'status-cancelled';
        default:
            return 'status-default';
    }
}

// Handle status update if submitted
if (isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['new_status'];
    $note = $_POST['note'] ?? '';
    
    try {
        // Begin transaction
        $conn->begin_transaction();
        
        // Update order status
        $update_query = "UPDATE orders SET order_status = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $new_status, $order_id);
        $stmt->execute();
        
        // Add note if provided
        if (!empty($note)) {
            $current_date = date('Y-m-d H:i:s');
            $note_text = "\nUpdate $current_date: $note";
            
            $note_query = "UPDATE orders SET notes = CONCAT(IFNULL(notes, ''), ?) WHERE id = ?";
            $note_stmt = $conn->prepare($note_query);
            $note_stmt->bind_param("si", $note_text, $order_id);
            $note_stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        $success_message = "Order #$order_id status updated to $new_status successfully";
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error_message = "Error updating order: " . $e->getMessage();
    }
}

// Handle filters
$where_conditions = [];
$params = [];
$param_types = "";

if (isset($_GET['filter_status']) && !empty($_GET['filter_status'])) {
    $where_conditions[] = "o.order_status = ?";
    $params[] = $_GET['filter_status'];
    $param_types .= "s";
}

if (isset($_GET['filter_date_from']) && !empty($_GET['filter_date_from'])) {
    $where_conditions[] = "o.order_date >= ?";
    $params[] = $_GET['filter_date_from'] . " 00:00:00";
    $param_types .= "s";
}

if (isset($_GET['filter_date_to']) && !empty($_GET['filter_date_to'])) {
    $where_conditions[] = "o.order_date <= ?";
    $params[] = $_GET['filter_date_to'] . " 23:59:59";
    $param_types .= "s";
}

if (isset($_GET['filter_medical_store']) && !empty($_GET['filter_medical_store'])) {
    $where_conditions[] = "m.name LIKE ?";
    $params[] = "%" . $_GET['filter_medical_store'] . "%";
    $param_types .= "s";
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

// Construct WHERE clause
$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM orders o 
                JOIN medical_users m ON o.user_id = m.id 
                $where_clause";

$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $items_per_page);

// Get orders with pagination
$query = "SELECT o.*, m.name as medical_store_name 
          FROM orders o 
          JOIN medical_users m ON o.user_id = m.id 
          $where_clause 
          ORDER BY o.order_date DESC 
          LIMIT ?, ?";

$stmt = $conn->prepare($query);
$param_types .= "ii";
$params[] = $offset;
$params[] = $items_per_page;

$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);

// Get all unique statuses for filter dropdown
$status_query = "SELECT DISTINCT order_status FROM orders ORDER BY order_status";
$status_result = $conn->query($status_query);
$statuses = [];
while ($row = $status_result->fetch_assoc()) {
    $statuses[] = $row['order_status'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Orders - Admin Dashboard</title>
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .orders-container {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .filter-form .form-group {
            margin-bottom: 0;
        }
        .filter-form button {
            height: 38px;
            align-self: end;
        }
        .order-item {
            background-color: #fff;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }
        .detail-group {
            margin-bottom: 10px;
        }
        .detail-label {
            font-weight: 600;
            color: #666;
            margin-bottom: 3px;
        }
        .order-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .status-badge {
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            color: white;
        }
        .status-pending {
            background-color: #f0ad4e;
        }
        .status-processing {
            background-color: #5bc0de;
        }
        .status-shipped {
            background-color: #0275d8;
        }
        .status-delivered {
            background-color: #5cb85c;
        }
        .status-cancelled {
            background-color: #d9534f;
        }
        .status-default {
            background-color: #777;
        }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
        }
        .pagination a, .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
        }
        .pagination a:hover {
            background-color: #f5f5f5;
        }
        .pagination .current {
            background-color: #4CAF50;
            color: white;
            border: 1px solid #4CAF50;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 600px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: #000;
        }
        @media (max-width: 768px) {
            .order-details {
                grid-template-columns: 1fr;
            }
            .order-actions {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="orders-container">
        <h2>Review Orders</h2>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Filters Form -->
        <form class="filter-form" method="GET" action="">
            <div class="form-group">
                <label for="filter_status">Status</label>
                <select name="filter_status" id="filter_status" class="form-control">
                    <option value="">All Statuses</option>
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?php echo $status; ?>" <?php echo (isset($_GET['filter_status']) && $_GET['filter_status'] == $status) ? 'selected' : ''; ?>>
                            <?php echo $status; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="filter_date_from">From Date</label>
                <input type="date" name="filter_date_from" id="filter_date_from" class="form-control" 
                       value="<?php echo isset($_GET['filter_date_from']) ? $_GET['filter_date_from'] : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="filter_date_to">To Date</label>
                <input type="date" name="filter_date_to" id="filter_date_to" class="form-control"
                       value="<?php echo isset($_GET['filter_date_to']) ? $_GET['filter_date_to'] : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="filter_medical_store">Medical Store</label>
                <input type="text" name="filter_medical_store" id="filter_medical_store" class="form-control"
                       placeholder="Search by name" 
                       value="<?php echo isset($_GET['filter_medical_store']) ? $_GET['filter_medical_store'] : ''; ?>">
            </div>
            
            <div class="form-group">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="review-orders.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>
        
        <!-- Orders List -->
        <?php if (count($orders) > 0): ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-item">
                    <div class="order-header">
                        <h3>Order #<?php echo $order['id']; ?></h3>
                        <span class="status-badge <?php echo getStatusClass($order['order_status']); ?>">
                            <?php echo $order['order_status']; ?>
                        </span>
                    </div>
                    
                    <div class="order-details">
                        <div class="detail-group">
                            <div class="detail-label">Order Date</div>
                            <div class="detail-value"><?php echo date('M d, Y h:i A', strtotime($order['order_date'])); ?></div>
                        </div>
                        
                        <div class="detail-group">
                            <div class="detail-label">Medical Store</div>
                            <div class="detail-value"><?php echo htmlspecialchars($order['medical_store_name']); ?></div>
                        </div>
                        
                        <div class="detail-group">
                            <div class="detail-label">Subtotal</div>
                            <div class="detail-value">$<?php echo number_format($order['subtotal'], 2); ?></div>
                        </div>
                        
                        <div class="detail-group">
                            <div class="detail-label">Tax</div>
                            <div class="detail-value">$<?php echo number_format($order['tax'], 2); ?></div>
                        </div>
                        
                        <div class="detail-group">
                            <div class="detail-label">Shipping</div>
                            <div class="detail-value">$<?php echo number_format($order['shipping'], 2); ?></div>
                        </div>
                        
                        <div class="detail-group">
                            <div class="detail-label">Total</div>
                            <div class="detail-value">$<?php echo number_format($order['total'], 2); ?></div>
                        </div>
                    </div>
                    
                    <div class="order-actions">
                        <div>
                            <button class="btn btn-info view-items-btn" data-order-id="<?php echo $order['id']; ?>">View Items</button>
                            <button class="btn btn-secondary view-notes-btn" data-notes="<?php echo htmlspecialchars($order['notes'] ?? ''); ?>">View Notes</button>
                        </div>
                        
                        <button class="btn btn-primary update-status-btn" data-order-id="<?php echo $order['id']; ?>" 
                                data-current-status="<?php echo $order['order_status']; ?>">
                            Update Status
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- Pagination -->
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=1<?php echo isset($_GET['filter_status']) ? '&filter_status=' . urlencode($_GET['filter_status']) : ''; ?><?php echo isset($_GET['filter_date_from']) ? '&filter_date_from=' . urlencode($_GET['filter_date_from']) : ''; ?><?php echo isset($_GET['filter_date_to']) ? '&filter_date_to=' . urlencode($_GET['filter_date_to']) : ''; ?><?php echo isset($_GET['filter_medical_store']) ? '&filter_medical_store=' . urlencode($_GET['filter_medical_store']) : ''; ?>">First</a>
                    <a href="?page=<?php echo $page - 1; ?><?php echo isset($_GET['filter_status']) ? '&filter_status=' . urlencode($_GET['filter_status']) : ''; ?><?php echo isset($_GET['filter_date_from']) ? '&filter_date_from=' . urlencode($_GET['filter_date_from']) : ''; ?><?php echo isset($_GET['filter_date_to']) ? '&filter_date_to=' . urlencode($_GET['filter_date_to']) : ''; ?><?php echo isset($_GET['filter_medical_store']) ? '&filter_medical_store=' . urlencode($_GET['filter_medical_store']) : ''; ?>">Previous</a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?><?php echo isset($_GET['filter_status']) ? '&filter_status=' . urlencode($_GET['filter_status']) : ''; ?><?php echo isset($_GET['filter_date_from']) ? '&filter_date_from=' . urlencode($_GET['filter_date_from']) : ''; ?><?php echo isset($_GET['filter_date_to']) ? '&filter_date_to=' . urlencode($_GET['filter_date_to']) : ''; ?><?php echo isset($_GET['filter_medical_store']) ? '&filter_medical_store=' . urlencode($_GET['filter_medical_store']) : ''; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo isset($_GET['filter_status']) ? '&filter_status=' . urlencode($_GET['filter_status']) : ''; ?><?php echo isset($_GET['filter_date_from']) ? '&filter_date_from=' . urlencode($_GET['filter_date_from']) : ''; ?><?php echo isset($_GET['filter_date_to']) ? '&filter_date_to=' . urlencode($_GET['filter_date_to']) : ''; ?><?php echo isset($_GET['filter_medical_store']) ? '&filter_medical_store=' . urlencode($_GET['filter_medical_store']) : ''; ?>">Next</a>
                    <a href="?page=<?php echo $total_pages; ?><?php echo isset($_GET['filter_status']) ? '&filter_status=' . urlencode($_GET['filter_status']) : ''; ?><?php echo isset($_GET['filter_date_from']) ? '&filter_date_from=' . urlencode($_GET['filter_date_from']) : ''; ?><?php echo isset($_GET['filter_date_to']) ? '&filter_date_to=' . urlencode($_GET['filter_date_to']) : ''; ?><?php echo isset($_GET['filter_medical_store']) ? '&filter_medical_store=' . urlencode($_GET['filter_medical_store']) : ''; ?>">Last</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No orders found matching the current filters.</div>
        <?php endif; ?>
    </div>
    
    <!-- Order Items Modal -->
    <div id="itemsModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Order Items</h3>
            <div id="itemsContainer">
                <!-- Items will be loaded here via AJAX -->
                <div class="loading">Loading items...</div>
            </div>
        </div>
    </div>
    
    <!-- Notes Modal -->
    <div id="notesModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Order Notes</h3>
            <div id="notesContainer">
                <!-- Notes will be displayed here -->
            </div>
        </div>
    </div>
    
    <!-- Update Status Modal -->
    <div id="updateStatusModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Update Order Status</h3>
            <form id="updateStatusForm" method="POST" action="">
                <input type="hidden" name="order_id" id="status_order_id">
                
                <div class="form-group">
                    <label for="new_status">Status</label>
                    <select name="new_status" id="new_status" class="form-control" required>
                        <option value="Pending">Pending</option>
                        <option value="Processing">Processing</option>
                        <option value="Shipped">Shipped</option>
                        <option value="Delivered">Delivered</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="note">Add Note (Optional)</label>
                    <textarea name="note" id="note" class="form-control" rows="3" placeholder="Add a note about this status change"></textarea>
                </div>
                
                <div class="form-group text-right">
                    <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Items Modal
        var itemsModal = document.getElementById('itemsModal');
        var itemsContainer = document.getElementById('itemsContainer');
        
        // Notes Modal
        var notesModal = document.getElementById('notesModal');
        var notesContainer = document.getElementById('notesContainer');
        
        // Update Status Modal
        var updateStatusModal = document.getElementById('updateStatusModal');
        var updateStatusForm = document.getElementById('updateStatusForm');
        var statusOrderId = document.getElementById('status_order_id');
        var newStatusSelect = document.getElementById('new_status');
        
        // Get all close buttons
        var closeButtons = document.getElementsByClassName('close');
        for (var i = 0; i < closeButtons.length; i++) {
            closeButtons[i].addEventListener('click', function() {
                itemsModal.style.display = 'none';
                notesModal.style.display = 'none';
                updateStatusModal.style.display = 'none';
            });
        }
        
        // View Items buttons
        var viewItemsButtons = document.getElementsByClassName('view-items-btn');
        for (var i = 0; i < viewItemsButtons.length; i++) {
            viewItemsButtons[i].addEventListener('click', function() {
                var orderId = this.getAttribute('data-order-id');
                fetchOrderItems(orderId);
                itemsModal.style.display = 'block';
            });
        }
        
        // View Notes buttons
        var viewNotesButtons = document.getElementsByClassName('view-notes-btn');
        for (var i = 0; i < viewNotesButtons.length; i++) {
            viewNotesButtons[i].addEventListener('click', function() {
                var notes = this.getAttribute('data-notes');
                displayNotes(notes);
                notesModal.style.display = 'block';
            });
        }
        
        // Update Status buttons
        var updateStatusButtons = document.getElementsByClassName('update-status-btn');
        for (var i = 0; i < updateStatusButtons.length; i++) {
            updateStatusButtons[i].addEventListener('click', function() {
                var orderId = this.getAttribute('data-order-id');
                var currentStatus = this.getAttribute('data-current-status');
                
                statusOrderId.value = orderId;
                
                // Set current status as selected
                for (var j = 0; j < newStatusSelect.options.length; j++) {
                    if (newStatusSelect.options[j].value === currentStatus) {
                        newStatusSelect.options[j].selected = true;
                        break;
                    }
                }
                
                updateStatusModal.style.display = 'block';
            });
        }
        
        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target == itemsModal) {
                itemsModal.style.display = 'none';
            }
            if (event.target == notesModal) {
                notesModal.style.display = 'none';
            }
            if (event.target == updateStatusModal) {
                updateStatusModal.style.display = 'none';
            }
        });
        
        // Fetch order items via AJAX
        function fetchOrderItems(orderId) {
            itemsContainer.innerHTML = '<div class="loading">Loading items...</div>';
            
            // Create AJAX request
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'get-order-items.php?order_id=' + orderId, true);
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        itemsContainer.innerHTML = xhr.responseText;
                    } else {
                        itemsContainer.innerHTML = '<div class="alert alert-danger">Error loading order items.</div>';
                    }
                }
            };
            
            xhr.send();
        }
        
        // Display notes in modal
        function displayNotes(notes) {
            if (notes && notes.trim() !== '') {
                var formattedNotes = notes.replace(/\n/g, '<br>');
                notesContainer.innerHTML = '<div class="notes-content">' + formattedNotes + '</div>';
            } else {
                notesContainer.innerHTML = '<div class="alert alert-info">No notes available for this order.</div>';
            }
        }
    });
    </script>
</body>
</html>