<?php
// api/reset-password.php

// Include your database connection file
require_once '../db.php';

// Set headers for JSON response
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the email from the form
    $email = trim($_POST['reset_email']);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }
    
    // Check if email exists in any of your tables
    $emailExists = false;
    
    // Check in medical_users
    $stmt = $conn->prepare("SELECT id, name FROM medical_users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $name);
        $stmt->fetch();
        $emailExists = true;
        $user_type = 'medical';
    }
    $stmt->close();
    
    // If not found in medical_users, check company_users
    if (!$emailExists) {
        $stmt = $conn->prepare("SELECT id, name FROM company_users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($user_id, $name);
            $stmt->fetch();
            $emailExists = true;
            $user_type = 'company';
        }
        $stmt->close();
    }
    
    if (!$emailExists) {
        echo json_encode(['success' => false, 'message' => 'Email not found in our records']);
        exit;
    }
    
    // Generate a unique token for password reset
    $token = bin2hex(random_bytes(32)); // Generates a 64 characters long token
    $expires = date('Y-m-d H:i:s', time() + 3600); // Token expires in 1 hour
    
    // Store the token in your password_resets table
    // You may need to create this table if it doesn't exist
    // CREATE TABLE password_resets (
    //     id INT AUTO_INCREMENT PRIMARY KEY,
    //     user_id INT NOT NULL,
    //     user_type VARCHAR(20) NOT NULL,
    //     token VARCHAR(64) NOT NULL,
    //     expires DATETIME NOT NULL,
    //     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    // );
    
    $stmt = $conn->prepare("INSERT INTO password_resets (user_id, user_type, token, expires) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $user_type, $token, $expires);
    
    if ($stmt->execute()) {
        // Now send the email with reset link
        $reset_link = "https://yourwebsite.com/reset-password.php?token=$token";
        $subject = "Password Reset Request";
        $message = "
            <html>
            <head>
                <title>Password Reset</title>
            </head>
            <body>
                <p>Hello $name,</p>
                <p>We received a request to reset your password. Click the link below to set a new password:</p>
                <p><a href='$reset_link'>Reset Your Password</a></p>
                <p>This link will expire in 1 hour.</p>
                <p>If you didn't request this, please ignore this email.</p>
                <p>Regards,<br>Your Website Team</p>
            </body>
            </html>
        ";
        
        // Set up email headers
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Your Website <noreply@yourwebsite.com>" . "\r\n";
        
        // Send the email
        if (mail($email, $subject, $message, $headers)) {
            echo json_encode(['success' => true, 'message' => 'Password reset link sent']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send email']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>