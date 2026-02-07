<?php
session_start();
require_once __DIR__ . '/../classes/Auth.php';

$auth = new Auth();
$auth->logout();

// Redirect to home page
header('Location: /');
exit;