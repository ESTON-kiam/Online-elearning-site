<?php
session_name('student_session');
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: http://localhost:8000/User/");
    exit();
}

require_once 'include/database.php';
$student_id = $_SESSION['student_id'];
$error_message = '';
$success_message = '';


$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $username = filter_input(INPUT_POST, 'username', );
    $first_name = filter_input(INPUT_POST, 'first_name',);
    $last_name = filter_input(INPUT_POST, 'last_name', );
    $email = filter_input(INPUT_POST, 'email', );
    $phone_number = filter_input(INPUT_POST, 'phone_number', );

    
    $profile_image = $student['profile_image'];
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $upload_dir = 'Userprof/';
        
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_name = $student_id . '_' . time() . '_' . basename($_FILES['profile_image']['name']);
        $upload_path = $upload_dir . $file_name;

       
        if ($_FILES['profile_image']['size'] <= 5 * 1024 * 1024) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = mime_content_type($_FILES['profile_image']['tmp_name']);

            if (in_array($file_type, $allowed_types)) {
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                    
                    if ($student['profile_image'] && file_exists($student['profile_image'])) {
                        unlink($student['profile_image']);
                    }
                    $profile_image = $upload_path;
                } else {
                    $error_message = "Failed to upload profile image.";
                }
            } else {
                $error_message = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
            }
        } else {
            $error_message = "File too large. Maximum size is 5MB.";
        }
    }

   
    if (empty($error_message)) {
        $stmt = $conn->prepare("UPDATE students SET username = ?, first_name = ?, last_name = ?, email = ?, phone_number = ?, profile_image = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $username, $first_name, $last_name, $email, $phone_number, $profile_image, $student_id);
        
        if ($stmt->execute()) {
            $success_message = "Profile updated successfully!";
            
            
            $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $student = $result->fetch_assoc();
        } else {
            $error_message = "Error updating profile: " . $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
     <link href="assets/css/profile.css" rel="stylesheet">
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 profile-container">
            <h2 class="text-center mb-4 text-primary">Edit Profile</h2>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="text-center mb-4">
                <img src="Userprof/<?php echo htmlspecialchars($student['profile_image']) ?: 'default-profile.png'; ?>" 
     alt="Profile Image" 
     class="profile-image mb-3">

                    <div class="mb-3">
                        <label for="profile_image" class="form-label">Change Profile Picture</label>
                        <input type="file" id="profile_image" name="profile_image" class="form-control" accept="image/jpeg,image/png,image/gif">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" 
                           value="<?php echo htmlspecialchars($student['username']); ?>" required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" class="form-control" 
                               value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-control" 
                               value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($student['email']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="phone_number" class="form-control" 
                           value="<?php echo htmlspecialchars($student['phone_number']); ?>">
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">Update Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>