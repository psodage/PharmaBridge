<?php
// Include database connection
require '../db.php';

// Check if the request is POST and has an ID
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    // Get and sanitize the medical ID
    $id = (int)$_POST['id'];
    
    // First check if the medical exists and is in 'declined' status
    $checkQuery = "SELECT approval_status FROM medical_users WHERE id = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "Medical not found.";
        exit;
    }
    
    $row = $result->fetch_assoc();
    
    // Only allow deletion of stores with 'declined' status for safety
    if ($row['approval_status'] !== 'declined') {
        echo "Only declined stores can be deleted. Please change the status to declined first.";
        exit;
    }
    
    // Prepare the SQL statement for deletion
    $deleteQuery = "DELETE FROM medical_users WHERE id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $id);
    
    // Execute the statement
    if ($stmt->execute()) {
        // Delete was successful
        echo "Medical successfully deleted.";
    } else {
        // Delete failed
        echo "Error deleting medical: " . $stmt->error;
    }
    
    // Close statement
    $stmt->close();
    
} else {
    // Invalid request
    echo "Invalid request method or missing medical ID.";
}

// Close database connection
$conn->close();
?>