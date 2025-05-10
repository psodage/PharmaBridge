<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Form</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .container {
            display: flex;
            background-color: white;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 1000px;
            height: 600px;
        }
        
        .left-side {
            flex: 1;
            position: relative;
            overflow: hidden;
        }
        
        .left-side img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .right-side {
            flex: 1;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        h1 {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #222;
        }
        
        .subtitle {
            color: #ff3366;
            font-size: 14px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        input, select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 14px;
            outline: none;
        }

        select {
            background-color: white;
            cursor: pointer;
        }
        
        button {
            background-color: #222;
            color: white;
            border: none;
            padding: 12px 15px;
            width: 100%;
            border-radius: 4px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 10px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        button:hover {
            background-color: #111;
        }
        
        .forgot-password {
            font-size: 12px;
            color: #777;
            margin-top: 15px;
            text-align: center;
        }
        
        .forgot-password a {
            color: #ff3366;
            text-decoration: none;
            cursor: pointer;
        }
        
        .forgot-password a:hover {
            text-decoration: underline;
        }
        
        .create-account {
            margin-top: 25px;
            text-align: center;
            font-size: 14px;
            color: #555;
        }
        
        .create-account a {
            color: #ff3366;
            text-decoration: none;
            font-weight: bold;
        }
        
        .create-account a:hover {
            text-decoration: underline;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            border-radius: 8px;
            width: 400px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
        }
        
        .modal-title {
            margin-bottom: 20px;
            color: #222;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="right-side">
            <h1>Welcome back!</h1>
            <p class="subtitle">Login to access your dashboard.</p>
            
            <form action="api/login.php" method="POST">
                <div class="form-group">
                    <input type="text" name="email" placeholder="Company Email Address" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit">LOGIN</button>
            </form>
            
            <p class="forgot-password">
                <a id="forgotPasswordLink">Forgot your password?</a>
            </p>
            
            <div class="create-account">
                Don't have an account? <a href="signup.php">Sign up</a>
            </div>
        </div>
        <div class="left-side">
            <img src="assets/i4.png" alt="Pharmacy Background">
        </div>
    </div>
    
    <!-- Forgot Password Modal -->
    <div id="forgotPasswordModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 class="modal-title">Reset Your Password</h2>
            <p style="margin-bottom: 15px;">Enter your email address and we'll send you a password reset link.</p>
            
            <form id="resetPasswordForm" action="api/reset-password.php" method="POST">
                <div class="form-group">
                    <input type="email" name="reset_email" placeholder="Your Email Address" required>
                </div>
                <button type="submit">SEND RESET LINK</button>
            </form>
        </div>
    </div>
    
    <script>
        // Get the modal
        const modal = document.getElementById("forgotPasswordModal");
        
        // Get the button that opens the modal
        const forgotLink = document.getElementById("forgotPasswordLink");
        
        // Get the <span> element that closes the modal
        const span = document.getElementsByClassName("close")[0];
        
        // When the user clicks on the link, open the modal
        forgotLink.onclick = function() {
            modal.style.display = "block";
        }
        
        // When the user clicks on <span> (x), close the modal
        span.onclick = function() {
            modal.style.display = "none";
        }
        
        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
        
        // Form submission handling
        document.getElementById("resetPasswordForm").addEventListener("submit", function(e) {
            e.preventDefault();
            
            const email = this.reset_email.value;
            
            // Perform AJAX request to send reset email
            fetch('api/reset-password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'reset_email=' + encodeURIComponent(email)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Password reset link has been sent to your email.');
                    modal.style.display = "none";
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('An error occurred. Please try again later.');
                console.error('Error:', error);
            });
        });
    </script>
</body>
</html>