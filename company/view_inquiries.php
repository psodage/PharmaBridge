<?php
// Start session if not already started
session_start();


// Database connection
require_once '../api/db.php';

// Get company ID from session
$company_id = $_SESSION['user_id'];

// Pagination settings
$results_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $results_per_page;

// Filter by status if provided
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$status_query = "";
if ($status_filter !== '') {
    $status_query = " AND i.status = '$status_filter'";
}

// Get total number of inquiries for pagination
$count_query = "SELECT COUNT(*) as total FROM inquiries i WHERE i.supplier_id = ?$status_query";
$stmt = $conn->prepare($count_query);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_inquiries = $row['total'];
$total_pages = ceil($total_inquiries / $results_per_page);

// Get inquiries with medical user details
$query = "SELECT i.*, m.name as medical_name, m.email as medical_email 
          FROM inquiries i 
          JOIN medical_users m ON i.medical_id = m.id 
          WHERE i.supplier_id = ?$status_query 
          ORDER BY i.created_at DESC
          LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $company_id, $results_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
$inquiries = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Inquiries - BridgeRx Supply Hub</title>
    <link rel="stylesheet" href="../css/home_company.css">
    <style>
        .content {
            padding: 20px;
        }
        
        .inquiry-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 15px;
            margin-bottom: 20px;
            transition: transform 0.2s;
        }
        
        .inquiry-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .inquiry-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        
        .inquiry-subject {
            font-weight: bold;
            font-size: 1.1em;
        }
        
        .inquiry-meta {
            display: flex;
            justify-content: space-between;
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
        }
        
        .inquiry-message {
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .inquiry-reply {
            background: #f9f9f9;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .status-pending {
            background-color: #ffe082;
            color: #856404;
        }
        
        .status-replied {
            background-color: #c8e6c9;
            color: #155724;
        }
        
        .status-closed {
            background-color: #d6d8db;
            color: #383d41;
        }
        
        .filter-bar {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filter-bar select {
            padding: 8px 10px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }
        
        .pagination a, .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
        }
        
        .pagination a:hover {
            background-color: #f5f5f5;
        }
        
        .pagination .active {
            background-color: #4CAF50;
            color: white;
            border-color: #4CAF50;
        }
        
        .action-button {
            padding: 6px 12px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 0.9em;
        }
        
        .action-button:hover {
            background-color: #45a049;
        }
        
        .summary-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            flex: 1;
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #4CAF50;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9em;
        }
        
        .no-inquiries {
            background: #fff;
            padding: 30px;
            text-align: center;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            <span class="logo-icon">💊</span> BridgeRx Supply Hub
        </div>
        <div class="header-controls">
            <button class="header-btn" id="sidebarToggle" title="Toggle Sidebar">☰</button>
            
            <div class="account-info">
                <span>Welcome, <?php echo isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest'; ?></span>
                <div class="account-icon"><?php echo isset($_SESSION['user_name']) ? strtoupper($_SESSION['user_name'][0]) : 'G'; ?></div>
            </div>
        </div>
    </div>
    
    <div class="sidebar" id="sidebar">
        <ul class="menu">
            <li class="menu-item">
                <button class="menu-btn" data-page="company.php">
                    <span class="menu-icon">🏠</span>
                    <span class="menu-text">Home</span>
                </button>
            </li>
            
            <li class="menu-item">
                <button class="menu-btn" onclick="toggleDropdown(this)">
                    <span class="menu-icon">📦</span>
                    <span class="menu-text">Products</span>
                    <span class="dropdown-indicator">▼</span>
                </button>
                <div class="dropdown">
                    <a href="#" class="dropdown-item" data-page="products/add_products.php">Add New <br>Products</a>
                    <a href="#" class="dropdown-item" data-page="products/manage_products.php">Manage <br>Products</a>
                    <a href="#" class="dropdown-item" data-page="products/view_products.php">View Product <br>Listings</a>
                </div>
            </li>
           
            <li class="menu-item">
                <button class="menu-btn" onclick="toggleDropdown(this)">
                    <span class="menu-icon">🛒</span>
                    <span class="menu-text">Orders</span>
                    <span class="dropdown-indicator">▼</span>
                </button>
                <div class="dropdown">
                    <a href="#" class="dropdown-item" data-page="order/view_orders.php">View <br>Orders</a>
                    <a href="#" class="dropdown-item" data-page="order/update_order_status.php">Update Order <br>Status</a>
                 
                </div>
            </li>
            <li class="menu-item">
                <button class="menu-btn active" onclick="toggleDropdown(this)">
                    <span class="menu-icon">💬</span>
                    <span class="menu-text">Inquiries</span>
                    <span class="dropdown-indicator">▼</span>
                </button>
                <div class="dropdown show">
                    <a href="#" class="dropdown-item active" data-page="view_inquiries.php">View Inquiries</a>
                    <a href="#" class="dropdown-item" data-page="inquires.php">Respond to <br>Inquiries</a>
                </div>
            </li>
            <li class="menu-item">
                <button class="menu-btn" onclick="toggleDropdown(this)">
                    <span class="menu-icon">⚙️</span>
                    <span class="menu-text">Settings</span>
                    <span class="dropdown-indicator">▼</span>
                </button>
                <div class="dropdown">
                    <a href="#" class="dropdown-item" data-page="profile.php">Profile <br>Management</a>

                </div>
            </li>
            <li class="menu-item">
                <button class="menu-btn" onclick="toggleDropdown(this)">
                    <span class="menu-icon">🔍</span>
                    <span class="menu-text">Support</span>
                    <span class="dropdown-indicator">▼</span>
                </button>
                <div class="dropdown">
                    <a href="#" class="dropdown-item" data-page="contact_admin.php">Contact Admin</a>
                   
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
        <h1>Inquiries Management</h1>
        
        <?php
        // Get counts for summary stats
        $status_counts = [];
        $status_query = "SELECT status, COUNT(*) as count FROM inquiries WHERE supplier_id = ? GROUP BY status";
        $stmt = $conn->prepare($status_query);
        $stmt->bind_param("i", $company_id);
        $stmt->execute();
        $status_result = $stmt->get_result();
        
        while ($status_row = $status_result->fetch_assoc()) {
            $status_counts[$status_row['status']] = $status_row['count'];
        }
        
        $pending_count = isset($status_counts['pending']) ? $status_counts['pending'] : 0;
        $replied_count = isset($status_counts['replied']) ? $status_counts['replied'] : 0;
        $closed_count = isset($status_counts['closed']) ? $status_counts['closed'] : 0;
        ?>
        
        <!-- Summary Statistics -->
        <div class="summary-stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_inquiries; ?></div>
                <div class="stat-label">Total Inquiries</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $pending_count; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $replied_count; ?></div>
                <div class="stat-label">Replied</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $closed_count; ?></div>
                <div class="stat-label">Closed</div>
            </div>
        </div>
        
        <!-- Filter Bar -->
        <div class="filter-bar">
            <form action="" method="get" id="filter-form">
                <label for="status">Filter by Status:</label>
                <select name="status" id="status" onchange="document.getElementById('filter-form').submit()">
                    <option value="">All Inquiries</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="replied" <?php echo $status_filter === 'replied' ? 'selected' : ''; ?>>Replied</option>
                    <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                </select>
            </form>
        </div>
        
        <?php if (count($inquiries) > 0): ?>
            <?php foreach ($inquiries as $inquiry): ?>
                <div class="inquiry-card">
                    <div class="inquiry-header">
                        <div class="inquiry-subject"><?php echo htmlspecialchars($inquiry['subject']); ?></div>
                        <span class="status-badge status-<?php echo $inquiry['status']; ?>">
                            <?php echo ucfirst($inquiry['status']); ?>
                        </span>
                    </div>
                    <div class="inquiry-meta">
                        <div>From: <?php echo htmlspecialchars($inquiry['medical_name']); ?> (<?php echo htmlspecialchars($inquiry['medical_email']); ?>)</div>
                        <div>Date: <?php echo date('M j, Y, g:i a', strtotime($inquiry['created_at'])); ?></div>
                    </div>
                    <div class="inquiry-message">
                        <?php echo nl2br(htmlspecialchars($inquiry['message'])); ?>
                    </div>
                    
                    <?php if (!empty($inquiry['reply'])): ?>
                        <div class="inquiry-reply">
                            <strong>Your Reply (<?php echo date('M j, Y, g:i a', strtotime($inquiry['reply_at'])); ?>):</strong>
                            <div><?php echo nl2br(htmlspecialchars($inquiry['reply'])); ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <div style="text-align: right; margin-top: 10px;">
                        <?php if ($inquiry['status'] === 'pending'): ?>
                            <a href="inquires.php?id=<?php echo $inquiry['id']; ?>" class="action-button">Reply</a>
                        <?php elseif ($inquiry['status'] === 'replied'): ?>
                            <a href="inquires.php?id=<?php echo $inquiry['id']; ?>" class="action-button">Update Reply</a>
                            <a href="close_inquiry.php?id=<?php echo $inquiry['id']; ?>" class="action-button" style="background-color: #6c757d;" onclick="return confirm('Are you sure you want to close this inquiry?')">Close Inquiry</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($current_page > 1): ?>
                        <a href="?page=<?php echo $current_page - 1; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>">Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $current_page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($current_page < $total_pages): ?>
                        <a href="?page=<?php echo $current_page + 1; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>">Next</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="no-inquiries">
                <h3>No inquiries found</h3>
                <p>There are no inquiries matching your current filter criteria.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Main JavaScript for Company Dashboard
        
        // On page load, initialize the dashboard
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize event listeners
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