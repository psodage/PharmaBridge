<?php
session_start();
require_once '../../api/db.php';

// Check if user is logged in and is a company
if (!isset($_SESSION['user_name']) || !isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'company') { 
    header("Location: ../../signin.php");
    exit();
}

// Get company ID of logged in user
$company_id = $_SESSION['user_id'];

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "No product selected for deletion.";
    header("Location: manage_products.php");
    exit();
}

$product_id = (int)$_GET['id'];

// Verify the product belongs to the logged-in company
$check_query = "SELECT id, product_name, product_image FROM products WHERE id = ? AND company_id = ?";
$check_stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($check_stmt, "ii", $product_id, $company_id);
mysqli_stmt_execute($check_stmt);
$result = mysqli_stmt_get_result($check_stmt);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['error_message'] = "You don't have permission to delete this product or the product doesn't exist.";
    header("Location: manage_products.php");
    exit();
}

$product = mysqli_fetch_assoc($result);
$product_name = $product['product_name'];
$product_image = $product['product_image'];

// Start a transaction
mysqli_begin_transaction($conn);

try {
    // Delete the product record
    $delete_query = "DELETE FROM products WHERE id = ? AND company_id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($delete_stmt, "ii", $product_id, $company_id);
    $success = mysqli_stmt_execute($delete_stmt);
    
    if (!$success) {
        throw new Exception("Failed to delete the product record.");
    }
    
    // If there's a product image, delete it from the server
    if (!empty($product_image)) {
        $file_path = "../uploads/products/" . $product_image;
        if (file_exists($file_path)) {
            if (!unlink($file_path)) {
                // Log the error but continue with deletion
                error_log("Failed to delete product image: " . $file_path);
            }
        }
    }
    
    // Commit the transaction
    mysqli_commit($conn);
    
    // Set success message and redirect
    $_SESSION['success_message'] = "Product '$product_name' has been successfully deleted.";
 
    header("Location: manage_products.php");
    exit();
    
} catch (Exception $e) {
    // Rollback the transaction in case of error
    mysqli_rollback($conn);
    
    $_SESSION['error_message'] = "Error deleting product: " . $e->getMessage();
    header("Location: manage_products.php");
    exit();
}
?><?php
session_start();
require_once '../api/db.php';

// Check if user is logged in and is a company
if (!isset($_SESSION['user_name']) || !isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'company') { 
    header("Location: ../../signin.php");
    exit();
}

// Get company ID of logged in user
$company_id = $_SESSION['user_id'];

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "No product selected for deletion.";
    header("Location: manage_products.php");
    exit();
}

$product_id = (int)$_GET['id'];

// Verify the product belongs to the logged-in company
$check_query = "SELECT id, product_name, product_image FROM products WHERE id = ? AND company_id = ?";
$check_stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($check_stmt, "ii", $product_id, $company_id);
mysqli_stmt_execute($check_stmt);
$result = mysqli_stmt_get_result($check_stmt);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['error_message'] = "You don't have permission to delete this product or the product doesn't exist.";
    header("Location: manage_products.php");
    exit();
}

$product = mysqli_fetch_assoc($result);
$product_name = $product['product_name'];
$product_image = $product['product_image'];

// Start a transaction
mysqli_begin_transaction($conn);

try {
    // Delete the product record
    $delete_query = "DELETE FROM products WHERE id = ? AND company_id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($delete_stmt, "ii", $product_id, $company_id);
    $success = mysqli_stmt_execute($delete_stmt);
    
    if (!$success) {
        throw new Exception("Failed to delete the product record.");
    }
    
    // If there's a product image, delete it from the server
    if (!empty($product_image)) {
        $file_path = "../uploads/products/" . $product_image;
        if (file_exists($file_path)) {
            if (!unlink($file_path)) {
                // Log the error but continue with deletion
                error_log("Failed to delete product image: " . $file_path);
            }
        }
    }
    
    // Commit the transaction
    mysqli_commit($conn);
    
    // Set success message and redirect
    $_SESSION['success_message'] = "Product '$product_name' has been successfully deleted.";
    header("Location: manage_products.php");
    exit();
    
} catch (Exception $e) {
    // Rollback the transaction in case of error
    mysqli_rollback($conn);
    
    $_SESSION['error_message'] = "Error deleting product: " . $e->getMessage();
    header("Location: manage_products.php");
    exit();
}
?>