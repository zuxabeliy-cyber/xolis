<?php include 'layout_header.php'; if(!$isSuper) exit;
if(isset($_GET['clear'])){ try{ db()->exec("DELETE FROM activity_log"); }catch(Exception $e){} header("Location: activity.php"); exit; }
$page=max(1,intval($_GET['page']??1)); $per=200; $off=($page-1)*$per;
try{ $total=(int)db()->query("SELECT COUNT(*) FROM activity_log")->fetchColumn(); }catch(Exception $e){ $total=0; }
$pages=max(1,ceil($total/$per));
try{ $rows=db()->query("SELECT * FROM activity_log ORDER BY id DESC LIMIT $per OFFSET $off")->fetchAll(); }catch(Exception $e){ $rows=[]; }
$meta=[
 'add'=>['✅','Qo\'shildi','text-[#7c6cff]'],
 'trash'=>['🗑','Chiqindiga','text-red-300'],
 'bulk_trash'=>['🗑','Ko\'p o\'chirish','text-red-300'],
 'restore'=>['↩','Tiklandi','text-[#7c6cff]'],
 'purge'=>['❌','Butunlay o\'chirildi','text-red-300'],
 'empty_trash'=>['❌','Chiqindi bo\'shatildi','text-red-300'],
 'winner'=>['🏆','G\'olib','text-[#f5a623]'],
 'spin'=>['🎲','Baraban','text-[#f5a623]'],
 'toggle'=>['🔀','Holat o\'zgardi','text-white/60'],
 'login'=>['🔑','Kirdi','text-white/60'],
 'import'=>['📥','Import','text-[#7c6cff]'],
];
?>
<div class="flex flex-wrap justify-between items-center gap-2 mb-3">
 <h1 class="font-black text-xl flex items-center gap-2">📜 Faollik jurnali <span class="text-white/30 text-sm"><?php echo $total; ?> ta</span></h1>
 <?php if($rows): ?><a href="?clear=1" onclick="return confirm('Butun jurnal tozalansinmi?')" class="btn btn-ghost btn-sm">🧹 Tozalash</a><?php endif; ?>
</div>
<p class="text-white/30 text-xs -mt-1 mb-3">Kim, qachon, nima qilgani — nazorat uchun.</p>
<div class="card overflow-auto"><table class="w-full text-sm"><tr class="bg-black/50 text-white/30 text-xs"><th class="p-3 text-left">Vaqt</th><th class="text-left">Kim</th><th class="text-left">Amal</th><th class="text-left">Tafsilot</th></tr>
<?php foreach($rows as $r): $m=$meta[$r['action']]??['•',$r['action'],'text-white/60']; ?>
<tr class="border-b border-white/5">
<td class="p-3 text-xs text-white/40 whitespace-nowrap"><?php echo date('d.m.Y H:i:s', strtotime($r['created_at'])); ?></td>
<td class="text-xs font-bold text-white/70"><?php echo htmlspecialchars($r['user_name']); ?></td>
<td class="text-xs whitespace-nowrap"><span class="<?php echo $m[2]; ?> font-bold"><?php echo $m[0].' '.$m[1]; ?></span></td>
<td class="text-xs text-white/50"><?php echo htmlspecialchars($r['detail']); ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php if(empty($rows)): ?><div class="p-10 text-center text-white/30">Hali yozuv yo'q</div><?php endif; ?>
</div>
<?php if($pages>1): ?>
<div class="flex gap-1 flex-wrap mt-3">
<?php for($i=1;$i<=$pages;$i++): ?><a href="?page=<?php echo $i; ?>" class="px-3 py-2 rounded-lg text-xs font-bold <?php echo $i==$page?'bg-[#7c6cff] text-white':'bg-white/5 border border-white/10 text-white/60'; ?>"><?php echo $i; ?></a><?php endfor; ?>
</div>
<?php endif; ?>
<?php include 'layout_footer.php'; ?>
