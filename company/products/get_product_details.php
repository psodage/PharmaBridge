<?php
session_start();
require_once '../../api/db.php';

// Check if user is logged in and is a company
if (!isset($_SESSION['user_name']) || !isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'company') { 
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Get company ID of logged in user
$company_id = $_SESSION['user_id'];

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Product ID is required']);
    exit();
}

$product_id = (int)$_GET['id'];

// Fetch product details
$query = "SELECT * FROM products WHERE id = ? AND company_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $product_id, $company_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    $product = mysqli_fetch_assoc($result);
    
    // Return product details as JSON
    header('Content-Type: application/json');
    echo json_encode($product);
} else {
    // Product not found or doesn't belong to the company
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Product not found']);
}

mysqli_close($conn);
?>