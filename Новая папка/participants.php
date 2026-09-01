<?php include 'layout_header.php';
$q=trim($_GET['q']??'');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 100;
$offset = ($page-1)*$perPage;

$sqlBase="FROM paid_participants p LEFT JOIN dealers d ON d.id=p.dealer_id WHERE 1";
$pr=[];
if(!$isSuper){ $sqlBase.=" AND p.dealer_id=?"; $pr[]=$u['id']; }
if($q!==''){
 $sqlBase.=" AND (p.name LIKE ? OR p.phone LIKE ? OR p.pretty_phone LIKE ? OR p.operator_name LIKE ? OR p.tarif_name LIKE ?)";
 for($i=0;$i<5;$i++) $pr[]="%$q%";
}

// Jami soni - FIXED: endi limit siz hisoblaydi
try{
 $cntSt=db()->prepare("SELECT COUNT(*) $sqlBase");
 $cntSt->execute($pr);
 $totalCount = (int)$cntSt->fetchColumn();
}catch(Exception $e){ $totalCount=0; }

$totalPages = max(1, ceil($totalCount / $perPage));
if($page > $totalPages) $page = $totalPages;
$offset = ($page-1)*$perPage;

$sql="SELECT p.*, d.name as dealer_name $sqlBase ORDER BY p.id DESC LIMIT $perPage OFFSET $offset";
try{ $st=db()->prepare($sql); $st->execute($pr); $rows=$st->fetchAll(); }catch(Exception $e){ $rows=[]; }

// promo bilan jami (dashboard bilan bir xil)
try{
 $sumSt=db()->prepare("SELECT COALESCE(SUM(promo_count),0) $sqlBase");
 $sumSt->execute($pr);
 $totalWithPromo = (int)$sumSt->fetchColumn();
}catch(Exception $e){ $totalWithPromo=$totalCount; }
?>
<div class="flex flex-wrap justify-between items-center gap-2 mb-3">
<div>
<h1 class="font-black text-xl flex items-center gap-2"><?php echo icon('list','w-5 h-5'); ?> Ro'yxat <?php echo $totalCount; ?> ta <?php if($totalWithPromo!=$totalCount): ?><span class="text-[#1fae76] text-sm">(promo bilan <?php echo $totalWithPromo; ?> ta)</span><?php endif; ?></h1>
<p class="text-[10px] text-white/30 mt-1">Sahifa <?php echo $page; ?> / <?php echo $totalPages; ?> • Har sahifada <?php echo $perPage; ?> tadan</p>
</div>
<a href="api.php?action=export_csv" class="bg-white text-black px-4 py-2 rounded-xl text-sm font-bold">⬇️ Excel</a>
</div>
<div class="card p-3 mb-3"><form class="flex gap-2"><input name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Ism, nomer, 4 xona, operator yoki tarif" class="flex-1 p-3 rounded-xl bg-black/50 border border-white/10 text-white outline-none"><button class="bg-white text-black px-5 rounded-xl font-bold">Qidir</button><?php if($q!==''): ?><a href="participants.php" class="bg-white/10 border border-white/10 px-4 py-3 rounded-xl text-xs">✕ Tozalash</a><?php endif; ?></form></div>

<?php if($totalPages>1): ?>
<div class="flex gap-1 flex-wrap mb-3">
<?php if($page>1): ?><a href="?q=<?php echo urlencode($q); ?>&page=<?php echo $page-1; ?>" class="bg-white/10 border border-white/10 px-3 py-2 rounded-lg text-xs">⬅ Oldingi</a><?php endif; ?>
<?php for($i=1;$i<=$totalPages;$i++): if($i==1 || $i==$totalPages || ($i>=$page-2 && $i<=$page+2)): ?>
<a href="?q=<?php echo urlencode($q); ?>&page=<?php echo $i; ?>" class="px-3 py-2 rounded-lg text-xs font-bold <?php echo $i==$page?'bg-white text-black':'bg-white/5 border border-white/10 text-white/60'; ?>"><?php echo $i; ?></a>
<?php elseif($i==$page-3 || $i==$page+3): ?><span class="px-2 py-2 text-white/20 text-xs">...</span><?php endif; endfor; ?>
<?php if($page<$totalPages): ?><a href="?q=<?php echo urlencode($q); ?>&page=<?php echo $page+1; ?>" class="bg-white/10 border border-white/10 px-3 py-2 rounded-lg text-xs">Keyingi ➡</a><?php endif; ?>
</div>
<?php endif; ?>

<div class="card overflow-auto"><table class="w-full text-sm"><tr class="bg-black/50 text-white/30 text-xs"><th class="p-3 text-left">Diller</th><th>Ism</th><th>Nomer</th><th>Operator</th><th>Holat</th><th>Sana / Soat</th><th></th></tr>
<?php foreach($rows as $r): ?>
<tr class="border-b border-white/5 <?php echo $r['blacklisted']?'bg-white/5':''; ?>">
<td class="p-3 text-white/60 text-xs font-bold"><?php echo htmlspecialchars($r['dealer_name']); ?></td>
<td class="p-3"><b><?php echo htmlspecialchars($r['name']); ?></b> <?php echo $r['blacklisted']?'<span class="bg-white text-black text-[9px] px-1 rounded">TASDIQLANGAN</span>':''; ?></td>
<td class="font-mono text-xs"><?php echo htmlspecialchars($r['pretty_phone']); ?></td>
<td class="text-xs"><?php echo htmlspecialchars($r['operator_name']); ?> / <?php echo htmlspecialchars($r['tarif_name']); ?><br>
<?php if($isSuper): ?>
<span id="pstate-<?php echo $r['id']; ?>" class="text-[10px] <?php echo $r['is_paid']?'text-[#1fae76]':'text-white/30'; ?>"><?php echo $r['is_paid']?"O'YINDA":'BAZADA'; ?></span>
<?php if(!empty($r['promo_count']) && $r['promo_count']==2): ?><span class="ml-1 text-[9px] bg-[#1fae76]/15 text-[#1fae76] px-1.5 py-0.5 rounded-full font-bold">🎁 x2</span><?php endif; ?>
<div class="flex gap-1 mt-1">
<button type="button" onclick="markState(<?php echo $r['id']; ?>,0)" class="px-2 py-0.5 rounded-md text-[9px] font-bold border border-white/10 bg-white/5 hover:bg-white/10">→ BAZAGA</button>
<button type="button" onclick="markState(<?php echo $r['id']; ?>,1)" class="px-2 py-0.5 rounded-md text-[9px] font-bold border border-[#1fae76]/20 bg-[#1fae76]/10 text-[#1fae76] hover:bg-[#1fae76]/20">→ O'YINGA</button>
</div>
<?php else: ?>
<span class="text-[10px] <?php echo $r['is_paid']?'text-[#1fae76]':'text-white/30'; ?>"><?php echo $r['is_paid']?"O'YINDA":'BAZADA'; ?></span>
<?php if(!empty($r['promo_count']) && $r['promo_count']==2): ?><span class="ml-1 text-[9px] bg-[#1fae76]/15 text-[#1fae76] px-1.5 py-0.5 rounded-full font-bold">🎁 x2</span><?php endif; ?>
<?php endif; ?>
</td>
<td class="text-xs"><?php if($r['status']=='pending'): ?><span class="px-2 py-1 rounded-full bg-[#1fae76]/10 text-[#1fae76] border border-[#1fae76]/20 text-[10px] font-bold">⏳ Kutilmoqda</span><?php elseif($r['status']=='rejected'): ?><span class="px-2 py-1 rounded-full bg-red-500/10 text-red-300 border border-red-500/20 text-[10px] font-bold">❌ Rad etildi</span><?php else: ?><span class="px-2 py-1 rounded-full bg-white/5 text-white/50 border border-white/10 text-[10px] font-bold">✅ Tasdiqlandi</span><?php endif; ?></td>
<td class="text-xs"><?php echo date('d.m.Y', strtotime($r['created_at'])); ?><br><span class="text-white/40"><?php echo date('H:i:s', strtotime($r['created_at'])); ?></span></td>
<td class="whitespace-nowrap">
<?php if($isSuper || ($r['dealer_id']==$u['id'] && $r['status']=='pending')): ?>
<a href="edit.php?id=<?php echo $r['id']; ?>" class="text-[#1fae76] mr-3">✏️</a><a href="delete.php?id=<?php echo $r['id']; ?>" onclick="return confirm('<?php echo $r['status']=='pending' && !$isSuper ? "Kutilmoqdagi yuborishingizni bekor qilasizmi?" : "Ochir?"; ?>')" class="text-red-400">🗑</a>
<?php else: ?><span class="text-white/10 text-xs">—</span><?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php if(empty($rows)): ?><div class="p-10 text-center text-white/30">Hech narsa topilmadi</div><?php endif; ?>
</div>

<?php if($totalPages>1): ?>
<div class="flex gap-1 flex-wrap mt-3">
<?php if($page>1): ?><a href="?q=<?php echo urlencode($q); ?>&page=<?php echo $page-1; ?>" class="bg-white/10 border border-white/10 px-3 py-2 rounded-lg text-xs">⬅ Oldingi</a><?php endif; ?>
<?php for($i=1;$i<=$totalPages;$i++): if($i==1 || $i==$totalPages || ($i>=$page-2 && $i<=$page+2)): ?>
<a href="?q=<?php echo urlencode($q); ?>&page=<?php echo $i; ?>" class="px-3 py-2 rounded-lg text-xs font-bold <?php echo $i==$page?'bg-white text-black':'bg-white/5 border border-white/10 text-white/60'; ?>"><?php echo $i; ?></a>
<?php elseif($i==$page-3 || $i==$page+3): ?><span class="px-2 py-2 text-white/20 text-xs">...</span><?php endif; endfor; ?>
<?php if($page<$totalPages): ?><a href="?q=<?php echo urlencode($q); ?>&page=<?php echo $page+1; ?>" class="bg-white/10 border border-white/10 px-3 py-2 rounded-lg text-xs">Keyingi ➡</a><?php endif; ?>
</div>
<?php endif; ?>

<?php if($isSuper): ?>
<script>
function markState(id, val){
 fetch('api.php?action=toggle_paid&id='+id+'&val='+val, {method:'POST'})
 .then(function(r){ return r.json(); })
 .then(function(d){
  if(d.ok){
   var el=document.getElementById('pstate-'+id);
   if(el){ el.textContent = d.is_paid=='1' ? "O'YINDA" : 'BAZADA'; el.className = 'text-[10px] ' + (d.is_paid=='1' ? 'text-[#1fae76]' : 'text-white/30'); }
  }
 }).catch(function(){});
}
</script>
<?php endif; ?>
<?php include 'layout_footer.php'; ?>