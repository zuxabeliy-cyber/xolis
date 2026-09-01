<?php include 'layout_header.php';
$ym=date('Y-m'); $start=$ym.'-01'; $end=date('Y-m-t',strtotime($start));
$scope=''; $sp=[]; if(!$isSuper){ $scope=" AND dealer_id=?"; $sp[]=$u['id']; }
function dashNum($sql,$sp){ try{ $s=db()->prepare($sql); $s->execute($sp); return (int)$s->fetchColumn(); }catch(Exception $e){ return 0; } }
$today = dashNum("SELECT COALESCE(SUM(promo_count),0) FROM paid_participants WHERE status='approved' AND trashed=0 AND DATE(created_at)=CURDATE()".$scope,$sp);
$monthTot = dashNum("SELECT COALESCE(SUM(promo_count),0) FROM paid_participants WHERE status='approved' AND trashed=0 AND DATE_FORMAT(created_at,'%Y-%m')='$ym'".$scope,$sp);
$monthGame = dashNum("SELECT COALESCE(SUM(promo_count),0) FROM paid_participants WHERE status='approved' AND trashed=0 AND is_paid=1 AND blacklisted=0 AND DATE_FORMAT(created_at,'%Y-%m')='$ym'".$scope,$sp);
$monthBaza = dashNum("SELECT COALESCE(SUM(promo_count),0) FROM paid_participants WHERE status='approved' AND trashed=0 AND is_paid=0 AND DATE_FORMAT(created_at,'%Y-%m')='$ym'".$scope,$sp);
$som = $isSuper ? totalBalance($start,$end) : dealerBalance($u['id'],$start,$end);
$pend = $isSuper ? pendingCount() : 0;
$trash = $isSuper ? trashCount() : 0;
// Oxirgi 7 kun grafigi
$days7=[]; for($i=6;$i>=0;$i--){ $d=date('Y-m-d',strtotime("-$i day")); $c=dashNum("SELECT COALESCE(SUM(promo_count),0) FROM paid_participants WHERE status='approved' AND trashed=0 AND DATE(created_at)='$d'".$scope,$sp); $days7[]=['d'=>date('d.m',strtotime($d)),'c'=>$c]; }
// Top diller (super)
$topDealers=[];
if($isSuper){ try{ $topDealers=db()->query("SELECT d.name, COUNT(p.id) c FROM dealers d LEFT JOIN paid_participants p ON p.dealer_id=d.id AND p.status='approved' AND p.trashed=0 AND DATE_FORMAT(p.created_at,'%Y-%m')='$ym' WHERE d.role='diller' GROUP BY d.id HAVING c>0 ORDER BY c DESC LIMIT 5")->fetchAll(); }catch(Exception $e){} }
function fmtS($n){ return number_format($n,0,'.',' '); }
?>
<div class="flex items-center justify-between gap-2 mb-4 flex-wrap">
 <div><h1 class="font-black text-2xl flex items-center gap-2">👋 Salom, <?php echo htmlspecialchars($u['name']); ?></h1>
 <p class="text-white/30 text-xs mt-1"><?php echo monthLabel($ym); ?> — tezkor ko'rinish</p></div>
 <?php if($isSuper): ?><button onclick="sendReport(this)" class="btn btn-primary btn-sm">📤 Oylik hisobotni Telegramga</button><?php endif; ?>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
 <a href="reports.php" class="card p-4 card-hover"><p class="text-[10px] text-white/30 tracking-widest">BUGUN</p><p class="text-3xl font-black mt-1 text-[#7c6cff]"><?php echo $today; ?></p><p class="text-[10px] text-white/25 mt-1">ta qo'shildi</p></a>
 <a href="reports.php" class="card p-4 card-hover"><p class="text-[10px] text-white/30 tracking-widest">BU OY JAMI</p><p class="text-3xl font-black mt-1"><?php echo $monthTot; ?></p><p class="text-[10px] text-white/25 mt-1">ishtirokchi</p></a>
 <a href="<?php echo $isSuper?'index.php':'reports.php'; ?>" class="card p-4 card-hover border-[#7c6cff]/15"><p class="text-[10px] text-[#7c6cff] tracking-widest">O'YINDA</p><p class="text-3xl font-black mt-1 text-[#7c6cff]"><?php echo $monthGame; ?></p><p class="text-[10px] text-white/25 mt-1">barabanda</p></a>
 <a href="participants.php" class="card p-4 card-hover"><p class="text-[10px] text-white/30 tracking-widest">BAZADA</p><p class="text-3xl font-black mt-1"><?php echo $monthBaza; ?></p><p class="text-[10px] text-white/25 mt-1">o'yinga kutilyapti</p></a>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
 <a href="balance.php" class="card p-4 card-hover md:col-span-2"><p class="text-[10px] text-white/30 tracking-widest">BU OY SUMMA</p><p class="text-2xl font-black mt-1 grad-text"><?php echo fmtS($som); ?> <span class="text-sm">so'm</span></p></a>
 <?php if($isSuper): ?>
 <a href="pending.php" class="card p-4 card-hover <?php echo $pend>0?'border-[#f5a623]/25':''; ?>"><p class="text-[10px] text-white/30 tracking-widest">KUTILMOQDA</p><p class="text-3xl font-black mt-1 <?php echo $pend>0?'text-[#f5a623]':''; ?>"><?php echo $pend; ?></p></a>
 <a href="trash.php" class="card p-4 card-hover"><p class="text-[10px] text-white/30 tracking-widest">CHIQINDI</p><p class="text-3xl font-black mt-1"><?php echo $trash; ?></p></a>
 <?php endif; ?>
</div>

<div class="grid lg:grid-cols-3 gap-4 mb-4">
 <div class="card p-4 lg:col-span-2"><h3 class="font-bold text-sm text-white/50 mb-2">📈 Oxirgi 7 kun</h3><canvas id="wk" height="90"></canvas></div>
 <div class="card p-4">
  <h3 class="font-bold text-sm text-white/50 mb-2"><?php echo $isSuper?'🏆 Top dillerlar (bu oy)':'⚡ Tezkor'; ?></h3>
  <?php if($isSuper): ?>
   <?php if($topDealers): $md=['🥇','🥈','🥉','4️⃣','5️⃣']; foreach($topDealers as $i=>$d): ?>
   <div class="flex justify-between items-center py-2 border-b border-white/5 last:border-0"><span class="text-sm"><?php echo $md[$i]??''; ?> <?php echo htmlspecialchars($d['name']); ?></span><b class="text-[#7c6cff] text-sm"><?php echo $d['c']; ?> ta</b></div>
   <?php endforeach; else: ?><p class="text-white/30 text-sm text-center py-6">Bu oy hali qo'shilmagan</p><?php endif; ?>
  <?php else: ?>
   <a href="add.php" class="btn btn-primary w-full mb-2">➕ Nomer qo'shish</a>
   <a href="participants.php" class="btn btn-ghost w-full mb-2">📋 Mening ro'yxatim</a>
   <a href="balance.php" class="btn btn-ghost w-full">💰 Balansim</a>
  <?php endif; ?>
 </div>
</div>

<script>
var wk=<?php echo json_encode($days7, JSON_UNESCAPED_UNICODE); ?>;
new Chart(document.getElementById('wk'),{type:'bar',data:{labels:wk.map(function(x){return x.d;}),datasets:[{data:wk.map(function(x){return x.c;}),backgroundColor:'rgba(124,108,255,.6)',borderRadius:6}]},options:{plugins:{legend:{display:false}},scales:{x:{grid:{display:false},ticks:{color:'#999',font:{size:10}}},y:{beginAtZero:true,grid:{color:'rgba(255,255,255,.05)'},ticks:{color:'#999',font:{size:10}}}}}});
function sendReport(btn){
 if(!confirm("Bu oy hisobotini Telegram kanalga yuborilsinmi?")) return;
 btn.disabled=true; var old=btn.textContent; btn.textContent='⏳ Yuborilmoqda...';
 fetch('api.php?action=send_month_report&ym=<?php echo $ym; ?>').then(function(r){return r.json();}).then(function(d){
  btn.disabled=false; btn.textContent=old;
  alert(d.ok ? '✅ Hisobot kanalga yuborildi!' : ('⚠️ '+(d.msg||'Xatolik')));
 }).catch(function(){ btn.disabled=false; btn.textContent=old; alert('⚠️ Tarmoq xatosi'); });
}
</script>
<?php include 'layout_footer.php'; ?>
