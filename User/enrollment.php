<?php


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/PHPMailer/src/Exception.php';
require 'PHPMailer/PHPMailer/src/PHPMailer.php';
require 'PHPMailer/PHPMailer/src/SMTP.php';


require 'vendor/autoload.php';
session_name('student_session');
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: http://localhost:8000/User/");
    exit();
}

require_once 'include/database.php';


$student_id = $_SESSION['student_id'];


if (isset($_GET['course_id'])) {
    $course_id = $_GET['course_id'];

    
    $check_enrollment_query = "SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?";
    $stmt = $conn->prepare($check_enrollment_query);
    $stmt->bind_param("ii", $student_id, $course_id);
    $stmt->execute();
    $enrollment_result = $stmt->get_result();

    if ($enrollment_result->num_rows > 0) {
       
        $message = "You are already enrolled in this course!";
    } else {
       
        $enrollment_query = "INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)";
        $stmt = $conn->prepare($enrollment_query);
        $stmt->bind_param("ii", $student_id, $course_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $message = "Successfully enrolled in the course!";

            
            $student_query = "SELECT * FROM students WHERE id = ?";
            $stmt = $conn->prepare($student_query);
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $student_result = $stmt->get_result();
            $student = $student_result->fetch_assoc();

            
            $course_query = "SELECT * FROM courses WHERE id = ?";
            $stmt = $conn->prepare($course_query);
            $stmt->bind_param("i", $course_id);
            $stmt->execute();
            $course_result = $stmt->get_result();
            $course = $course_result->fetch_assoc();

           
            $mail = new PHPMailer(true);
            try {
                
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';                   
            $mail->SMTPAuth   = true;                                
            $mail->Username   = 'engestonbrandon@gmail.com';            
            $mail->Password   = 'dsth izzm npjl qebi';                    
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;     
            $mail->Port       = 587; 

               
                $mail->setFrom('your-email@gmail.com', 'Course Enrollment');
                $mail->addAddress($student['email'], $student['username']); 

             
                $mail->isHTML(true);
                $mail->Subject = 'Course Enrollment Confirmation';
                $mail->Body    = "<h1>Enrollment Successful</h1>
                                  <p>Hello, " . htmlspecialchars($student['username']) . ",</p>
                                  <p>You have successfully enrolled in the course: <strong>" . htmlspecialchars($course['title']) . "</strong></p>
                                 
                                  <p><strong>Course Description:</strong> " . htmlspecialchars($course['description']) . "</p>
                                  <p><strong>Price:</strong> KES " . number_format($course['price'], 2) . "</p>
                                  <p>Thank you for choosing our platform for your learning journey!</p>";

                $mail->send();
            } catch (Exception $e) {
                $message = "There was an error sending the email: " . $mail->ErrorInfo;
            }
        } else {
            $message = "There was an error enrolling in the course.";
        }
    }
} else {
    $message = "No course selected.";
}


$courses_query = "
    SELECT c.id, c.title, c.description, c.YearOfStudent, c.price, i.name AS instructor_name,
    (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) AS enrolled_count
    FROM courses c
    JOIN course_instructors ci ON c.id = ci.course_id
    JOIN instructors i ON ci.instructor_id = i.id
    WHERE c.is_active = 1 AND 
    c.id NOT IN (SELECT course_id FROM enrollments WHERE student_id = ?)";
$stmt = $conn->prepare($courses_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$courses_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Enrollment</title>

    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
     
body {
    font-family: 'Arial', sans-serif;
    background-color: #f8f9fa;
    margin: 0;
    padding: 0;
}

/* Header Styling */
.dashboard-header {
    background-color: #343a40;
    color: #fff;
    padding: 20px 0;
}

.dashboard-header h1 {
    font-size: 2.5rem;
    margin: 0;
}

.dashboard-header p {
    font-size: 1.25rem;
    margin: 5px 0;
}

/* Main Content */
.main-content {
    padding: 20px;
}

/* Dashboard Section */
.dashboard-section {
    margin-top: 30px;
}

/* Course Card Styles */
.course-card {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    position: relative;
}

.course-card:hover {
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    transform: translateY(-5px);
}

.course-card h4 {
    font-size: 1.5rem;
    color: #333;
    margin-bottom: 15px;
}

.course-card p {
    color: #555;
    font-size: 1rem;
    margin-bottom: 15px;
}

.course-card .d-flex {
    margin-top: 10px;
}

.course-card .badge {
    font-size: 0.9rem;
    background-color: #007bff;
    color: white;
}

.course-card .text-muted {
    font-size: 0.9rem;
}

.course-card .fw-bold {
    font-size: 1.1rem;
}

.course-card .btn-custom {
    background-color: #28a745;
    color: white;
    font-weight: bold;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    transition: background-color 0.3s ease;
}

.course-card .btn-custom:hover {
    background-color: #218838;
}

.enrolled-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background-color: #28a745;
    color: white;
    padding: 5px 10px;
    border-radius: 3px;
}

/* Alert Message */
.alert-info {
    background-color: #17a2b8;
    color: white;
    font-weight: bold;
}

/* Responsive Styling */
@media (max-width: 768px) {
    .dashboard-header h1 {
        font-size: 2rem;
    }

    .dashboard-header p {
        font-size: 1rem;
    }

    .course-card {
        margin-bottom: 15px;
    }

    .course-card .btn-custom {
        width: 100%;
    }
}
    </style>
</head>

<body>
    <div id="mainContent" class="main-content">
        <header class="dashboard-header">
            <div class="container">
                <h1>Course Enrollment</h1>
                <p>Select and Enroll in Courses</p>
            </div>
        </header>

        <div class="container dashboard-section">
            <?php if (isset($message)): ?>
                <div class="alert alert-info"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-12">
                    <h2 class="mb-4">Available Courses</h2>
                    <?php if ($courses_result->num_rows > 0): ?>
                        <?php while ($course = $courses_result->fetch_assoc()): ?>
                            <div class="course-card p-3 mb-3">
                                <h4><?php echo htmlspecialchars($course['title']); ?></h4>
                                <p><?php echo htmlspecialchars($course['description']); ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($course['YearOfStudent']); ?> Year</span>
                                    <span class="text-muted">Instructor: <?php echo htmlspecialchars($course['instructor_name']); ?></span>
                                    <span class="fw-bold">KES<?php echo number_format($course['price'], 2); ?></span>
                                </div>
                                <span class="text-muted">Enrolled Students: <?php echo $course['enrolled_count']; ?></span>
                                <a href="#" 
                                   class="btn btn-custom mt-3 enroll-btn" 
                                   data-course-id="<?php echo $course['id']; ?>"
                                   data-course-title="<?php echo htmlspecialchars($course['title']); ?>">
                                    Enroll Now
                                </a>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="alert alert-info">No courses available for enrollment at the moment.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add click event listener to all enroll buttons
            const enrollButtons = document.querySelectorAll('.enroll-btn');
            
            enrollButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const courseId = this.getAttribute('data-course-id');
                    const courseTitle = this.getAttribute('data-course-title');
                    
                    // Show confirmation dialog
                    const confirmEnroll = confirm(`Are you sure you want to enroll in the course: ${courseTitle}?`);
                    
                    if (confirmEnroll) {
                        // Redirect to enrollment page with course ID
                        window.location.href = `enrollment.php?course_id=${courseId}`;
                    }
                });
            });
        });
    </script>
</body>

</html>