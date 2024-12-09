<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful</title>
    <link href="assets/css/success.css" rel="stylesheet">
</head>
<body>

<div class="container">
    <h2>Registration Successful!</h2>
    <?php
    if (isset($_SESSION['message'])) {
        echo "<p>{$_SESSION['message']}</p>";
        unset($_SESSION['message']);
    }
    ?>
    <p>You have successfully registered as a student.</p>
    <p>Please check your email for confirmation and further instructions.</p>
    <a href="http://localhost:8000/User" class="btn">Go to Login</a>
</div>

</body>
</html>
