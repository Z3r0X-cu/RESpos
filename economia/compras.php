<?php
require_once __DIR__.'/../includes/header.php'; require_once __DIR__.'/../includes/pdf_generator.php'; checkAuth(['economia','admin']);
$db=getDBConnection();$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $db->beginTransaction();
 try{
  $numero=nextNumber('COMP-');$proveedor=trim($_POST['proveedor']);$notas=trim($_POST['notas']);
  $db->prepare('INSERT INTO compras(numero,proveedor,fecha,total_cup,notas,usuario_id) VALUES(?,?,?,?,?,?)')->execute([$numero,$proveedor,now(),0,$notas,$_SESSION['usuario']['id']]);$cid=$db->lastInsertId();$total=0.0;
  $ins=$db->prepare('INSERT INTO compra_detalles(compra_id,insumo_id,cantidad,precio_unitario,subtotal) VALUES(?,?,?,?,?)');$up=$db->prepare('UPDATE insumos SET stock_actual=stock_actual+?,precio_compra_unidad=?,updated_at=? WHERE id=?');$mv=$db->prepare("INSERT INTO movimientos_almacen(insumo_id,tipo,cantidad,precio_unitario,concepto,fecha,usuario_id) VALUES(?,'entrada',?,?,?,?,?)");
  foreach($_POST['insumo_id'] as $i=>$iid){$qty=numberValue($_POST['cantidad'][$i]??0);$price=numberValue($_POST['precio'][$i]??0);if($qty<=0)continue;$sub=$qty*$price;$total+=$sub;$ins->execute([$cid,(int)$iid,$qty,$price,$sub]);$up->execute([$qty,$price,now(),(int)$iid]);$mv->execute([(int)$iid,$qty,$price,'Compra '.$numero,now(),$_SESSION['usuario']['id']]);}
  $path=generarDocumentoPDF('factura',$numero,'Factura de compra',['Proveedor: '.$proveedor,'Total: '.money($total),'Notas: '.$notas]);$db->prepare('UPDATE compras SET total_cup=?,pdf_path=? WHERE id=?')->execute([$total,$path,$cid]);$db->commit();storeDocument('factura',(int)$cid,$numero,$path);$msg='Compra registrada: '.$numero.' · '.money($total);
 }catch(Throwable $e){if($db->inTransaction())$db->rollBack();$msg='No se pudo registrar la compra: '.$e->getMessage();}
}
$insumos=$db->query('SELECT id,nombre,unidad_medida FROM insumos WHERE activo=1 ORDER BY nombre')->fetchAll();$compras=$db->query('SELECT * FROM compras ORDER BY id DESC LIMIT 30')->fetchAll();
?>
<div class="card"><h1>Compras / entradas de almacén</h1><?php if($msg):?><div class="notice"><?=h($msg)?></div><?php endif;?><form method="post"><label>Proveedor</label><input name="proveedor"><label>Notas</label><input name="notas"><div id="rows"><div class="form-grid purchase-row"><div><label>Insumo</label><select name="insumo_id[]"><?php foreach($insumos as $i):?><option value="<?=$i['id']?>"><?=h($i['nombre'].' · '.$i['unidad_medida'])?></option><?php endforeach;?></select></div><div><label>Cantidad</label><input type="number" step="0.01" name="cantidad[]" required></div><div><label>Precio/U CUP</label><input type="number" step="0.01" name="precio[]" required></div></div></div><button type="button" class="btn olive" onclick="addRow()">+ Otra línea</button> <button class="btn gold">Registrar compra + PDF</button></form></div>
<div class="card"><h2>Últimas compras</h2><div class="table-wrap"><table><tr><th>Número</th><th>Proveedor</th><th>Fecha</th><th>Total</th><th>Documento</th></tr><?php foreach($compras as $c):?><tr><td><?=h($c['numero'])?></td><td><?=h($c['proveedor'])?></td><td><?=h($c['fecha'])?></td><td class="gold"><?=money($c['total_cup'])?></td><td><?php if($c['pdf_path']):?><a class="btn olive" target="_blank" href="<?=url($c['pdf_path'])?>">PDF</a><?php endif;?></td></tr><?php endforeach;?></table></div></div><script>function addRow(){const r=document.querySelector('.purchase-row').cloneNode(true);r.querySelectorAll('input').forEach(x=>x.value='');document.getElementById('rows').appendChild(r)}</script>
<?php require_once __DIR__.'/../includes/footer.php';?>
