<?php 
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $account_type = $_POST['account_type'];
    $license_number = trim($_POST['license_number']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $confirm_password = $_POST['confirm_password'];
    
    if (!password_verify($confirm_password, $password)) {
        echo "<script>alert('Passwords do not match.'); window.history.back();</script>";
        exit();
    }
    
    // Check if email exists in EITHER table
    $email_exists = false;
    
    // Check medical_users table
    $check_medical = $conn->prepare("SELECT id FROM medical_users WHERE email = ?");
    $check_medical->bind_param("s", $email);
    $check_medical->execute();
    $check_medical->store_result();
    
    if ($check_medical->num_rows > 0) {
        $email_exists = true;
    }
    $check_medical->close();
    
    // If not found in medical_users, check company_users table
    if (!$email_exists) {
        $check_company = $conn->prepare("SELECT id FROM company_users WHERE email = ?");
        $check_company->bind_param("s", $email);
        $check_company->execute();
        $check_company->store_result();
        
        if ($check_company->num_rows > 0) {
            $email_exists = true;
        }
        $check_company->close();
    }
    
    // If email exists in either table, show error
    if ($email_exists) {
        echo "<script>alert('Email already exists! Please use a different email.'); window.history.back();</script>";
        exit();
    }
    
    // Handle license document upload
    $license_doc_path = null;
    
    if (isset($_FILES['license_document']) && $_FILES['license_document']['error'] == 0) {
        $upload_dir = '../uploads/licenses/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Generate unique filename
        $file_extension = pathinfo($_FILES['license_document']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid('license_') . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $file_name;
        
        // Check file type (optional - you can modify allowed types)
        $allowed_types = array('pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx');
        if (!in_array($file_extension, $allowed_types)) {
            echo "<script>alert('Invalid file type. Allowed types: PDF, JPG, JPEG, PNG, DOC, DOCX'); window.history.back();</script>";
            exit();
        }
        
        // Check file size (limit to 5MB)
        if ($_FILES['license_document']['size'] > 5242880) {
            echo "<script>alert('File is too large. Maximum size: 5MB'); window.history.back();</script>";
            exit();
        }
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['license_document']['tmp_name'], $upload_path)) {
            $license_doc_path = 'uploads/licenses/' . $file_name;
        } else {
            echo "<script>alert('Failed to upload license document. Please try again.'); window.history.back();</script>";
            exit();
        }
    } else {
        echo "<script>alert('Please upload your license document.'); window.history.back();</script>";
        exit();
    }
    
    // Determine table based on account type for insertion
    $table = ($account_type === "medical") ? "medical_users" : "company_users";
    
    // Insert new user with license information
    $stmt = $conn->prepare("INSERT INTO $table (name, email, password, license_number, license_document) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $email, $password, $license_number, $license_doc_path);
    
    if ($stmt->execute()) {
        echo "<script>alert('Registration Successful! Redirecting to login...');</script>";
        echo "<script>setTimeout(function(){ window.location.href = '../signin.php'; }, 2000);</script>";
    } else {
        echo "<script>alert('Registration failed. Please try again.'); window.history.back();</script>";
    }
    
    $stmt->close();
    $conn->close();
}
?>