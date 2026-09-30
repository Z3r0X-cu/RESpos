<?php
require_once __DIR__ . '/../config/database.php';
function h($v):string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function now():string{return date('Y-m-d H:i:s');}
function money(float $v,string $currency='CUP'):string{return number_format((float)$v,2,',','.').' '.$currency;}
function numberValue($value):float{
    $s=trim((string)$value);
    if($s==='') return 0.0;
    $s=str_replace([" ","\xC2\xA0"],'',$s);
    if(str_contains($s,',') && str_contains($s,'.')){
        $s=str_replace('.','',$s);
        $s=str_replace(',','.',$s);
    }elseif(str_contains($s,',')){
        $s=str_replace(',','.',$s);
    }elseif(preg_match('/^-?\d+\.\d{3}$/',$s)){
        $s=str_replace('.','',$s);
    }
    return is_numeric($s)?(float)$s:0.0;
}
function formatNumberInput($value):string{return rtrim(rtrim(number_format((float)$value,2,'.',''),'0'),'.');}
function getAppConfig():array{$r=getDBConnection()->query('SELECT * FROM configuracion WHERE id=1')->fetch();return $r?:[];}
function logAction(string $action,string $detail=''):void{$u=$_SESSION['usuario']['id']??null;$s=getDBConnection()->prepare('INSERT INTO auditoria(usuario_id,accion,detalle,fecha) VALUES(?,?,?,?)');$s->execute([$u,$action,$detail,now()]);}
function uploadAndResizeImage($file,$relativeDir,$outputName,$size=500){
    if(($file['error']??1)!==UPLOAD_ERR_OK) return null; if(!is_uploaded_file($file['tmp_name'])) return null;
    $info=@getimagesize($file['tmp_name']); if(!$info) return null; $mime=$info['mime']??'';
    if(!in_array($mime,['image/jpeg','image/png','image/webp'],true)) return null;
    $target=__DIR__.'/../'.$relativeDir; if(!is_dir($target)) mkdir($target,0775,true);
    if(!extension_loaded('gd')){ $ext=$mime==='image/jpeg'?'jpg':($mime==='image/webp'?'webp':'png');$dest=$target.'/'.$outputName.'.'.$ext;move_uploaded_file($file['tmp_name'],$dest);return $relativeDir.'/'.$outputName.'.'.$ext; }
    $src=$mime==='image/jpeg'?imagecreatefromjpeg($file['tmp_name']):($mime==='image/png'?imagecreatefrompng($file['tmp_name']):imagecreatefromwebp($file['tmp_name']));
    $w=$info[0];$h=$info[1];$crop=min($w,$h);$x=($w-$crop)/2;$y=($h-$crop)/2;$dst=imagecreatetruecolor($size,$size);imagealphablending($dst,false);imagesavealpha($dst,true);imagecopyresampled($dst,$src,0,0,$x,$y,$size,$size,$crop,$crop);
    $png=$target.'/'.$outputName.'.png';imagepng($dst,$png,6);$jpg=$target.'/'.$outputName.'.jpg';$white=imagecreatetruecolor($size,$size);$bg=imagecolorallocate($white,255,255,255);imagefill($white,0,0,$bg);imagecopy($white,$dst,0,0,0,0,$size,$size);imagejpeg($white,$jpg,88);imagedestroy($src);imagedestroy($dst);imagedestroy($white);return $relativeDir.'/'.$outputName.'.png';
}
function logoPdfPath($config){$p=$config['logo_pdf_path']??'';if($p&&file_exists(__DIR__.'/../'.$p))return __DIR__.'/../'.$p;return null;}
function convertToCUP(float $amount,string $currency,array $config):float{if($currency==='CUP')return $amount;$map=['USD'=>'tasa_usd','EUR'=>'tasa_eur','JPY'=>'tasa_jpy','YEN'=>'tasa_jpy','MXN'=>'tasa_mxn'];$k=$map[$currency]??null;$rate=$k?(float)$config[$k]:1;return $rate>0?$amount*$rate:$amount;}
function fromCUP(float $cup,string $currency,array $config):float{if($currency==='CUP')return $cup;$map=['USD'=>'tasa_usd','EUR'=>'tasa_eur','JPY'=>'tasa_jpy','YEN'=>'tasa_jpy','MXN'=>'tasa_mxn'];$k=$map[$currency]??null;$rate=$k?(float)$config[$k]:1;return $rate>0?$cup/$rate:$cup;}
function availableCurrencies():array{return ['CUP'=>'Peso Cubano','USD'=>'Dólar estadounidense','EUR'=>'Euro','JPY'=>'Yen japonés','MXN'=>'Peso mexicano'];}
function nextNumber(string $prefix):string{return $prefix.date('YmdHis').'-'.random_int(100,999);}
function storeDocument(string $type,int $refId,string $number,string $path):void{$db=getDBConnection();$s=$db->prepare('INSERT INTO documentos(tipo,referencia_id,numero,nombre_archivo,ruta,fecha,usuario_id) VALUES(?,?,?,?,?,?,?)');$s->execute([$type,$refId,$number,basename($path),$path,now(),$_SESSION['usuario']['id']??null]);}
function publicAsset($path,$fallback='assets/uploads/logo.png'){return url($path?:$fallback);}
function ensureDir(string $dir):void{if(!is_dir($dir))mkdir($dir,0775,true);@chmod($dir,0755);}
?>
