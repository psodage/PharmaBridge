<?php 
session_start();
require_once('../api/db.php');
if (!isset($pdo)) {
  try {
      // Define database connection parameters - adjust these based on your actual settings
      $host = 'localhost';
      $dbname = 'pharma'; // Replace with your actual database name
      $username = 'root';     // Replace with your actual database username
      $password = '';         // Replace with your actual database password
      
      // Create PDO instance
      $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  } catch(PDOException $e) {
      die("Database connection failed: " . $e->getMessage());
  }
}
if (!isset($_SESSION['user_name']) || !isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'medical') { 
    header("Location: ../../signin.php");
    exit();
}

// Initialize variables
$search_query = "";
$companies = [];
$error_message = "";

// Process search if submitted
if (isset($_GET['search'])) {
    $search_query = trim($_GET['search']);
}

// Fetch companies with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

try {
    // Prepare the base query
    $query = "SELECT id, name, email, license_number, created_at, approval_status FROM company_users WHERE approval_status = 'approved'";
    $count_query = "SELECT COUNT(*) as total FROM company_users WHERE approval_status = 'approved'";
    
    // Add search condition if search query exists
    if (!empty($search_query)) {
        $search_param = "%{$search_query}%";
        $query .= " AND (name LIKE ? OR email LIKE ? OR license_number LIKE ?)";
        $count_query .= " AND (name LIKE ? OR email LIKE ? OR license_number LIKE ?)";
        
        // Get total count with search
        $count_stmt = $pdo->prepare($count_query);
        $count_stmt->execute([$search_param, $search_param, $search_param]);
        
        // Get companies with search - FIX: Use bindValue with explicit types
        $stmt = $pdo->prepare($query . " ORDER BY name ASC LIMIT ? OFFSET ?");
        $stmt->bindValue(1, $search_param, PDO::PARAM_STR);
        $stmt->bindValue(2, $search_param, PDO::PARAM_STR);
        $stmt->bindValue(3, $search_param, PDO::PARAM_STR);
        $stmt->bindValue(4, $per_page, PDO::PARAM_INT); // Explicitly bind as integer
        $stmt->bindValue(5, $offset, PDO::PARAM_INT);   // Explicitly bind as integer
        $stmt->execute();
    } else {
        // Get total count without search
        $count_stmt = $pdo->prepare($count_query);
        $count_stmt->execute();
        
        // Get companies without search - FIX: Use bindValue with explicit types
        $stmt = $pdo->prepare($query . " ORDER BY name ASC LIMIT ? OFFSET ?");
        $stmt->bindValue(1, $per_page, PDO::PARAM_INT); // Explicitly bind as integer
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);   // Explicitly bind as integer
        $stmt->execute();
    }
    
    $total_results = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_results / $per_page);
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}

// Get company details if ID is provided
$company_details = null;
$company_ratings = [];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $company_id = (int)$_GET['id'];
    
    try {
        // Get company details
        $stmt = $pdo->prepare("SELECT * FROM company_users WHERE id = ?");
        $stmt->execute([$company_id]);
        $company_details = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get company ratings if available (assuming there's a ratings table)
        // Note: You would need to create this table if it doesn't exist
        $stmt = $pdo->prepare("
            SELECT r.*, name as reviewer_name 
            FROM company_ratings r
            LEFT JOIN medical_users m ON r.reviewer_id = m.id
            WHERE r.company_id = ?
            ORDER BY r.created_at DESC");
        $stmt->execute([$company_id]);
        $company_ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
}

// Submit a rating if form is submitted
if (isset($_POST['submit_rating']) && isset($_POST['company_id']) && isset($_POST['rating']) && isset($_POST['comment'])) {
    $company_id = (int)$_POST['company_id'];
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);
    $medical_user_id = $_SESSION['user_id']; // Assuming you have user_id in session
    
    // Validate input
    if ($rating >= 1 && $rating <= 5 && !empty($comment) && strlen($comment) <= 500) {
        try {
            // Check if user already rated this company
            $stmt = $pdo->prepare("SELECT id FROM company_ratings WHERE company_id = ? AND reviewer_id = ?");
            $stmt->execute([$company_id, $medical_user_id]);
            $existing_rating = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_rating) {
                // Update existing rating
                $stmt = $pdo->prepare("
                    UPDATE company_ratings 
                    SET rating = ?, comment = ?, updated_at = NOW() 
                    WHERE id = ?");
                $stmt->execute([$rating, $comment, $existing_rating['id']]);
                $success_message = "Your rating has been updated successfully!";
            } else {
                // Insert new rating
                $stmt = $pdo->prepare("
                    INSERT INTO company_ratings (company_id, reviewer_id, rating, comment, created_at) 
                    VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$company_id, $medical_user_id, $rating, $comment]);
                $success_message = "Your rating has been submitted successfully!";
            }
            
            // Redirect to avoid form resubmission
            header("Location: view_supplier.php?id=$company_id&success=rating_submitted");
            exit();
            
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    } else {
        $error_message = "Invalid rating input. Rating must be between 1-5 and comment must be between 1-500 characters.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Supplier Details - BridgeRx Connect</title>
  <link rel="stylesheet" href="../css/medical_nav.css">

  <style>
    /* Additional styles for supplier details */
    .container {
      margin-top:30px;
      padding: 20px;
      margin-left: 250px;
      transition: margin-left 0.3s;
    }
    
    .sidebar-collapsed + .container {
      margin-left: 60px;
    }
    
    .search-form {
      display: flex;
      margin-bottom: 20px;
      max-width: 600px;
    }
    
    .search-form input {
      flex: 1;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px 0 0 4px;
    }
    
    .search-form button {
      padding: 10px 15px;
      background-color: #4CAF50;
      color: white;
      border: none;
      border-radius: 0 4px 4px 0;
      cursor: pointer;
    }
    
    .suppliers-list {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 20px;
      margin-bottom: 20px;
    }
    
    .supplier-card {
      border: 1px solid #ddd;
      border-radius: 4px;
      padding: 15px;
      background-color: #f9f9f9;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .supplier-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .supplier-name {
      font-size: 18px;
      font-weight: bold;
      margin-bottom: 10px;
      color: #333;
    }
    
    .supplier-info {
      margin-bottom: 5px;
      color: #666;
    }
    
    .supplier-action {
      margin-top: 15px;
      text-align: right;
    }
    
    .supplier-action a {
      background-color: #4a6fa5;
      color: white;
      padding: 8px 12px;
      border-radius: 4px;
      text-decoration: none;
      display: inline-block;
    }
    
    .pagination {
      display: flex;
      justify-content: center;
      margin-top: 20px;
    }
    
    .pagination a, .pagination span {
      margin: 0 5px;
      padding: 8px 12px;
      border: 1px solid #ddd;
      border-radius: 4px;
      text-decoration: none;
      color: #333;
    }
    
    .pagination .active {
      background-color: #4CAF50;
      color: white;
      border-color: #4CAF50;
    }
    
    .company-details {
      background-color: white;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      padding: 20px;
      margin-bottom: 30px;
    }
    
    .company-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 1px solid #eee;
    }
    
    .company-name {
      font-size: 24px;
      font-weight: bold;
      color: #333;
    }
    
    .company-status {
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 14px;
      background-color: #4CAF50;
      color: white;
    }
    
    .company-info {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-bottom: 20px;
    }
    
    .info-group {
      margin-bottom: 15px;
    }
    
    .info-label {
      font-weight: bold;
      color: #555;
      margin-bottom: 5px;
    }
    
    .info-value {
      color: #333;
    }
    
    .license-document {
      margin-top: 10px;
    }
    
    .license-document a {
      background-color: #ff9800;
      color: white;
      padding: 5px 10px;
      border-radius: 4px;
      text-decoration: none;
      display: inline-block;
      font-size: 14px;
    }
    
    .ratings-section {
      margin-top: 30px;
    }
    
    .ratings-header {
      font-size: 20px;
      margin-bottom: 15px;
      padding-bottom: 10px;
      border-bottom: 1px solid #eee;
    }
    
    .rating-form {
      background-color: #f9f9f9;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 20px;
    }
    
    .rating-stars {
      margin-bottom: 15px;
    }
    
    .star-rating {
      display: inline-block;
      font-size: 24px;
      cursor: pointer;
      color: #ddd;
    }
    
    .star-rating.active {
      color: #FFD700;
    }
    
    .rating-comment textarea {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      min-height: 100px;
      margin-bottom: 15px;
    }
    
    .rating-submit {
      text-align: right;
    }
    
    .rating-submit button {
      padding: 8px 15px;
      background-color: #4CAF50;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    
    .ratings-list {
      margin-top: 20px;
    }
    
    .rating-card {
      border: 1px solid #eee;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 15px;
      background-color: white;
    }
    
    .rating-header {
      display: flex;
      justify-content: space-between;
      margin-bottom: 10px;
      font-size: 14px;
      color: #777;
    }
    
    .rating-stars-display {
      color: #FFD700;
      margin-bottom: 10px;
    }
    
    .rating-comment-text {
      color: #333;
      line-height: 1.5;
    }
    
    .back-button {
      margin-bottom: 20px;
      display: inline-block;
      padding: 8px 15px;
      background-color: #f0f0f0;
      border: 1px solid #ddd;
      border-radius: 4px;
      text-decoration: none;
      color: #333;
    }
    
    .alert {
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 4px;
    }
    
    .alert-success {
      background-color: #dff0d8;
      border-color: #d6e9c6;
      color: #3c763d;
    }
    
    .alert-danger {
      background-color: #f2dede;
      border-color: #ebccd1;
      color: #a94442;
    }
    
    @media (max-width: 768px) {
      .container {
        margin-left: 0;
        padding: 10px;
      }
      
      .company-info {
        grid-template-columns: 1fr;
      }
      
      .suppliers-list {
        grid-template-columns: 1fr;
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
        <button class="menu-btn active" onclick="toggleDropdown(this)">
          <span class="menu-icon">🏢</span>
          <span class="menu-text">Suppliers</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item " data-page="view_company.php">Browse Pharmaceutical Companies</a>
          <a href="#" class="dropdown-item active" data-page="view_supplier,php">View Supplier <br>Details</a>
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
        <button class="menu-btn" onclick="toggleDropdown(this)">
          <span class="menu-icon">💬</span>
          <span class="menu-text">Communication</span>
          <span class="dropdown-indicator">▼</span>
        </button>
        <div class="dropdown">
          <a href="#" class="dropdown-item" data-page="send_inquiries.php">Send Inquiries <br>to Suppliers</a>
          <a href="#" class="dropdown-item" data-page="view_messages.php">View & Respond <br>to Messages</a>
        
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
  
  <div class="container" id="content">
    <h1>Supplier Details</h1>
    
    <?php if (isset($_GET['success']) && $_GET['success'] == 'rating_submitted'): ?>
    <div class="alert alert-success">
      Your rating has been submitted successfully!
    </div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
    <div class="alert alert-danger">
      <?php echo $error_message; ?>
    </div>
    <?php endif; ?>
    
    <?php if ($company_details): ?>
      <!-- Company Details View -->
      <a href="view_supplier.php" class="back-button">← Back to Suppliers List</a>
      
      <div class="company-details">
        <div class="company-header">
          <div class="company-name"><?php echo htmlspecialchars($company_details['name']); ?></div>
          <div class="company-status"><?php echo ucfirst(htmlspecialchars($company_details['approval_status'])); ?></div>
        </div>
        
        <div class="company-info">
          <div class="info-column">
            <div class="info-group">
              <div class="info-label">Email:</div>
              <div class="info-value"><?php echo htmlspecialchars($company_details['email']); ?></div>
            </div>
            
            <div class="info-group">
              <div class="info-label">License Number:</div>
              <div class="info-value"><?php echo htmlspecialchars($company_details['license_number']); ?></div>
            </div>
            
            <?php if (!empty($company_details['license_document'])): ?>
            <div class="info-group">
              <div class="info-label">License Document:</div>
              <div class="license-document">
                <a href="../uploads/licenses/<?php echo htmlspecialchars($company_details['license_document']); ?>" target="_blank">View License</a>
              </div>
            </div>
            <?php endif; ?>
          </div>
          
          <div class="info-column">
            <div class="info-group">
              <div class="info-label">Member Since:</div>
              <div class="info-value"><?php echo date('F j, Y', strtotime($company_details['created_at'])); ?></div>
            </div>
            
            <?php
            // Calculate average rating
            $avg_rating = 0;
            $total_ratings = count($company_ratings);
            
            if ($total_ratings > 0) {
                $sum = 0;
                foreach ($company_ratings as $rating) {
                    $sum += $rating['rating'];
                }
                $avg_rating = $sum / $total_ratings;
            }
            ?>
            
            <div class="info-group">
              <div class="info-label">Average Rating:</div>
              <div class="info-value">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                  <span class="star-rating <?php echo ($i <= round($avg_rating)) ? 'active' : ''; ?>">★</span>
                <?php endfor; ?>
                (<?php echo number_format($avg_rating, 1); ?> from <?php echo $total_ratings; ?> reviews)
              </div>
            </div>
          </div>
        </div>
        
        <div class="ratings-section">
          <div class="ratings-header">Reviews and Ratings</div>
          
          <!-- Rating Form -->
          <div class="rating-form">
            <form method="post" action="">
              <input type="hidden" name="company_id" value="<?php echo $company_details['id']; ?>">
              
              <div class="rating-stars">
                <div class="info-label">Your Rating:</div>
                <div>
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="star-rating" data-rating="<?php echo $i; ?>">★</span>
                  <?php endfor; ?>
                  <input type="hidden" name="rating" id="selected_rating" value="5">
                </div>
              </div>
              
              <div class="rating-comment">
                <div class="info-label">Your Review:</div>
                <textarea name="comment" placeholder="Share your experience with this supplier..." required></textarea>
              </div>
              
              <div class="rating-submit">
                <button type="submit" name="submit_rating">Submit Review</button>
              </div>
            </form>
          </div>
          
          <!-- Ratings List -->
          <div class="ratings-list">
            <?php if (count($company_ratings) > 0): ?>
              <?php foreach ($company_ratings as $rating): ?>
                <div class="rating-card">
                  <div class="rating-header">
                    <div class="rating-author"><?php echo htmlspecialchars($rating['reviewer_name']); ?></div>
                    <div class="rating-date"><?php echo date('M j, Y', strtotime($rating['created_at'])); ?></div>
                  </div>
                  <div class="rating-stars-display">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <span class="star-rating <?php echo ($i <= $rating['rating']) ? 'active' : ''; ?>">★</span>
                    <?php endfor; ?>
                  </div>
                  <div class="rating-comment-text">
                    <?php echo nl2br(htmlspecialchars($rating['comment'])); ?>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <p>No reviews yet. Be the first to review this supplier!</p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php else: ?>
      <!-- Suppliers List View -->
      <form class="search-form" method="get" action="">
        <input type="text" name="search" placeholder="Search by name, email or license number" value="<?php echo htmlspecialchars($search_query); ?>">
        <button type="submit">Search</button>
      </form>
      
      <?php if (count($companies) > 0): ?>
        <div class="suppliers-list">
          <?php foreach ($companies as $company): ?>
            <div class="supplier-card">
              <div class="supplier-name"><?php echo htmlspecialchars($company['name']); ?></div>
              <div class="supplier-info">Email: <?php echo htmlspecialchars($company['email']); ?></div>
              <div class="supplier-info">License: <?php echo htmlspecialchars($company['license_number']); ?></div>
              <div class="supplier-info">Member since: <?php echo date('M j, Y', strtotime($company['created_at'])); ?></div>
              <div class="supplier-action">
                <a href="?id=<?php echo $company['id']; ?>">View Details</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        
        <?php if ($total_pages > 1): ?>
          <div class="pagination">
            <?php if ($page > 1): ?>
              <a href="?page=<?php echo ($page - 1); ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>">Previous</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
              <?php if ($i == $page): ?>
                <span class="active"><?php echo $i; ?></span>
              <?php else: ?>
                <a href="?page=<?php echo $i; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>"><?php echo $i; ?></a>
              <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
              <a href="?page=<?php echo ($page + 1); ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>">Next</a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <p>No suppliers found<?php echo !empty($search_query) ? ' matching "' . htmlspecialchars($search_query) . '"' : ''; ?>.</p>
      <?php endif; ?>
    <?php endif; ?>
  </div>
  
  <script>
    // Rating stars functionality
    document.addEventListener('DOMContentLoaded', function() {
      const stars = document.querySelectorAll('.rating-form .star-rating');
      const ratingInput = document.getElementById('selected_rating');
      
      stars.forEach(star => {
        star.addEventListener('mouseover', function() {
          const rating = parseInt(this.getAttribute('data-rating'));
          highlightStars(rating);
        });
        
        star.addEventListener('mouseout', function() {
          const currentRating = parseInt(ratingInput.value);
          highlightStars(currentRating);
        });
        
        star.addEventListener('click', function() {
          const rating = parseInt(this.getAttribute('data-rating'));
          ratingInput.value = rating;
          highlightStars(rating);
        });
      });
      
      function highlightStars(rating) {
        stars.forEach(star => {
          const starRating = parseInt(star.getAttribute('data-rating'));
          if (starRating <= rating) {
            star.classList.add('active');
          } else {
            star.classList.remove('active');
          }
        });
      }
      
      // Initialize stars
      highlightStars(5);
    });
    
    // Sidebar toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
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