<?php require_once __DIR__.'/config/database.php';require_once __DIR__.'/database/setup.php';initializeRESposDatabase();require_once __DIR__.'/includes/auth.php';if(empty($_SESSION['usuario'])){header('Location: '.url('login.php'));exit;} $r=$_SESSION['usuario']['rol'];header('Location: '.url($r==='admin'?'admin/index.php':($r==='economia'?'economia/index.php':'recepcion/index.php')));exit;
?>
