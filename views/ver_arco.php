<?php include('../views/header.php'); include('../config/db.php');
$id = (int)$_GET['id'];
$a = $pdo->prepare('SELECT a.*, u.nombre as ubic FROM arcos a JOIN ubicaciones u ON a.ubicacion_id=u.id WHERE a.id=?');
$a->execute([$id]); $arco = $a->fetch();
?>
<h4><?=htmlspecialchars($arco['nombre'])?> (<?=htmlspecialchars($arco['ubic'])?>)</h4>
<h5>Materiales instalados</h5>
<ul>
<?php
$stmt = $pdo->prepare('SELECT am.*, m.nombre FROM arco_material am JOIN materiales m ON am.material_id=m.id WHERE am.arco_id=?');
$stmt->execute([$id]);
foreach($stmt as $row):
?>
  <li><?=htmlspecialchars($row['nombre'])?> 
    <?php if($row['foto']): ?> - <a href="/<?= $row['foto'] ?>" target="_blank">Ver foto</a><?php endif;?>
  </li>
<?php endforeach; ?>
</ul>
<?php include('../views/footer.php'); ?>
