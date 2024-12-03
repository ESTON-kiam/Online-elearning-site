<?php
session_name('super_admin');
session_start();
require_once 'include/database.php';


if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $current_password = $database->sanitizeInput($_POST['current_password']);
        $new_password = $database->sanitizeInput($_POST['new_password']);
        $confirm_password = $database->sanitizeInput($_POST['confirm_password']);
        $admin_id = $_SESSION['admin_id'];

       
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            throw new Exception("All fields are required.");
        }
        if ($new_password !== $confirm_password) {
            throw new Exception("New password and confirm password do not match.");
        }

        
        $query = "SELECT password FROM admin WHERE admin_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Admin not found.");
        }

        $admin = $result->fetch_assoc();
        $hashed_password = $admin['password'];

       
        if (!password_verify($current_password, $hashed_password)) {
            throw new Exception("Current password is incorrect.");
        }

  
        $new_hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

       
        $update_query = "UPDATE admin SET password = ? WHERE admin_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $new_hashed_password, $admin_id);

        if ($update_stmt->execute()) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Password changed successfully.'
            ]);
        } else {
            throw new Exception("Error updating password.");
        }

    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <style>
/* General body styles */
body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f9;
    color: #333;
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    text-align: center; /* Ensure text is centered */
}

/* Form container */
form {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    width: 100%;
    max-width: 400px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    margin-top: 20px; /* Add margin for spacing from the top */
}

/* Form heading */
h2 {
    text-align: center;
    color: #555;
    margin-bottom: 20px;
}

/* Form fields */
form div {
    margin-bottom: 15px;
}

label {
    display: block;
    font-weight: bold;
    margin-bottom: 5px;
}

input[type="password"] {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    box-sizing: border-box;
}

/* Submit button */
button {
    width: 100%;
    padding: 10px;
    border: none;
    border-radius: 4px;
    background-color: #007bff;
    color: #fff;
    font-size: 16px;
    cursor: pointer;
}

button:hover {
    background-color: #0056b3;
}

/* Response message styles */
#responseMessage {
    margin-top: 15px;
    font-size: 14px;
    text-align: center;
}

#responseMessage p {
    margin: 0;
    padding: 10px;
    border-radius: 4px;
}

#responseMessage p[style="color: green;"] {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

#responseMessage p[style="color: red;"] {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

</style>
</head>
<body>
    <h2>Change Password</h2>
    <form id="changePasswordForm" method="POST">
        <div>
            <label for="current_password">Current Password:</label>
            <input type="password" id="current_password" name="current_password" required>
        </div>
        <div>
            <label for="new_password">New Password:</label>
            <input type="password" id="new_password" name="new_password" required>
        </div>
        <div>
            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        <button type="submit">Change Password</button>
    </form>

    <div id="responseMessage"></div>

    <script>
    document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
        e.preventDefault();

        var formData = new FormData(this);

        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            var messageDiv = document.getElementById('responseMessage');
            if (data.status === 'success') {
                messageDiv.innerHTML = '<p style="color: green;">' + data.message + '</p>';
            } else {
                messageDiv.innerHTML = '<p style="color: red;">' + data.message + '</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    });
    </script>
</body>
</html>
