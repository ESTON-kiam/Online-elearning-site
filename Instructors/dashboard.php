<?php
session_name('instructor_session');
session_start();

if (!isset($_SESSION['instructor_id'])) {
    header("Location:http://localhost:8000/instructors");
    exit();
}


require_once 'include/database.php';


$instructor_id = $_SESSION['instructor_id'];
$instructor_query = "SELECT * FROM instructors WHERE id = ?";
$stmt = $conn->prepare($instructor_query);
$stmt->bind_param("i", $instructor_id);
$stmt->execute();
$instructor_result = $stmt->get_result();
$instructor = $instructor_result->fetch_assoc();


$courses_query = "SELECT c.id, c.title, c.description, c.YearOfStudent, c.price, c.is_active,
                         COUNT(DISTINCT ci.id) AS enrolled_students
                  FROM courses c
                  JOIN course_instructors ci ON c.id = ci.course_id
                  WHERE ci.instructor_id = ?
                  GROUP BY c.id";
$stmt = $conn->prepare($courses_query);
$stmt->bind_param("i", $instructor_id);
$stmt->execute();
$courses_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link href="assets/css/dash.css" rel="stylesheet">
</head>
<body>
    <header class="dashboard-header">
        <div class="container">
            <h1>Instructor Dashboard</h1>
            <p>Welcome, <?php echo htmlspecialchars($instructor['name']); ?></p>
        </div>
    </header>
    <div class="container dashboard-section">
        <div class="row">
            <div class="col-md-4">
                <div class="profile-card">
                    <h3 class="mb-4">Profile Details</h3>
                    <p><strong>Username:</strong> <?php echo htmlspecialchars($instructor['username']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($instructor['email']); ?></p>
                    <p><strong>Expertise:</strong> <?php echo htmlspecialchars($instructor['expertise'] ?? 'Not specified'); ?></p>
                    <a href="edit_profile.php" class="btn btn-custom mt-3">Edit Profile</a>
                </div>
            </div>
            <div class="col-md-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>My Courses</h2>
                    <a href="create_course.php" class="btn btn-custom">Create New Course</a>
                </div>
                
                <?php if ($courses_result->num_rows > 0): ?>
                    <?php while($course = $courses_result->fetch_assoc()): ?>
                        <div class="course-card p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="m-0"><?php echo htmlspecialchars($course['title']); ?></h4>
                                <span class="badge <?php echo $course['is_active'] ? 'bg-success' : 'bg-warning'; ?> badge-status">
                                    <?php echo $course['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </div>
                            <p><?php echo htmlspecialchars($course['description']); ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($course['YearOfStudent']); ?> Year</span>
                                    <span class="badge bg-info ms-2">$<?php echo number_format($course['price'], 2); ?></span>
                                </div>
                                <div class="text-muted">
                                    Students Enrolled: <?php echo intval($course['enrolled_students']); ?>
                                </div>
                            </div>
                            <div class="mt-3">
                                <a href="edit_course.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-custom me-2">Edit Course</a>
                                <a href="course_details.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-outline-secondary">View Details</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        You haven't created any courses yet. 
                        <a href="create_course.php" class="alert-link">Create your first course</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>