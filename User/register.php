<?php
session_name('student_session');
session_start();
require_once 'include/database.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


require 'vendor/autoload.php';


$database = new DatabaseConnection();
$conn = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        
        $username = $database->sanitizeInput($_POST['username']);
        $first_name = $database->sanitizeInput($_POST['first_name']);
        $last_name = $database->sanitizeInput($_POST['last_name']);
        $email = $database->sanitizeInput($_POST['email']);
        $password = $database->sanitizeInput($_POST['password']);
        $phone_number = $database->sanitizeInput($_POST['phone_number']);

        
        if (empty($username) || empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
            throw new Exception("All fields are required.");
        }

        
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        $query = "INSERT INTO students (username, first_name, last_name, email, password, phone_number) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Error preparing the query: " . $conn->error);
        }
        
        $stmt->bind_param("ssssss", $username, $first_name, $last_name, $email, $hashed_password, $phone_number);
        if ($stmt->execute()) {
            sendConfirmationEmail($email, $first_name);

            $_SESSION['message'] = "Registration successful! Please check your email.";
            header("Location: success.php");
            exit();
        } else {
            throw new Exception("Error registering user: " . $stmt->error);
        }
    } catch (Exception $e) {
        $_SESSION['message'] = $e->getMessage();
    }
}


function sendConfirmationEmail($email, $first_name) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';                    
        $mail->SMTPAuth   = true;                                
        $mail->Username   = 'engestonbrandon@gmail.com';            
        $mail->Password   = 'dsth izzm npjl qebi';                      
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;     
        $mail->Port       = 587;

        $mail->setFrom('no-reply@yourdomain.com', 'E-learning Platform');
        $mail->addAddress($email, $first_name);

        $mail->isHTML(true);
        $mail->Subject = 'Welcome to Our Platform';
        $mail->Body    = "<p>Dear $first_name,</p><p>Thank you for registering on our platform. Your account has been created successfully.</p><p>Best regards,<br>Your Platform Team</p>";

        $mail->send();
    } catch (Exception $e) {
        error_log("Error sending email: {$mail->ErrorInfo}");
        $_SESSION['message'] = "Error sending confirmation email. Please try again later.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration</title>
    <link href="assets/css/register.css" rel="stylesheet">
</head>
<body>
    <h2>Register as a Student</h2>
    <?php
    if (isset($_SESSION['message'])) {
        echo "<p>{$_SESSION['message']}</p>";
        unset($_SESSION['message']);
    }
    ?>
    <form method="POST" action="register.php">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required><br>

        <label for="first_name">First Name:</label>
        <input type="text" id="first_name" name="first_name" required><br>

        <label for="last_name">Last Name:</label>
        <input type="text" id="last_name" name="last_name" required><br>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required><br>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required><br>

        <label for="phone_number">Phone Number:</label>
        <input type="text" id="phone_number" name="phone_number"><br>

        <button type="submit">Register</button>
    </form>
</body>
</html>
