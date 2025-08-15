<?php
session_start();
require_once 'vendor/autoload.php';

use InnovantCafe\Auth;

// Cerrar sesión
$auth = new Auth();
$auth->logout();

// Redirigir al login
header('Location: /login');
exit;
?>
