<?php
session_name('student_session');
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: http://localhost:8000/User/");
    exit();
}

require_once 'include/database.php';

$student_id = $_SESSION['student_id'];
$student_query = "SELECT * FROM students WHERE id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();

$courses_query = "
    SELECT c.id, c.title, c.description, c.YearOfStudent, c.price, i.name AS instructor_name
    FROM courses c
    JOIN course_instructors ci ON c.id = ci.course_id
    JOIN instructors i ON ci.instructor_id = i.id
    WHERE c.is_active = 1";
$courses_result = $conn->query($courses_query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Courses</title>

  
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
        }

        body {
            background-color: #f4f6f7;
            font-family: 'Arial', sans-serif;
        }

        .course-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
            transition: transform 0.3s ease;
        }

        .course-card:hover {
            transform: scale(1.02);
        }

        .btn-custom {
            background-color: var(--primary-color);
            color: white;
            border: none;
            transition: background-color 0.3s ease;
        }

        .btn-custom:hover {
            background-color: #2980b9;
            color: white;
        }

        .container {
            margin-top: 20px;
        }
    </style>
</head>

<body>
   

    <div class="container">
        <h2 class="my-4">Available Courses</h2>
        <div class="row">
            <?php if ($courses_result->num_rows > 0): ?>
                <?php while ($course = $courses_result->fetch_assoc()): ?>
                    <div class="col-md-4">
                        <div class="course-card p-3">
                            <h4><?php echo htmlspecialchars($course['title']); ?></h4>
                            <p><?php echo htmlspecialchars($course['description']); ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge bg-primary"><?php echo htmlspecialchars($course['YearOfStudent']); ?> Year</span>
                                <span class="text-muted">Instructor: <?php echo htmlspecialchars($course['instructor_name']); ?></span>
                                <span class="fw-bold">KES<?php echo number_format($course['price'], 2); ?></span>
                            </div>
                            <a href="enrollment.php?course_id=<?php echo $course['id']; ?>" class="btn btn-custom mt-3">Enroll Now</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info col-12">No courses available at the moment.</div>
            <?php endif; ?>
        </div>
    </div>

   

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
