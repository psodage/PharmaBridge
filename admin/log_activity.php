<?php
// log_activity.php - Script to log admin activities

// Start session if not already started
session_start();

// Check if admin is logged in

// Database connection
require_once("../api/db.php");

// Get admin ID from session
$admin_id = $_SESSION['admin_id'];

// Check if activity data is provided
if (isset($_POST['activity_type']) && isset($_POST['description'])) {
    $activity_type = mysqli_real_escape_string($conn, $_POST['activity_type']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    // Insert activity log
    $query = "INSERT INTO admin_activity_logs (admin_id, activity_type, activity_description) 
              VALUES ($admin_id, '$activity_type', '$description')";
    
    if (mysqli_query($conn, $query)) {
        echo "Success";
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo "Error: " . mysqli_error($conn);
    }
} else {
    header('HTTP/1.1 400 Bad Request');
    echo "Missing required data";
}