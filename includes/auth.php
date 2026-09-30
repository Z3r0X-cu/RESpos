<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
if(session_status()===PHP_SESSION_NONE){ini_set('session.gc_maxlifetime','31536000');session_set_cookie_params(['lifetime'=>31536000,'path'=>'/','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off','httponly'=>true,'samesite'=>'Lax']);session_start();}
function checkAuth(array $roles=[]):void{if(empty($_SESSION['usuario'])){header('Location: '.url('login.php'));exit;}if($roles&&!in_array($_SESSION['usuario']['rol'],$roles,true)){http_response_code(403);die('Acceso denegado.');}}
function currentUser():array{return $_SESSION['usuario']??[];}
function requirePost():void{if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);die('Método no permitido.');}}
?>
