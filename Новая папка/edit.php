<?php include 'layout_header.php';
$id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
try{ $s=db()->prepare("SELECT * FROM paid_participants WHERE id=?"); $s->execute([$id]); $r=$s->fetch(); }catch(Exception $e){ $r=null; }
if(!$r){ header("Location: participants.php"); exit; }
if(!$isSuper && $r['dealer_id']!=$u['id']){ header("Location: participants.php"); exit; }
try{ $ops=db()->query("SELECT name FROM operators ORDER BY name")->fetchAll(); }catch(Exception $e){ $ops=[]; }
try{ $ta=db()->query("SELECT operator_name, name FROM tarifs")->fetchAll(); }catch(Exception $e){ $ta=[]; }
$tm=[]; foreach($ta as $t){ $tm[$t['operator_name']][]=$t['name']; }
// Bu yozuvning operatori/tarifi keyinchalik o'chirilgan bo'lishi mumkin - ro'yxatda yo'q bo'lsa ham saqlanib qolsin
$opNames = array_column($ops,'name');
if($r['operator_name'] !== '' && !in_array($r['operator_name'],$opNames,true)){ $ops[] = ['name'=>$r['operator_name']]; }
if($r['operator_name'] !== '' && $r['tarif_name'] !== '' && !in_array($r['tarif_name'], $tm[$r['operator_name']] ?? [], true)){ $tm[$r['operator_name']][] = $r['tarif_name']; }
$msg='';
if($_POST && isset($_POST['save'])){
 $name=trim($_POST['name']); if($name=='') $name='Nomalum';
 $ph=preg_replace('/\D/','',$_POST['phone']); if(strlen($ph)==9) $ph='998'.$ph; $pretty=prettyUz($ph);
 $op=$_POST['operator']; $tar=$_POST['tarif']; $is_paid=intval($_POST['is_paid']);
 $promo = isset($_POST['promo_1_1']) ? 2 : 1;
 $cdate=trim($_POST['created_date'] ?? '');
 try{
  $chk=db()->prepare("SELECT id FROM paid_participants WHERE phone=? AND id!=?"); $chk->execute([$ph,$id]);
  if($chk->fetch()){ $msg='<div class="bg-red-500/10 border border-red-500/20 text-red-300 p-3 rounded-xl mb-3">❌ Bu nomer boshqa yozuvda bor!</div>'; }
  else{
   if($cdate!=='' && preg_match('/^\d{4}-\d{2}-\d{2}$/',$cdate)){
    $time=date('H:i:s', strtotime($r['created_at']));
    $newDate=$cdate.' '.$time;
    db()->prepare("UPDATE paid_participants SET name=?,phone=?,pretty_phone=?,operator_name=?,tarif_name=?,is_paid=?,promo_count=?,created_at=? WHERE id=?")->execute([$name,$ph,$pretty,$op,$tar,$is_paid,$promo,$newDate,$id]);
   }else{
    db()->prepare("UPDATE paid_participants SET name=?,phone=?,pretty_phone=?,operator_name=?,tarif_name=?,is_paid=?,promo_count=? WHERE id=?")->execute([$name,$ph,$pretty,$op,$tar,$is_paid,$promo,$id]);
   }
   header("Location: participants.php"); exit;
  }
 }catch(Exception $e){ $msg='<div class="bg-red-500/10 border border-red-500/20 text-red-300 p-3 rounded-xl mb-3">Xatolik yuz berdi, qayta urinib ko\'ring</div>'; }
 $r['name']=$name; $r['pretty_phone']=$pretty; $r['operator_name']=$op; $r['tarif_name']=$tar; $r['is_paid']=$is_paid; $r['promo_count']=$promo;
}
?>
<h1 class="font-black text-xl mb-4 flex items-center gap-2"><?php echo icon('edit','w-5 h-5'); ?> Tahrirlash</h1><?php echo $msg; ?>
<div class="card p-6 max-w-lg"><form method="post" class="space-y-3">
<input type="hidden" name="id" value="<?php echo $r['id']; ?>">
<input name="name" value="<?php echo htmlspecialchars($r['name']); ?>" placeholder="Ism" class="w-full p-4 rounded-xl bg-black/50 border border-white/10 text-white outline-none">
<input id="ph" name="phone" required value="<?php echo htmlspecialchars($r['pretty_phone']); ?>" class="w-full p-4 rounded-xl bg-black/50 border border-white/10 font-mono text-lg text-white outline-none">
<select id="op" name="operator" class="w-full p-4 rounded-xl bg-black/50 border border-white/10 text-white"><?php foreach($ops as $o): ?><option <?php echo $o['name']==$r['operator_name']?'selected':''; ?>><?php echo htmlspecialchars($o['name']); ?></option><?php endforeach; ?></select>
<select id="tar" name="tarif" class="w-full p-4 rounded-xl bg-black/50 border border-white/10 text-white"></select>
<div class="grid grid-cols-2 gap-2" id="paidToggle">
<label class="paid-opt p-3 rounded-xl text-sm text-center cursor-pointer transition <?php echo $r['is_paid']?'bg-white text-black font-bold':'bg-white/5 border border-white/10'; ?>"><input type="radio" name="is_paid" value="1" <?php echo $r['is_paid']?'checked':''; ?> class="hidden" onchange="updPaidUI()"> O'YINDA</label>
<label class="paid-opt p-3 rounded-xl text-sm text-center cursor-pointer transition <?php echo !$r['is_paid']?'bg-white text-black font-bold':'bg-white/5 border border-white/10'; ?>"><input type="radio" name="is_paid" value="0" <?php echo !$r['is_paid']?'checked':''; ?> class="hidden" onchange="updPaidUI()"> BAZADA</label>
</div>
<label class="flex items-center gap-2 bg-[#1fae76]/5 border border-[#1fae76]/20 p-3 rounded-xl text-sm cursor-pointer"><input type="checkbox" name="promo_1_1" value="1" <?php echo intval($r['promo_count'])==2?'checked':''; ?> class="w-5 h-5 accent-[#1fae76]"><span>🎁 1+1 Aksiya — bu nomer <b>2 ta</b> bo'lib hisoblansin</span></label>
<div><label class="text-xs text-white/40">Sana (nomer o'sha kuni qo'shilishi kerak bo'lsa shu yerdan to'g'irlang)</label><input type="date" name="created_date" value="<?php echo date('Y-m-d', strtotime($r['created_at'])); ?>" class="w-full mt-1 p-3 rounded-xl bg-black/50 border border-white/10 text-white outline-none"></div>
<div class="flex gap-2 pt-2"><a href="participants.php" class="flex-1 text-center bg-white/5 border border-white/10 p-4 rounded-xl font-bold">Bekor qilish</a><button name="save" value="1" class="flex-1 bg-white text-black p-4 rounded-xl font-black">SAQLASH</button></div>
</form></div>
<script>
const tm=<?php echo json_encode($tm, JSON_UNESCAPED_UNICODE); ?>; const curTar=<?php echo json_encode($r['tarif_name']); ?>;
function upd(){ const o=document.getElementById('op').value; const s=document.getElementById('tar'); s.innerHTML=''; (tm[o]||[]).forEach(t=>{ let e=document.createElement('option'); e.value=t; e.textContent=t; if(t===curTar) e.selected=true; s.appendChild(e); }); }
document.getElementById('op').addEventListener('change',upd); upd();
const ph=document.getElementById('ph'); ph.addEventListener('input', e=>{ let v=e.target.value.replace(/\D/g,''); if(v.startsWith('998')) v=v.substring(3); if(v.length>9) v=v.substring(0,9); let f='+998'; if(v.length>0) f+=' '+v.substring(0,2); if(v.length>2) f+=' '+v.substring(2,5); if(v.length>5) f+=' '+v.substring(5,7); if(v.length>7) f+=' '+v.substring(7,9); e.target.value=f; });
function updPaidUI(){ document.querySelectorAll('#paidToggle .paid-opt').forEach(function(l){ const r=l.querySelector('input'); if(r.checked){ l.classList.add('bg-white','text-black','font-bold'); l.classList.remove('bg-white/5','border','border-white/10'); } else { l.classList.remove('bg-white','text-black','font-bold'); l.classList.add('bg-white/5','border','border-white/10'); } }); }
</script>
<?php include 'layout_footer.php'; ?>