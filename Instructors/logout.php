<?php
session_name('instructor_session');
session_start();
require_once 'include/database.php';
$instructor_id = $_SESSION['instructor_id'] ?? null; 
if ($instructor_id) {
    $current_time = date('Y-m-d H:i:s'); 
    $query = "UPDATE instructors SET last_logout = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param('si', $current_time, $instructor_id);
        $stmt->execute();
        $stmt->close();
    } else {
        error_log("Error preparing the query: " . $conn->error);
    }
}
session_unset();
session_destroy();
header('Location: http://localhost:8000/instructors');
exit();
?>
