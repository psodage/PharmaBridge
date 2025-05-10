<?php
session_start();
require_once '../api/db.php';

// Check if user is logged in and is a medical professional
if (!isset($_SESSION['user_name']) || !isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'medical') { 
    header("Location: ../signin.php");
    exit();
}

// Ensure user_id is set in session
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Please log in to continue.";
    header("Location: ../signin.php");
    exit();
}

// Handle order cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    
    // Validate order belongs to current user
    $order_check_query = "SELECT id, status FROM orders WHERE id = ? AND user_id = ?";
    $order_check_stmt = $conn->prepare($order_check_query);
    $order_check_stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
    $order_check_stmt->execute();
    $order_check_result = $order_check_stmt->get_result();
    $order = $order_check_result->fetch_assoc();
    
    if (!$order) {
        $_SESSION['error_message'] = "Invalid order or unauthorized access.";
        header("Location: track_order.php");
        exit();
    }
    
    // Check if order can be cancelled
    if (!in_array($order['status'], ['Pending', 'Processing'])) {
        $_SESSION['error_message'] = "This order cannot be cancelled as it is no longer in a cancellable state.";
        header("Location: track_order.php");
        exit();
    }
    
    try {
        $conn->begin_transaction();
        
        // Restore product stock
        $restore_stock_query = "UPDATE products p
                                JOIN order_items oi ON p.id = oi.product_id
                                SET p.stock_quantity = p.stock_quantity + oi.quantity
                                WHERE oi.order_id = ?";
        $restore_stock_stmt = $conn->prepare($restore_stock_query);
        $restore_stock_stmt->bind_param("i", $order_id);
        $restore_stock_stmt->execute();
        
        // Update order status to Cancelled
        $cancel_order_query = "UPDATE orders SET status = 'Cancelled' WHERE id = ?";
        $cancel_order_stmt = $conn->prepare($cancel_order_query);
        $cancel_order_stmt->bind_param("i", $order_id);
        $cancel_order_stmt->execute();
        
        $conn->commit();
        
        $_SESSION['success_message'] = "Order #$order_id has been successfully cancelled.";
        header("Location: track_order.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Failed to cancel order. " . $e->getMessage();
        header("Location: track_order.php");
        exit();
    }
} else {
    // Direct access prevention
    header("Location: track_order.php");
    exit();
}