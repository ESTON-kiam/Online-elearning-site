<?php
session_name('instructor_session');
session_start();

if (!isset($_SESSION['instructor_id'])) {
    header("Location:http://localhost:8000/instructors");
    exit();
}

require_once 'include/database.php';

$instructor_id = $_SESSION['instructor_id'];
$error = '';
$success = '';


$stmt = $conn->prepare("SELECT * FROM instructors WHERE id = ?");
$stmt->bind_param("i", $instructor_id);
$stmt->execute();
$result = $stmt->get_result();
$instructor = $result->fetch_assoc();


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $expertise = filter_input(INPUT_POST, 'expertise', FILTER_SANITIZE_STRING);
    $bio = filter_input(INPUT_POST, 'bio', FILTER_SANITIZE_STRING);

    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    }

    
    $profile_image = $instructor['profile_image'];
    if (!empty($_FILES['profile_image']['name'])) {
        $upload_dir = 'profile_images/';
       
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $filename = uniqid() . '_' . basename($_FILES['profile_image']['name']);
        $upload_path = $upload_dir . $filename;

        
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_file_size = 5 * 1024 * 1024; 

        if (in_array($_FILES['profile_image']['type'], $allowed_types) && 
            $_FILES['profile_image']['size'] <= $max_file_size) {
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                
                if ($profile_image && file_exists($profile_image)) {
                    unlink($profile_image);
                }
                $profile_image = $upload_path;
            } else {
                $error = "Failed to upload profile image.";
            }
        } else {
            $error = "Invalid file type or size. Please upload a JPEG, PNG, or GIF under 5MB.";
        }
    }

   
    if (empty($error)) {
        $stmt = $conn->prepare("UPDATE instructors SET name = ?, email = ?, expertise = ?, bio = ?, profile_image = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $name, $email, $expertise, $bio, $profile_image, $instructor_id);
        
        if ($stmt->execute()) {
            $success = "Profile updated successfully!";
           
            $stmt = $conn->prepare("SELECT * FROM instructors WHERE id = ?");
            $stmt->bind_param("i", $instructor_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $instructor = $result->fetch_assoc();
        } else {
            $error = "Failed to update profile. " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Instructor Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h2 class="mb-4">Edit Profile</h2>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="profile_image" class="form-label">Profile Image</label>
                    <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
                    <?php if (!empty($instructor['profile_image'])): ?>
                        <img src="<?php echo htmlspecialchars($instructor['profile_image']); ?>" 
                             alt="Current Profile Image" 
                             class="img-thumbnail mt-2" 
                             style="max-width: 200px;">
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" 
                           value="<?php echo htmlspecialchars($instructor['username']); ?>" 
                           readonly>
                </div>

                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" name="name" 
                           value="<?php echo htmlspecialchars($instructor['name']); ?>" 
                           required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($instructor['email']); ?>" 
                           required>
                </div>

                <div class="mb-3">
                    <label for="expertise" class="form-label">Expertise</label>
                    <input type="text" class="form-control" id="expertise" name="expertise" 
                           value="<?php echo htmlspecialchars($instructor['expertise'] ?? ''); ?>">
                </div>

                <div class="mb-3">
                    <label for="bio" class="form-label">Bio</label>
                    <textarea class="form-control" id="bio" name="bio" rows="4"><?php 
                        echo htmlspecialchars($instructor['bio'] ?? ''); 
                    ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Last Login</label>
                    <input type="text" class="form-control" 
                           value="<?php echo htmlspecialchars($instructor['last_login'] ?? 'Never'); ?>" 
                           readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label">Account Created</label>
                    <input type="text" class="form-control" 
                           value="<?php echo htmlspecialchars($instructor['created_at']); ?>" 
                           readonly>
                </div>

                <button type="submit" class="btn btn-primary">Update Profile</button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>