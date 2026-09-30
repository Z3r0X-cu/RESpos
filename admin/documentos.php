<?php
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/functions.php';
checkAuth(['admin']);

$db=getDBConnection();
$base=realpath(__DIR__.'/../storage/pdfs');
if(!$base) $base=__DIR__.'/../storage/pdfs';

// PDF stream: this block MUST run before header.php or any HTML output.
if(isset($_GET['doc'])){
    $id=(int)$_GET['doc'];
    $s=$db->prepare('SELECT nombre_archivo,ruta FROM documentos WHERE id=?');
    $s->execute([$id]);
    $d=$s->fetch();
    if(!$d){ http_response_code(404); exit('Documento no encontrado.'); }

    $relative=ltrim(str_replace('\\','/',$d['ruta']),'/');
    $file=realpath(__DIR__.'/../'.$relative);
    $root=realpath(__DIR__.'/../storage/pdfs');
    if(!$file || !$root || strpos($file,$root)!==0 || !is_file($file) || strtolower(pathinfo($file,PATHINFO_EXTENSION))!=='pdf'){
        http_response_code(404); exit('Archivo no encontrado.');
    }

    // Make files readable by Apache/XAMPP and by the local user.
    @chmod($file,0644);
    while(ob_get_level()>0) @ob_end_clean();
    clearstatcache(true,$file);
    $size=filesize($file);
    $filename=basename($file);
    $mode=(($_GET['mode']??'view')==='download')?'attachment':'inline';

    header('Content-Type: application/pdf');
    header('Content-Disposition: '.$mode.'; filename="'.str_replace(['\\','"'],['/',''],$filename).'"');
    header('Content-Length: '.(int)$size);
    header('Content-Transfer-Encoding: binary');
    header('Accept-Ranges: bytes');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    readfile($file);
    exit;
}

function documentLabel(string $folder):string{
    return match($folder){
        'facturas'=>'Facturas',
        'comprobantes'=>'Comprobantes',
        'cierres'=>'Cierres de caja',
        'inventario'=>'Inventario',
        'reportes'=>'Reportes',
        default=>ucfirst($folder)
    };
}

$search=trim($_GET['q']??'');
$type=trim($_GET['tipo']??'');
$from=trim($_GET['desde']??'');
$to=trim($_GET['hasta']??'');

$rows=[];
$known=[];
try{
    foreach($db->query('SELECT * FROM documentos ORDER BY fecha DESC,id DESC')->fetchAll() as $d){
        $relative=ltrim(str_replace('\\','/',$d['ruta']),'/');
        $absolute=__DIR__.'/../'.$relative;
        if(!is_file($absolute)) continue;
        @chmod($absolute,0644);
        $folder=basename(dirname($absolute));
        $known[$relative]=true;
        $rows[]=[
            'id'=>(int)$d['id'],
            'tipo'=>$d['tipo'],
            'categoria'=>documentLabel($folder),
            'numero'=>$d['numero']??'',
            'nombre'=>$d['nombre_archivo'],
            'ruta'=>$relative,
            'fecha'=>$d['fecha'],
            'size'=>(int)filesize($absolute)
        ];
    }
}catch(Throwable $e){
    $rows=[];
}

// También descubre PDFs existentes en disco que no llegaron a registrarse en documentos.
if(is_dir($base)){
    $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base,FilesystemIterator::SKIP_DOTS));
    foreach($it as $file){
        if(strtolower($file->getExtension())!=='pdf') continue;
        $absolute=$file->getPathname();
        @chmod($absolute,0644);
        $relative=ltrim(str_replace('\\','/',str_replace(realpath(__DIR__.'/../'),'',realpath($absolute))),'/');
        if(isset($known[$relative])) continue;
        $folder=basename(dirname($absolute));
        $rows[]=[
            'id'=>0,
            'tipo'=>$folder,
            'categoria'=>documentLabel($folder),
            'numero'=>pathinfo($absolute,PATHINFO_FILENAME),
            'nombre'=>basename($absolute),
            'ruta'=>$relative,
            'fecha'=>date('Y-m-d H:i:s',filemtime($absolute)),
            'size'=>(int)filesize($absolute)
        ];
    }
}

usort($rows,fn($a,$b)=>strcmp($b['fecha'],$a['fecha']));
$filtered=array_values(array_filter($rows,function($r)use($search,$type,$from,$to){
    if($type!=='' && strtolower($r['categoria'])!==strtolower($type)) return false;
    if($from!=='' && substr($r['fecha'],0,10)<$from) return false;
    if($to!=='' && substr($r['fecha'],0,10)>$to) return false;
    if($search!==''){
        $hay=strtolower($r['nombre'].' '.$r['numero'].' '.$r['categoria'].' '.$r['tipo']);
        if(strpos($hay,strtolower($search))===false) return false;
    }
    return true;
}));

function docUrl(array $r,string $mode='view'):string{
    if($r['id']>0) return url('admin/documentos.php?doc='.(int)$r['id'].'&mode='.$mode);
    return url($r['ruta']);
}

require_once __DIR__.'/../includes/header.php';
?>
<div class="card">
    <div class="actions" style="justify-content:space-between;align-items:center">
        <div><h1 style="margin-bottom:4px">Explorador de documentos</h1><p class="muted">Consulta central de todos los PDF generados por RESpos.</p></div>
        <div class="stat" style="min-width:150px;text-align:center"><span class="muted">Documentos</span><strong><?=count($filtered)?></strong></div>
    </div>
</div>
<div class="card">
<form method="get" class="form-grid">
    <div><label>Buscar</label><input name="q" value="<?=h($search)?>" placeholder="Número, archivo, tipo..."></div>
    <div><label>Categoría</label><select name="tipo"><option value="">Todas</option><?php foreach(['Facturas','Comprobantes','Cierres de caja','Inventario','Reportes'] as $t):?><option value="<?=h($t)?>" <?=$type===$t?'selected':''?>><?=h($t)?></option><?php endforeach;?></select></div>
    <div><label>Desde</label><input type="date" name="desde" value="<?=h($from)?>"></div>
    <div><label>Hasta</label><input type="date" name="hasta" value="<?=h($to)?>"></div>
    <div class="full actions"><button class="btn gold">Buscar</button><a class="btn" href="<?=url('admin/documentos.php')?>">Limpiar</a></div>
</form>
</div>
<div class="card">
<?php if(!$filtered):?><div class="notice">No hay documentos que coincidan con los filtros.</div><?php else:?><div class="table-wrap"><table><thead><tr><th>Documento</th><th>Categoría</th><th>Fecha</th><th>Tamaño</th><th>Acciones</th></tr></thead><tbody>
<?php foreach($filtered as $r):?><tr>
<td><strong><?=h($r['numero']?:$r['nombre'])?></strong><br><small class="muted"><?=h($r['nombre'])?></small></td>
<td><?=h($r['categoria'])?></td><td><?=h($r['fecha'])?></td><td><?=number_format($r['size']/1024,1,',','.')?> KB</td>
<td class="actions"><a class="btn olive doc-open" href="<?=h(docUrl($r,'view'))?>" data-doc-url="<?=h(docUrl($r,'view'))?>">Ver aquí</a><a class="btn gold" href="<?=h(docUrl($r,'download'))?>">Descargar</a></td>
</tr><?php endforeach;?></tbody></table></div><?php endif;?>
</div>
<div id="docModal" class="doc-modal" aria-hidden="true">
  <div class="doc-modal-box">
    <div class="actions" style="justify-content:space-between;align-items:center">
      <strong>Visor de documento</strong><button type="button" class="btn danger" id="docClose">Cerrar</button>
    </div>
    <iframe id="docFrame" title="Documento RESpos"></iframe>
  </div>
</div>
<style>.doc-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.78);z-index:9999;padding:4vh 4vw}.doc-modal.open{display:block}.doc-modal-box{height:92vh;background:#fff;border-radius:14px;overflow:hidden;display:flex;flex-direction:column}.doc-modal-box .actions{padding:10px 14px;background:#1b1b1b;color:#fff}.doc-modal iframe{border:0;flex:1;width:100%;background:#fff}</style>
<script>(function(){const m=document.getElementById('docModal'),f=document.getElementById('docFrame'),c=document.getElementById('docClose');document.querySelectorAll('.doc-open').forEach(a=>a.addEventListener('click',e=>{e.preventDefault();f.src=a.dataset.docUrl;m.classList.add('open');m.setAttribute('aria-hidden','false')}));c.addEventListener('click',()=>{m.classList.remove('open');m.setAttribute('aria-hidden','true');f.src='about:blank'});m.addEventListener('click',e=>{if(e.target===m)c.click()})})();</script>
<?php require_once __DIR__.'/../includes/footer.php';
