<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/PHPMailer/src/Exception.php';
require 'PHPMailer/PHPMailer/src/PHPMailer.php';
require 'PHPMailer/PHPMailer/src/SMTP.php';


require 'vendor/autoload.php';
require_once 'include/database.php';

$message = '';

try {
    $database = new DatabaseConnection();
    $conn = $database->getConnection();
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $database->sanitizeInput($_POST['email']);

   
    $query = "SELECT * FROM students WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        $token = bin2hex(random_bytes(32));
       
$expiry = (new DateTime('now', new DateTimeZone('Africa/Nairobi')))
->modify('+1 hour')
->format('Y-m-d H:i:s');

        
        $update_query = "UPDATE students SET reset_token = ?, token_expiry = ? WHERE email = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sss", $token, $expiry, $email);
        $stmt->execute();

     
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';                   
            $mail->SMTPAuth   = true;                                
            $mail->Username   = 'engestonbrandon@gmail.com';            
            $mail->Password   = 'dsth izzm npjl qebi';                    
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;     
            $mail->Port       = 587; 
            $mail->setFrom('your_email@gmail.com', 'E-Learning Platform');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body = "Hello, <br><br>Click the link below to reset your password:<br>
            <a href='http://localhost:8000/User/resetpassword.php?token=$token'>Reset Password</a><br><br>This link will expire in 1 hour.";

            $mail->send();
            $message = 'Password reset link has been sent to your email.';
        } catch (Exception $e) {
            $message = 'Email could not be sent. Error: ' . $mail->ErrorInfo;
        }
    } else {
        $message = 'No account found with that email.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
<div class="container">
    <h2>Forgot Password</h2>
    <form method="POST">
        <label for="email">Enter your email:</label>
        <input type="email" name="email" id="email" required>
        <button type="submit">Send Reset Link</button>
    </form>
    <p><?php echo htmlspecialchars($message); ?></p>
</div>
</body>
</html>
