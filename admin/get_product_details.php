<?php
// Include database connection
require_once '../api/db.php';

// Check if ID parameter exists
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Product ID is required']);
    exit;
}

// Sanitize input
$product_id = intval($_GET['id']);

// Prepare query to get product details with company name
$sql = "SELECT p.*, c.name as company_name 
        FROM products p 
        LEFT JOIN company_users c ON p.company_id = c.id 
        WHERE p.id = ?";

// Prepare and execute statement
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if product exists
if ($result && $result->num_rows > 0) {
    // Fetch product data
    $product = $result->fetch_assoc();
    
    // Format dates for consistency if needed
    if (isset($product['manufacturing_date'])) {
        $product['manufacturing_date'] = date('Y-m-d', strtotime($product['manufacturing_date']));
    }
    
    if (isset($product['expiry_date'])) {
        $product['expiry_date'] = date('Y-m-d', strtotime($product['expiry_date']));
    }
    
    // Return product data as JSON
    header('Content-Type: application/json');
    echo json_encode($product);
} else {
    // Product not found
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Product not found']);
}

// Close statement and connection
$stmt->close();
$conn->close();
?>