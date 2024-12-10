<?php
session_name('instructor_session');
session_start();


if (!isset($_SESSION['instructor_id'])) {
    header("Location: http://localhost:8000/User/");
    exit();
}

require_once 'include/database.php';


if (!isset($_GET['course_id']) || !is_numeric($_GET['course_id'])) {
    header("Location: dashboard.php");
    exit();
}

$instructor_id = $_SESSION['instructor_id'];
$course_id = $_GET['course_id'];


$course_check_query = "SELECT c.id, c.title 
                       FROM courses c
                       JOIN course_instructors ci ON c.id = ci.course_id
                       WHERE c.id = ? AND ci.instructor_id = ?";
$stmt = $conn->prepare($course_check_query);
$stmt->bind_param("ii", $course_id, $instructor_id);
$stmt->execute();
$course_result = $stmt->get_result();

if ($course_result->num_rows == 0) {
    header("Location: dashboard.php");
    exit();
}

$course = $course_result->fetch_assoc();


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['notes_file'])) {
    $file = $_FILES['notes_file'];
    $title = $_POST['title'] ?? '';

    
    $errors = [];
    $allowed_types = ['pdf', 'docx', 'pptx', 'xlsx', 'txt'];
    $max_file_size = 10 * 1024 * 1024; 

    if (empty($title)) $errors[] = "Note title is required.";

    
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_types)) {
        $errors[] = "Invalid file type. Allowed types: " . implode(', ', $allowed_types);
    }

    
    if ($file['size'] > $max_file_size) {
        $errors[] = "File size must be less than 10 MB.";
    }

    if (empty($errors)) {
        
        $upload_dir = 'uploads/course_notes/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

       
        $filename = uniqid() . '_' . $file['name'];
        $file_path = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            
            $insert_query = "INSERT INTO course_notes 
                             (course_id, title, file_path, file_type, file_size) 
                             VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $file_size_kb = round($file['size'] / 1024, 2);
            $stmt->bind_param("isssi", $course_id, $title, $file_path, $file_ext, $file_size_kb);

            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Notes uploaded successfully!";
                header("Location: course_resources.php?id=" . $course_id);
                exit();
            } else {
                $errors[] = "Failed to save file information.";
                unlink($file_path); 
            }
        } else {
            $errors[] = "File upload failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Course Notes - <?php echo htmlspecialchars($course['title']); ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h2 class="mb-0">
                            <i class="bi bi-cloud-upload me-2"></i>Upload Course Notes
                        </h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $error): ?>
                                    <p class="mb-1"><?php echo htmlspecialchars($error); ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="title" class="form-label">Notes Title</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                                <small class="form-text text-muted">
                                    Provide a descriptive title for the notes
                                </small>
                            </div>

                            <div class="mb-3">
                                <label for="notes_file" class="form-label">Upload File</label>
                                <input class="form-control" type="file" id="notes_file" name="notes_file" 
                                       accept=".pdf,.docx,.pptx,.xlsx,.txt" required>
                                <small class="form-text text-muted">
                                    File types: PDF, DOCX, PPTX, XLSX, TXT (Max 10MB)
                                </small>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-cloud-upload me-2"></i>Upload Notes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <a href="course_resources.php?id=<?php echo $course_id; ?>" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Back to Course Resources
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>