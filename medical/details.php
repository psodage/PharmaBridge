<?php 
session_start();
require_once('../api/db_con.php');
if (!isset($_SESSION['user_id']) || !isset($_SESSION['account_type']) || $_SESSION['account_type'] !== 'medical') { 
    header("Location: ../../signin.php");
    exit();
}

// Get the user ID from the session
$user_id = $_SESSION['user_id'];

// Create billing_details table if it doesn't exist already 
// (you should run this in a migration script in a production environment)
try {
    $createTableQuery = "CREATE TABLE IF NOT EXISTS `billing_details` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `billing_name` varchar(255) NOT NULL,
        `billing_address` text NOT NULL,
        `billing_city` varchar(100) NOT NULL,
        `billing_state` varchar(100) NOT NULL,
        `billing_zip` varchar(20) NOT NULL,
        `billing_country` varchar(100) NOT NULL,
        `billing_phone` varchar(20) NOT NULL,
        `billing_email` varchar(255) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        CONSTRAINT `billing_details_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `medical_users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    $pdo->exec($createTableQuery);
    
    $createPaymentMethodsTable = "CREATE TABLE IF NOT EXISTS `payment_methods` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `payment_type` enum('credit_card','bank_transfer','other') NOT NULL,
        `card_last_four` varchar(4) DEFAULT NULL,
        `card_expiry` varchar(7) DEFAULT NULL,
        `bank_name` varchar(255) DEFAULT NULL,
        `bank_account_number` varchar(255) DEFAULT NULL,
        `is_default` tinyint(1) NOT NULL DEFAULT 0,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        CONSTRAINT `payment_methods_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `medical_users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    $pdo->exec($createPaymentMethodsTable);
} catch (PDOException $e) {
    // Handle error but continue execution
    $error_message = "Database setup error: " . $e->getMessage();
}

// Process form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_billing'])) {
        // Update billing details
        try {
            // Check if billing details already exist
            $checkStmt = $pdo->prepare("SELECT id FROM billing_details WHERE user_id = ?");
            $checkStmt->execute([$user_id]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Update existing record
                $updateStmt = $pdo->prepare("UPDATE billing_details SET 
                    billing_name = ?,
                    billing_address = ?,
                    billing_city = ?,
                    billing_state = ?,
                    billing_zip = ?,
                    billing_country = ?,
                    billing_phone = ?,
                    billing_email = ?
                    WHERE user_id = ?");
                
                $updateStmt->execute([
                    $_POST['billing_name'],
                    $_POST['billing_address'],
                    $_POST['billing_city'],
                    $_POST['billing_state'],
                    $_POST['billing_zip'],
                    $_POST['billing_country'],
                    $_POST['billing_phone'],
                    $_POST['billing_email'],
                    $user_id
                ]);
            } else {
                // Insert new record
                $insertStmt = $pdo->prepare("INSERT INTO billing_details 
                    (user_id, billing_name, billing_address, billing_city, billing_state, billing_zip, billing_country, billing_phone, billing_email) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $insertStmt->execute([
                    $user_id,
                    $_POST['billing_name'],
                    $_POST['billing_address'],
                    $_POST['billing_city'],
                    $_POST['billing_state'],
                    $_POST['billing_zip'],
                    $_POST['billing_country'],
                    $_POST['billing_phone'],
                    $_POST['billing_email']
                ]);
            }
            
            $success_message = "Billing details updated successfully!";
        } catch (PDOException $e) {
            $error_message = "Error updating billing details: " . $e->getMessage();
        }
    } elseif (isset($_POST['add_payment_method'])) {
        // Add payment method
        try {
            // Reset all defaults if this is marked as default
            if (isset($_POST['is_default']) && $_POST['is_default'] == 1) {
                $resetStmt = $pdo->prepare("UPDATE payment_methods SET is_default = 0 WHERE user_id = ?");
                $resetStmt->execute([$user_id]);
            }
            
            $insertStmt = $pdo->prepare("INSERT INTO payment_methods 
                (user_id, payment_type, card_last_four, card_expiry, bank_name, bank_account_number, is_default) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            // Determine values based on payment type
            if ($_POST['payment_type'] == 'credit_card') {
                $insertStmt->execute([
                    $user_id,
                    $_POST['payment_type'],
                    substr($_POST['card_number'], -4),
                    $_POST['card_expiry'],
                    null,
                    null,
                    isset($_POST['is_default']) ? 1 : 0
                ]);
            } else if ($_POST['payment_type'] == 'bank_transfer') {
                $insertStmt->execute([
                    $user_id,
                    $_POST['payment_type'],
                    null,
                    null,
                    $_POST['bank_name'],
                    $_POST['bank_account_number'],
                    isset($_POST['is_default']) ? 1 : 0
                ]);
            } else {
                $insertStmt->execute([
                    $user_id,
                    $_POST['payment_type'],
                    null,
                    null,
                    null,
                    null,
                    isset($_POST['is_default']) ? 1 : 0
                ]);
            }
            
            $success_message = "Payment method added successfully!";
        } catch (PDOException $e) {
            $error_message = "Error adding payment method: " . $e->getMessage();
        }
    } elseif (isset($_POST['delete_payment'])) {
        // Delete payment method
        try {
            $deleteStmt = $pdo->prepare("DELETE FROM payment_methods WHERE id = ? AND user_id = ?");
            $deleteStmt->execute([$_POST['payment_id'], $user_id]);
            
            $success_message = "Payment method removed successfully!";
        } catch (PDOException $e) {
            $error_message = "Error removing payment method: " . $e->getMessage();
        }
    } elseif (isset($_POST['set_default'])) {
        // Set default payment method
        try {
            // Reset all defaults
            $resetStmt = $pdo->prepare("UPDATE payment_methods SET is_default = 0 WHERE user_id = ?");
            $resetStmt->execute([$user_id]);
            
            // Set the new default
            $updateStmt = $pdo->prepare("UPDATE payment_methods SET is_default = 1 WHERE id = ? AND user_id = ?");
            $updateStmt->execute([$_POST['payment_id'], $user_id]);
            
            $success_message = "Default payment method updated!";
        } catch (PDOException $e) {
            $error_message = "Error updating default payment method: " . $e->getMessage();
        }
    }
}

// Fetch existing billing details
$billing_details = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM billing_details WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $billing_details = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching billing details: " . $e->getMessage();
}

// Fetch existing payment methods
$payment_methods = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM payment_methods WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
    $stmt->execute([$user_id]);
    $payment_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching payment methods: " . $e->getMessage();
}

// Fetch user info
$user_info = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM medical_users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching user info: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment & Billing Details - BridgeRx Connect</title>
  <link rel="stylesheet" href="../css/medical_nav.css">
  <style>
    /* Custom CSS for Payment & Billing Details page */
    .content-container {
      
      padding: 20px;
      background-color: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .section {
      margin-bottom: 30px;
      padding-bottom: 20px;
      border-bottom: 1px solid #e0e0e0;
    }

    .section:last-child {
      border-bottom: none;
    }

    h2 {
      color: #2c3e50;
      margin-bottom: 20px;
    }

    .form-row {
      display: flex;
      flex-wrap: wrap;
      margin-bottom: 15px;
    }

    .form-group {
      flex: 1 0 300px;
      margin-right: 20px;
      margin-bottom: 15px;
    }

    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
      color: #34495e;
    }

    .form-control {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 14px;
    }

    .btn {
      display: inline-block;
      padding: 10px 20px;
      background-color: #3498db;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
      transition: background-color 0.3s;
    }

    .btn:hover {
      background-color: #2980b9;
    }

    .btn-secondary {
      background-color: #95a5a6;
    }

    .btn-secondary:hover {
      background-color: #7f8c8d;
    }

    .btn-danger {
      background-color: #e74c3c;
    }

    .btn-danger:hover {
      background-color: #c0392b;
    }

    .alert {
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 4px;
    }

    .alert-success {
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    .alert-danger {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    .payment-methods {
      margin-top: 20px;
    }

    .payment-card {
      background-color: #f9f9f9;
      border-radius: 6px;
      padding: 15px;
      margin-bottom: 15px;
      border: 1px solid #eee;
      position: relative;
    }

    .payment-card.default {
      border-color: #3498db;
      box-shadow: 0 0 0 1px #3498db;
    }

    .default-badge {
      position: absolute;
      top: 10px;
      right: 10px;
      background-color: #3498db;
      color: white;
      padding: 3px 8px;
      border-radius: 3px;
      font-size: 12px;
    }

    .payment-type {
      font-weight: bold;
      margin-bottom: 5px;
    }

    .payment-details {
      color: #666;
      margin-bottom: 10px;
    }

    .payment-actions {
      display: flex;
      gap: 10px;
    }

    .payment-actions .btn {
      padding: 5px 10px;
      font-size: 12px;
    }

    .payment-type-selector {
      margin-bottom: 20px;
    }

    .form-check {
      margin-bottom: 10px;
    }

    .payment-form {
      padding-top: 15px;
    }

    .hidden {
      display: none;
    }

    .page-title {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
    }

    @media (max-width: 768px) {
      .form-group {
        flex: 1 0 100%;
        margin-right: 0;
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
        <button class="menu-btn active" data-page="medical.php">
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
  
  <div class="content" id="content">
    <div class="content-container">
      <div class="page-title">
        <h2>Payment & Billing Details</h2>
      </div>
      
      <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
      <?php endif; ?>
      
      <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
      <?php endif; ?>
      
      <div class="section">
        <h3>Billing Information</h3>
        <form method="post" action="">
          <div class="form-row">
            <div class="form-group">
              <label for="billing_name">Billing Name</label>
              <input type="text" id="billing_name" name="billing_name" class="form-control" value="<?php echo isset($billing_details['billing_name']) ? htmlspecialchars($billing_details['billing_name']) : (isset($user_info['name']) ? htmlspecialchars($user_info['name']) : ''); ?>" required>
            </div>
            <div class="form-group">
              <label for="billing_email">Billing Email</label>
              <input type="email" id="billing_email" name="billing_email" class="form-control" value="<?php echo isset($billing_details['billing_email']) ? htmlspecialchars($billing_details['billing_email']) : (isset($user_info['email']) ? htmlspecialchars($user_info['email']) : ''); ?>" required>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="billing_address">Address</label>
              <input type="text" id="billing_address" name="billing_address" class="form-control" value="<?php echo isset($billing_details['billing_address']) ? htmlspecialchars($billing_details['billing_address']) : ''; ?>" required>
            </div>
            <div class="form-group">
              <label for="billing_phone">Phone Number</label>
              <input type="tel" id="billing_phone" name="billing_phone" class="form-control" value="<?php echo isset($billing_details['billing_phone']) ? htmlspecialchars($billing_details['billing_phone']) : ''; ?>" required>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="billing_city">City</label>
              <input type="text" id="billing_city" name="billing_city" class="form-control" value="<?php echo isset($billing_details['billing_city']) ? htmlspecialchars($billing_details['billing_city']) : ''; ?>" required>
            </div>
            <div class="form-group">
              <label for="billing_state">State/Province</label>
              <input type="text" id="billing_state" name="billing_state" class="form-control" value="<?php echo isset($billing_details['billing_state']) ? htmlspecialchars($billing_details['billing_state']) : ''; ?>" required>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="billing_zip">ZIP/Postal Code</label>
              <input type="text" id="billing_zip" name="billing_zip" class="form-control" value="<?php echo isset($billing_details['billing_zip']) ? htmlspecialchars($billing_details['billing_zip']) : ''; ?>" required>
            </div>
            <div class="form-group">
              <label for="billing_country">Country</label>
              <input type="text" id="billing_country" name="billing_country" class="form-control" value="<?php echo isset($billing_details['billing_country']) ? htmlspecialchars($billing_details['billing_country']) : ''; ?>" required>
            </div>
          </div>
          <button type="submit" name="update_billing" class="btn">Update Billing Information</button>
        </form>
      </div>
      
      <div class="section">
        <h3>Payment Methods</h3>
        
        <?php if (empty($payment_methods)): ?>
          <p>No payment methods added yet.</p>
        <?php else: ?>
          <div class="payment-methods">
            <?php foreach ($payment_methods as $method): ?>
              <div class="payment-card <?php echo $method['is_default'] ? 'default' : ''; ?>">
                <?php if ($method['is_default']): ?>
                  <div class="default-badge">Default</div>
                <?php endif; ?>
                
                <div class="payment-type">
                  <?php if ($method['payment_type'] == 'credit_card'): ?>
                    Credit Card
                  <?php elseif ($method['payment_type'] == 'bank_transfer'): ?>
                    Bank Transfer
                  <?php else: ?>
                    Other Payment Method
                  <?php endif; ?>
                </div>
                
                <div class="payment-details">
                  <?php if ($method['payment_type'] == 'credit_card'): ?>
                    Card ending in <?php echo htmlspecialchars($method['card_last_four']); ?> - 
                    Expires: <?php echo htmlspecialchars($method['card_expiry']); ?>
                  <?php elseif ($method['payment_type'] == 'bank_transfer'): ?>
                    Bank: <?php echo htmlspecialchars($method['bank_name']); ?> - 
                    Account: <?php echo substr(htmlspecialchars($method['bank_account_number']), -4); ?>
                  <?php endif; ?>
                </div>
                
                <div class="payment-actions">
                  <?php if (!$method['is_default']): ?>
                    <form method="post" action="" style="display: inline;">
                      <input type="hidden" name="payment_id" value="<?php echo $method['id']; ?>">
                      <button type="submit" name="set_default" class="btn btn-secondary">Set as Default</button>
                    </form>
                  <?php endif; ?>
                  
                  <form method="post" action="" style="display: inline;">
                    <input type="hidden" name="payment_id" value="<?php echo $method['id']; ?>">
                    <button type="submit" name="delete_payment" class="btn btn-danger" onclick="return confirm('Are you sure you want to remove this payment method?');">Remove</button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        
        <h4 style="margin-top: 30px;">Add Payment Method</h4>
        
        <div class="payment-type-selector">
          <div class="form-check">
            <input type="radio" id="credit_card" name="payment_type_selector" value="credit_card" checked>
            <label for="credit_card">Credit Card</label>
          </div>
          <div class="form-check">
            <input type="radio" id="bank_transfer" name="payment_type_selector" value="bank_transfer">
            <label for="bank_transfer">Bank Transfer</label>
          </div>
          <div class="form-check">
            <input type="radio" id="other_payment" name="payment_type_selector" value="other">
            <label for="other_payment">Other</label>
          </div>
        </div>
        
        <!-- Credit Card Form -->
        <form method="post" action="" id="credit_card_form" class="payment-form">
          <input type="hidden" name="payment_type" value="credit_card">
          <div class="form-row">
            <div class="form-group">
              <label for="card_number">Card Number</label>
              <input type="text" id="card_number" name="card_number" class="form-control" required pattern="[0-9]{13,16}" maxlength="16">
            </div>
            <div class="form-group">
              <label for="card_holder">Card Holder Name</label>
              <input type="text" id="card_holder" name="card_holder" class="form-control" required>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="card_expiry">Expiry Date (MM/YYYY)</label>
              <input type="text" id="card_expiry" name="card_expiry" class="form-control" required pattern="[0-9]{2}/[0-9]{4}" placeholder="MM/YYYY">
            </div>
            <div class="form-group">
              <label for="card_cvv">CVV</label>
              <input type="text" id="card_cvv" name="card_cvv" class="form-control" required pattern="[0-9]{3,4}" maxlength="4">
            </div>
          </div>
          <div class="form-check" style="margin-top: 10px;">
            <input type="checkbox" id="cc_is_default" name="is_default" value="1">
            <label for="cc_is_default">Set as default payment method</label>
          </div>
          <button type="submit" name="add_payment_method" class="btn" style="margin-top: 15px;">Add Credit Card</button>
        </form>
        
        <!-- Bank Transfer Form -->
        <form method="post" action="" id="bank_transfer_form" class="payment-form hidden">
          <input type="hidden" name="payment_type" value="bank_transfer">
          <div class="form-row">
            <div class="form-group">
              <label for="bank_name">Bank Name</label>
              <input type="text" id="bank_name" name="bank_name" class="form-control" required>
            </div>
            <div class="form-group">
              <label for="bank_account_number">Account Number</label>
              <input type="text" id="bank_account_number" name="bank_account_number" class="form-control" required>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="account_holder">Account Holder Name</label>
              <input type="text" id="account_holder" name="account_holder" class="form-control" required>
            </div>
            <div class="form-group">
              <label for="routing_number">Routing Number</label>
              <input type="text" id="routing_number" name="routing_number" class="form-control" required>
            </div>
          </div>
          <div class="form-check" style="margin-top: 10px;">
            <input type="checkbox" id="bank_is_default" name="is_default" value="1">
            <label for="bank_is_default">Set as default payment method</label>
          </div>
          <button type="submit" name="add_payment_method" class="btn" style="margin-top: 15px;">Add Bank Account</button>
        </form>
        
        <!-- Other Payment Form -->
        <form method="post" action="" id="other_payment_form" class="payment-form hidden">
          <input type="hidden" name="payment_type" value="other">
          <div class="form-row">
            <div class="form-group">
            <label for="other_payment_description">Payment Method Description</label>
              <input type="text" id="other_payment_description" name="other_payment_description" class="form-control" required>
            </div>
          </div>
          <div class="form-check" style="margin-top: 10px;">
            <input type="checkbox" id="other_is_default" name="is_default" value="1">
            <label for="other_is_default">Set as default payment method</label>
          </div>
          <button type="submit" name="add_payment_method" class="btn" style="margin-top: 15px;">Add Payment Method</button>
        </form>
      </div>
    </div>
  </div>
  
  <script>
    // Toggle sidebar
    document.getElementById('sidebarToggle').addEventListener('click', function() {
      document.getElementById('sidebar').classList.toggle('active');
      document.getElementById('content').classList.toggle('expanded');
    });
    
    // Dropdown toggle
    function toggleDropdown(button) {
      const dropdown = button.nextElementSibling;
      const allDropdowns = document.querySelectorAll('.dropdown');
      
      // Close all other dropdowns
      allDropdowns.forEach(function(item) {
        if (item !== dropdown && item.classList.contains('show')) {
          item.classList.remove('show');
          item.previousElementSibling.classList.remove('active');
        }
      });
      
      // Toggle this dropdown
      dropdown.classList.toggle('show');
      button.classList.toggle('active');
    }
    
    // Navigation
    document.querySelectorAll('.menu-btn, .dropdown-item').forEach(function(button) {
      button.addEventListener('click', function(e) {
        if (this.dataset.page) {
          e.preventDefault();
          window.location.href = this.dataset.page;
        }
      });
    });
    
    // Logout function
    function logout() {
      if (confirm('Are you sure you want to logout?')) {
        window.location.href = '../logout.php';
      }
    }
    
    // Payment method selector
    document.querySelectorAll('input[name="payment_type_selector"]').forEach(function(radio) {
      radio.addEventListener('change', function() {
        document.querySelectorAll('.payment-form').forEach(function(form) {
          form.classList.add('hidden');
        });
        
        document.getElementById(this.value + '_form').classList.remove('hidden');
      });
    });
    
    // Form validation for credit card expiry
    document.getElementById('card_expiry').addEventListener('input', function(e) {
      let value = e.target.value;
      if (value.length === 2 && !value.includes('/')) {
        e.target.value = value + '/';
      }
    });
  </script>
</body>
</html>