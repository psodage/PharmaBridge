<?php 
require 'db.php'; 
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    // ✅ Admin Check
    if ($email === "admin" && $password === "admin") {
        $_SESSION['user_id'] = "admin";
        $_SESSION['user_name'] = "Administrator";
        $_SESSION['account_type'] = "admin";
        
        echo "<script>alert('Admin Login Successful! Redirecting...');</script>";
        echo "<script>setTimeout(function(){ window.location.href = '../admin/admin.php'; }, 2000);</script>";
        exit();
    }
    
    // ✅ First check medical_users table
    $found = false;
    $stmt = $conn->prepare("SELECT id, name, password, approval_status FROM medical_users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $name, $hashed_password, $action);
        $stmt->fetch();
        $found = true;
        $account_type = "medical";
    }
    $stmt->close();
    
    // ✅ If not found in medical_users, check company_users table
    if (!$found) {
        $stmt = $conn->prepare("SELECT id, name, password, approval_status FROM company_users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $name, $hashed_password, $action);
            $stmt->fetch();
            $found = true;
            $account_type = "company";
        }
        $stmt->close();
    }
    
    // ✅ Process login if user was found in either table
    if ($found) {
        // ❌ Check if the Account is Approved
        if ($action !== 'approved') {
            echo "<script>alert('Your account is not approved yet. Please wait for admin approval.'); window.history.back();</script>";
            exit();
        }
        
        // ✅ Verify Password
        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['user_name'] = $name;
            $_SESSION['account_type'] = $account_type;
            
            // ✅ Redirect Based on Account Type
            $redirect_page = ($account_type === "medical") ? "../medical/medical.php" : "../company/company.php";
            
            echo "<script>alert('Login Successful! Redirecting...');</script>";
            echo "<script>setTimeout(function(){ window.location.href = '$redirect_page'; }, 2000);</script>";
        } else {
            echo "<script>alert('Incorrect password! Try again.'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('No account found with this email! Or Acc is Not approved by Admin'); window.history.back();</script>";
    }
    
    $conn->close();
}
?>