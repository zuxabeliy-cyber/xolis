<?php include 'layout_header.php'; if(!$isSuper) exit;
if(isset($_GET['clear'])){ try{ db()->exec("DELETE FROM duplicate_attempts"); }catch(Exception $e){} header("Location: duplicates.php"); exit; }
try{ $rows=db()->query("SELECT da.*, d.name as dealer_name, p.name as existing_name, p.dealer_id as existing_dealer FROM duplicate_attempts da LEFT JOIN dealers d ON d.id=da.dealer_id LEFT JOIN paid_participants p ON p.phone=da.phone ORDER BY da.id DESC LIMIT 500")->fetchAll(); }catch(Exception $e){ $rows=[]; }
?>
<div class="flex flex-wrap justify-between items-center gap-2 mb-3">
 <h1 class="font-black text-xl flex items-center gap-2">🔁 Dublikat urinishlar <span class="text-white/30 text-sm"><?php echo count($rows); ?> ta</span></h1>
 <?php if($rows): ?><a href="?clear=1" onclick="return confirm('Ro\'yxat tozalansinmi?')" class="btn btn-ghost btn-sm">🧹 Tozalash</a><?php endif; ?>
</div>
<p class="text-white/30 text-xs -mt-1 mb-3">Kimdir allaqachon bazada bor nomerni qayta qo'shmoqchi bo'lganda shu yerga tushadi.</p>
<div class="card overflow-auto"><table class="w-full text-sm"><tr class="bg-black/50 text-white/30 text-xs"><th class="p-3 text-left">Vaqt</th><th class="text-left">Kim urindi</th><th class="text-left">Nomer</th><th class="text-left">Urinilgan ism</th><th class="text-left">Bazada bor</th></tr>
<?php foreach($rows as $r): ?>
<tr class="border-b border-white/5">
<td class="p-3 text-xs text-white/40 whitespace-nowrap"><?php echo date('d.m.Y H:i', strtotime($r['created_at'])); ?></td>
<td class="text-xs font-bold text-white/70"><?php echo htmlspecialchars($r['dealer_name']??'—'); ?></td>
<td class="font-mono text-xs"><?php echo htmlspecialchars($r['pretty_phone']); ?></td>
<td class="text-xs text-white/60"><?php echo htmlspecialchars($r['attempted_name']); ?> <span class="text-white/30">(<?php echo htmlspecialchars($r['attempted_operator']); ?> / <?php echo htmlspecialchars($r['attempted_tarif']); ?>)</span></td>
<td class="text-xs"><?php echo $r['existing_name']!==null ? '<b class="text-[#f5a623]">'.htmlspecialchars($r['existing_name']).'</b>' : '<span class="text-white/25">—</span>'; ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php if(empty($rows)): ?><div class="p-10 text-center text-white/30">Dublikat urinish yo'q 🎉</div><?php endif; ?>
</div>
<?php include 'layout_footer.php'; ?>
