<?php
require_once __DIR__.'/../includes/header.php';
checkAuth(['admin']);
$db=getDBConnection();$msg='';$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        $photo=null;
        if(!empty($_FILES['foto']['name'])){
            if(($_FILES['foto']['size']??0)>8*1024*1024) throw new RuntimeException('La foto no puede superar 8 MB.');
            $photo=uploadAndResizeImage($_FILES['foto'],'assets/uploads/employees','emp_'.date('YmdHis').'_'.random_int(100,999),500);
            if(!$photo) throw new RuntimeException('La foto no pudo procesarse.');
        }
        $ci=trim($_POST['ci']??'');
        $s=$db->prepare('INSERT INTO empleados(nombre,apellidos,ci,foto_path,direccion,telefono,puesto,salario) VALUES(?,?,?,?,?,?,?,?)');
        $s->execute([trim($_POST['nombre']??''),trim($_POST['apellidos']??''),$ci,$photo,trim($_POST['direccion']??''),trim($_POST['telefono']??''),trim($_POST['puesto']??''),numberValue($_POST['salario']??0)]);
        $msg='Empleado registrado correctamente.';
    }catch(Throwable $e){$err='No se pudo registrar el empleado: '.$e->getMessage();}
}
$rows=$db->query('SELECT * FROM empleados WHERE activo=1 ORDER BY id DESC')->fetchAll();
?>
<div class="card"><h1>Empleados</h1>
<?php if($msg):?><div class="notice"><?=h($msg)?></div><?php endif;?>
<?php if($err):?><div class="notice error"><?=h($err)?></div><?php endif;?>
<form method="post" enctype="multipart/form-data" class="form-grid">
<div><label>Nombre</label><input name="nombre" required></div><div><label>Apellidos</label><input name="apellidos" required></div>
<div><label>CI</label><input name="ci" required></div><div><label>Teléfono</label><input name="telefono"></div>
<div><label>Dirección</label><input name="direccion"></div><div><label>Puesto</label><input name="puesto" placeholder="Chef, camarero, etc." required></div>
<div><label>Salario (CUP)</label><input type="number" step="0.01" name="salario" required></div><div><label>Foto · máximo 8 MB</label><input type="file" name="foto" accept="image/jpeg,image/png,image/webp"></div>
<div class="full"><button class="btn gold">Guardar expediente</button></div></form></div>
<div class="card"><div class="table-wrap"><table><tr><th>Foto</th><th>Empleado</th><th>CI</th><th>Puesto</th><th>Salario</th><th>Contacto</th></tr>
<?php foreach($rows as $e):?><tr><td><?php if($e['foto_path']):?><img class="thumb" src="<?=publicAsset($e['foto_path'])?>"><?php endif;?></td><td><b><?=h($e['nombre'].' '.$e['apellidos'])?></b><br><span class="muted"><?=h($e['direccion'])?></span></td><td><?=h($e['ci'])?></td><td><?=h($e['puesto'])?></td><td><?=money((float)$e['salario'])?></td><td><?=h($e['telefono'])?></td></tr><?php endforeach;?></table></div></div>
<?php require_once __DIR__.'/../includes/footer.php';?>
