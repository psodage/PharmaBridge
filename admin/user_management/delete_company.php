<?php
// Include database connection
require '../db.php';

// Check if the request is POST and has an ID
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    // Get and sanitize the company ID
    $id = (int)$_POST['id'];
    
    // First check if the company exists and is in 'declined' status
    $checkQuery = "SELECT approval_status FROM company_users WHERE id = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "Company not found.";
        exit;
    }
    
    $row = $result->fetch_assoc();
    
    // Only allow deletion of companies with 'declined' status for safety
    if ($row['approval_status'] !== 'declined') {
        echo "Only declined companies can be deleted. Please change the status to declined first.";
        exit;
    }
    
    // Prepare the SQL statement for deletion
    $deleteQuery = "DELETE FROM company_users WHERE id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $id);
    
    // Execute the statement
    if ($stmt->execute()) {
        // Delete was successful
        echo "Company successfully deleted.";
    } else {
        // Delete failed
        echo "Error deleting company: " . $stmt->error;
    }
    
    // Close statement
    $stmt->close();
    
} else {
    // Invalid request
    echo "Invalid request method or missing company ID.";
}

// Close database connection
$conn->close();
?>