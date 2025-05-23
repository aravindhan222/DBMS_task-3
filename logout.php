<?php
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Logout the user
logout_user();

// Redirect to home page
redirect('index.php');
?>
