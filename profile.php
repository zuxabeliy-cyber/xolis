<?php include 'layout_header.php'; $msg='';
if($_POST && isset($_POST['new_pass'])){
 $np=trim($_POST['new_pass']);
 if($np){ try{ db()->prepare("UPDATE dealers SET password=? WHERE id=?")->execute([password_hash($np,PASSWORD_DEFAULT),$u['id']]); $msg="<div class='card p-3 mb-3'>✅ Parol o'zgardi</div>"; }catch(Exception $e){} }
}
if($_POST && isset($_POST['new_login']) && trim($_POST['new_login'])!==''){
 try{
  db()->prepare("UPDATE dealers SET login=? WHERE id=?")->execute([trim($_POST['new_login']),$u['id']]);
  $_SESSION['user']['login']=trim($_POST['new_login']);
  $msg="<div class='card p-3 mb-3'>✅ Login o'zgardi</div>";
 }catch(Exception $e){ $msg="<div class='card p-3 mb-3 text-red-300'>Bu login band, boshqasini tanlang</div>"; }
}
?>
<h1 class="font-black text-xl mb-3 flex items-center gap-2"><?php echo icon('user','w-5 h-5'); ?> Profil</h1><div class="card p-5 max-w-md"><?php echo $msg; ?><div class="flex items-center gap-3 mb-4"><img src="logo.png" class="w-12 h-12 object-contain"><div><p class="font-black"><?php echo htmlspecialchars($u['name']); ?></p><p class="text-xs text-white/40"><?php echo $isSuper?'BOSH ADMIN':'DILLER: '.htmlspecialchars($u['name']); ?></p></div></div>
<form method="post" class="space-y-3 mb-3"><label class="text-xs text-white/40">Loginni o'zgartirish</label><div class="flex gap-2"><input name="new_login" placeholder="Yangi login" value="<?php echo htmlspecialchars($u['login'] ?? ''); ?>" class="flex-1 p-3 rounded-xl bg-black/50 border border-white/10 text-white"><button class="bg-white/10 border border-white/10 px-5 rounded-xl font-bold">Saqlash</button></div></form>
<form method="post" class="space-y-3"><label class="text-xs text-white/40">Yangi parol</label><input name="new_pass" type="password" placeholder="Yangi parol" class="w-full p-3 rounded-xl bg-black/50 border border-white/10 text-white"><button class="bg-white text-black px-6 py-3 rounded-xl font-bold">O'zgartirish</button></form></div><?php include 'layout_footer.php'; ?>