
<?php
session_name('super_admin');
session_start();
require_once 'include/database.php';
$student_id = $_SESSION['admin_id'] ?? null; 
if ($student_id) {
    $current_time = date('Y-m-d H:i:s'); 
    $query = "UPDATE admin SET last_logout = ? WHERE admin_id = ?";
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
header('Location: http://localhost:8000/Admin');
exit();
?>
