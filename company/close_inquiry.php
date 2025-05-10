<?php
// Start session if not already started
session_start();

// Database connection
require_once '../api/db.php';


// Get company ID from session
$company_id = $_SESSION['user_id'];

// Check if inquiry ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect back to the inquiries page with an error message
    $_SESSION['error_message'] = "Invalid inquiry ID.";
    header("Location: view_inquiries.php");
    exit();
}

$inquiry_id = (int)$_GET['id'];

// Verify that the inquiry belongs to the company and is in 'replied' status
$check_query = "SELECT * FROM inquiries WHERE id = ? AND supplier_id = ? AND status = 'replied'";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ii", $inquiry_id, $company_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // If inquiry doesn't exist, doesn't belong to the company, or isn't in 'replied' status
    $_SESSION['error_message'] = "You cannot close this inquiry. It either doesn't exist, doesn't belong to your company, or is not in 'replied' status.";
    header("Location: view_inquiries.php");
    exit();
}

// Update the inquiry status to 'closed'
$update_query = "UPDATE inquiries SET status = 'closed', closed_at = NOW() WHERE id = ? AND supplier_id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("ii", $inquiry_id, $company_id);

if ($stmt->execute()) {
    // Success - set success message and redirect
    $_SESSION['success_message'] = "Inquiry has been successfully closed.";
} else {
    // Error occurred - set error message
    $_SESSION['error_message'] = "An error occurred while closing the inquiry. Please try again.";
}

// Redirect back to the inquiries page
header("Location: view_inquiries.php");
exit();
?>