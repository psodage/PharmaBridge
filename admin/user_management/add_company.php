<?php
require '../db.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data and sanitize
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
    $licenseNumber = $conn->real_escape_string($_POST['license_number']);
    $approvalStatus = $conn->real_escape_string($_POST['approval_status']);
    
    // Handle file upload
    $licenseDocument = '';
    $uploadOk = true;
    $errorMessage = '';
    
    if (isset($_FILES['license_document']) && $_FILES['license_document']['error'] == 0) {
        $targetDir = "../uploads/licenses/";
        
        // Create directory if it doesn't exist
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        // Get file info
        $fileName = basename($_FILES["license_document"]["name"]);
        $fileSize = $_FILES["license_document"]["size"];
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Generate unique file name to prevent overwriting
        $uniqueFileName = uniqid() . '_' . $fileName;
        $targetFile = $targetDir . $uniqueFileName;
        
        // Check file size (5MB max)
        if ($fileSize > 5000000) {
            $errorMessage = "File is too large. Maximum size is 5MB.";
            $uploadOk = false;
        }
        
        // Allow certain file formats
        if (!in_array($fileType, ["pdf", "jpg", "jpeg", "png", "gif"])) {
            $errorMessage = "Only PDF, JPG, JPEG, PNG & GIF files are allowed.";
            $uploadOk = false;
        }
        
        // Check if $uploadOk is set to false by an error
        if ($uploadOk) {
            if (move_uploaded_file($_FILES["license_document"]["tmp_name"], $targetFile)) {
                $licenseDocument = $uniqueFileName;
            } else {
                $errorMessage = "Sorry, there was an error uploading your file.";
                $uploadOk = false;
            }
        }
    } else {
        $errorMessage = "License document is required.";
        $uploadOk = false;
    }
    
    // Check if email already exists
    $checkEmail = "SELECT id FROM company_users WHERE email = ?";
    $checkStmt = $conn->prepare($checkEmail);
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        // Email already exists
        $_SESSION['error'] = "Email address already in use. Please use a different email.";
        header("Location: manage-pharma-companies.php");
        exit;
    }
    
    // If upload successful, insert into database
    if ($uploadOk) {
        // Current timestamp
        $createdAt = date('Y-m-d H:i:s');
        
        // Prepare and execute SQL statement
        $sql = "INSERT INTO company_users (name, email, password, license_number, license_document, approval_status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssss", $name, $email, $password, $licenseNumber, $licenseDocument, $approvalStatus, $createdAt);
        
        if ($stmt->execute()) {
            // Success
            $_SESSION['success'] = "New company added successfully.";
            header("Location: manage-pharma-companies.php");
            exit;
        } else {
            // Error
            $_SESSION['error'] = "Error: " . $stmt->error;
            header("Location: manage-pharma-companies.php");
            exit;
        }
    } else {
        // Upload error
        $_SESSION['error'] = $errorMessage;
        header("Location: manage-pharma-companies.php");
        exit;
    }
} else {
    // Not a POST request, redirect
    header("Location: manage-pharma-companies.php");
    exit;
}
?>