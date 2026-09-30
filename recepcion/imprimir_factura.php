<?php
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/functions.php';
checkAuth(['recepcion','admin','economia']);
$db=getDBConnection();
$id=(int)($_GET['id']??0);
$s=$db->prepare('SELECT pdf_path FROM facturas WHERE id=?');
$s->execute([$id]);
$r=$s->fetch();
$file=$r&&$r['pdf_path']?realpath(__DIR__.'/../'.$r['pdf_path']):false;
$root=realpath(__DIR__.'/../storage/pdfs');
if(!$file||!$root||strpos($file,$root)!==0||!is_file($file)||strtolower(pathinfo($file,PATHINFO_EXTENSION))!=='pdf'){http_response_code(404);exit('PDF no encontrado.');}
@chmod($file,0644);
while(ob_get_level()>0)@ob_end_clean();
clearstatcache(true,$file);
$mode=(($_GET['mode']??'view')==='download')?'attachment':'inline';
header('Content-Type: application/pdf');
header('Content-Disposition: '.$mode.'; filename="RESpos-'.preg_replace('/[^A-Za-z0-9._-]/','-',basename($file)).'"');
header('Content-Length: '.(int)filesize($file));
header('Content-Transfer-Encoding: binary');
header('Accept-Ranges: bytes');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, must-revalidate');
readfile($file);
exit;
