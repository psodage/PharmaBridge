<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pharma";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if all required parameters are set
if (!isset($_GET['id']) || !isset($_GET['table']) || !isset($_GET['action'])) {
    die("Missing required parameters");
}

// Get parameters and sanitize them
$id = (int)$_GET['id'];
$table = $_GET['table'];
$action = $_GET['action'];

// Validate table name to prevent SQL injection
if ($table !== 'medical_users' && $table !== 'company_users') {
    die("Invalid table specified");
}

// Validate action
if ($action !== 'approved' && $action !== 'reject') {
    die("Invalid action specified");
}

// Set the appropriate status based on the action
$status = ($action === 'approved') ? 'approved' : 'declined';

// Update the status in the database
$query = "UPDATE $table SET approval_status = ? WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $status, $id);

// Execute the query
if ($stmt->execute()) {
    // If the action was approved, you might want to send an email notification
    if ($action === 'approved') {
        // Get user email
        $email_query = "SELECT email, name FROM $table WHERE id = ?";
        $email_stmt = $conn->prepare($email_query);
        $email_stmt->bind_param("i", $id);
        $email_stmt->execute();
        $email_result = $email_stmt->get_result();
        
        if ($row = $email_result->fetch_assoc()) {
            $user_email = $row['email'];
            $user_name = $row['name'];
            
            // Email notification code would go here
            // This is just a placeholder - you'd need to implement actual email sending
            // mail($user_email, "Your BridgeRx Account Has Been Approved", "Dear $user_name,\n\nYour account has been approved...");
        }
    }
    
    // Redirect back to the manage status page with a success message
    header("Location: manage-user-status.php?status=success&action=$action");
    exit();
} else {
    // Redirect back with error message
    header("Location: manage-user-status.php?status=error&message=" . urlencode($stmt->error));
    exit();
}

// Close connection
$stmt->close();
$conn->close();
?>