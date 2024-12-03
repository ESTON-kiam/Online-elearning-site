<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/PHPMailer/src/Exception.php';
require 'PHPMailer/PHPMailer/src/PHPMailer.php';
require 'PHPMailer/PHPMailer/src/SMTP.php';

require 'vendor/autoload.php';
session_name('super_admin');
session_start();
require_once 'include/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin');
    exit();
}


global $database, $conn;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
   
    $name = $database->sanitizeInput($_POST['name']);
    $username = $database->sanitizeInput($_POST['username']);
    $email = $database->sanitizeInput($_POST['email']);
    $expertise = $database->sanitizeInput($_POST['expertise'] ?? '');
    $bio = $database->sanitizeInput($_POST['bio'] ?? '');
    $password = $_POST['password'];

    $errors = [];

    if (empty($name)) $errors[] = "Name is required";
    if (empty($username)) $errors[] = "Username is required";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
    if (empty($password) || strlen($password) < 8) $errors[] = "Password must be at least 8 characters long";

    $profile_image = null;
    if (!empty($_FILES['profile_image']['name'])) {
        $upload_dir = 'InstructorsProf/';
        $filename = uniqid() . '_' . basename($_FILES['profile_image']['name']);
        $upload_path = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
            $profile_image = $upload_path;
        } else {
            $errors[] = "Failed to upload profile image";
        }
    }

    if (empty($errors)) {
        try {
            
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

           
            $stmt = $conn->prepare("INSERT INTO instructors 
                (name, username, email, password, expertise, bio, profile_image) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            
            $stmt->bind_param("sssssss", 
                $name, 
                $username, 
                $email, 
                $hashed_password, 
                $expertise, 
                $bio, 
                $profile_image
            );
            
           
            $result = $stmt->execute();

            if ($result) {
                $mail = new PHPMailer(true);

                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'engestonbrandon@gmail.com';
                    $mail->Password = 'dsth izzm npjl qebi'; 
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->setFrom('your_email@example.com', 'Elearning Platform');
                    $mail->addAddress($email, $name);

                    $mail->isHTML(true);
                    $mail->Subject = 'Instructor Registration Successful';
                    $mail->Body = "<h3>Welcome to Our Platform!</h3>
                                   <p>Hi <b>$name</b>,</p>
                                   <p>You have been successfully registered as an instructor. Here are your login details:</p>
                                   <ul>
                                       <li><b>Username:</b> $username</li>
                                       <li><b>Password:</b> $password</li>
                                   </ul>
                                   <p>Please change your password after logging in.</p>";

                    $mail->send();
                } catch (Exception $e) {
                    $errors[] = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                }

                $_SESSION['success_message'] = "Instructor added successfully!";
                header("Location: manage_instructors.php");
                exit();
            } else {
                
                if ($conn->errno == 1062) {
                    $errors[] = "Username or email already exists";
                } else {
                    $errors[] = "Database error: " . $conn->error;
                }
            }

           
            $stmt->close();
        } catch (Exception $e) {
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Instructor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h2 class="text-center">Add New Instructor</h2>
                    </div>
                    <div class="card-body">
                        <?php 
                        if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $error): ?>
                                    <p><?php echo $error; ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo isset($name) ? $name : ''; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo isset($username) ? $username : ''; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo isset($email) ? $email : ''; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="expertise" class="form-label">Area of Expertise</label>
                                <input type="text" class="form-control" id="expertise" name="expertise" 
                                       value="<?php echo isset($expertise) ? $expertise : ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="bio" class="form-label">Bio</label>
                                <textarea class="form-control" id="bio" name="bio" rows="4"><?php 
                                    echo isset($bio) ? $bio : ''; 
                                ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="profile_image" class="form-label">Profile Image</label>
                                <input type="file" class="form-control" id="profile_image" name="profile_image" 
                                       accept="image/jpeg,image/png,image/gif">
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Add Instructor</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php 

$database->closeConnection();
?>