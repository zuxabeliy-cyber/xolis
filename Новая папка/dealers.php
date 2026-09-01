<?php include 'layout_header.php'; if(!$isSuper) exit;
if($_POST && isset($_POST['new_login'])){
 try{
  $newRole = ($_POST['new_role'] ?? 'diller')==='super' ? 'super' : 'diller';
  db()->prepare("INSERT INTO dealers (login,password,name,role) VALUES (?,?,?,?)")->execute([trim($_POST['new_login']),password_hash($_POST['new_pass'],PASSWORD_DEFAULT),trim($_POST['new_name']),$newRole]);
  header("Location: dealers.php?ok=1&role=".$newRole); exit;
 }catch(Exception $e){
  $code = stripos($e->getMessage(),'Duplicate')!==false ? 'dup' : 'err';
  header("Location: dealers.php?err=$code"); exit;
 }
}
if(isset($_POST['del_id'])){ try{ db()->prepare("DELETE FROM dealers WHERE id=? AND role!='super'")->execute([$_POST['del_id']]); }catch(Exception $e){} header("Location: dealers.php?del=1"); exit; }
if(isset($_POST['reset_id']) && isset($_POST['reset_pass']) && trim($_POST['reset_pass'])!==''){
 try{ db()->prepare("UPDATE dealers SET password=? WHERE id=?")->execute([password_hash(trim($_POST['reset_pass']),PASSWORD_DEFAULT),intval($_POST['reset_id'])]); }catch(Exception $e){}
 header("Location: dealers.php?reset=1"); exit;
}
// Diller uchun "nomer qo'shish" ruxsatini yoqish/o'chirish — faqat 1-Bosh admin (id=1) ko'radi va boshqaradi
if(isset($_POST['toggle_add_id']) && $u['id']==1){
 try{
  $newVal = intval($_POST['toggle_add_val'])==1 ? 1 : 0;
  db()->prepare("UPDATE dealers SET can_add=? WHERE id=? AND role='diller'")->execute([$newVal,intval($_POST['toggle_add_id'])]);
 }catch(Exception $e){}
 header("Location: dealers.php#access"); exit;
}
// Bosh admin dillerning (yoki o'zining) LOGIN'ini o'zgartirishi
if(isset($_POST['login_id']) && isset($_POST['new_login_val']) && trim($_POST['new_login_val'])!==''){
 try{
  db()->prepare("UPDATE dealers SET login=? WHERE id=?")->execute([trim($_POST['new_login_val']),intval($_POST['login_id'])]);
  header("Location: dealers.php?loginok=1"); exit;
 }catch(Exception $e){
  $code = stripos($e->getMessage(),'Duplicate')!==false ? 'dup' : 'err';
  header("Location: dealers.php?err=$code"); exit;
 }
}
try{ $all=db()->query("SELECT d.*, (SELECT COUNT(*) FROM paid_participants p WHERE p.dealer_id=d.id AND p.status='approved') cnt, (SELECT COUNT(*) FROM paid_participants p WHERE p.dealer_id=d.id AND p.status='pending') pcnt FROM dealers d ORDER BY cnt DESC")->fetchAll(); }catch(Exception $e){ $all=[]; }
$medals=['🥇','🥈','🥉']; $rank=0;
$toast='';
if(isset($_GET['ok'])) $toast='<div class="bg-white/5 border border-[#1fae76]/20 text-[#1fae76] p-3 rounded-xl text-sm">✅ '.(($_GET['role']??'')==='super' ? 'Yangi Bosh admin qo\'shildi' : 'Diller qo\'shildi').'</div>';
elseif(isset($_GET['del'])) $toast='<div class="bg-white/5 border border-white/10 p-3 rounded-xl text-sm">🗑 Diller o\'chirildi</div>';
elseif(isset($_GET['reset'])) $toast='<div class="bg-white/5 border border-[#1fae76]/20 text-[#1fae76] p-3 rounded-xl text-sm">✅ Parol yangilandi</div>';
elseif(isset($_GET['loginok'])) $toast='<div class="bg-white/5 border border-[#1fae76]/20 text-[#1fae76] p-3 rounded-xl text-sm">✅ Login yangilandi</div>';
elseif(isset($_GET['err'])){
 $toast = $_GET['err']=='dup'
  ? '<div class="bg-red-500/10 border border-red-500/20 text-red-300 p-3 rounded-xl text-sm">❌ Bu login band, boshqa login tanlang</div>'
  : '<div class="bg-red-500/10 border border-red-500/20 text-red-300 p-3 rounded-xl text-sm">Xatolik yuz berdi, qayta urinib ko\'ring</div>';
}
?>
<h1 class="font-black text-xl mb-3 flex items-center gap-2"><?php echo icon('users','w-5 h-5'); ?> Dillerlar va Adminlar</h1>
<div class="card p-4 mb-4"><form method="post" class="grid md:grid-cols-4 gap-2">
<input name="new_name" required placeholder="Ism" class="p-3 rounded-xl bg-black/50 border border-white/10 text-white">
<input name="new_login" required placeholder="Login" class="p-3 rounded-xl bg-black/50 border border-white/10 text-white">
<input name="new_pass" required placeholder="Parol" class="p-3 rounded-xl bg-black/50 border border-white/10 text-white">
<select name="new_role" class="p-3 rounded-xl bg-black/50 border border-white/10 text-white"><option value="diller">👤 Diller</option><option value="super">👑 Bosh admin</option></select>
<button class="md:col-span-4 bg-white text-black p-3 rounded-xl font-bold">Qo'shish</button></form>
<p class="text-[11px] text-white/30 mt-2">👑 "Bosh admin" tanlansa, yangi hisob to'liq admin huquqiga (tasdiqlash, sozlamalar, boshqa dillerlarni boshqarish) ega bo'ladi — faqat ishonchli odamlarga bering.</p>
</div>
<?php if($u['id']==1): ?>
<div id="access" class="card p-4 mb-4 border-[#1fae76]/20">
<h3 class="font-black text-sm mb-1">🔒 Nomer qo'shish ruxsati</h3>
<p class="text-[11px] text-white/30 mb-3">Diller uchun o'chirilsa, u "Bitta qo'shish" orqali yangi nomer yubora olmaydi. Faqat sizga (1-Bosh admin) ko'rinadi.</p>
<div class="space-y-2">
<?php foreach($all as $d): if($d['role']!='diller') continue; $on = intval($d['can_add'] ?? 1)===1; ?>
<div class="flex justify-between items-center bg-white/5 border border-white/10 p-3 rounded-xl">
<span class="text-sm font-bold"><?php echo htmlspecialchars($d['name']); ?></span>
<form method="post"><input type="hidden" name="toggle_add_id" value="<?php echo $d['id']; ?>"><input type="hidden" name="toggle_add_val" value="<?php echo $on ? 0 : 1; ?>">
<button type="submit" class="relative w-14 h-8 rounded-full transition <?php echo $on ? 'bg-[#1fae76]' : 'bg-white/10 border border-white/15'; ?>">
<span class="absolute top-1 <?php echo $on ? 'right-1' : 'left-1'; ?> w-6 h-6 bg-white rounded-full transition shadow"></span>
</button></form>
</div>
<?php endforeach; ?>
</div>
</div>
<?php endif; ?>
<div class="grid md:grid-cols-2 gap-2"><?php foreach($all as $d): $rank++; ?><div class="card p-3 card-hover <?php echo $d['role']=='super'?'border-[#1fae76]/25':''; ?>"><div class="flex justify-between items-start"><div><b><?php echo $d['role']=='super' ? '👑 ' : (isset($medals[$rank-1]) ? $medals[$rank-1].' ' : ''); ?><?php echo htmlspecialchars($d['name']); ?></b> <?php if($d['role']=='super'): ?><span class="text-[9px] bg-[#1fae76]/15 text-[#1fae76] px-2 py-0.5 rounded-full font-black align-middle">BOSH ADMIN</span><?php endif; ?><br><span class="text-xs text-white/40"><?php echo htmlspecialchars($d['login']); ?> - <?php echo $d['cnt']; ?> ta tasdiqlangan<?php if($d['pcnt']>0): ?> • <span class="text-[#1fae76]">⏳ <?php echo $d['pcnt']; ?> kutilmoqda</span><?php endif; ?></span></div><?php if($d['role']!='super'): ?><form method="post" onsubmit="return confirm('Ochirish?')"><input type="hidden" name="del_id" value="<?php echo $d['id']; ?>"><button class="text-red-400">🗑</button></form><?php endif; ?></div>
<form method="post" class="flex gap-2 mt-2" onsubmit="return confirm('Bu diller uchun yangi parol o\'rnatilsinmi?')"><input type="hidden" name="reset_id" value="<?php echo $d['id']; ?>"><input name="reset_pass" placeholder="Yangi parol" class="flex-1 p-2 rounded-lg bg-black/40 border border-white/10 text-white text-xs"><button class="bg-white/10 border border-white/10 px-3 rounded-lg text-xs whitespace-nowrap">Parolni almashtirish</button></form>
<form method="post" class="flex gap-2 mt-2" onsubmit="return confirm('Login shu qiymatga almashtirilsinmi?')"><input type="hidden" name="login_id" value="<?php echo $d['id']; ?>"><input name="new_login_val" placeholder="Yangi login" value="<?php echo htmlspecialchars($d['login']); ?>" class="flex-1 p-2 rounded-lg bg-black/40 border border-white/10 text-white text-xs"><button class="bg-[#1fae76]/10 border border-[#1fae76]/20 text-[#1fae76] px-3 rounded-lg text-xs whitespace-nowrap">Loginni almashtirish</button></form>
</div><?php endforeach; ?></div>
<?php if($toast): ?><div id="toast" class="fixed top-4 left-1/2 -translate-x-1/2 z-[999] max-w-sm w-[92%] shadow-2xl"><?php echo $toast; ?></div>
<script>setTimeout(function(){ var t=document.getElementById('toast'); if(t){ t.style.transition='opacity .4s'; t.style.opacity='0'; setTimeout(function(){ t.remove(); },400); } },4000);</script><?php endif; ?>
<?php include 'layout_footer.php'; ?>
