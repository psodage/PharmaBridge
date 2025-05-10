<?php


// Include database connection
require_once '../api/db.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Check if required parameters are set
if (!isset($_POST['product_id']) || !isset($_POST['reason']) || !isset($_POST['comments'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Get parameters
$product_id = $_POST['product_id'];
$reason = $_POST['reason'];
$comments = $_POST['comments'];

// Validate product_id is numeric
if (!is_numeric($product_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

try {
    // First, update the flagged status in the products table
    $update_sql = "UPDATE products SET flagged = 1 WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $product_id);
    $update_result = $update_stmt->execute();
    
    if (!$update_result) {
        throw new Exception("Failed to update product flag status");
    }
    
    // Then, insert a record into the flag_reports table (create this table if it doesn't exist)
    $insert_sql = "INSERT INTO flag_reports (product_id, reason, comments, reported_at) 
                  VALUES (?, ?, ?, NOW())";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("iss", $product_id, $reason, $comments);
    $insert_result = $insert_stmt->execute();
    
    if (!$insert_result) {
        throw new Exception("Failed to save flag report");
    }
    
    // Return success response
    echo json_encode(['success' => true, 'message' => 'Product flagged successfully']);
    
} catch (Exception $e) {
    // Return error response
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>