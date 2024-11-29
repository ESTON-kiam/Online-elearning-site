<?php

session_name('super_admin');
session_start();


session_unset();    
session_destroy();  


header('Location: http://localhost:8000/admin');
exit();
?>
