<?php include 'layout_header.php';
try{ $ops=db()->query("SELECT name FROM operators ORDER BY name")->fetchAll(); }catch(Exception $e){ $ops=[]; }
try{ $ta=db()->query("SELECT operator_name, name FROM tarifs")->fetchAll(); }catch(Exception $e){ $ta=[]; }
$tm=[]; foreach($ta as $t){ $tm[$t['operator_name']][]=$t['name']; }
try{ $dils=db()->query("SELECT * FROM dealers WHERE role='diller'")->fetchAll(); }catch(Exception $e){ $dils=[]; }
// Har bir operator uchun eng ko'p ishlatilgan tarifni aniqlash (nomer yozganda avto tanlanadi)
try{ $muRows=db()->query("SELECT operator_name, tarif_name, COUNT(*) c FROM paid_participants WHERE status='approved' GROUP BY operator_name, tarif_name ORDER BY c DESC")->fetchAll(); }catch(Exception $e){ $muRows=[]; }
$mostUsed=[]; foreach($muRows as $r){ if(!isset($mostUsed[$r['operator_name']])) $mostUsed[$r['operator_name']]=$r['tarif_name']; }
$msg='';
if($_POST){
 $did=$isSuper?intval($_POST['dealer_id']):$u['id']; if($did==0) $did=$u['id'];
 $name=trim($_POST['name']); if($name=='') $name='Nomalum';
 $ph=preg_replace('/\D/','',$_POST['phone']); if(strlen($ph)==9) $ph='998'.$ph; $pretty=prettyUz($ph);
 $op=$_POST['operator']; $tar=$_POST['tarif']; $is_paid=intval($_POST['is_paid']);
 $promo = isset($_POST['promo_1_1']) ? 2 : 1;
 $cdate=trim($_POST['created_date'] ?? '');
 $createdAt = ($cdate!=='' && preg_match('/^\d{4}-\d{2}-\d{2}$/',$cdate)) ? ($cdate.' '.date('H:i:s')) : date('Y-m-d H:i:s');
 if(!$isSuper && !dealerCanAdd($u['id'])){
  $msg='<div class="bg-red-500/10 border border-red-500/20 text-red-300 p-3 rounded-xl">⛔ Sizga nomer qo\'shish vaqtincha yopilgan. Bosh admin bilan bog\'laning.</div>';
 } elseif(!$isSuper && !checkDailyLimit($did)){
  $msg='<div class="bg-red-500/10 border border-red-500/20 text-red-300 p-3 rounded-xl">⛔ Bugungi qo\'shish limitingiz tugadi. Ertaga qayta urinib ko\'ring.</div>';
 } else {
 try{
  $chk=db()->prepare("SELECT id FROM paid_participants WHERE phone=?"); $chk->execute([$ph]);
  if($chk->fetch()){
   logDuplicateAttempt($ph,$pretty,$did,$name,$op,$tar);
   $msg='<div class="bg-red-500/10 border border-red-500/20 text-red-300 p-3 rounded-xl">Bu nomer bor!</div>';
  }
  else{
   if($isSuper){
    // Bosh admin o'zi qo'shsa - to'g'ridan bazaga tushadi, sozlamaga qarab shu zahoti kanalga ketishi mumkin
    db()->prepare("INSERT INTO paid_participants (phone,pretty_phone,name,operator_name,tarif_name,is_paid,dealer_id,status,approved_by,approved_at,created_at,promo_count) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")->execute([$ph,$pretty,$name,$op,$tar,$is_paid,$did,'approved',$u['id'],date('Y-m-d H:i:s'),$createdAt,$promo]);
    if(shouldSendToChannel($is_paid)){
     try{ $s=db()->prepare("SELECT name FROM dealers WHERE id=?"); $s->execute([$did]); $dname=$s->fetchColumn()?:$u['name']; }catch(Exception $e){ $dname=$u['name']; }
     $tpl=getSetting('template'); if(!$tpl) $tpl="1. Diller: {diller}\n2. Ism: {ism}\n3. Nomer: {nomer}\n4. Operator: {operator}\n5. Tarif: {tarif}";
     $txt=str_replace(['{diller}','{ism}','{nomer}','{operator}','{tarif}'],[$dname,$name,$pretty,$op,$tar],$tpl);
     sendToChannel($txt);
     $msg='<div class="bg-white/5 border border-white/10 p-3 rounded-xl text-sm">✅ Qo\'shildi va kanalga ketdi (Sozlamadagi shablon bo\'yicha)'.($promo==2?' • 🎁 1+1 (2 ta hisoblandi)':'').'</div>';
    } else $msg='<div class="bg-white/5 p-3 rounded-xl text-sm">✅ Bazaga saqlandi'.($promo==2?' • 🎁 1+1 (2 ta hisoblandi)':'').'</div>';
   } else {
    // Diller qo'shsa - endi to'g'ridan-to'g'ri tasdiqlangan holatda saqlanadi va sozlamaga qarab kanalga ham ketadi
    db()->prepare("INSERT INTO paid_participants (phone,pretty_phone,name,operator_name,tarif_name,is_paid,dealer_id,status,approved_by,approved_at,created_at,promo_count) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")->execute([$ph,$pretty,$name,$op,$tar,$is_paid,$did,'approved',$did,date('Y-m-d H:i:s'),$createdAt,$promo]);
    if(shouldSendToChannel($is_paid)){
     $tpl=getSetting('template'); if(!$tpl) $tpl="1. Diller: {diller}\n2. Ism: {ism}\n3. Nomer: {nomer}\n4. Operator: {operator}\n5. Tarif: {tarif}";
     $txt=str_replace(['{diller}','{ism}','{nomer}','{operator}','{tarif}'],[$u['name'],$name,$pretty,$op,$tar],$tpl);
     sendToChannel($txt);
     $msg='<div class="bg-white/5 border border-[#7c6cff]/20 text-[#7c6cff] p-3 rounded-xl text-sm">✅ Qo\'shildi va kanalga ketdi'.($promo==2?' • 🎁 1+1 (2 ta hisoblandi)':'').'</div>';
    } else {
     $msg='<div class="bg-white/5 border border-[#7c6cff]/20 text-[#7c6cff] p-3 rounded-xl text-sm">✅ Qo\'shildi'.($promo==2?' • 🎁 1+1 (2 ta hisoblandi)':'').'</div>';
    }
   }
  }
 }catch(Exception $e){
  if(stripos($e->getMessage(),'Duplicate')!==false) $msg='<div class="bg-red-500/10 border border-red-500/20 text-red-300 p-3 rounded-xl">❌ Bu nomer allaqachon ro\'yxatda bor!</div>';
  else $msg='<div class="bg-red-500/10 border border-red-500/20 text-red-300 p-3 rounded-xl">Xatolik yuz berdi, qayta urinib ko\'ring</div>';
 }
 }
}
?>
<h1 class="font-black text-xl mb-4 flex items-center gap-2"><?php echo icon('plus','w-5 h-5'); ?> Qo'shish</h1>
<?php if(!$isSuper): ?><p class="text-white/30 text-xs -mt-3 mb-4">Nomer qo'shilishi bilan darhol ro'yxatga tushadi va sozlamaga qarab kanalga ham ketadi.</p><?php endif; ?>
<?php echo $msg; ?>
<?php if(!$isSuper && !dealerCanAdd($u['id'])): ?>
<div class="card p-6 max-w-lg text-center border-red-500/20"><p class="text-3xl mb-2">⛔</p><p class="font-bold text-red-300">Sizga nomer qo'shish vaqtincha yopilgan</p><p class="text-white/30 text-xs mt-2">Bosh admin bilan bog'laning</p></div>
<?php else: ?>
<div class="card p-6 max-w-lg card-hover">
<form method="post" class="space-y-3">
<input name="name" placeholder="Ism" class="w-full p-4 rounded-xl bg-black/50 border border-white/10 text-white outline-none">
<input id="ph" name="phone" required placeholder="+998 93 487 56 23" class="w-full p-4 rounded-xl bg-black/50 border border-white/10 font-mono text-lg text-white outline-none">
<?php if($isSuper): ?><select name="dealer_id" required class="w-full p-4 rounded-xl bg-black/50 border border-white/10 text-white"><option value="">Diller tanla</option><?php foreach($dils as $d): ?><option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option><?php endforeach; ?></select><?php endif; ?>
<select id="op" name="operator" class="w-full p-4 rounded-xl bg-black/50 border border-white/10 text-white"><?php foreach($ops as $o): ?><option><?php echo $o['name']; ?></option><?php endforeach; ?></select>
<select id="tar" name="tarif" class="w-full p-4 rounded-xl bg-black/50 border border-white/10 text-white"></select>
<div class="grid grid-cols-2 gap-2" id="paidToggle"><label class="paid-opt bg-white text-black p-3 rounded-xl text-sm font-bold text-center cursor-pointer transition"><input type="radio" name="is_paid" value="0" checked class="hidden" onchange="updPaidUI()"> BAZAGA</label><label class="paid-opt bg-white/5 border border-white/10 p-3 rounded-xl text-sm text-center cursor-pointer transition"><input type="radio" name="is_paid" value="1" class="hidden" onchange="updPaidUI()"> O'YINGA</label></div>
<label class="flex items-center gap-2 bg-[#7c6cff]/5 border border-[#7c6cff]/20 p-3 rounded-xl text-sm cursor-pointer"><input type="checkbox" name="promo_1_1" value="1" class="w-5 h-5 accent-[#7c6cff]"><span>🎁 1+1 Aksiya — bu nomer <b>2 ta</b> bo'lib bazaga hisoblansin</span></label>
<div><label class="text-xs text-white/40">Sana (ixtiyoriy — bo'sh qoldirsangiz bugungi kun bilan qo'shiladi, kechroq kirgan nomerni o'sha kuniga yozmoqchi bo'lsangiz shu yerdan tanlang)</label><input type="date" name="created_date" max="<?php echo date('Y-m-d'); ?>" class="w-full mt-1 p-3 rounded-xl bg-black/50 border border-white/10 text-white outline-none"></div>
<button class="btn btn-primary btn-glow w-full py-4 text-base">✅ QO'SHISH</button></form></div>
<?php endif; ?>
<?php if($msg): ?><div id="toast" class="fixed top-4 left-1/2 -translate-x-1/2 z-[999] max-w-sm w-[92%] shadow-2xl"><?php echo $msg; ?></div>
<script>setTimeout(function(){ var t=document.getElementById('toast'); if(t){ t.style.transition='opacity .4s'; t.style.opacity='0'; setTimeout(function(){ t.remove(); },400); } },4500);</script><?php endif; ?>
<?php if($isSuper || dealerCanAdd($u['id'])): ?>
<script>
const tm=<?php echo json_encode($tm, JSON_UNESCAPED_UNICODE); ?>;
const mostUsed=<?php echo json_encode($mostUsed, JSON_UNESCAPED_UNICODE); ?>;
function upd(){ const o=document.getElementById('op').value; const s=document.getElementById('tar'); s.innerHTML=''; (tm[o]||[]).forEach(t=>{ let e=document.createElement('option'); e.value=t; e.textContent=t; s.appendChild(e); }); if(mostUsed[o] && [...s.options].some(op=>op.value===mostUsed[o])){ s.value=mostUsed[o]; } }
document.getElementById('op').addEventListener('change',upd); upd();
// Nomer kodi bo'yicha operatorni avtomatik aniqlash
const codeMap={'90':'Beeline','91':'Beeline','92':'Beeline','93':'Ucell','94':'Ucell','50':'Ucell','95':'Uztelecom','99':'Uztelecom','70':'Uztelecom','77':'Uztelecom','97':'Mobiuz','88':'Mobiuz','87':'Mobiuz','33':'Humans'};
function autoDetectOperator(v){
 if(v.length<2) return;
 const code=v.substring(0,2); const opName=codeMap[code]; if(!opName) return;
 const opSel=document.getElementById('op');
 for(let i=0;i<opSel.options.length;i++){ if(opSel.options[i].value===opName){ if(opSel.selectedIndex!==i){ opSel.selectedIndex=i; upd(); } break; } }
}
const ph=document.getElementById('ph'); ph.addEventListener('input', e=>{ let v=e.target.value.replace(/\D/g,''); if(v.startsWith('998')) v=v.substring(3); if(v.length>9) v=v.substring(0,9); let f='+998'; if(v.length>0) f+=' '+v.substring(0,2); if(v.length>2) f+=' '+v.substring(2,5); if(v.length>5) f+=' '+v.substring(5,7); if(v.length>7) f+=' '+v.substring(7,9); e.target.value=f; autoDetectOperator(v); });
function updPaidUI(){ document.querySelectorAll('#paidToggle .paid-opt').forEach(function(l){ const r=l.querySelector('input'); if(r.checked){ l.classList.add('bg-white','text-black','font-bold'); l.classList.remove('bg-white/5','border','border-white/10'); } else { l.classList.remove('bg-white','text-black','font-bold'); l.classList.add('bg-white/5','border','border-white/10'); } }); }
updPaidUI();
</script>
<?php endif; ?>
<?php include 'layout_footer.php'; ?>