<?php
require_once __DIR__.'/config.php';
requireLogin();
$u=$_SESSION['user'];
$isSuper=($u['role']=='super');

function fmtSum($n){ return number_format($n,0,'.',' '); }
function csvSum($n){ return number_format($n,0,'.',''); }

// ==== Sana filtri (preset yoki qo'lda) ====
$range = $_GET['range'] ?? '';
$from = trim($_GET['from'] ?? '');
$to   = trim($_GET['to'] ?? '');
if($range!==''){
 switch($range){
  case 'today': $from=$to=date('Y-m-d'); break;
  case 'week': $from=date('Y-m-d', strtotime('monday this week')); $to=date('Y-m-d'); break;
  case 'month': $from=date('Y-m-01'); $to=date('Y-m-d'); break;
  case 'all': $from=''; $to=''; break;
 }
}
if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$from)) $from='';
if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$to)) $to='';
$qsFilter = 'from='.urlencode($from).'&to='.urlencode($to);

// ==== AJAX: diller batafsil ma'lumoti (ochilganda) ====
if(isset($_GET['ajax']) && $_GET['ajax']==='dealer_detail'){
 header('Content-Type: application/json; charset=utf-8');
 $id=intval($_GET['id'] ?? 0);
 if(!$isSuper && $id!==intval($u['id'])){ echo json_encode(['error'=>'forbidden']); exit; }
 $balance = dealerBalance($id,$from?:null,$to?:null);
 $paid = dealerPaidTotal($id);
 $list = dealerParticipantsDetailed($id,$from?:null,$to?:null);
 $payments = dealerPaymentsHistory($id);
 echo json_encode(['balance'=>$balance,'paid'=>$paid,'pending'=>max(0,$balance-$paid),'list'=>$list,'payments'=>$payments], JSON_UNESCAPED_UNICODE);
 exit;
}

// ==== AJAX: operator ichidagi tariflar ====
if(isset($_GET['ajax']) && $_GET['ajax']==='operator_detail'){
 header('Content-Type: application/json; charset=utf-8');
 $op=$_GET['op'] ?? '';
 $did = $isSuper ? (intval($_GET['dealer_id'] ?? 0) ?: null) : intval($u['id']);
 $rows = tarifBalancesForOperator($op,$did,$from?:null,$to?:null);
 echo json_encode(['rows'=>$rows], JSON_UNESCAPED_UNICODE);
 exit;
}

// ==== To'lov qayd etish (faqat Bosh admin) ====
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='add_payment' && $isSuper){
 $did=intval($_POST['dealer_id']); $amt=floatval(str_replace([' ',','],['',''],$_POST['amount'] ?? '0')); $note=trim($_POST['note'] ?? '');
 if($did>0 && $amt>0){ addDealerPayment($did,$amt,$note,$u['id']); }
 header('Location: balance.php?'.$qsFilter); exit;
}

// ==== Oylik maqsad belgilash (faqat Bosh admin) ====
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='set_target' && $isSuper){
 $did=intval($_POST['dealer_id']); $tgt=floatval(str_replace([' ',','],['',''],$_POST['target'] ?? '0'));
 if($did>0){ setDealerTarget($did,$tgt); }
 header('Location: balance.php?'.$qsFilter); exit;
}

// ==== Excel/CSV eksport ====
if(isset($_GET['export']) && $_GET['export']==='csv'){
 $scope = $_GET['scope'] ?? 'all';
 header('Content-Type: text/csv; charset=UTF-8');
 header('Content-Disposition: attachment; filename="paynet_som_'.date('Y-m-d').'.csv"');
 echo "\xEF\xBB\xBF"; // BOM - Excelda kirill/lotin harflari to'g'ri chiqishi uchun
 $out=fopen('php://output','w');
 if($scope==='dealer'){
  $did=intval($_GET['id'] ?? 0);
  if(!$isSuper && $did!==intval($u['id'])) exit;
  $rows=dealerParticipantsDetailed($did,$from?:null,$to?:null);
  fputcsv($out,['Ism','Nomer','Operator','Tarif','Narx','1+1','Sana']);
  foreach($rows as $r){ fputcsv($out,[$r['name'],$r['pretty_phone'],$r['operator_name'],$r['tarif_name'],csvSum($r['price']),($r['promo_count']==2?'Ha':'Yoq'),$r['created_at']]); }
 } else {
  if(!$isSuper) exit;
  $rows=allDealerBalances($from?:null,$to?:null);
  fputcsv($out,['Diller','Soni','Jami summa','Oylik maqsad']);
  foreach($rows as $r){ fputcsv($out,[$r['name'],$r['cnt'],csvSum($r['balance']),csvSum($r['monthly_target'])]); }
  fputcsv($out,['JAMI','','',csvSum(totalBalance($from?:null,$to?:null)),'']);
 }
 fclose($out); exit;
}

// ==== Oddiy sahifa render qilinadi ====
include 'layout_header.php';

$sort=$_GET['sort'] ?? 'balance'; $dir=$_GET['dir'] ?? 'DESC';

if($isSuper){
 $rows = allDealerBalances($from?:null,$to?:null,$sort,$dir);
 $total = totalBalance($from?:null,$to?:null);
 $topDealers = $rows; usort($topDealers, fn($a,$b)=>$b['balance']<=>$a['balance']); $topDealers=array_slice($topDealers,0,5);
 $topTarifsList = topTarifs(5,null,$from?:null,$to?:null);
 $opBalances = operatorBalances(null,$from?:null,$to?:null);
 $monthly = monthlyRevenue(12,null);
 $todaySum = periodSum(date('Y-m-d 00:00:00'),date('Y-m-d 23:59:59'));
 $yestSum = periodSum(date('Y-m-d 00:00:00',strtotime('-1 day')),date('Y-m-d 23:59:59',strtotime('-1 day')));
 $weekSum = periodSum(date('Y-m-d 00:00:00',strtotime('monday this week')),date('Y-m-d 23:59:59'));
 $lastWeekSum = periodSum(date('Y-m-d 00:00:00',strtotime('monday last week')),date('Y-m-d 23:59:59',strtotime('sunday this week -7 days')));
 $todayGrowth = $yestSum>0 ? round((($todaySum-$yestSum)/$yestSum)*100) : ($todaySum>0?100:0);
 $weekGrowth = $lastWeekSum>0 ? round((($weekSum-$lastWeekSum)/$lastWeekSum)*100) : ($weekSum>0?100:0);
} else {
 $myBalance = dealerBalance($u['id'],$from?:null,$to?:null);
 $myPaid = dealerPaidTotal($u['id']);
 $myPending = max(0,$myBalance-$myPaid);
 $breakdown = topTarifs(50,$u['id'],$from?:null,$to?:null);
 $opBalances = operatorBalances($u['id'],$from?:null,$to?:null);
 $monthly = monthlyRevenue(12,$u['id']);
 $myTarget = 0; try{ $s=db()->prepare("SELECT monthly_target FROM dealers WHERE id=?"); $s->execute([$u['id']]); $myTarget=(float)$s->fetchColumn(); }catch(Exception $e){}
 $myPaymentsHist = dealerPaymentsHistory($u['id']);
 $todaySum = periodSum(date('Y-m-d 00:00:00'),date('Y-m-d 23:59:59'),$u['id']);
 $yestSum = periodSum(date('Y-m-d 00:00:00',strtotime('-1 day')),date('Y-m-d 23:59:59',strtotime('-1 day')),$u['id']);
 $todayGrowth = $yestSum>0 ? round((($todaySum-$yestSum)/$yestSum)*100) : ($todaySum>0?100:0);
}

function growthBadge($pct){
 $up = $pct>=0;
 $cls = $up ? 'text-[#7c6cff] bg-[#7c6cff]/10 border-[#7c6cff]/20' : 'text-red-300 bg-red-500/10 border-red-500/20';
 $arrow = $up ? '↑' : '↓';
 return '<span class="text-[11px] font-bold px-2 py-0.5 rounded-full border '.$cls.'">'.$arrow.' '.abs($pct).'%</span>';
}
?>
<h1 class="font-black text-xl mb-4 flex items-center gap-2"><?php echo icon('wallet','w-5 h-5'); ?> So'm</h1>

<!-- ==== JAMI PUL - eng tepada, doim ko'rinadi ==== -->
<div class="card p-6 mb-4 border-[#7c6cff]/25 text-center relative overflow-hidden">
<p class="text-white/40 text-xs tracking-widest mb-1"><?php echo $isSuper ? 'UMUMIY JAMI' : 'SIZNING BALANSINGIZ'; ?></p>
<p class="font-black text-4xl md:text-5xl grad-text"><?php echo fmtSum($isSuper ? $total : $myBalance); ?> <span class="text-lg">so'm</span></p>
<div class="flex justify-center gap-2 mt-3 flex-wrap">
<?php echo growthBadge($todayGrowth); ?><span class="text-white/30 text-[11px] self-center">bugun (kechagiga nisbatan)</span>
<?php if($isSuper): ?><span class="mx-1"></span><?php echo growthBadge($weekGrowth); ?><span class="text-white/30 text-[11px] self-center">shu hafta (o'tganiga nisbatan)</span><?php endif; ?>
</div>
<?php if(!$isSuper): ?>
<div class="flex justify-center gap-6 mt-4 text-sm">
<div><p class="text-white/30 text-[10px]">TO'LANGAN</p><p class="font-bold text-[#7c6cff]"><?php echo fmtSum($myPaid); ?></p></div>
<div><p class="text-white/30 text-[10px]">KUTILAYOTGAN</p><p class="font-bold text-yellow-300"><?php echo fmtSum($myPending); ?></p></div>
</div>
<?php if($myTarget>0): $pct=min(100,round($myBalance/$myTarget*100)); ?>
<div class="mt-4 max-w-sm mx-auto text-left">
<div class="flex justify-between text-[11px] text-white/40 mb-1"><span>Oylik maqsad</span><span><?php echo fmtSum($myBalance); ?> / <?php echo fmtSum($myTarget); ?></span></div>
<div class="w-full h-2.5 bg-white/5 rounded-full overflow-hidden"><div class="h-full bg-gradient-to-r from-[#7c6cff] to-[#f5a623]" style="width:<?php echo $pct; ?>%"></div></div>
</div>
<?php endif; ?>
<?php endif; ?>
</div>

<!-- ==== Filtr paneli ==== -->
<div class="card p-4 mb-4">
<div class="flex flex-wrap gap-2 items-center justify-between">
<div class="flex flex-wrap gap-2">
<?php foreach(['all'=>'Hammasi','today'=>'Bugun','week'=>'Shu hafta','month'=>'Shu oy'] as $k=>$lbl): ?>
<a href="balance.php?range=<?php echo $k; ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold border <?php echo ($range===$k || ($k==='all' && $range==='' && !$from)) ? 'bg-[#7c6cff] text-white border-[#7c6cff]' : 'bg-white/5 text-white/60 border-white/10'; ?>"><?php echo $lbl; ?></a>
<?php endforeach; ?>
</div>
<form method="get" class="flex gap-2 items-center flex-wrap">
<input type="date" name="from" value="<?php echo htmlspecialchars($from); ?>" class="p-2 rounded-lg bg-black/40 border border-white/10 text-white text-xs">
<span class="text-white/30 text-xs">—</span>
<input type="date" name="to" value="<?php echo htmlspecialchars($to); ?>" class="p-2 rounded-lg bg-black/40 border border-white/10 text-white text-xs">
<button class="bg-white/10 text-white px-3 py-2 rounded-lg text-xs font-bold border border-white/10">Filtrlash</button>
<?php if($isSuper): ?><a href="balance.php?export=csv&scope=all&<?php echo $qsFilter; ?>" class="bg-[#7c6cff] text-white px-3 py-2 rounded-lg text-xs font-bold">📊 Excel</a><?php else: ?><a href="balance.php?export=csv&scope=dealer&id=<?php echo $u['id']; ?>&<?php echo $qsFilter; ?>" class="bg-[#7c6cff] text-white px-3 py-2 rounded-lg text-xs font-bold">📊 Excel</a><?php endif; ?>
</form>
</div>
</div>

<!-- ==== Grafik: oylik daromad ==== -->
<div class="card p-4 mb-4">
<h3 class="font-bold text-sm text-white/50 mb-2">📈 Oylik daromad (12 oy)</h3>
<canvas id="monthlyChart" height="90"></canvas>
</div>

<?php if($isSuper): ?>
<!-- ==== Reyting: Top-5 diller va Top-5 tarif ==== -->
<div class="grid md:grid-cols-2 gap-4 mb-4">
<div class="card p-4">
<h3 class="font-bold text-sm text-white/50 mb-3">🏆 Top-5 diller</h3>
<?php $medals=['🥇','🥈','🥉','4️⃣','5️⃣']; foreach($topDealers as $i=>$d): if($d['balance']<=0) continue; ?>
<div class="flex justify-between items-center py-2 border-b border-white/5 last:border-0">
<span class="text-sm"><?php echo $medals[$i] ?? ($i+1); ?> <?php echo htmlspecialchars($d['name']); ?></span>
<b class="text-[#7c6cff] text-sm"><?php echo fmtSum($d['balance']); ?></b>
</div>
<?php endforeach; ?>
</div>
<div class="card p-4">
<h3 class="font-bold text-sm text-white/50 mb-3">🏆 Top-5 tarif</h3>
<?php foreach($topTarifsList as $i=>$t): ?>
<div class="flex justify-between items-center py-2 border-b border-white/5 last:border-0">
<span class="text-sm"><?php echo $medals[$i] ?? ($i+1); ?> <?php echo htmlspecialchars($t['operator_name']); ?> — <?php echo htmlspecialchars($t['tarif_name']); ?></span>
<b class="text-[#7c6cff] text-sm"><?php echo fmtSum($t['balance']); ?></b>
</div>
<?php endforeach; ?>
</div>
</div>
<?php endif; ?>

<!-- ==== Operatorlar bo'yicha kartalar ==== -->
<h2 class="font-black text-sm text-white/40 mb-3 tracking-widest">OPERATOR BO'YICHA</h2>
<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-6">
<?php foreach($opBalances as $ob): ?>
<div class="card p-4 card-hover cursor-pointer op-card" data-op="<?php echo htmlspecialchars($ob['operator_name']); ?>">
<div class="flex justify-between items-center">
<b><?php echo htmlspecialchars($ob['operator_name']); ?></b>
<span class="text-white/30 text-xs"><?php echo $ob['cnt']; ?> ta</span>
</div>
<p class="font-black text-lg text-[#7c6cff] mt-1"><?php echo fmtSum($ob['balance']); ?> <span class="text-xs text-white/30">so'm</span></p>
<div class="op-detail hidden mt-3 pt-3 border-t border-white/5 text-xs space-y-1"></div>
</div>
<?php endforeach; ?>
<?php if(empty($opBalances)): ?><div class="card p-8 text-center text-white/30 sm:col-span-2 lg:col-span-3">Hali ma'lumot yo'q</div><?php endif; ?>
</div>

<?php if($isSuper): ?>
<!-- ==== Dillerlar ro'yxati: qidiruv, saralash, ochiladigan tafsilot ==== -->
<div class="flex justify-between items-center mb-3 flex-wrap gap-2">
<h2 class="font-black text-sm text-white/40 tracking-widest">DILLERLAR BO'YICHA</h2>
<input id="dealerSearch" placeholder="🔍 Diller qidirish..." class="p-2 rounded-lg bg-black/40 border border-white/10 text-white text-xs w-52">
</div>
<div class="flex gap-2 mb-3 text-[11px]">
<span class="text-white/30">Saralash:</span>
<?php foreach(['balance'=>'Summa','cnt'=>'Soni','name'=>'Nomi'] as $k=>$lbl): $nd=($sort===$k && $dir==='DESC')?'ASC':'DESC'; ?>
<a href="balance.php?<?php echo $qsFilter; ?>&sort=<?php echo $k; ?>&dir=<?php echo $nd; ?>" class="px-2 py-1 rounded-lg border <?php echo $sort===$k?'bg-white/10 border-white/20 text-white':'border-white/10 text-white/50'; ?>"><?php echo $lbl; ?> <?php echo $sort===$k ? ($dir==='DESC'?'↓':'↑') : ''; ?></a>
<?php endforeach; ?>
</div>
<div id="dealerList" class="space-y-3 mb-6">
<?php foreach($rows as $r): ?>
<div class="card p-4 dealer-card" data-name="<?php echo htmlspecialchars(mb_strtolower($r['name'])); ?>">
<div class="flex justify-between items-center cursor-pointer dealer-toggle" data-id="<?php echo $r['id']; ?>">
<div>
<b class="text-base"><?php echo htmlspecialchars($r['name']); ?></b>
<p class="text-xs text-white/40 mt-0.5"><?php echo $r['cnt']; ?> ta tasdiqlangan nomer</p>
</div>
<div class="text-right flex items-center gap-3">
<div><p class="font-black text-lg text-[#7c6cff]"><?php echo fmtSum($r['balance']); ?></p><p class="text-[10px] text-white/30">so'm</p></div>
<span class="text-white/30 chevron">▾</span>
</div>
</div>
<?php if($r['monthly_target']>0): $pct=min(100,round($r['balance']/$r['monthly_target']*100)); ?>
<div class="mt-2"><div class="flex justify-between text-[10px] text-white/30 mb-1"><span>Maqsad</span><span><?php echo $pct; ?>%</span></div><div class="w-full h-1.5 bg-white/5 rounded-full overflow-hidden"><div class="h-full bg-gradient-to-r from-[#7c6cff] to-[#f5a623]" style="width:<?php echo $pct; ?>%"></div></div></div>
<?php endif; ?>
<div class="dealer-detail hidden mt-4 pt-4 border-t border-white/5" data-loaded="0"></div>
</div>
<?php endforeach; ?>
<?php if(empty($rows)): ?><div class="card p-10 text-center text-white/30">Hali dillerlar yo'q</div><?php endif; ?>
</div>
<?php else: ?>

<!-- ==== Diller: tariflar bo'yicha jadval ==== -->
<h2 class="font-black text-sm text-white/40 mb-3 tracking-widest">TARIFLAR BO'YICHA</h2>
<?php if(empty($breakdown)): ?>
<div class="card p-10 text-center text-white/30">Hali tasdiqlangan nomeringiz yo'q</div>
<?php else: ?>
<div class="card overflow-auto mb-6">
<table class="w-full text-sm"><thead><tr class="bg-black/50 text-white/30 text-[11px]"><th class="p-3 text-left">Operator / Tarif</th><th>Narx</th><th>Soni</th><th>Jami</th></tr></thead><tbody>
<?php foreach($breakdown as $b): if($b['balance']<=0) continue; ?>
<tr class="border-b border-white/5">
<td class="p-3"><b><?php echo htmlspecialchars($b['operator_name']); ?></b><br><span class="text-xs text-white/40"><?php echo htmlspecialchars($b['tarif_name']); ?></span></td>
<td class="text-center text-xs text-white/60"><?php echo fmtSum($b['price']); ?></td>
<td class="text-center text-xs text-white/60"><?php echo $b['cnt']; ?></td>
<td class="text-center font-bold text-[#7c6cff]"><?php echo fmtSum($b['balance']); ?></td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div>
<?php endif; ?>

<?php if(!empty($myPaymentsHist)): ?>
<h2 class="font-black text-sm text-white/40 mb-3 tracking-widest">TO'LOVLAR TARIXI</h2>
<div class="card overflow-auto mb-6">
<table class="w-full text-sm"><thead><tr class="bg-black/50 text-white/30 text-[11px]"><th class="p-3 text-left">Sana</th><th class="text-left">Izoh</th><th>Summa</th></tr></thead><tbody>
<?php foreach($myPaymentsHist as $p): ?>
<tr class="border-b border-white/5"><td class="p-3 text-xs text-white/50"><?php echo $p['created_at']; ?></td><td class="text-xs text-white/50"><?php echo htmlspecialchars($p['note']); ?></td><td class="text-center font-bold text-[#7c6cff]"><?php echo fmtSum($p['amount']); ?></td></tr>
<?php endforeach; ?>
</tbody></table>
</div>
<?php endif; ?>
<?php endif; ?>

<p class="text-[11px] text-white/25 mt-2 mb-6">Balans faqat tasdiqlangan (bloklanmagan) nomerlar bo'yicha hisoblanadi, 1+1 aksiyada narx bitta marta hisoblanadi. Tarif narxlarini Bosh admin "Operator & Tarif" sahifasida belgilaydi.</p>

<script>
const monthlyData = <?php echo json_encode($monthly, JSON_UNESCAPED_UNICODE); ?>;
new Chart(document.getElementById('monthlyChart'), {
 type: 'bar',
 data: {
  labels: monthlyData.map(m => m.ym),
  datasets: [{ label: "So'm", data: monthlyData.map(m => m.balance), backgroundColor: 'rgba(124,108,255,.55)', borderRadius: 6 }]
 },
 options: { responsive:true, plugins:{ legend:{ display:false } }, scales:{ x:{ ticks:{ color:'rgba(255,255,255,.4)' }, grid:{ display:false } }, y:{ ticks:{ color:'rgba(255,255,255,.4)' }, grid:{ color:'rgba(255,255,255,.05)' } } } }
});

// Operator kartalarini ochish/yopish (tariflarni ko'rsatish)
document.querySelectorAll('.op-card').forEach(card => {
 card.addEventListener('click', () => {
  const detail = card.querySelector('.op-detail');
  if(!detail.classList.contains('hidden')){ detail.classList.add('hidden'); return; }
  document.querySelectorAll('.op-detail').forEach(d => d.classList.add('hidden'));
  if(detail.dataset.loaded==='1'){ detail.classList.remove('hidden'); return; }
  const op = card.dataset.op;
  fetch('balance.php?ajax=operator_detail&op='+encodeURIComponent(op)+'&<?php echo $qsFilter; ?>')
   .then(r=>r.json()).then(d=>{
    detail.innerHTML = (d.rows||[]).map(t=>`<div class="flex justify-between"><span class="text-white/50">${t.tarif_name}</span><b class="text-[#7c6cff]">${Number(t.balance).toLocaleString('ru-RU')}</b></div>`).join('') || '<span class="text-white/30">Ma\'lumot yo\'q</span>';
    detail.dataset.loaded='1'; detail.classList.remove('hidden');
   }).catch(()=>{ detail.innerHTML='<span class="text-red-300">Xatolik</span>'; detail.classList.remove('hidden'); });
 });
});

<?php if($isSuper): ?>
// Diller qidiruv
document.getElementById('dealerSearch').addEventListener('input', e=>{
 const q = e.target.value.trim().toLowerCase();
 document.querySelectorAll('.dealer-card').forEach(c=>{ c.style.display = c.dataset.name.includes(q) ? '' : 'none'; });
});

// Diller kartasini ochish - batafsil ro'yxat, to'lov, maqsad
document.querySelectorAll('.dealer-toggle').forEach(t => {
 t.addEventListener('click', () => {
  const card = t.closest('.dealer-card');
  const detail = card.querySelector('.dealer-detail');
  const chevron = t.querySelector('.chevron');
  if(!detail.classList.contains('hidden')){ detail.classList.add('hidden'); chevron.textContent='▾'; return; }
  chevron.textContent='▴';
  if(detail.dataset.loaded==='1'){ detail.classList.remove('hidden'); return; }
  const id = t.dataset.id;
  fetch('balance.php?ajax=dealer_detail&id='+id+'&<?php echo $qsFilter; ?>')
   .then(r=>r.json()).then(d=>{
    let html='';
    html += `<div class="grid grid-cols-3 gap-2 mb-4 text-center">
      <div class="bg-black/30 rounded-xl p-2"><p class="text-[10px] text-white/30">JAMI</p><p class="font-bold text-sm">${Number(d.balance).toLocaleString('ru-RU')}</p></div>
      <div class="bg-black/30 rounded-xl p-2"><p class="text-[10px] text-white/30">TO'LANGAN</p><p class="font-bold text-sm text-[#7c6cff]">${Number(d.paid).toLocaleString('ru-RU')}</p></div>
      <div class="bg-black/30 rounded-xl p-2"><p class="text-[10px] text-white/30">QOLDI</p><p class="font-bold text-sm text-yellow-300">${Number(d.pending).toLocaleString('ru-RU')}</p></div>
    </div>`;
    html += `<form method="post" class="flex gap-2 mb-2">
      <input type="hidden" name="action" value="add_payment"><input type="hidden" name="dealer_id" value="${id}">
      <input name="amount" type="number" step="0.01" min="0" placeholder="Summa" class="flex-1 p-2 rounded-lg bg-black/40 border border-white/10 text-white text-xs">
      <input name="note" placeholder="Izoh (ixtiyoriy)" class="flex-1 p-2 rounded-lg bg-black/40 border border-white/10 text-white text-xs">
      <button class="bg-[#7c6cff] text-white px-3 rounded-lg text-xs font-bold whitespace-nowrap">💵 To'ladim</button>
    </form>`;
    html += `<form method="post" class="flex gap-2 mb-4">
      <input type="hidden" name="action" value="set_target"><input type="hidden" name="dealer_id" value="${id}">
      <input name="target" type="number" step="0.01" min="0" placeholder="Oylik maqsad (so'm)" class="flex-1 p-2 rounded-lg bg-black/40 border border-white/10 text-white text-xs">
      <button class="bg-white/10 text-white px-3 rounded-lg text-xs font-bold border border-white/10 whitespace-nowrap">🎯 Maqsad qo'yish</button>
    </form>`;
    if((d.list||[]).length){
     html += '<div class="max-h-64 overflow-auto"><table class="w-full text-xs"><thead><tr class="text-white/30"><th class="text-left p-1">Ism</th><th class="text-left">Nomer</th><th>Tarif</th><th>Narx</th><th>Sana</th></tr></thead><tbody>';
     d.list.forEach(p=>{ html += `<tr class="border-t border-white/5"><td class="p-1">${p.name}</td><td>${p.pretty_phone}</td><td class="text-center">${p.tarif_name}</td><td class="text-center text-[#7c6cff]">${Number(p.price).toLocaleString('ru-RU')}</td><td class="text-center text-white/30">${p.created_at}</td></tr>`; });
     html += '</tbody></table></div>';
    } else { html += '<p class="text-white/30 text-xs text-center py-3">Bu davrda nomer topilmadi</p>'; }
    if((d.payments||[]).length){
     html += '<p class="text-white/30 text-[11px] mt-3 mb-1">To\'lovlar tarixi:</p><div class="max-h-32 overflow-auto space-y-1">';
     d.payments.forEach(p=>{ html += `<div class="flex justify-between text-xs bg-black/20 p-2 rounded-lg"><span class="text-white/40">${p.created_at} ${p.note?('• '+p.note):''}</span><b class="text-[#7c6cff]">${Number(p.amount).toLocaleString('ru-RU')}</b></div>`; });
     html += '</div>';
    }
    detail.innerHTML = html; detail.dataset.loaded='1'; detail.classList.remove('hidden');
   }).catch(()=>{ detail.innerHTML='<span class="text-red-300 text-xs">Xatolik yuz berdi</span>'; detail.classList.remove('hidden'); });
 });
});
<?php endif; ?>
</script>
<?php include 'layout_footer.php'; ?>
