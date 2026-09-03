<?php include 'layout_header.php'; if(!$isSuper) exit;

function sendApprovedToChannel($r){
 if(shouldSendToChannel($r['is_paid'])){
  try{ $s=db()->prepare("SELECT name FROM dealers WHERE id=?"); $s->execute([$r['dealer_id']]); $dname=$s->fetchColumn()?:'Nomalum'; }catch(Exception $e){ $dname='Nomalum'; }
  $tpl=getSetting('template'); if(!$tpl) $tpl="1. Diller: {diller}\n2. Ism: {ism}\n3. Nomer: {nomer}\n4. Operator: {operator}\n5. Tarif: {tarif}";
  $txt=str_replace(['{diller}','{ism}','{nomer}','{operator}','{tarif}'],[$dname,$r['name'],$r['pretty_phone'],$r['operator_name'],$r['tarif_name']],$tpl);
  sendToChannel($txt);
 }
}

// ---- amallar ----
if($_POST && isset($_POST['approve_id'])){
 $id=intval($_POST['approve_id']);
 try{
  $s=db()->prepare("SELECT * FROM paid_participants WHERE id=? AND status='pending'"); $s->execute([$id]); $r=$s->fetch();
  if($r){
   db()->prepare("UPDATE paid_participants SET status='approved', approved_by=?, approved_at=NOW() WHERE id=?")->execute([$u['id'],$id]);
   sendApprovedToChannel($r);
  }
 }catch(Exception $e){}
 header("Location: pending.php?ok=1"); exit;
}
if($_POST && isset($_POST['reject_id'])){
 $id=intval($_POST['reject_id']); $reason=trim($_POST['reject_reason'] ?? '');
 try{ db()->prepare("UPDATE paid_participants SET status='rejected', reject_reason=?, approved_by=?, approved_at=NOW() WHERE id=? AND status='pending'")->execute([$reason,$u['id'],$id]); }catch(Exception $e){}
 header("Location: pending.php?rej=1"); exit;
}
if($_POST && isset($_POST['approve_all'])){
 try{
  $rows=db()->query("SELECT * FROM paid_participants WHERE status='pending'")->fetchAll();
  foreach($rows as $r){
   db()->prepare("UPDATE paid_participants SET status='approved', approved_by=?, approved_at=NOW() WHERE id=?")->execute([$u['id'],$r['id']]);
   sendApprovedToChannel($r);
  }
 }catch(Exception $e){}
 header("Location: pending.php?okall=1"); exit;
}
if($_POST && isset($_POST['reject_all'])){
 try{ db()->prepare("UPDATE paid_participants SET status='rejected', approved_by=?, approved_at=NOW() WHERE status='pending'")->execute([$u['id']]); }catch(Exception $e){}
 header("Location: pending.php?rejall=1"); exit;
}

$q=trim($_GET['q'] ?? '');
$sql="SELECT p.*, d.name as dealer_name FROM paid_participants p LEFT JOIN dealers d ON d.id=p.dealer_id WHERE p.status='pending'"; $pr=[];
if($q!==''){ $sql.=" AND (p.name LIKE ? OR p.pretty_phone LIKE ? OR p.operator_name LIKE ? OR p.tarif_name LIKE ? OR d.name LIKE ?)"; for($i=0;$i<5;$i++) $pr[]="%$q%"; }
$sql.=" ORDER BY p.id DESC";
try{ $rows=db()->prepare($sql); $rows->execute($pr); $rows=$rows->fetchAll(); }catch(Exception $e){ $rows=[]; }

try{ $history=db()->query("SELECT p.*, d.name as dealer_name, a.name as approver_name FROM paid_participants p LEFT JOIN dealers d ON d.id=p.dealer_id LEFT JOIN dealers a ON a.id=p.approved_by WHERE p.status IN ('approved','rejected') AND p.approved_at IS NOT NULL ORDER BY p.approved_at DESC LIMIT 30")->fetchAll(); }catch(Exception $e){ $history=[]; }

$toast='';
if(isset($_GET['ok'])) $toast='✅ Tasdiqlandi';
elseif(isset($_GET['rej'])) $toast='❌ Rad etildi';
elseif(isset($_GET['okall'])) $toast='✅ Hammasi tasdiqlandi';
elseif(isset($_GET['rejall'])) $toast='❌ Hammasi rad etildi';
?>
<div class="flex flex-wrap justify-between items-center gap-2 mb-4">
<h1 class="font-black text-xl flex items-center gap-2"><?php echo icon('hourglass','w-5 h-5'); ?> Kutilmoqda <span class="text-[#7c6cff]"><?php echo count($rows); ?></span> ta</h1>
<div class="flex gap-2">
<?php if(count($rows)>0): ?>
<form method="post" onsubmit="return confirm('Kutilmoqdagi HAMMA nomerlarni tasdiqlaysizmi? Bu amalni ortga qaytarib bo\'lmaydi.')"><input type="hidden" name="approve_all" value="1"><button class="bg-[#7c6cff] text-white px-4 py-2 rounded-xl text-xs font-black btn-glow">✅ Hammasini tasdiqlash</button></form>
<form method="post" onsubmit="return confirm('Kutilmoqdagi HAMMA nomerlarni rad etasizmi?')"><input type="hidden" name="reject_all" value="1"><button class="bg-red-500/10 border border-red-500/20 text-red-300 px-4 py-2 rounded-xl text-xs font-bold">❌ Hammasini rad etish</button></form>
<?php endif; ?>
</div>
</div>

<div class="card p-3 mb-4"><form class="flex gap-2"><input name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Diller, ism, nomer, operator yoki tarif bo'yicha qidir" class="flex-1 p-3 rounded-xl bg-black/50 border border-white/10 text-white outline-none"><button class="bg-white text-black px-5 rounded-xl font-bold">Qidir</button></form></div>

<?php if(empty($rows)): ?>
<div class="card p-10 text-center text-white/30">🎉 Hozircha kutilayotgan nomer yo'q</div>
<?php else: ?>
<div class="space-y-3 mb-8">
<?php foreach($rows as $r): ?>
<div class="card p-4 card-hover border-l-2 border-l-[#7c6cff]/40">
<div class="flex flex-wrap justify-between items-start gap-3">
<div>
<div class="flex items-center gap-2 flex-wrap"><b class="text-base"><?php echo htmlspecialchars($r['name']); ?></b><span class="text-[10px] px-2 py-1 rounded-full bg-[#7c6cff]/10 text-[#7c6cff] border border-[#7c6cff]/20 font-bold">⏳ KUTILMOQDA</span><span class="text-[10px] px-2 py-1 rounded-full <?php echo $r['is_paid']?'bg-white text-black':'bg-white/10 text-white/40'; ?>"><?php echo $r['is_paid']?"O'YINDA":'BAZADA'; ?></span></div>
<p class="font-mono text-sm text-white/70 mt-1"><?php echo htmlspecialchars($r['pretty_phone']); ?></p>
<p class="text-xs text-white/40 mt-1"><b class="text-white/60"><?php echo htmlspecialchars($r['operator_name']); ?></b> / <?php echo htmlspecialchars($r['tarif_name']); ?></p>
<p class="text-[11px] text-white/30 mt-1">Diller: <b class="text-white/50"><?php echo htmlspecialchars($r['dealer_name']); ?></b> • <?php echo date('d.m.Y H:i', strtotime($r['created_at'])); ?></p>
</div>
<div class="flex gap-2 items-center">
<a href="edit.php?id=<?php echo $r['id']; ?>" class="bg-white/5 border border-white/10 px-3 py-2 rounded-xl text-xs">✏️ Tahrirlash</a>
<form method="post" onsubmit="return confirm('Tasdiqlaysizmi?')"><input type="hidden" name="approve_id" value="<?php echo $r['id']; ?>"><button class="bg-[#7c6cff] text-white px-3 py-2 rounded-xl text-xs font-black">✅ Tasdiqlash</button></form>
<button type="button" onclick="document.getElementById('rej-<?php echo $r['id']; ?>').classList.toggle('hidden')" class="bg-red-500/10 border border-red-500/20 text-red-300 px-3 py-2 rounded-xl text-xs font-bold">❌ Rad etish</button>
</div>
</div>
<div id="rej-<?php echo $r['id']; ?>" class="hidden mt-3 pt-3 border-t border-white/5">
<form method="post" class="flex gap-2"><input type="hidden" name="reject_id" value="<?php echo $r['id']; ?>"><input name="reject_reason" placeholder="Rad etish sababi (ixtiyoriy)" class="flex-1 p-2 rounded-lg bg-black/40 border border-white/10 text-white text-xs"><button class="bg-red-500 text-white px-4 rounded-lg text-xs font-bold">Rad etishni tasdiqlash</button></form>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<h2 class="font-black text-sm text-white/40 mb-3 tracking-widest">🕒 SO'NGGI HARAKATLAR TARIXI</h2>
<div class="card overflow-auto">
<table class="w-full text-sm"><thead><tr class="bg-black/50 text-white/30 text-[11px]"><th class="p-3 text-left">Ism / Nomer</th><th>Diller</th><th>Holat</th><th>Kim / Qachon</th><th>Sabab</th></tr></thead><tbody>
<?php foreach($history as $h): ?>
<tr class="border-b border-white/5">
<td class="p-3"><b><?php echo htmlspecialchars($h['name']); ?></b><br><span class="font-mono text-xs text-white/40"><?php echo htmlspecialchars($h['pretty_phone']); ?></span></td>
<td class="text-xs text-white/50"><?php echo htmlspecialchars($h['dealer_name']); ?></td>
<td><?php echo $h['status']=='approved' ? '<span class="text-[10px] px-2 py-1 rounded-full bg-[#7c6cff]/10 text-[#7c6cff] border border-[#7c6cff]/20 font-bold">✅ TASDIQLANDI</span>' : '<span class="text-[10px] px-2 py-1 rounded-full bg-red-500/10 text-red-300 border border-red-500/20 font-bold">❌ RAD ETILDI</span>'; ?></td>
<td class="text-xs text-white/40"><?php echo htmlspecialchars($h['approver_name'] ?? '-'); ?><br><?php echo $h['approved_at'] ? date('d.m.Y H:i', strtotime($h['approved_at'])) : '-'; ?></td>
<td class="text-xs text-white/30"><?php echo htmlspecialchars($h['reject_reason'] ?: '-'); ?></td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div>

<?php if($toast): ?><div id="toast" class="fixed top-4 left-1/2 -translate-x-1/2 z-[999] max-w-sm w-[92%] shadow-2xl"><div class="bg-white/5 border border-[#7c6cff]/20 text-[#7c6cff] p-3 rounded-xl text-sm font-bold text-center"><?php echo $toast; ?></div></div>
<script>setTimeout(function(){ var t=document.getElementById('toast'); if(t){ t.style.transition='opacity .4s'; t.style.opacity='0'; setTimeout(function(){ t.remove(); },400); } },3500);</script><?php endif; ?>
<?php include 'layout_footer.php'; ?>