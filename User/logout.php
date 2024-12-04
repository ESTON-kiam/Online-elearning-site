<?php
session_name('student_session');
session_start();
require_once 'include/database.php';
$student_id = $_SESSION['student_id'] ?? null; 
if ($student_id) {
    $current_time = date('Y-m-d H:i:s'); 
    $query = "UPDATE students SET last_logout = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param('si', $current_time, $student_id);
        $stmt->execute();
        $stmt->close();
    } else {
        
        error_log("Error preparing the query: " . $conn->error);
    }
}
session_unset();
session_destroy();
header('Location: http://localhost:8000/User');
exit();
?>
