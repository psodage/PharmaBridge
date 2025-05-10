<?php 
session_start();
require_once('../api/db.php');

// Check authentication and user type
if (!isset($_SESSION['user_name']) || !isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'medical') { 
    header("Location: ../../signin.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// Handle message reply submission
if (isset($_POST['submit_reply'])) {
    $inquiry_id = $_POST['inquiry_id'];
    $reply = $_POST['reply'];
    
    // Update with reply message from medical user (in this case, we're storing any follow-up in the notes field)
    $stmt = $conn->prepare("UPDATE inquiries SET message = CONCAT(message, '\n\nFollow-up (', NOW(), '): ', ?) WHERE id = ? AND medical_id = ?");
    $stmt->bind_param("sii", $reply, $inquiry_id, $user_id);
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Your follow-up message has been sent successfully.</div>';
    } else {
        $message = '<div class="alert alert-danger">Failed to send follow-up message. Please try again.</div>';
    }
}

// Get all inquiries for this medical user
$stmt = $conn->prepare("
    SELECT i.*, c.name as supplier_name
    FROM inquiries i
    JOIN company_users c ON i.supplier_id = c.id
    WHERE i.medical_id = ?
    ORDER BY i.created_at DESC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View & Respond to Messages - BridgeRx Connect</title>
    <link rel="stylesheet" href="../css/medical_nav.css">
    <style>
 /* Updated Styles for Modernized Landscape UI */
 body {
    font-family: 'Roboto', 'Segoe UI', sans-serif;
    background-color: #f5f7fa;
    margin: 0;
    padding: 0;
    color: #333;
}

/* Header Styling */

.logo {
    display: flex;
    align-items: center;
    font-weight: 700;
    font-size: 1.5rem;
    letter-spacing: 0.5px;
}

.logo-icon {
    margin-right: 10px;
    font-size: 1.6rem;
}

/* Content Container - Landscape Orientation */
.content-container {
    margin-left:275px;
    padding: 20px;
    margin-top: 60px;
    transition: all 0.3s ease;
    min-height: calc(100vh - 60px);
    box-sizing: border-box;
    width: 85%;
}

/* Messages Container - Landscape Layout */
.messages-container {
    max-width: 100%;
    margin: 0 auto;
}

/* Responsive Grid Layout for Messages */
.messages-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(450px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.page-title {
    color: #2c3e50;
    margin-bottom: 25px;
    font-size: 1.8rem;
    font-weight: 600;
    position: relative;
    padding-bottom: 10px;
}

.page-title:after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 50px;
    height: 3px;
    background: #2563eb;
}

/* Message Card Styling */
.message-card {
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    margin-bottom: 20px;
    padding: 25px;
    border-left: 5px solid #3498db;
    transition: transform 0.2s, box-shadow 0.2s;
}

.message-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

.message-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
    padding-bottom: 12px;
    border-bottom: 1px solid #eee;
}

.message-subject {
    font-size: 1.2em;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 5px;
}

.message-supplier {
    font-weight: 500;
    color: #64748b;
}

.message-date {
    color: #94a3b8;
    font-size: 0.85em;
}

.message-content {
    margin-bottom: 20px;
    line-height: 1.6;
    white-space: pre-line;
    color: #4b5563;
}

.message-reply {
    padding: 15px 20px;
    background-color: #f8fafc;
    border-radius: 8px;
    margin-top: 15px;
    border-left: 3px solid #10b981;
}

.reply-label {
    font-weight: 600;
    color: #10b981;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
}

.reply-label:before {
    content: '↩';
    margin-right: 5px;
    font-size: 1.1em;
}

.reply-form {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.reply-textarea {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    min-height: 100px;
    margin-bottom: 15px;
    font-family: inherit;
    resize: vertical;
    transition: border-color 0.2s;
}

.reply-textarea:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75em;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-left: 10px;
}

.status-pending {
    background-color: #fbbf24;
    color: #7c2d12;
}

.status-replied {
    background-color: #10b981;
    color: #064e3b;
}

.status-closed {
    background-color: #94a3b8;
    color: #334155;
}

.btn {
    display: inline-block;
    padding: 10px 20px;
    background-color: #3b82f6;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9em;
    font-weight: 500;
    transition: all 0.2s;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.btn:hover {
    background-color: #2563eb;
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.alert {
    padding: 15px 20px;
    margin-bottom: 20px;
    border-radius: 8px;
    position: relative;
    animation: fadeIn 0.5s;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.alert-success {
    background-color: #d1fae5;
    color: #065f46;
    border-left: 4px solid #10b981;
}

.alert-danger {
    background-color: #fee2e2;
    color: #991b1b;
    border-left: 4px solid #ef4444;
}

.no-messages {
    padding: 40px;
    text-align: center;
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    color: #64748b;
}

.no-messages h3 {
    color: #334155;
    font-size: 1.4em;
    margin-bottom: 10px;
}

/* Account Info Styling */
.account-info {
    display: flex;
    align-items: center;
}

.account-icon {
    width: 36px;
    height: 36px;
    background-color: white;
    color: #2563eb;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-left: 10px;
}

/* Media Queries for Responsiveness */
@media (max-width: 992px) {
    .messages-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .content-container {
        margin-left: 0;
        width: 100%;
    }
    
    .header-btn {
        display: block;
    }
    
    .message-header {
        flex-direction: column;
    }
    
    .message-header > div:last-child {
        margin-top: 10px;
    }
}
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            <span class="logo-icon">🏥</span> BridgeRx Connect
        </div>
        <div class="header-controls">
            <button class="header-btn" id="sidebarToggle" title="Toggle Sidebar">☰</button>
            <div class="account-info">
                <span>Welcome, <?php echo isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest'; ?></span>
                <div class="account-icon"><?php echo isset($_SESSION['user_name']) ? strtoupper($_SESSION['user_name'][0]) : 'A'; ?></div>
            </div>
        </div>
    </div>
    
    <div class="sidebar" id="sidebar">
    <ul class="menu">
      <li class="menu-item">
        <button class="menu-btn " data-page="medical.php">
          <span class="menu-icon">🏠</span>
          <span class="menu-text">Home</span>
        </button>
      </li>
      
      <li class="menu-item">
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">🏢</span>
          <span class="menu-text">Suppliers</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item" data-page="view_company.php">Browse Pharmaceutical Companies</a>
          <a href="#" class="dropdown-item" data-page="view_supplier.php">View Supplier <br>Details</a>
        </div>
      </li>
     
      <li class="menu-item">
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">💊</span>
          <span class="menu-text">Products</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item" data-page="view_product.php">Search & Browse <br>Products</a>
        </div>
      </li>
      <li class="menu-item">
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">📋</span>
          <span class="menu-text">Order Oversight</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item" data-page="place_order.php">Place New Order</a>
          <a href="#" class="dropdown-item" data-page="track_order.php">Track Order Status</a>
          <a href="#" class="dropdown-item" data-page="view_history.php">View Order History</a>
        </div>
      </li>
      <li class="menu-item">
        <button class="menu-btn active" onclick="toggleDropdown(this)">
          <span class="menu-icon">💬</span>
          <span class="menu-text">Communication</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item" data-page="send_inquiries.php">Send Inquiries <br>to Suppliers</a>
          <a href="#" class="dropdown-item active" data-page="view_messages.php">View & Respond <br>to Messages</a>
        
        </div>
      </li>
      <li class="menu-item">
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">⚙️</span>
          <span class="menu-text">Settings</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item" data-page="profile.php">Profile Management</a>
      
        </div>
      </li>
      <li class="menu-item">
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">🔍</span>
          <span class="menu-text">Support</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item" data-page="admin.php">Contact Admin</a>
      
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
    
    <div class="content-container" id="content">
        <div class="messages-container">
            <h1 class="page-title">View & Respond to Messages</h1>
            
            <?php echo $message; ?>
            
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="message-card">
                        <div class="message-header">
                            <div>
                                <div class="message-subject"><?php echo htmlspecialchars($row['subject']); ?></div>
                                <div class="message-supplier">To: <?php echo htmlspecialchars($row['supplier_name']); ?></div>
                            </div>
                            <div>
                                <span class="message-date"><?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?></span>
                                <span class="status-badge status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span>
                            </div>
                        </div>
                        
                        <div class="message-content">
                            <?php echo nl2br(htmlspecialchars($row['message'])); ?>
                        </div>
                        
                        <?php if ($row['reply']): ?>
                            <div class="message-reply">
                                <div class="reply-label">Supplier Reply:</div>
                                <div><?php echo nl2br(htmlspecialchars($row['reply'])); ?></div>
                                <div class="message-date">Replied on: <?php echo date('M d, Y h:i A', strtotime($row['reply_at'])); ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($row['status'] !== 'closed'): ?>
                            <div class="reply-form">
                                <form method="post" action="">
                                    <input type="hidden" name="inquiry_id" value="<?php echo $row['id']; ?>">
                                    <textarea name="reply" class="reply-textarea" placeholder="Add a follow-up message or additional information..."></textarea>
                                    <button type="submit" name="submit_reply" class="btn">Send Follow-up</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-messages">
                    <h3>No messages found</h3>
                    <p>You haven't sent any inquiries to suppliers yet.</p>
                    <a href="send_inquiries.php" class="btn">Send a New Inquiry</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Main JavaScript for Dashboard
        
        // On page load, initialize the dashboard
        document.addEventListener('DOMContentLoaded', function() {
            initializeEventListeners();
        });
        
        // Initialize all event listeners for the dashboard
        function initializeEventListeners() {
            // Toggle sidebar
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
            
            // Handle menu buttons and dropdown items clicks using event delegation
            document.addEventListener('click', function(event) {
                // For main menu buttons
                if (event.target.classList.contains('menu-btn') && !event.target.hasAttribute('onclick')) {
                    const page = event.target.getAttribute('data-page');
                    if (page) {
                        window.location.href = page;
                    }
                }
                
                // For dropdown items
                if (event.target.classList.contains('dropdown-item')) {
                    event.preventDefault();
                    const page = event.target.getAttribute('data-page');
                    if (page) {
                        window.location.href = page;
                    }
                }
            });
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('sidebar-collapsed');
                    content.classList.remove('content-full');
                    sidebar.classList.remove('show');
                }
            });
        }
        
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