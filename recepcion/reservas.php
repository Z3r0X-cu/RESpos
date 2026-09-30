<?php
require_once __DIR__.'/../includes/header.php';
checkAuth(['recepcion','admin']);
$db=getDBConnection();$msg='';$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        if(isset($_POST['cancelar'])){
            $rid=(int)$_POST['reserva_id'];
            $s=$db->prepare("SELECT * FROM reservas WHERE id=? AND estado IN('pendiente','confirmada')");$s->execute([$rid]);$r=$s->fetch();
            if(!$r) throw new RuntimeException('La reserva ya no está activa.');
            $db->beginTransaction();
            $db->prepare("UPDATE reservas SET estado='cancelada' WHERE id=?")->execute([$rid]);
            $check=$db->prepare("SELECT COUNT(*) FROM reservas WHERE mesa_id=? AND estado IN('pendiente','confirmada')");$check->execute([(int)$r['mesa_id']]);
            if((int)$check->fetchColumn()===0){
                $occ=$db->prepare("SELECT COUNT(*) FROM comandas WHERE mesa_id=? AND estado='abierta'");$occ->execute([(int)$r['mesa_id']]);
                if((int)$occ->fetchColumn()===0)$db->prepare("UPDATE mesas SET estado='libre' WHERE id=?")->execute([(int)$r['mesa_id']]);
            }
            $db->commit();$msg='Reserva cancelada.';
        }else{
            $mesa=(int)$_POST['mesa'];
            $fecha=trim($_POST['fecha_hora']);
            $conf=$db->prepare("SELECT COUNT(*) FROM reservas WHERE mesa_id=? AND estado IN('pendiente','confirmada') AND fecha_hora=?");$conf->execute([$mesa,$fecha]);
            if((int)$conf->fetchColumn()>0) throw new RuntimeException('Esa mesa ya tiene una reserva para esa fecha y hora.');
            $s=$db->prepare('INSERT INTO reservas(cliente_nombre,cliente_apellidos,cliente_telefono,cliente_ci,mesa_id,fecha_hora,personas,notas,estado,created_by) VALUES(?,?,?,?,?,?,?,?,?,?)');
            $s->execute([trim($_POST['nombre']),trim($_POST['apellidos']),trim($_POST['telefono']),trim($_POST['ci']),$mesa,$fecha,max(1,(int)$_POST['personas']),trim($_POST['notas']),'confirmada',$_SESSION['usuario']['id']]);
            $db->prepare("UPDATE mesas SET estado='reservada' WHERE id=? AND NOT EXISTS(SELECT 1 FROM comandas WHERE mesa_id=mesas.id AND estado='abierta')")->execute([$mesa]);
            $msg='Reserva confirmada.';
        }
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();$err='No se pudo procesar la reserva: '.$e->getMessage();}
}
$mesas=$db->query('SELECT * FROM mesas WHERE activa=1 ORDER BY numero')->fetchAll();
$rows=$db->query("SELECT r.*,m.numero mesa FROM reservas r JOIN mesas m ON m.id=r.mesa_id ORDER BY r.fecha_hora DESC LIMIT 100")->fetchAll();
$activeByMesa=[];foreach($rows as $r){if(in_array($r['estado'],['pendiente','confirmada'],true)&&!isset($activeByMesa[$r['mesa_id']]))$activeByMesa[$r['mesa_id']]=$r;}
?>
<div class="card"><h1>Reservas</h1>
<?php if($msg):?><div class="notice"><?=h($msg)?></div><?php endif;?><?php if($err):?><div class="notice error"><?=h($err)?></div><?php endif;?>
<form method="post" class="form-grid"><div><label>Nombre</label><input name="nombre" required></div><div><label>Apellidos</label><input name="apellidos"></div><div><label>CI</label><input name="ci"></div><div><label>Teléfono</label><input name="telefono" required></div><div><label>Fecha y hora</label><input type="datetime-local" name="fecha_hora" required></div><div><label>Personas</label><input type="number" min="1" name="personas" value="2"></div><div><label>Mesa</label><select name="mesa"><?php foreach($mesas as $m):?><option value="<?=$m['id']?>">Mesa <?=$m['numero']?> · <?=$m['capacidad']?> pax<?=isset($activeByMesa[$m['id']])?' · RESERVADA':''?></option><?php endforeach;?></select></div><div><label>Notas</label><input name="notas"></div><div class="full"><button class="btn gold">Confirmar reserva</button></div></form></div>
<div class="card"><h2>Reservas activas por mesa</h2><div class="table-wrap"><table><tr><th>Mesa</th><th>Cliente</th><th>Fecha</th><th>Contacto</th><th>Estado</th><th>Acción</th></tr><?php foreach($activeByMesa as $r):?><tr><td><b>Mesa <?=$r['mesa']?></b></td><td><?=h($r['cliente_nombre'].' '.$r['cliente_apellidos'])?><br>CI: <?=h($r['cliente_ci'])?></td><td><?=h($r['fecha_hora'])?></td><td><?=h($r['cliente_telefono'])?></td><td><span class="badge">RESERVADA</span></td><td><form method="post"><input type="hidden" name="cancelar" value="1"><input type="hidden" name="reserva_id" value="<?=$r['id']?>"><button class="btn danger" data-confirm="¿Cancelar esta reserva?">Cancelar</button></form></td></tr><?php endforeach;?></table></div></div>
<div class="card"><h2>Historial</h2><div class="table-wrap"><table><tr><th>Cliente</th><th>Mesa</th><th>Fecha</th><th>Estado</th></tr><?php foreach($rows as $r):?><tr><td><?=h($r['cliente_nombre'].' '.$r['cliente_apellidos'])?></td><td>Mesa <?=$r['mesa']?></td><td><?=h($r['fecha_hora'])?></td><td><span class="badge"><?=strtoupper(h($r['estado']))?></span></td></tr><?php endforeach;?></table></div></div>
<?php require_once __DIR__.'/../includes/footer.php';?>
