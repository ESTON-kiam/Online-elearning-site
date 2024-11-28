<?php
session_start();


if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'super_admin') {
    header("Location: login.php");
    exit();
}


require_once 'config/database.php';


function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}


if (isset($_POST['add_course'])) {
    $course_title = sanitize_input($_POST['course_title']);
    $course_description = sanitize_input($_POST['course_description']);
    $course_category = sanitize_input($_POST['course_category']);
    $course_level = sanitize_input($_POST['course_level']);
    $course_price = floatval($_POST['course_price']);

    $stmt = $pdo->prepare("INSERT INTO courses (title, description, category, level, price, created_at) 
                            VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$course_title, $course_description, $course_category, $course_level, $course_price]);
}


if (isset($_POST['add_instructor'])) {
    $instructor_name = sanitize_input($_POST['instructor_name']);
    $instructor_email = filter_var($_POST['instructor_email'], FILTER_VALIDATE_EMAIL);
    $instructor_expertise = sanitize_input($_POST['instructor_expertise']);
    $instructor_bio = sanitize_input($_POST['instructor_bio']);

    
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("INSERT INTO instructors (name, email, expertise, bio, password, created_at) 
                            VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$instructor_name, $instructor_email, $instructor_expertise, $instructor_bio, $password]);
}

// Handle Course Allocation
if (isset($_POST['allocate_course'])) {
    $course_id = intval($_POST['course_id']);
    $instructor_id = intval($_POST['instructor_id']);

    $stmt = $pdo->prepare("INSERT INTO course_instructors (course_id, instructor_id, allocated_at) 
                            VALUES (?, ?, NOW())");
    $stmt->execute([$course_id, $instructor_id]);
}

// Fetch Courses
$courses_stmt = $pdo->query("SELECT * FROM courses ORDER BY created_at DESC");
$courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Instructors
$instructors_stmt = $pdo->query("SELECT * FROM instructors ORDER BY created_at DESC");
$instructors = $instructors_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Course Allocations
$allocations_stmt = $pdo->query("
    SELECT ci.id, c.title AS course_title, i.name AS instructor_name, ci.allocated_at 
    FROM course_instructors ci
    JOIN courses c ON ci.course_id = c.id
    JOIN instructors i ON ci.instructor_id = i.id
    ORDER BY ci.allocated_at DESC
");
$course_allocations = $allocations_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Super Admin Dashboard - E-Learning Platform</title>
    <link rel="stylesheet" href="assets/css/dash.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Super Admin Dashboard</h1>
            <div class="user-info">
                Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?> 
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </header>

        <div class="dashboard-grid">
            <!-- Add Course Section -->
            <section class="dashboard-section">
                <h2>Add New Course</h2>
                <form method="POST" action="">
                    <input type="text" name="course_title" placeholder="Course Title" required>
                    <textarea name="course_description" placeholder="Course Description" required></textarea>
                    <select name="course_category" required>
                        <option value="">Select Category</option>
                        <option value="technology">Technology</option>
                        <option value="business">Business</option>
                        <option value="design">Design</option>
                        <option value="personal_development">Personal Development</option>
                    </select>
                    <select name="course_level" required>
                        <option value="">Select Level</option>
                        <option value="beginner">Beginner</option>
                        <option value="intermediate">Intermediate</option>
                        <option value="advanced">Advanced</option>
                    </select>
                    <input type="number" name="course_price" step="0.01" placeholder="Course Price" required>
                    <button type="submit" name="add_course">Add Course</button>
                </form>
            </section>

            <!-- Add Instructor Section -->
            <section class="dashboard-section">
                <h2>Add New Instructor</h2>
                <form method="POST" action="">
                    <input type="text" name="instructor_name" placeholder="Instructor Name" required>
                    <input type="email" name="instructor_email" placeholder="Email" required>
                    <input type="password" name="password" placeholder="Initial Password" required>
                    <input type="text" name="instructor_expertise" placeholder="Area of Expertise" required>
                    <textarea name="instructor_bio" placeholder="Instructor Biography" required></textarea>
                    <button type="submit" name="add_instructor">Add Instructor</button>
                </form>
            </section>

            <!-- Course Allocation Section -->
            <section class="dashboard-section">
                <h2>Allocate Course to Instructor</h2>
                <form method="POST" action="">
                    <select name="course_id" required>
                        <option value="">Select Course</option>
                        <?php foreach($courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>">
                                <?php echo htmlspecialchars($course['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="instructor_id" required>
                        <option value="">Select Instructor</option>
                        <?php foreach($instructors as $instructor): ?>
                            <option value="<?php echo $instructor['id']; ?>">
                                <?php echo htmlspecialchars($instructor['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="allocate_course">Allocate Course</button>
                </form>
            </section>

            <!-- Course List Section -->
            <section class="dashboard-section">
                <h2>Existing Courses</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Level</th>
                            <th>Price</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($courses as $course): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course['title']); ?></td>
                                <td><?php echo htmlspecialchars($course['category']); ?></td>
                                <td><?php echo htmlspecialchars($course['level']); ?></td>
                                <td>$<?php echo number_format($course['price'], 2); ?></td>
                                <td><?php echo date('d M Y', strtotime($course['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>

            <!-- Instructor List Section -->
            <section class="dashboard-section">
                <h2>Instructors</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Expertise</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($instructors as $instructor): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($instructor['name']); ?></td>
                                <td><?php echo htmlspecialchars($instructor['email']); ?></td>
                                <td><?php echo htmlspecialchars($instructor['expertise']); ?></td>
                                <td><?php echo date('d M Y', strtotime($instructor['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>

            <!-- Course Allocation List Section -->
            <section class="dashboard-section">
                <h2>Course Allocations</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Instructor</th>
                            <th>Allocated At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($course_allocations as $allocation): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($allocation['course_title']); ?></td>
                                <td><?php echo htmlspecialchars($allocation['instructor_name']); ?></td>
                                <td><?php echo date('d M Y H:i', strtotime($allocation['allocated_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </div>

        <footer>
            <p>&copy; <?php echo date('Y'); ?> E-Learning Platform. All Rights Reserved.</p>
        </footer>
    </div>

    <script src="assets/js/admin.js"></script>
</body>
</html>