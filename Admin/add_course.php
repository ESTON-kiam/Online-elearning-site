<?php
session_name('super_admin');
session_start();

require_once 'include/database.php'; 

if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new DatabaseConnection(); 
    $conn = $database->getConnection(); 

    
    $title = $database->sanitizeInput($_POST['title']);
    $description = $database->sanitizeInput($_POST['description']);
    $year_of_student = $database->sanitizeInput($_POST['year_of_student']);
    $category = $database->sanitizeInput($_POST['category']);
    $price = $database->sanitizeInput($_POST['price']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $errors = [];
    if (empty($title)) {
        $errors[] = "Course title is required.";
    }
    if (!is_numeric($price) || $price < 0) {
        $errors[] = "Invalid price format.";
    }
    if (!in_array($year_of_student, ['1', '2', '3', '4'])) {
        $errors[] = "Invalid year of student.";
    }

    $valid_categories = [
        'SCHOOL OF CO-OPERATIVES AND COMMUNITY DEVELOPMENT',
        'SCHOOL OF BUSINESS AND ECONOMICS',
        'SCHOOL OF COMPUTING AND MATHEMATICS',
        'SCHOOL OF NURSING'
    ];
    if (!in_array($category, $valid_categories)) {
        $errors[] = "Invalid category selected.";
    }

    if (empty($errors)) {
        try {
            
            $stmt = $conn->prepare("INSERT INTO courses (title, description, YearOfStudent, category, price, is_active) 
                                    VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssid", $title, $description, $year_of_student, $category, $price, $is_active);

            if ($stmt->execute()) {
                $success_message = "Course added successfully. Course ID: " . $conn->insert_id;
            } else {
                $errors[] = "Failed to add course.";
            }

            $stmt->close(); 
        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }

    $database->closeConnection(); 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Course</title>
    <link href="assets/css/add.css" rel="stylesheet">
</head>
<body>
   

    <h1>Add New Course</h1>
 <?php
    if (!empty($errors)) {
        echo "<div style='color: red;'><ul>";
        foreach ($errors as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul></div>";
    }

    if (isset($success_message)) {
        echo "<div style='color: green;'>" . htmlspecialchars($success_message) . "</div>";
    }
    ?>

    <form method="POST" action="">
        <div>
            <label for="title">Course Title:</label>
            <input type="text" id="title" name="title" required>
        </div>

        <div>
            <label for="description">Description:</label>
            <textarea id="description" name="description"></textarea>
        </div>

        <div>
            <label for="year_of_student">Year of Student:</label>
            <select id="year_of_student" name="year_of_student" required>
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
            </select>
        </div>

        <div>
            <label for="category">Category:</label>
            <select id="category" name="category" required>
                <option value="">Select a Category</option>
                <option value="SCHOOL OF CO-OPERATIVES AND COMMUNITY DEVELOPMENT">School of Co-operatives and Community Development</option>
                <option value="SCHOOL OF BUSINESS AND ECONOMICS">School of Business and Economics</option>
                <option value="SCHOOL OF COMPUTING AND MATHEMATICS">School of Computing and Mathematics</option>
                <option value="SCHOOL OF NURSING">School of Nursing</option>
            </select>
        </div>

        <div>
            <label for="price">Price:</label>
            <input type="number" id="price" name="price" step="0.01" min="0" required>
        </div>

        <div>
            <label for="is_active">Active Course:</label>
            <input type="checkbox" id="is_active" name="is_active" value="1" checked>
        </div>

        <div>
            <input type="submit" value="Add Course">
        </div>
    </form>
</body>
</html>
