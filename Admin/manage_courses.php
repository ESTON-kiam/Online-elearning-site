<?php
session_name('super_admin');
session_start();

require_once 'include/database.php'; 

if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin');
    exit();
}


$database = new DatabaseConnection();
$conn = $database->getConnection();


if (isset($_GET['delete_id'])) {
    $course_id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
    $stmt->bind_param("i", $course_id);
    if ($stmt->execute()) {
        $success_message = "Course deleted successfully.";
    } else {
        $errors[] = "Failed to delete course.";
    }
    $stmt->close();
}


if (isset($_POST['edit_id'])) {
    $course_id = $_POST['edit_id'];
    $title = $database->sanitizeInput($_POST['title']);
    $description = $database->sanitizeInput($_POST['description']);
    $year_of_student = $database->sanitizeInput($_POST['year_of_student']);
    $category = $database->sanitizeInput($_POST['category']);
    $price = $database->sanitizeInput($_POST['price']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $stmt = $conn->prepare("UPDATE courses SET title = ?, description = ?, YearOfStudent = ?, category = ?, price = ?, is_active = ? WHERE id = ?");
    $stmt->bind_param("ssssid", $title, $description, $year_of_student, $category, $price, $is_active, $course_id);

    if ($stmt->execute()) {
        $success_message = "Course updated successfully.";
    } else {
        $errors[] = "Failed to update course.";
    }
    $stmt->close();
}


$sql = "SELECT * FROM courses";
$result = $conn->query($sql);

$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }

        h1 {
            text-align: center;
            color: #333;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .course-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .course-table th, .course-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .course-table th {
            background-color: #f2f2f2;
        }

        .btn {
            padding: 8px 16px;
            margin: 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-edit {
            background-color: #4CAF50;
            color: white;
        }

        .btn-delete {
            background-color: #f44336;
            color: white;
        }

        .btn:hover {
            opacity: 0.8;
        }

        .success-message {
            color: green;
            font-weight: bold;
        }

        .error-message {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <h1>Manage Courses</h1>
    <div class="container">

        <?php
        if (isset($success_message)) {
            echo "<div class='success-message'>" . htmlspecialchars($success_message) . "</div>";
        }

        if (!empty($errors)) {
            echo "<div class='error-message'><ul>";
            foreach ($errors as $error) {
                echo "<li>" . htmlspecialchars($error) . "</li>";
            }
            echo "</ul></div>";
        }
        ?>

        <table class="course-table">
            <thead>
                <tr>
                    <th>Course Title</th>
                    <th>Year of Student</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Active</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['YearOfStudent']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['category']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['price']) . "</td>";
                    echo "<td>" . ($row['is_active'] ? 'Yes' : 'No') . "</td>";
                    echo "<td>
                            <a href='#' class='btn btn-edit' onclick='editCourse(" . $row['id'] . ", \"" . addslashes($row['title']) . "\", \"" . addslashes($row['description']) . "\", \"" . addslashes($row['category']) . "\", " . $row['YearOfStudent'] . ", " . $row['price'] . ", " . $row['is_active'] . ")'>Edit</a>
                            <a href='?delete_id=" . $row['id'] . "' class='btn btn-delete' onclick='return confirm(\"Are you sure you want to delete this course?\")'>Delete</a>
                        </td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>

       
        <div id="editModal" style="display:none; background: rgba(0, 0, 0, 0.7); position: fixed; top: 0; left: 0; right: 0; bottom: 0; justify-content: center; align-items: center;">
            <div style="background: white; padding: 20px; max-width: 500px; margin: 100px auto; border-radius: 8px;">
                <h2>Edit Course</h2>
                <form id="editCourseForm" method="POST">
                    <input type="hidden" name="edit_id" id="edit_id" />
                    <div>
                        <label for="edit_title">Course Title:</label>
                        <input type="text" id="edit_title" name="title" required />
                    </div>
                    <div>
                        <label for="edit_description">Description:</label>
                        <textarea id="edit_description" name="description"></textarea>
                    </div>
                    <div>
                        <label for="edit_year_of_student">Year of Student:</label>
                        <select id="edit_year_of_student" name="year_of_student" required>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                        </select>
                    </div>
                    <div>
                        <label for="edit_category">Category:</label>
                        <select id="edit_category" name="category" required>
                            <option value="SCHOOL OF CO-OPERATIVES AND COMMUNITY DEVELOPMENT">School of Co-operatives and Community Development</option>
                            <option value="SCHOOL OF BUSINESS AND ECONOMICS">School of Business and Economics</option>
                            <option value="SCHOOL OF COMPUTING AND MATHEMATICS">School of Computing and Mathematics</option>
                            <option value="SCHOOL OF NURSING">School of Nursing</option>
                        </select>
                    </div>
                    <div>
                        <label for="edit_price">Price:</label>
                        <input type="number" id="edit_price" name="price" step="0.01" min="0" required />
                    </div>
                    <div>
                        <label for="edit_is_active">Active Course:</label>
                        <input type="checkbox" id="edit_is_active" name="is_active" value="1" />
                    </div>
                    <div>
                        <button type="submit">Save Changes</button>
                        <button type="button" onclick="closeEditModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <script>
        function editCourse(id, title, description, category, year, price, is_active) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_title').value = title;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_category').value = category;
            document.getElementById('edit_year_of_student').value = year;
            document.getElementById('edit_price').value = price;
            document.getElementById('edit_is_active').checked = is_active == 1;

            document.getElementById('editModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
    </script>

</body>
</html>
