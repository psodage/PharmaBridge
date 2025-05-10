<?php
session_start();

// Check if medical store user is logged in
if (!isset($_SESSION['user_name']) || !isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'medical') { 
    header("Location: ../signin.php");
    exit();
}

// Database connection
require_once '../api/db.php';

// Check if company ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("No company ID provided.");
}

$company_id = mysqli_real_escape_string($conn, $_GET['id']);

// Fetch the license document
$query = "SELECT name, license_document, license_number FROM company_users WHERE id = '$company_id'";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    die("Company not found or no document available.");
}

$company = mysqli_fetch_assoc($result);

// Check if license document exists
if (empty($company['license_document'])) {
    die("No license document available for this company.");
}

// Full file path
$file_path = '../' . $company['license_document'];

// Verify file exists
if (!file_exists($file_path)) {
    die("File not found: " . htmlspecialchars($file_path));
}

// Determine file extension and MIME type
$file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

// Set appropriate MIME type based on file extension
switch ($file_extension) {
    case 'pdf':
        $mime_type = 'application/pdf';
        break;
    case 'jpg':
    case 'jpeg':
        $mime_type = 'image/jpeg';
        break;
    case 'png':
        $mime_type = 'image/png';
        break;
    case 'gif':
        $mime_type = 'image/gif';
        break;
    case 'doc':
    case 'docx':
        $mime_type = 'application/msword';
        break;
    default:
        $mime_type = 'application/octet-stream';
}

// Generate filename
$file_name = $company['name'] . "_License_" . $company['license_number'] . "." . $file_extension;

// Set headers to open file in browser
header('Content-Type: ' . $mime_type);
header('Content-Disposition: inline; filename="' . $file_name . '"');
header('Content-Length: ' . filesize($file_path));

// Output file contents
readfile($file_path);

// Close database connection
mysqli_close($conn);
exit();
?>