<?php
session_name('super_admin');
session_start();
require_once 'include/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin');
    exit();
}
function fetchPendingCourses() {
    global $conn;
    
    $query = "SELECT id, title, description FROM courses WHERE allocation_status = 'pending'";
    $result = $conn->query($query);
    
    $courses = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }
    }
    
    return $courses;
}


function fetchInstructors() {
    global $conn;
    
    $query = "SELECT id, name,username, expertise,bio FROM instructors";
    $result = $conn->query($query);
    
    $instructors = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $instructors[] = $row;
        }
    }
    
    return $instructors;
}


function allocateCourseToInstructor($course_id, $instructor_id) {
    global $conn;

    try {
    
        $conn->begin_transaction();

        
        $check_course_query = "SELECT id, allocation_status FROM courses WHERE id = ?";
        $check_stmt = $conn->prepare($check_course_query);
        $check_stmt->bind_param("i", $course_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows === 0) {
            throw new Exception("Course not found.");
        }

        $course_data = $check_result->fetch_assoc();
        
        
        if ($course_data['allocation_status'] === 'allocated') {
            throw new Exception("Course is already allocated.");
        }

        
        $check_instructor_query = "SELECT id FROM instructors WHERE id = ?";
        $check_inst_stmt = $conn->prepare($check_instructor_query);
        $check_inst_stmt->bind_param("i", $instructor_id);
        $check_inst_stmt->execute();
        $check_inst_result = $check_inst_stmt->get_result();

        if ($check_inst_result->num_rows === 0) {
            throw new Exception("Instructor not found.");
        }

       
        $insert_query = "INSERT INTO course_instructors (course_id, instructor_id) VALUES (?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("ii", $course_id, $instructor_id);
        
        if (!$insert_stmt->execute()) {
            
            if ($insert_stmt->errno == 1062) {
                throw new Exception("This course is already allocated to this instructor.");
            }
            throw new Exception("Error allocating course: " . $insert_stmt->error);
        }

       
        $update_query = "UPDATE courses SET allocation_status = 'allocated' WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("i", $course_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception("Error updating course allocation status: " . $update_stmt->error);
        }

       
        $conn->commit();

        return true;

    } catch (Exception $e) {
        
        $conn->rollback();
        
       
        error_log("Course Allocation Error: " . $e->getMessage());
        
        return $e->getMessage();
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    $pending_courses = fetchPendingCourses();
    $instructors = fetchInstructors();
}
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
   
    $course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
    $instructor_id = filter_input(INPUT_POST, 'instructor_id', FILTER_VALIDATE_INT);

    if ($course_id === false || $instructor_id === false) {
        die(json_encode([
            'status' => 'error',
            'message' => 'Invalid input. Please provide valid course and instructor IDs.'
        ]));
    }

    $result = allocateCourseToInstructor($course_id, $instructor_id);

    if ($result === true) {
       
        echo json_encode([
            'status' => 'success',
            'message' => 'Course successfully allocated to instructor.'
        ]);
    } else {
       
        echo json_encode([
            'status' => 'error',
            'message' => $result  
        ]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Course Allocation</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        form { background-color: #f9f9f9; padding: 20px; border-radius: 5px; }
        select, input[type="submit"] { margin: 10px 0; padding: 5px; width: 100%; }
    </style>
</head>
<body>
    <h1>Course Allocation</h1>

    <h2>Pending Courses</h2>
    <table>
        <thead>
            <tr>
                <th>Course ID</th>
                <th>Course Code</th>
                <th>Course Name</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pending_courses as $course): ?>
                <tr>
                    <td><?php echo htmlspecialchars($course['id']); ?></td>
                    <td><?php echo htmlspecialchars($course['title']); ?></td>
                    <td><?php echo htmlspecialchars($course['description']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Allocate Course</h2>
    <form id="courseAllocationForm" method="POST">
        <label for="course_id">Select Course:</label>
        <select id="course_id" name="course_id" required>
            <option value="">Choose a Course</option>
            <?php foreach ($pending_courses as $course): ?>
                <option value="<?php echo $course['id']; ?>">
                    <?php echo htmlspecialchars($course['title'] . ' - ' . $course['description']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="instructor_id">Select Instructor:</label>
        <select id="instructor_id" name="instructor_id" required>
            <option value="">Choose an Instructor</option>
            <?php foreach ($instructors as $instructor): ?>
                <option value="<?php echo $instructor['id']; ?>">
                    <?php echo htmlspecialchars($instructor['name'] . ' ' . $instructor['username'] . ' (' . $instructor['expertise'] . ')'); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="submit" value="Allocate Course">
    </form>

    <script>
    document.getElementById('courseAllocationForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        fetch('<?php echo $_SERVER["PHP_SELF"]; ?>', {
            method: 'POST',
            body: new FormData(this)
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert('Course allocated successfully!');
                // Optionally, reload the page to show updated list
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An unexpected error occurred.');
        });
    });
    </script>
</body>
</html>