<?php
require_once __DIR__.'/../includes/header.php';
checkAuth(['recepcion','admin']);
$db=getDBConnection();
$mesas=$db->query("SELECT m.*,
CASE WHEN EXISTS(SELECT 1 FROM comandas c WHERE c.mesa_id=m.id AND c.estado='abierta') THEN 1 ELSE 0 END ocupada,
(SELECT r.cliente_nombre||' '||COALESCE(r.cliente_apellidos,'')||' · '||r.fecha_hora FROM reservas r WHERE r.mesa_id=m.id AND r.estado IN('pendiente','confirmada') ORDER BY r.fecha_hora ASC LIMIT 1) reserva
FROM mesas m WHERE m.activa=1 ORDER BY m.numero")->fetchAll();
?>
<div class="grid grid-2"><div class="card"><h1>Salón · mesas</h1><p class="muted">Seleccione una mesa para abrir/continuar su comanda. Las reservas activas aparecen directamente en la mesa.</p><div class="mesa-grid">
<?php foreach($mesas as $m):?><a class="mesa <?=$m['ocupada']?'busy':''?>" href="ordenes.php?mesa=<?=$m['id']?>"><strong>MESA <?=$m['numero']?></strong><span><?= $m['ocupada']?'Ocupada':($m['reserva']?'Reservada':'Libre')?></span><small><?=h($m['capacidad'])?> personas</small><?php if($m['reserva']):?><small class="gold">Reserva: <?=h($m['reserva'])?></small><?php endif;?></a><?php endforeach;?></div></div>
<div><div class="card calculator"><h2>Calculadora rápida</h2><input id="calc" class="calc-display" readonly><div class="calc-grid" style="margin-top:8px"><?php foreach(['7','8','9','/','4','5','6','*','1','2','3','-','0','.','C','+','(',')','='] as $b):?><button class="btn <?=$b==='='?'gold':'olive'?>" onclick="calcKey('<?=h($b)?>');return false;"><?=h($b)?></button><?php endforeach;?></div></div><div class="card"><h3>Accesos rápidos</h3><div class="actions"><a class="btn gold" href="reservas.php">Nueva reserva</a><a class="btn olive" href="cierre.php">Cierre de caja</a><a class="btn" href="calculadora.php">Calculadora completa</a></div></div></div></div>
<script>function calcKey(v){let d=document.getElementById('calc');if(v==='C'){d.value='';return}if(v==='='){try{d.value=Function('return '+d.value)()}catch(e){d.value='Error'}return}d.value+=v}</script>
<?php require_once __DIR__.'/../includes/footer.php';?>
