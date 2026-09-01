<?php include 'layout_header.php';
// Chiqindi qutisi: o'chirilgan (trashed=1) nomerlar. Diller o'zinikini, Bosh admin hammasini ko'radi.
$restrict=''; $rp=[];
if(!$isSuper){ $restrict=" AND p.dealer_id=?"; $rp[]=$u['id']; }

// Tiklash
if(isset($_GET['restore'])){
 $id=intval($_GET['restore']);
 try{
  $s=db()->prepare("SELECT * FROM paid_participants WHERE id=? AND trashed=1".$restrict); $s->execute(array_merge([$id],$rp)); $r=$s->fetch();
  if($r){ db()->prepare("UPDATE paid_participants SET trashed=0, trashed_at=NULL WHERE id=?")->execute([$id]); logActivity('restore',"Tiklandi: ".$r['name']." ".$r['pretty_phone']); }
 }catch(Exception $e){}
 header("Location: trash.php"); exit;
}
// Butunlay o'chirish (faqat Bosh admin)
if(isset($_GET['purge']) && $isSuper){
 $id=intval($_GET['purge']);
 try{ $s=db()->prepare("SELECT pretty_phone,name FROM paid_participants WHERE id=? AND trashed=1"); $s->execute([$id]); $r=$s->fetch();
  if($r){ db()->prepare("DELETE FROM paid_participants WHERE id=? AND trashed=1")->execute([$id]); logActivity('purge',"Butunlay o'chirildi: ".$r['name']." ".$r['pretty_phone']); }
 }catch(Exception $e){}
 header("Location: trash.php"); exit;
}
// Chiqindini bo'shatish (faqat Bosh admin)
if(isset($_GET['empty']) && $isSuper){
 try{ $n=db()->query("SELECT COUNT(*) FROM paid_participants WHERE trashed=1")->fetchColumn(); db()->exec("DELETE FROM paid_participants WHERE trashed=1"); logActivity('empty_trash',"$n ta butunlay o'chirildi"); }catch(Exception $e){}
 header("Location: trash.php"); exit;
}

try{ $st=db()->prepare("SELECT p.*, d.name as dealer_name FROM paid_participants p LEFT JOIN dealers d ON d.id=p.dealer_id WHERE p.trashed=1".$restrict." ORDER BY p.trashed_at DESC, p.id DESC"); $st->execute($rp); $rows=$st->fetchAll(); }catch(Exception $e){ $rows=[]; }
?>
<div class="flex flex-wrap justify-between items-center gap-2 mb-3">
 <h1 class="font-black text-xl flex items-center gap-2"><?php echo icon('trash','w-5 h-5'); ?> Chiqindi qutisi <span class="text-white/30 text-sm"><?php echo count($rows); ?> ta</span></h1>
 <?php if($isSuper && $rows): ?><a href="?empty=1" onclick="return confirm('Chiqindidagi HAMMA nomer butunlay o\'chiriladi. Davom etilsinmi?')" class="btn btn-danger btn-sm">🗑 Chiqindini bo'shatish</a><?php endif; ?>
</div>
<p class="text-white/30 text-xs -mt-1 mb-3">O'chirilgan nomerlar shu yerda saqlanadi. Tiklash mumkin. "Butunlay o'chirish" — qaytarib bo'lmaydi.</p>

<div class="card overflow-auto"><table class="w-full text-sm"><tr class="bg-black/50 text-white/30 text-xs"><th class="p-3 text-left">Diller</th><th>Ism</th><th>Nomer</th><th>Operator / Tarif</th><th>O'chirilgan sana</th><th></th></tr>
<?php foreach($rows as $r): ?>
<tr class="border-b border-white/5">
<td class="p-3 text-white/60 text-xs font-bold"><?php echo htmlspecialchars($r['dealer_name']); ?></td>
<td class="p-3"><b><?php echo htmlspecialchars($r['name']); ?></b></td>
<td class="font-mono text-xs"><?php echo htmlspecialchars($r['pretty_phone']); ?></td>
<td class="text-xs"><?php echo htmlspecialchars($r['operator_name']); ?> / <?php echo htmlspecialchars($r['tarif_name']); ?></td>
<td class="text-xs text-white/40"><?php echo $r['trashed_at'] ? date('d.m.Y H:i', strtotime($r['trashed_at'])) : '—'; ?></td>
<td class="whitespace-nowrap">
<a href="?restore=<?php echo $r['id']; ?>" class="btn btn-ghost btn-xs">↩ Tiklash</a>
<?php if($isSuper): ?><a href="?purge=<?php echo $r['id']; ?>" onclick="return confirm('Butunlay o\'chirilsinmi? Qaytarib bo\'lmaydi.')" class="btn btn-danger btn-xs">🗑</a><?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php if(empty($rows)): ?><div class="p-10 text-center text-white/30">Chiqindi qutisi bo'sh 🎉</div><?php endif; ?>
</div>
<?php include 'layout_footer.php'; ?>
