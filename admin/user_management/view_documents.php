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
if (!isset($_GET['id']) || !isset($_GET['table'])) {
    die("Missing required parameters");
}

// Get parameters and sanitize them
$id = (int)$_GET['id'];
$table = $_GET['table'];

// Validate table name to prevent SQL injection
if ($table !== 'medical_users' && $table !== 'company_users') {
    die("Invalid table specified");
}

// Get document path from database
$query = "SELECT license_document FROM $table WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("User not found");
}

$user = $result->fetch_assoc();

// Check if document exists
if (empty($user['license_document'])) {
    echo "<script>alert('No document found for this user.');</script>";
    exit;
}

// Get the document path
$file_path = $user['license_document'];

// Simply output a script that opens the document in a new tab
// This way, the current page won't change
?>
<!DOCTYPE html>
<html>
<head>
    <title>Document Viewer</title>
    <script>
        // Open the document in a new tab and do nothing else
        window.onload = function() {
            window.open('../../<?php echo $file_path; ?>', '_blank');
            // Redirect back to the previous page after a brief delay
            setTimeout(function() {
                window.location.href = "manage-user-status.php<?php echo isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''; ?>";
            }, 100);
        }
    </script>
</head>
<body>
    <p>Opening document in a new tab...</p>
</body>
</html>
<?php
// Close connection
$stmt->close();
$conn->close();
?>