<?php
session_name('super_admin');
session_start();
require_once 'include/database.php';

global $database, $conn;


if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin');
    exit();
}


if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $_SESSION['error_message'] = "Invalid instructor ID.";
    header('Location: manage_instructors.php');
    exit();
}

$instructor_id = (int)$_GET['id'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $database->sanitizeInput($_POST['name']);
    $username = $database->sanitizeInput($_POST['username']);
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $expertise = $database->sanitizeInput($_POST['expertise']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $errors = [];

   
    if (empty($name)) {
        $errors[] = "Name is required.";
    }

    if (empty($username)) {
        $errors[] = "Username is required.";
    }

    if (!$email) {
        $errors[] = "Invalid email address.";
    }

   
    $profile_image = null;
    if (!empty($_FILES['profile_image']['name'])) {
        $upload_dir = 'uploads/instructors/';
        
        
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = uniqid() . '_' . basename($_FILES['profile_image']['name']);
        $target_path = $upload_dir . $file_name;
        $image_file_type = strtolower(pathinfo($target_path, PATHINFO_EXTENSION));

        
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($image_file_type, $allowed_types)) {
            $errors[] = "Invalid image file type. Allowed types: JPG, JPEG, PNG, GIF.";
        }

      
        if ($_FILES['profile_image']['size'] > 5 * 1024 * 1024) {
            $errors[] = "Image file is too large. Maximum size is 5MB.";
        }

        if (empty($errors)) {
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_path)) {
                $profile_image = $target_path;
            } else {
                $errors[] = "Failed to upload image.";
            }
        }
    }

   
    if (empty($errors)) {
        try {
            
            $update_query = $profile_image 
                ? "UPDATE instructors SET name = ?, username = ?, email = ?, expertise = ?, is_active = ?, profile_image = ? WHERE id = ?"
                : "UPDATE instructors SET name = ?, username = ?, email = ?, expertise = ?, is_active = ? WHERE id = ?";

            $stmt = $conn->prepare($update_query);

            if ($profile_image) {
                $stmt->bind_param("ssssissi", $name, $username, $email, $expertise, $is_active, $profile_image, $instructor_id);
            } else {
                $stmt->bind_param("sssssi", $name, $username, $email, $expertise, $is_active, $instructor_id);
            }

            $result = $stmt->execute();
            $stmt->close();

            if ($result) {
                $_SESSION['success_message'] = "Instructor updated successfully!";
                header('Location: manage_instructors.php');
                exit();
            } else {
                $errors[] = "Failed to update instructor.";
            }
        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}


try {
    $stmt = $conn->prepare("SELECT * FROM instructors WHERE id = ?");
    $stmt->bind_param("i", $instructor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $instructor = $result->fetch_assoc();
    $stmt->close();

    if (!$instructor) {
        $_SESSION['error_message'] = "Instructor not found.";
        header('Location: manage_instructors.php');
        exit();
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = "Database error: " . $e->getMessage();
    header('Location: manage_instructors.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Instructor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h2 class="mb-0">Edit Instructor</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul>
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($instructor['name']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($instructor['username']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($instructor['email']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="expertise" class="form-label">Expertise</label>
                                <input type="text" class="form-control" id="expertise" name="expertise" 
                                       value="<?php echo htmlspecialchars($instructor['expertise'] ?? ''); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="profile_image" class="form-label">Profile Image</label>
                                <input type="file" class="form-control" id="profile_image" name="profile_image" 
                                       accept="image/jpeg,image/png,image/gif">
                                <?php if (!empty($instructor['profile_image'])): ?>
                                    <small class="text-muted">Current image: 
                                        <?php echo basename(htmlspecialchars($instructor['profile_image'])); ?>
                                    </small>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                                       <?php echo $instructor['is_active'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">Active Instructor</label>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="manage_instructors.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left me-1"></i>Back to Instructors
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i>Update Instructor
                                </button>
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