<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up Form</title>
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
            position: relative;
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
            object-position: center;
        }
        
        .right-side {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: auto;
        }
        
        h1 {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 8px;
            line-height: 1.3;
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
        
        .password-section {
            display: flex;
            gap: 10px;
        }
        
        .password-section input {
            flex: 1;
        }
        
        .file-input-wrapper {
            position: relative;
            margin-top: 5px;
        }
        
        .file-input-label {
            display: inline-block;
            padding: 10px 15px;
            background-color: #f5f5f5;
            color: #555;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-align: center;
            width: 100%;
        }
        
        .file-input {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-name {
            margin-top: 5px;
            font-size: 12px;
            color: #777;
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
        
        .terms {
            font-size: 12px;
            color: #777;
            margin-top: 15px;
            text-align: center;
        }
      
        .create-account {
            margin-top: 20px;
            text-align: left;
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
        
        .form-label {
            font-size: 12px;
            color: #555;
            margin-bottom: 5px;
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-side">
            <!-- Replace with your actual image path -->
            <img src="assets/i1.jpg" alt="Workspace with keyboard, notebook and stationery">
        </div>
        <div class="right-side">
            <h1>Connecting Pharmacies & Suppliers.</h1>
            <p class="subtitle">Designed for seamless healthcare solutions.</p>
            
            <form action="api/register.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <input type="text" name="name" placeholder="Company Name" required>
                </div>
                <div class="form-group">
                    <input type="email" name="email" placeholder="Company Email Address" required>
                </div>
                <div class="form-group">
                    <select name="account_type" id="account_type" required>
                        <option value="" disabled selected>Select Account Type</option>
                        <option value="medical">Medical (Pharmacy/Hospital)</option>
                        <option value="company">Company (Supplier/Manufacturer)</option>
                    </select>
                </div>
                <div class="form-group">
                    <input type="text" name="license_number" placeholder="License Number" required>
                </div>
                <div class="form-group">
                    <label class="form-label">License Document</label>
                    <div class="file-input-wrapper">
                        <label class="file-input-label">Choose File</label>
                        <input type="file" name="license_document" class="file-input" id="license-document" required>
                    </div>
                    <div class="file-name" id="file-name">No file chosen</div>
                </div>
                <div class="form-group password-section">
                    <input type="password" name="password" placeholder="Password" required>
                    <input type="password" name="confirm_password" placeholder="Confirmation" required>
                </div>
                <div class="create-account">Already have an account? <a href="signin.php">Sign In</a>
                </div>
                <button type="submit">CREATE AN ACCOUNT</button>
            </form>
            
            <p class="terms">By signing up, you agree to the terms and conditions.</p>
        </div>
    </div>
    
    <script>
        document.getElementById('license-document').addEventListener('change', function(e) {
            var fileName = e.target.files[0] ? e.target.files[0].name : 'No file chosen';
            document.getElementById('file-name').textContent = fileName;
        });
    </script>
</body>
</html>