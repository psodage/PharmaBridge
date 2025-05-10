<?php
// Database connection
$servername = "localhost";
$username = "root"; // Default XAMPP username
$password = ""; // Default XAMPP password
$dbname = "pharma";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle responses to messages
if (isset($_POST['submit_response'])) {
    $message_id = $_POST['message_id'];
    $admin_id = 1; // Assuming admin ID is 1, you should get this from session
    $admin_name = "System Admin"; // You should get this from session
    $response = $_POST['response'];
    
    // Insert response
    $insert_response = "INSERT INTO admin_responses_company (message_id, admin_id, admin_name, response, date_sent) 
                         VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($insert_response);
    $stmt->bind_param("iiss", $message_id, $admin_id, $admin_name, $response);
    
    if ($stmt->execute()) {
        // Update message status
        $update_status = "UPDATE admin_messages_company SET status = 'responded', last_updated = NOW() WHERE message_id = ?";
        $stmt = $conn->prepare($update_status);
        $stmt->bind_param("i", $message_id);
        $stmt->execute();
        
        // Success message
        $success_message = "Response sent successfully!";
    } else {
        // Error message
        $error_message = "Failed to send response: " . $conn->error;
    }
}

// Handle status changes
if (isset($_GET['action']) && isset($_GET['id'])) {
    $message_id = $_GET['id'];
    $action = $_GET['action'];
    
    if ($action == 'mark_read') {
        $update = "UPDATE admin_messages_company SET status = 'read', last_updated = NOW() WHERE message_id = ?";
        $stmt = $conn->prepare($update);
        $stmt->bind_param("i", $message_id);
        if ($stmt->execute()) {
            $status_message = "Message marked as read.";
        }
    } elseif ($action == 'mark_unread') {
        $update = "UPDATE admin_messages_company SET status = 'unread', last_updated = NOW() WHERE message_id = ?";
        $stmt = $conn->prepare($update);
        $stmt->bind_param("i", $message_id);
        if ($stmt->execute()) {
            $status_message = "Message marked as unread.";
        }
    }
}

// Pagination setup
$results_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $results_per_page;

// Filter setup
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$priority_filter = isset($_GET['priority']) ? (int)$_GET['priority'] : 0;
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query conditions
$conditions = [];
$params = [];
$types = '';

if ($status_filter) {
    $conditions[] = "status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($priority_filter > 0) {
    $conditions[] = "priority = ?";
    $params[] = $priority_filter;
    $types .= 'i';
}

if ($search) {
    $conditions[] = "(sender_name LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

// Build the final query
$where_clause = !empty($conditions) ? " WHERE " . implode(" AND ", $conditions) : "";

// Count total results for pagination
$count_query = "SELECT COUNT(*) as total FROM admin_messages_company" . $where_clause;
$stmt = $conn->prepare($count_query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$total_results = $result->fetch_assoc()['total'];
$total_pages = ceil($total_results / $results_per_page);

// Get the messages with pagination
$query = "SELECT * FROM admin_messages_company" . $where_clause . 
         " ORDER BY CASE WHEN status = 'unread' THEN 0 WHEN status = 'read' THEN 1 ELSE 2 END, 
          priority ASC, date_sent DESC LIMIT ? OFFSET ?";
$params[] = $results_per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$messages = $stmt->get_result();

// Get responses for all messages on this page to avoid n+1 query problem
$message_ids = [];
$message_data = [];

while ($row = $messages->fetch_assoc()) {
    $message_ids[] = $row['message_id'];
    $message_data[$row['message_id']] = $row;
}

// Reset the result pointer
$messages->data_seek(0);

// Get responses if we have messages
$responses_data = [];
if (!empty($message_ids)) {
    $placeholders = str_repeat('?,', count($message_ids) - 1) . '?';
    $responses_query = "SELECT * FROM admin_responses_company WHERE message_id IN ($placeholders) ORDER BY date_sent ASC";
    
    $stmt = $conn->prepare($responses_query);
    
    $types = str_repeat('i', count($message_ids));
    $stmt->bind_param($types, ...$message_ids);
    
    $stmt->execute();
    $responses = $stmt->get_result();
    
    while ($response = $responses->fetch_assoc()) {
        if (!isset($responses_data[$response['message_id']])) {
            $responses_data[$response['message_id']] = [];
        }
        $responses_data[$response['message_id']][] = $response;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Handle Complaints & Disputes - BridgeRx Admin</title>
  <link rel="stylesheet" href="../css/admin.css">
  <style>
/* Enhanced styles for the complaints page */
.messages-container {
  display: flex;
  height: calc(100vh - 180px);
  box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
  border-radius: 8px;
  overflow: hidden;
  margin-bottom: 20px;
}

.message-list {
  width: 40%;
  overflow-y: auto;
  border-right: 1px solid #e0e0e0;
  background-color: #f9fafb;
  transition: all 0.3s ease;
}

.message-detail {
  width: 60%;
  padding: 20px;
  overflow-y: auto;
  background-color: #ffffff;
  transition: all 0.3s ease;
}

.message-item {
  padding: 15px;
  border-bottom: 1px solid #eaeaea;
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
}

.message-item:hover {
  background-color: #f0f4f8;
}

.message-item.selected {
  background-color: #e3f2fd;
  border-left: 4px solid #2196f3;
}

.message-item.unread {
  font-weight: 600;
  border-left: 4px solid #3498db;
  background-color: rgba(52, 152, 219, 0.05);
}

.message-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.message-sender {
  font-weight: 600;
  color: #2c3e50;
}

.message-date {
  color: #7f8c8d;
  font-size: 0.8em;
  font-weight: 500;
}

.message-subject {
  margin-bottom: 10px;
  font-size: 1.05em;
  color: #34495e;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 5px;
}

.message-excerpt {
  color: #7f8c8d;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-size: 0.9em;
  line-height: 1.5;
}

.priority-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 3px 8px;
  border-radius: 20px;
  font-size: 0.75em;
  font-weight: 600;
  letter-spacing: 0.3px;
  margin-right: 5px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.priority-1 {
  background-color: #e74c3c;
  color: white;
}

.priority-2 {
  background-color: #f39c12;
  color: white;
}

.priority-3 {
  background-color: #3498db;
  color: white;
}

.priority-4 {
  background-color: #2ecc71;
  color: white;
}

.priority-5 {
  background-color: #95a5a6;
  color: white;
}

.message-content {
  background-color: #f8f9fa;
  padding: 20px;
  border-radius: 8px;
  margin-bottom: 25px;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
  line-height: 1.6;
  color: #343a40;
  border-left: 4px solid #3498db;
}

.response-form {
  background-color: #f8f9fa;
  padding: 20px;
  border-radius: 8px;
  margin-top: 25px;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;
}

.response-form:hover {
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
}

.response-form h3 {
  margin-top: 0;
  color: #2c3e50;
  font-size: 1.2em;
  margin-bottom: 15px;
}

.response-form textarea {
  width: 100%;
  min-height: 120px;
  margin-bottom: 15px;
  padding: 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-family: inherit;
  resize: vertical;
  transition: border-color 0.3s;
}

.response-form textarea:focus {
  border-color: #3498db;
  outline: none;
  box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
}

.response-form button {
  background-color: #3498db;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.3s;
}

.response-form button:hover {
  background-color: #2980b9;
}

.response-list {
  margin-top: 25px;
}

.response-item {
  background-color: #f0f7fc;
  padding: 15px;
  border-radius: 8px;
  margin-bottom: 15px;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
  border-left: 3px solid #3498db;
}

.response-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 10px;
  font-size: 0.85em;
  color: #586069;
  border-bottom: 1px solid #e1e4e8;
  padding-bottom: 8px;
}

.response-content {
  line-height: 1.6;
  color: #24292e;
}

.filters {
  display: flex;
  margin-bottom: 25px;
  background-color: #f8f9fa;
  padding: 15px;
  border-radius: 8px;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
}

.filters form {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  width: 100%;
  align-items: center;
}

.filters select, .filters input {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 0.9em;
}

.filters select:focus, .filters input:focus {
  border-color: #3498db;
  outline: none;
  box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
}

.filters button {
  padding: 8px 15px;
  background-color: #3498db;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  transition: background-color 0.3s;
}

.filters button:hover {
  background-color: #2980b9;
}

.filters button[type="button"] {
  background-color: #95a5a6;
}

.filters button[type="button"]:hover {
  background-color: #7f8c8d;
}

.pagination {
  margin-top: 30px;
  text-align: center;
  padding: 10px 0;
}

.pagination a, .pagination span {
  display: inline-block;
  padding: 8px 15px;
  margin: 0 3px;
  border: 1px solid #ddd;
  border-radius: 4px;
  text-decoration: none;
  color: #3498db;
  transition: all 0.3s;
}

.pagination a:hover {
  background-color: #f2f8fd;
  border-color: #3498db;
}

.pagination .current-page {
  background-color: #3498db;
  color: white;
  border-color: #3498db;
  font-weight: 600;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 3px 8px;
  border-radius: 20px;
  font-size: 0.75em;
  font-weight: 600;
  letter-spacing: 0.3px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.status-unread {
  background-color: #e74c3c;
  color: white;
}

.status-read {
  background-color: #f39c12;
  color: white;
}

.status-responded {
  background-color: #2ecc71;
  color: white;
}

.message-actions {
  margin-bottom: 20px;
  padding: 10px 0;
  border-bottom: 1px solid #eee;
}

.message-actions a {
  margin-right: 15px;
  text-decoration: none;
  color: #3498db;
  font-weight: 500;
  transition: color 0.3s;
  display: inline-flex;
  align-items: center;
}

.message-actions a:hover {
  color: #2980b9;
}

.message-actions a::before {
  content: '';
  display: inline-block;
  width: 6px;
  height: 6px;
  background-color: #3498db;
  border-radius: 50%;
  margin-right: 5px;
}

.alert {
  padding: 15px;
  margin-bottom: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
  animation: fadeIn 0.5s;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}

.alert-success {
  background-color: #d4edda;
  color: #155724;
  border-left: 4px solid #28a745;
}

.alert-error {
  background-color: #f8d7da;
  color: #721c24;
  border-left: 4px solid #dc3545;
}

.alert-info {
  background-color: #d1ecf1;
  color: #0c5460;
  border-left: 4px solid #17a2b8;
}

.no-messages {
  text-align: center;
  padding: 60px 0;
  color: #6c757d;
}

.no-messages p {
  font-size: 1.1em;
  margin-bottom: 15px;
}

.message-info {
  background-color: #f8f9fa;
  padding: 15px;
  border-radius: 8px;
  margin-bottom: 20px;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
}

.message-info p {
  margin: 5px 0;
  color: #2c3e50;
}

h2 {
  color: #2c3e50;
  margin-bottom: 20px;
  font-weight: 600;
  border-bottom: 2px solid #3498db;
  padding-bottom: 10px;
  display: inline-block;
}

h3 {
  color: #34495e;
  margin-bottom: 15px;
  font-weight: 600;
}

/* Responsive adjustments */
@media (max-width: 992px) {
  .messages-container {
    flex-direction: column;
    height: auto;
  }
  
  .message-list, .message-detail {
    width: 100%;
    max-height: 50vh;
  }
  
  .message-list {
    border-right: none;
    border-bottom: 1px solid #e0e0e0;
  }
}

@media (max-width: 768px) {
  .filters form {
    flex-direction: column;
    align-items: stretch;
  }
  
  .filters button {
    margin-top: 10px;
  }
}

/* Enhanced scrollbar */
::-webkit-scrollbar {
  width: 10px;
  height: 10px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

/* Animation for message selection */
.message-item {
  transition: transform 0.2s ease;
}

.message-item:active {
  transform: scale(0.99);
}

/* Better focus styles */
button:focus, a:focus, input:focus, select:focus, textarea:focus {
  outline: 2px solid rgba(52, 152, 219, 0.5);
  outline-offset: 2px;
}

/* Content area enhancement */
.content {
  background-color: #f8f9fa;
  padding: 20px;
  transition: all 0.3s ease;
}

.content-header {
  margin-bottom: 25px;
  border-bottom: 1px solid #e0e0e0;
  padding-bottom: 15px;
}

.content-title {
  color: #2c3e50;
  font-weight: 600;
  margin: 0;
  font-size: 1.5em;
}
  </style>
</head>
<body>
  <div class="header">
    <div class="logo">
      <span class="logo-icon">⚕️</span> BridgeRx Admin
    </div>
    <div class="header-controls">
      <button class="header-btn" id="sidebarToggle" title="Toggle Sidebar">☰</button>
      <button class="header-btn" title="Notifications">
        🔔
        <span class="alert-badge"><?php echo isset($pending_verifications) ? $pending_verifications : 0; ?></span>
      </button>
      
      <div class="account-info">
        <span>System Admin</span>
        <div class="account-icon">A</div>
      </div>
    </div>
  </div>
  
  <div class="sidebar" id="sidebar">
    <ul class="menu">
      <li class="menu-item">
        <button class="menu-btn" data-page="home.php">
          <span class="menu-icon">📊</span>
          <span class="menu-text">Home</span>
        </button>
      </li>
      
      <li class="menu-item">
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">👥</span>
          <span class="menu-text">User Management</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="user_management/manage-pharma-companies.php" class="dropdown-item" data-page="user_management/manage-pharma-companies.php">View & Manage Pharmaceutical Companies</a>
          <a href="user_management/manage-medical-stores.php" class="dropdown-item" data-page="user_management/manage-medical-stores.php">View & Manage Medical Stores</a>
          <a href="user_management/manage-user-status.php" class="dropdown-item" data-page="user_management/manage-user-status.php">Approve / Suspend / Remove Users</a>
        </div>
      </li>

      <li class="menu-item">
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">📦</span>
          <span class="menu-text">Product & Order Oversight</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="monitor-products.php" class="dropdown-item" data-page="monitor-products.php">Monitor Listed Products</a>
        
          <a href="review-orders.php" class="dropdown-item" data-page="review-orders.php">Review Order Activities</a>
        </div>
      </li>

      <li class="menu-item">
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">⚙️</span>
          <span class="menu-text">Settings</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="admin-profile.php" class="dropdown-item" data-page="admin-profile.php">Admin Profile Management</a>
        
        </div>
      </li>
      <li class="menu-item">
        <button class="menu-btn active" onclick="toggleDropdown(this)">
          <span class="menu-icon">🛡️</span>
          <span class="menu-text">Support & Security</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown show">
        <a href="handle-complaints_medical.php" class="dropdown-item" data-page="handle-complaints_medical.php">Handle Complaints From Medical</a>
          <a href="handle-complaints_company.php" class="dropdown-item active" data-page="handle-complaints_company.php">Handle Complaints From Company</a>

        </div>
      </li>
      <li class="menu-item">
        <button class="menu-btn" onclick="logout()">
          <span class="menu-icon">🚪</span>
          <span class="menu-text">Logout</span>
        </button>
      </li>
    </ul>
  </div>
  
  <div class="content" id="content">
    <div class="content-header">
      <h1 class="content-title">Handle Complaints & Disputes</h1>
    </div>
    
    <?php if (isset($success_message)): ?>
    <div class="alert alert-success">
      <?php echo $success_message; ?>
    </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
    <div class="alert alert-error">
      <?php echo $error_message; ?>
    </div>
    <?php endif; ?>
    
    <?php if (isset($status_message)): ?>
    <div class="alert alert-info">
      <?php echo $status_message; ?>
    </div>
    <?php endif; ?>
    
    <div class="filters">
      <form action="" method="get" id="filter-form">
        <select name="status" onchange="document.getElementById('filter-form').submit()">
          <option value="">All Status</option>
          <option value="unread" <?php echo $status_filter == 'unread' ? 'selected' : ''; ?>>Unread</option>
          <option value="read" <?php echo $status_filter == 'read' ? 'selected' : ''; ?>>Read</option>
          <option value="responded" <?php echo $status_filter == 'responded' ? 'selected' : ''; ?>>Responded</option>
        </select>
        
        <select name="priority" onchange="document.getElementById('filter-form').submit()">
          <option value="0">All Priorities</option>
          <option value="1" <?php echo $priority_filter == 1 ? 'selected' : ''; ?>>High</option>
          <option value="2" <?php echo $priority_filter == 2 ? 'selected' : ''; ?>>Medium</option>
          <option value="3" <?php echo $priority_filter == 3 ? 'selected' : ''; ?>>Low</option>
        </select>
        
        <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit">Filter</button>
        <button type="button" onclick="window.location.href='handle-complaints.php'">Reset</button>
      </form>
    </div>
    
    <div class="messages-container">
      <div class="message-list">
        <?php if ($messages->num_rows > 0): ?>
          <?php while ($message = $messages->fetch_assoc()): ?>
            <div class="message-item <?php echo $message['status'] == 'unread' ? 'unread' : ''; ?>" 
                 onclick="showMessageDetail('<?php echo $message['message_id']; ?>')">
              <div class="message-header">
                <span class="message-sender"><?php echo htmlspecialchars($message['sender_name']); ?></span>
                <span class="message-date"><?php echo date('M d, Y H:i', strtotime($message['date_sent'])); ?></span>
              </div>
              <div class="message-subject">
                <span class="priority-badge priority-<?php echo $message['priority']; ?>">
                  <?php 
                    switch($message['priority']) {
                      case 1: echo "High"; break;
                      case 2: echo "Medium"; break;
                      case 3: echo "Low"; break;
                      default: echo "Normal";
                    } 
                  ?>
                </span>
                <span class="status-badge status-<?php echo $message['status']; ?>">
                  <?php echo ucfirst($message['status']); ?>
                </span>
                <?php echo htmlspecialchars($message['subject']); ?>
              </div>
              <div class="message-excerpt"><?php echo htmlspecialchars(substr($message['message'], 0, 100) . (strlen($message['message']) > 100 ? '...' : '')); ?></div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <div class="no-messages">
            <p>No messages found.</p>
          </div>
        <?php endif; ?>
      </div>
      
      <div class="message-detail" id="message-detail">
        <div id="detail-content">
          <p>Select a message to view details.</p>
        </div>
      </div>
    </div>
    
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
      <?php if ($page > 1): ?>
        <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&priority=<?php echo $priority_filter; ?>&search=<?php echo urlencode($search); ?>">&laquo; Previous</a>
      <?php endif; ?>
      
      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <?php if ($i == $page): ?>
          <span class="current-page"><?php echo $i; ?></span>
        <?php else: ?>
          <a href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&priority=<?php echo $priority_filter; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
        <?php endif; ?>
      <?php endfor; ?>
      
      <?php if ($page < $total_pages): ?>
        <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&priority=<?php echo $priority_filter; ?>&search=<?php echo urlencode($search); ?>">Next &raquo;</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
  
  <script>
    // JavaScript to handle the message selection and display
    function showMessageDetail(messageId) {
      // Get the messages
      const messages = <?php echo json_encode($message_data); ?>;
      const responses = <?php echo json_encode($responses_data); ?>;
      
      // Find the selected message
      const message = messages[messageId];
      
      if (!message) {
        return;
      }
      
      // Highlight the selected message
      const messageItems = document.querySelectorAll('.message-item');
      messageItems.forEach(item => {
        item.classList.remove('selected');
      });
      
      event.currentTarget.classList.add('selected');
      
      // Format the date
      const date = new Date(message.date_sent);
      const formattedDate = date.toLocaleString();
      
      // Get priority label
      let priorityLabel = "Normal";
      switch(message.priority) {
        case "1": priorityLabel = "High"; break;
        case "2": priorityLabel = "Medium"; break;
        case "3": priorityLabel = "Low"; break;
      }
      
      // Build the detail content
      let detailHTML = `
        <div class="message-actions">
          ${message.status === 'unread' ? 
            `<a href="?action=mark_read&id=${messageId}${window.location.search.replace(/^\?/, '&')}">Mark as Read</a>` : 
            `<a href="?action=mark_unread&id=${messageId}${window.location.search.replace(/^\?/, '&')}">Mark as Unread</a>`
          }
        </div>
        
        <h2>Message: ${message.subject}</h2>
        
        <div class="message-info">
          <p><strong>From:</strong> ${message.sender_name} (ID: ${message.sender_id})</p>
          <p><strong>Date:</strong> ${formattedDate}</p>
          <p><strong>Priority:</strong> <span class="priority-badge priority-${message.priority}">${priorityLabel}</span></p>
          <p><strong>Status:</strong> <span class="status-badge status-${message.status}">${message.status.charAt(0).toUpperCase() + message.status.slice(1)}</span></p>
        </div>
        
        <div class="message-content">
          ${message.message.replace(/\n/g, '<br>')}
        </div>
      `;
      
      // Add responses if they exist
      if (responses[messageId] && responses[messageId].length > 0) {
        detailHTML += '<div class="response-list"><h3>Responses</h3>';
        
        responses[messageId].forEach(response => {
          const responseDate = new Date(response.date_sent);
          const formattedResponseDate = responseDate.toLocaleString();
          
          detailHTML += `
            <div class="response-item">
              <div class="response-header">
                <span>From: ${response.admin_name}</span>
                <span>${formattedResponseDate}</span>
              </div>
              <div class="response-content">
                ${response.response.replace(/\n/g, '<br>')}
              </div>
            </div>
          `;
        });
        
        detailHTML += '</div>';
      }
      
      // Add response form if status is not 'responded'
      if (message.status !== 'responded') {
        detailHTML += `
          <div class="response-form">
            <h3>Respond to this Message</h3>
            <form action="" method="post">
              <input type="hidden" name="message_id" value="${messageId}">
              <textarea name="response" placeholder="Type your response here..." required></textarea>
              <button type="submit" name="submit_response">Send Response</button>
            </form>
          </div>
        `;
      }
      
      // Set the HTML
      document.getElementById('detail-content').innerHTML = detailHTML;
    }
    
    // Initialize the sidebar toggle
    document.addEventListener('DOMContentLoaded', function() {
      const sidebarToggle = document.getElementById('sidebarToggle');
      const sidebar = document.getElementById('sidebar');
      const content = document.getElementById('content');
      
      sidebarToggle.addEventListener('click', function() {
        if (window.innerWidth <= 768) {
          sidebar.classList.toggle('show');
        } else {
          sidebar.classList.toggle('sidebar-collapsed');
          content.classList.toggle('content-full');
        }
      });
    });
    
    // Toggle dropdown menus
    function toggleDropdown(button) {
      const dropdown = button.nextElementSibling;
      const arrow = button.querySelector('.dropdown-indicator');
      const sidebar = document.getElementById('sidebar');
      
      if (sidebar.classList.contains('sidebar-collapsed') && window.innerWidth > 768) {
        return; // Don't toggle dropdowns in collapsed mode on desktop
      }
      
      const dropdowns = document.querySelectorAll('.dropdown');
      dropdowns.forEach(function(item) {
        if (item !== dropdown && item.classList.contains('show')) {
          item.classList.remove('show');
          item.previousElementSibling.querySelector('.dropdown-indicator').style.transform = 'rotate(0deg)';
        }
      });
      
      dropdown.classList.toggle('show');
      if (dropdown.classList.contains('show')) {
        arrow.style.transform = 'rotate(180deg)';
      } else {
        arrow.style.transform = 'rotate(0deg)';
      }
    }
    
    // Logout function
    function logout() {
      if (confirm("Are you sure you want to log out?")) {
        window.location.href = "../logout.php";
      }
    }
  </script>
</body>
</html>
<?php
// Close the database connection
$conn->close();
?>