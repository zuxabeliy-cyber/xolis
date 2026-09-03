<?php include 'layout_header.php'; if(!$isSuper) exit;
if(isset($_GET['clear'])){ try{ db()->exec("DELETE FROM spin_log"); }catch(Exception $e){} header("Location: spins.php"); exit; }
$ym=selectedMonth(); list($wc,$wp)=monthCond($ym,'created_at');
try{ $st=db()->prepare("SELECT * FROM spin_log WHERE 1".str_replace('p.created_at','created_at',$wc)." ORDER BY id DESC LIMIT 500"); $st->execute($wp); $rows=$st->fetchAll(); }catch(Exception $e){ $rows=[]; }
$poolLabel=['paid'=>"O'YINDA",'free'=>'BAZADA','all'=>'HAMMASI'];
?>
<div class="flex flex-wrap justify-between items-center gap-2 mb-3">
 <h1 class="font-black text-xl flex items-center gap-2"><?php echo icon('baraban','w-5 h-5'); ?> Aylanishlar tarixi <span class="text-white/30 text-sm"><?php echo count($rows); ?> ta</span></h1>
 <div class="flex gap-2">
  <a href="index.php" class="btn btn-ghost btn-sm">🎲 Barabanga</a>
  <?php if($rows): ?><a href="?clear=1" onclick="return confirm('Tarix tozalansinmi?')" class="btn btn-ghost btn-sm">🧹 Tozalash</a><?php endif; ?>
 </div>
</div>
<?php echo monthSelectorHtml($ym); ?>
<p class="text-white/30 text-xs -mt-1 mb-3">Har bir aylantirish qachon bo'lgani va kim yutgani (bu tasdiqlash emas — shunchaki tarix).</p>
<div class="card overflow-auto"><table class="w-full text-sm"><tr class="bg-black/50 text-white/30 text-xs"><th class="p-3 text-left">Vaqt</th><th class="text-left">Kim aylantirdi</th><th class="text-left">Guruh</th><th class="text-left">G'oliblar</th></tr>
<?php foreach($rows as $r): ?>
<tr class="border-b border-white/5">
<td class="p-3 text-xs text-white/40 whitespace-nowrap"><?php echo date('d.m.Y H:i', strtotime($r['created_at'])); ?></td>
<td class="text-xs font-bold text-white/70"><?php echo htmlspecialchars($r['created_by_name']); ?></td>
<td class="text-xs"><span class="px-2 py-0.5 rounded-full bg-[#7c6cff]/10 text-[#7c6cff] text-[10px] font-bold"><?php echo $poolLabel[$r['pool']]??$r['pool']; ?></span></td>
<td class="text-xs text-white/60"><?php echo htmlspecialchars($r['winners']); ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php if(empty($rows)): ?><div class="p-10 text-center text-white/30">Bu oyda aylanish bo'lmagan</div><?php endif; ?>
</div>
<?php include 'layout_footer.php'; ?>
