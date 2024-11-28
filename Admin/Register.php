<?php
session_start();
require_once 'include/database.php';


error_reporting(E_ALL);
ini_set('display_errors', 1);


function uploadProfileImage($file) {
    $target_dir = "Profile/";
    
   
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

   
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $unique_filename = uniqid('admin_', true) . '.' . $file_extension;
    $target_file = $target_dir . $unique_filename;

    
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

   
    if (!in_array($file_extension, $allowed_types)) {
        throw new Exception("Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.");
    }

  
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception("File is too large. Maximum size is 5MB.");
    }


    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return $target_file;
    } else {
        throw new Exception("Failed to upload profile image.");
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
       
        $db = new DatabaseConnection();
        $conn = $db->getConnection();

        
        $username = $db->sanitizeInput($_POST['username']);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        
        if (empty($username) || empty($email) || empty($password)) {
            throw new Exception("All fields are required.");
        }

       
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format.");
        }

        
        if ($password !== $confirm_password) {
            throw new Exception("Passwords do not match.");
        }

       
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

       
        $profile_image = null;
        if (!empty($_FILES['profile_image']['name'])) {
            $profile_image = uploadProfileImage($_FILES['profile_image']);
        }

        
        $stmt = $conn->prepare("
            INSERT INTO admin 
            (username, email, password, profile_image) 
            VALUES (?, ?, ?, ?)
        ");

       
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $profile_image);

       
        if ($stmt->execute()) {
            
            $_SESSION['registration_success'] = "Admin account created successfully!";
            header("Location: http://localhost:8000/admin");
            exit();
        } else {
           
            if ($conn->errno == 1062) {
                throw new Exception("Username or email already exists.");
            } else {
                throw new Exception("Registration failed: " . $stmt->error);
            }
        }
    } catch (Exception $e) {
       
        $_SESSION['registration_error'] = $e->getMessage();
        error_log("Admin Registration Error: " . $e->getMessage());
    } finally {
      
        if (isset($stmt)) $stmt->close();
        if (isset($conn)) $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/register.css">
</head>
<body>
    <div class="registration-container">
        <div class="registration-logo">
            <i class="fas fa-user-shield"></i>
        </div>
        <h2 class="registration-title">Admin Registration</h2>

        <?php
        // Display error messages
        if (isset($_SESSION['registration_error'])) {
            echo '<div class="error-message">' . 
                 htmlspecialchars($_SESSION['registration_error']) . 
                 '</div>';
            
            unset($_SESSION['registration_error']);
        }
        ?>

        <form class="registration-form" action="" method="POST" enctype="multipart/form-data">
            <input type="text" name="username" placeholder="Username" required 
                   pattern="[A-Za-z0-9_]{3,50}" 
                   title="3-50 characters. Letters, numbers, and underscore only.">
            
            <input type="email" name="email" placeholder="Email" required>
            
            <input type="password" name="password" placeholder="Password" required
                   minlength="8"
                   pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                   title="Must contain at least one number, one uppercase and lowercase letter, and be at least 8 characters long.">
            
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            
            <div class="file-upload">
                <label for="profile_image">
                    <i class="fas fa-upload"></i> Upload Profile Image (Optional)
                </label>
                <input type="file" id="profile_image" name="profile_image" 
                       accept=".jpg,.jpeg,.png,.gif">
            </div>

            <button type="submit" class="registration-button">Register</button>
        </form>

        <div class="registration-extras">
            <p>Already have an account? <a href="http://localhost:8000/admin">Login here</a></p>
        </div>
    </div>
</body>
</html>