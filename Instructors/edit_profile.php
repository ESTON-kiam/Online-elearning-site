<?php
session_name('instructor_session');
session_start();

if (!isset($_SESSION['instructor_id'])) {
    header("Location:http://localhost:8000/instructors");
    exit();
}

require_once 'include/database.php';
?>