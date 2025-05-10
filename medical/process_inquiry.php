<?php
session_start();
require_once('../api/db.php');

// Check if user is logged in and is a medical user
if (!isset($_SESSION['user_id']) || !isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'medical') {
    header("Location: ../../signin.php");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $medical_id = $_SESSION['user_id'];
    $supplier_id = filter_input(INPUT_POST, 'supplier_id', FILTER_SANITIZE_NUMBER_INT);
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_SPECIAL_CHARS);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_SPECIAL_CHARS);
    
    // Validate input
    if (empty($supplier_id) || empty($subject) || empty($message)) {
        header("Location: send_inquiries.php?error=empty_fields");
        exit();
    }
    
    // Check if the supplier exists and the medical user has ordered from them
    $check_query = "
        SELECT COUNT(*) as count
        FROM company_users cu
        JOIN products p ON cu.id = p.company_id
        JOIN order_items oi ON p.id = oi.product_id
        JOIN orders o ON oi.order_id = o.id
        WHERE cu.id = ? AND o.user_id = ?
    ";
    
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $supplier_id, $medical_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] === 0) {
        header("Location: send_inquiries.php?error=invalid_supplier");
        exit();
    }
    
    // First, let's check if we need to create the inquiries table
    $check_table_query = "SHOW TABLES LIKE 'inquiries'";
    $table_result = $conn->query($check_table_query);
    
    if ($table_result->num_rows === 0) {
        // Create the inquiries table
        $create_table_query = "
            CREATE TABLE IF NOT EXISTS `inquiries` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `medical_id` int(11) NOT NULL,
              `supplier_id` int(11) NOT NULL,
              `subject` varchar(255) NOT NULL,
              `message` text NOT NULL,
              `reply` text DEFAULT NULL,
              `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
              `reply_at` timestamp NULL DEFAULT NULL,
              `status` enum('pending','replied','closed') NOT NULL DEFAULT 'pending',
              PRIMARY KEY (`id`),
              KEY `medical_id` (`medical_id`),
              KEY `supplier_id` (`supplier_id`),
              CONSTRAINT `inquiries_ibfk_1` FOREIGN KEY (`medical_id`) REFERENCES `medical_users` (`id`) ON DELETE CASCADE,
              CONSTRAINT `inquiries_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `company_users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ";
        
        if (!$conn->query($create_table_query)) {
            header("Location: send_inquiries.php?error=database_error");
            exit();
        }
    }
    
    // Insert the inquiry
    $insert_query = "
        INSERT INTO inquiries (medical_id, supplier_id, subject, message)
        VALUES (?, ?, ?, ?)
    ";
    
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("iiss", $medical_id, $supplier_id, $subject, $message);
    
    if ($stmt->execute()) {
        header("Location: send_inquiries.php?success=1");
        exit();
    } else {
        header("Location: send_inquiries.php?error=database_error");
        exit();
    }
} else {
    // If not a POST request, redirect to the inquiry form
    header("Location: send_inquiries.php");
    exit();
}