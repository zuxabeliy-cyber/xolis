<?php include 'layout_header.php'; if(!$isSuper) exit;
$msg='';
$logoDir = __DIR__.'/logos';
if(!is_dir($logoDir)) @mkdir($logoDir, 0775, true);

function opKey($name){
 $map = ['Beeline'=>'beeline','Ucell'=>'ucell','Uztelecom'=>'uztelecom','Mobiuz'=>'mobiuz','Humans'=>'humans','Jami'=>'jami','JAMI'=>'jami','TOTAL'=>'jami'];
 return $map[$name] ?? strtolower(preg_replace('/[^a-z0-9]/','',strtolower($name)));
}

if($_SERVER['REQUEST_METHOD']=='POST'){
 if(isset($_POST['new_op'])){
  $op=trim($_POST['new_op']);
  if($op!=''){ try{ db()->prepare("INSERT IGNORE INTO operators (name) VALUES (?)")->execute([$op]); $msg="✅ Operator qo'shildi: $op"; }catch(Exception $e){ $msg="Xato: ".$e->getMessage(); } }
 }
 if(isset($_POST['new_tarif_op'])){
  $opN=trim($_POST['new_tarif_op']); $tarN=trim($_POST['new_tarif_name']); $priceN=str_replace([' ',','],['',''],trim($_POST['new_tarif_price'] ?? '0')); $priceN=is_numeric($priceN)?floatval($priceN):0;
  if($opN && $tarN){ try{ db()->prepare("INSERT INTO tarifs (operator_name,name,price) VALUES (?,?,?) ON DUPLICATE KEY UPDATE price=VALUES(price)")->execute([$opN,$tarN,$priceN]); $msg="✅ Tarif qo'shildi: $opN - $tarN"; }catch(Exception $e){ $msg="Xato: ".$e->getMessage(); } }
 }
 if(isset($_POST['update_price_id'])){
  $pid=intval($_POST['update_price_id']); $priceN=str_replace([' ',','],['',''],trim($_POST['update_price_val'] ?? '0')); $priceN=is_numeric($priceN)?floatval($priceN):0;
  try{ db()->prepare("UPDATE tarifs SET price=? WHERE id=?")->execute([$priceN,$pid]); $msg="✅ Narx yangilandi"; }catch(Exception $e){ $msg="Xato: ".$e->getMessage(); }
 }
 if(isset($_POST['del_tarif'])){ try{ db()->prepare("DELETE FROM tarifs WHERE id=?")->execute([$_POST['del_tarif']]); $msg="Tarif o'chirildi"; }catch(Exception $e){} }
 if(isset($_POST['del_op'])){
  try{ $id=$_POST['del_op']; $n=db()->prepare("SELECT name FROM operators WHERE id=?"); $n->execute([$id]); $on=$n->fetchColumn(); if($on){ db()->prepare("DELETE FROM tarifs WHERE operator_name=?")->execute([$on]); db()->prepare("DELETE FROM operators WHERE id=?")->execute([$id]); $msg="Operator o'chirildi"; } }catch(Exception $e){ $msg=$e->getMessage(); }
 }
 // Logo yuklash
 if(isset($_POST['upload_logo_op']) && isset($_FILES['logo_file']) && $_FILES['logo_file']['error']===UPLOAD_ERR_OK){
  $opName = trim($_POST['upload_logo_op']);
  $key = opKey($opName);
  $ext = strtolower(pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION));
  $allowed = ['png','jpg','jpeg','webp','svg'];
  if(in_array($ext,$allowed,true) && $_FILES['logo_file']['size'] <= 4*1024*1024){
   // eski logolarni o'chirish
   foreach(['png','jpg','jpeg','webp','svg'] as $e){ $old=$logoDir.'/'.$key.'.'.$e; if(file_exists($old)) @unlink($old); }
   $dest = $logoDir.'/'.$key.'.'.$ext;
   if(move_uploaded_file($_FILES['logo_file']['tmp_name'], $dest)){
    $msg = "✅ Logo yangilandi: $opName uchun ".htmlspecialchars($key.'.'.$ext);
   } else $msg="Xato: faylni saqlab bo'lmadi";
  } else $msg="Xato: faqat png/jpg/webp/svg, 4MB gacha";
 }
 // JAMI logosi
 if(isset($_POST['upload_jami_logo']) && isset($_FILES['jami_logo_file']) && $_FILES['jami_logo_file']['error']===UPLOAD_ERR_OK){
  $ext = strtolower(pathinfo($_FILES['jami_logo_file']['name'], PATHINFO_EXTENSION));
  $allowed = ['png','jpg','jpeg','webp','svg'];
  if(in_array($ext,$allowed,true) && $_FILES['jami_logo_file']['size'] <= 4*1024*1024){
   foreach(['png','jpg','jpeg','webp','svg'] as $e){ $old=$logoDir.'/jami.'.$e; if(file_exists($old)) @unlink($old); }
   $dest = $logoDir.'/jami.'.$ext;
   if(move_uploaded_file($_FILES['jami_logo_file']['tmp_name'], $dest)){
    $msg = "✅ JAMI logosi yangilandi";
   } else $msg="Xato: faylni saqlab bo'lmadi";
  } else $msg="Xato: faqat png/jpg/webp/svg, 4MB gacha";
 }
 if(isset($_POST['delete_logo_op'])){
  $opName = trim($_POST['delete_logo_op']);
  $key = opKey($opName);
  $deleted=false;
  foreach(['png','jpg','jpeg','webp','svg'] as $e){ $p=$logoDir.'/'.$key.'.'.$e; if(file_exists($p)){ @unlink($p); $deleted=true; } }
  $msg = $deleted ? "🗑 Logo o'chirildi: $opName" : "Logo topilmadi";
 }
}
try{ $ops=db()->query("SELECT * FROM operators ORDER BY name")->fetchAll(); }catch(Exception $e){ $ops=[]; }
try{ $tarifs=db()->query("SELECT * FROM tarifs ORDER BY operator_name,name")->fetchAll(); }catch(Exception $e){ $tarifs=[]; }
$grouped=[]; foreach($tarifs as $t){ $grouped[$t['operator_name']][]=$t; }

function getLogoUrl($name){
 $map = ['Beeline'=>'beeline','Ucell'=>'ucell','Uztelecom'=>'uztelecom','Mobiuz'=>'mobiuz','Humans'=>'humans','Jami'=>'jami','JAMI'=>'jami'];
 $key = $map[$name] ?? strtolower(preg_replace('/[^a-z0-9]/','',strtolower($name)));
 foreach(['png','jpg','jpeg','webp','svg'] as $ext){
  $path = __DIR__.'/logos/'.$key.'.'.$ext;
  if(file_exists($path)) return 'logos/'.$key.'.'.$ext.'?v='.filemtime($path);
 }
 return null;
}
?>
<h1 class="font-black text-xl mb-3 flex items-center gap-2"><?php echo icon('radio','w-5 h-5'); ?> Operator & Tarif - Logolarni boshqarish</h1>
<?php if($msg): ?><div id="toast" class="fixed top-4 left-1/2 -translate-x-1/2 z-[999] max-w-sm w-[92%] shadow-2xl"><div class="<?php echo strpos($msg,'Xato')===0?'bg-red-500/10 border border-red-500/20 text-red-300':'bg-white/5 border border-[#7c6cff]/20 text-[#7c6cff]'; ?> p-3 rounded-xl text-sm"><?php echo htmlspecialchars($msg); ?></div></div>
<script>setTimeout(function(){ var t=document.getElementById('toast'); if(t){ t.style.transition='opacity .4s'; t.style.opacity='0'; setTimeout(function(){ t.remove(); },400); } },4000);</script><?php endif; ?>

<div class="card p-5 mb-4 border-[#7c6cff]/20">
<h3 class="font-bold mb-3">🌐 JAMI kartasi uchun rasm</h3>
<div class="flex items-center gap-4">
<div class="w-16 h-16 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center overflow-hidden">
<?php $jamiLogo=getLogoUrl('jami'); if($jamiLogo): ?><img src="<?php echo $jamiLogo; ?>" class="w-full h-full object-cover"><?php else: ?>🌐<?php endif; ?>
</div>
<div class="flex-1">
<form method="post" enctype="multipart/form-data" class="flex gap-2">
<input type="hidden" name="upload_jami_logo" value="1">
<input type="file" name="jami_logo_file" accept=".png,.jpg,.jpeg,.webp,.svg" required class="flex-1 p-2 rounded-xl bg-black/50 border border-white/10 text-white text-xs">
<button class="bg-[#7c6cff] text-white px-4 py-2 rounded-xl text-xs font-black">Yuklash</button>
</form>
<p class="text-[10px] text-white/30 mt-1">Baraban dagi JAMI kartasida ko'rinadi. Tavsiya: 256x256 PNG, shaffof fon.</p>
<?php if($jamiLogo): ?>
<form method="post" class="mt-2"><input type="hidden" name="delete_logo_op" value="jami"><button class="text-[10px] text-red-400">🗑 JAMI logosini o'chirish</button></form>
<?php endif; ?>
</div>
</div>
</div>

<div class="grid lg:grid-cols-2 gap-4">
<div class="card p-5"><h3 class="font-bold mb-3">Operatorlar (<?php echo count($ops); ?> ta) - Logoni bosib o'zgartiring</h3>
<div class="space-y-2 max-h-[700px] overflow-auto">
<?php if(!function_exists('opLogoSmall')){ function opLogoSmall($n){
 $map = ['Beeline'=>'beeline','Ucell'=>'ucell','Uztelecom'=>'uztelecom','Mobiuz'=>'mobiuz','Humans'=>'humans'];
 $key = $map[$n] ?? strtolower(preg_replace('/[^a-z0-9]/','',strtolower($n)));
 foreach(['png','jpg','jpeg','webp','svg'] as $ext){
  $path = __DIR__.'/logos/'.$key.'.'.$ext;
  if(file_exists($path)){ return '<img src="logos/'.$key.'.'.$ext.'?v='.filemtime($path).'" class="w-full h-full object-cover" alt="'.htmlspecialchars($n).'">'; }
 }
 $svgs=['Beeline'=>'<circle cx="24" cy="24" r="22" fill="#FFC900"/><text x="24" y="31" font-family="Arial" font-weight="900" font-size="20" fill="#1a1a1a" text-anchor="middle">B</text>','Ucell'=>'<circle cx="24" cy="24" r="22" fill="#F5821F"/><text x="24" y="31" font-family="Arial" font-weight="900" font-size="20" fill="#fff" text-anchor="middle">U</text>','Uztelecom'=>'<circle cx="24" cy="24" r="22" fill="#0072BC"/><text x="24" y="31" font-family="Arial" font-weight="900" font-size="20" fill="#fff" text-anchor="middle">T</text>','Mobiuz'=>'<circle cx="24" cy="24" r="22" fill="#8E44AD"/><text x="24" y="31" font-family="Arial" font-weight="900" font-size="20" fill="#fff" text-anchor="middle">M</text>','Humans'=>'<circle cx="24" cy="24" r="22" fill="#FF4C79"/><text x="24" y="31" font-family="Arial" font-weight="900" font-size="20" fill="#fff" text-anchor="middle">H</text>'];
 $inner = $svgs[$n] ?? '<circle cx="24" cy="24" r="22" fill="#333"/><text x="24" y="31" font-family="Arial" font-weight="900" font-size="18" fill="#fff" text-anchor="middle">'.mb_strtoupper(mb_substr($n,0,1)).'</text>';
 return '<svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">'.$inner.'</svg>';
} } ?>
<?php foreach($ops as $o): 
 $logoUrl = getLogoUrl($o['name']);
?>
<div class="bg-black/30 p-3 rounded-xl border border-white/5">
<div class="flex justify-between items-center">
<span class="font-bold text-sm flex items-center gap-2"><span class="w-10 h-10 rounded-full overflow-hidden inline-block bg-white/5 border border-white/10"><?php echo opLogoSmall($o['name']); ?></span><?php echo htmlspecialchars($o['name']); ?> <span class="text-white/30 text-xs">(<?php echo count($grouped[$o['name']] ?? []); ?> ta tarif)</span></span>
<form method="post" onsubmit="return confirm('Ochirish?')"><input type="hidden" name="del_op" value="<?php echo $o['id']; ?>"><button class="bg-red-500/10 text-red-400 px-3 py-1 rounded-lg text-xs border border-red-500/20">O'chirish</button></form>
</div>
<div class="mt-3 flex gap-2 items-center bg-[#16162a] p-2 rounded-xl border border-white/5">
<form method="post" enctype="multipart/form-data" class="flex gap-2 flex-1">
<input type="hidden" name="upload_logo_op" value="<?php echo htmlspecialchars($o['name']); ?>">
<input type="file" name="logo_file" accept=".png,.jpg,.jpeg,.webp,.svg" required class="flex-1 p-2 rounded-lg bg-black/50 border border-white/10 text-white text-[11px]">
<button class="bg-white text-black px-3 py-2 rounded-lg text-[11px] font-bold whitespace-nowrap">📤 Logo yuklash</button>
</form>
<?php if($logoUrl): ?>
<form method="post"><input type="hidden" name="delete_logo_op" value="<?php echo htmlspecialchars($o['name']); ?>"><button class="text-red-400 text-[10px] px-2">🗑</button></form>
<?php endif; ?>
</div>
<?php if($logoUrl): ?><p class="text-[9px] text-[#7c6cff] mt-1">✅ Logo mavjud: <?php echo $logoUrl; ?></p><?php else: ?><p class="text-[9px] text-white/30 mt-1">Logo yo'q - standart harf ko'rinadi</p><?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<form method="post" class="flex gap-2 mt-4"><input name="new_op" required placeholder="Yangi operator nomi" class="flex-1 p-3 rounded-xl bg-black/50 border border-white/10 text-white outline-none focus:border-[#7c6cff]"><button class="bg-white text-black px-6 rounded-xl font-bold">+ Qo'shish</button></form>
</div>

<div class="card p-5"><h3 class="font-bold mb-3">Tarif qo'shish - yo'qolmaydi</h3>
<form method="post" class="space-y-3 bg-black/20 p-4 rounded-xl border border-white/5">
<select name="new_tarif_op" required class="w-full p-3 rounded-xl bg-[#16162a] border border-white/10 text-white"><option value="">Operator tanla</option><?php foreach($ops as $o): ?><option value="<?php echo htmlspecialchars($o['name']); ?>"><?php echo htmlspecialchars($o['name']); ?></option><?php endforeach; ?></select>
<input name="new_tarif_name" required placeholder="Yangi tarif nomi, masalan: Yangi 100GB" class="w-full p-3 rounded-xl bg-[#16162a] border border-white/10 text-white outline-none focus:border-[#7c6cff]">
<input name="new_tarif_price" type="number" step="0.01" min="0" placeholder="Narxi (so'm), masalan: 15000" class="w-full p-3 rounded-xl bg-[#16162a] border border-white/10 text-white outline-none focus:border-[#7c6cff]">
<button class="w-full bg-[#7c6cff] text-white p-3 rounded-xl font-black">+ Tarif qo'shish</button>
</form>
<div class="mt-4 space-y-3 max-h-[600px] overflow-auto">
<?php foreach($grouped as $opN=>$list): ?>
<div class="bg-black/30 rounded-xl p-3 border border-white/5"><p class="font-bold text-[#7c6cff] text-sm mb-2"><?php echo htmlspecialchars($opN); ?> - <?php echo count($list); ?> ta</p>
<?php foreach($list as $t): ?><div class="flex justify-between items-center gap-2 bg-[#16162a] p-2 rounded-lg mb-1 text-sm border border-white/5">
<span class="flex-1"><?php echo htmlspecialchars($t['name']); ?></span>
<form method="post" class="flex gap-1 items-center" onsubmit="return confirm('Narx yangilansinmi?')">
<input type="hidden" name="update_price_id" value="<?php echo $t['id']; ?>">
<input name="update_price_val" type="number" step="0.01" min="0" value="<?php echo rtrim(rtrim(number_format($t['price'],2,'.',''),'0'),'.'); ?>" placeholder="Narx" class="w-24 p-1.5 rounded-lg bg-black/40 border border-white/10 text-white text-xs">
<button class="bg-[#7c6cff]/10 border border-[#7c6cff]/20 text-[#7c6cff] px-2 py-1 rounded-lg text-[10px] font-bold whitespace-nowrap">Saqlash</button>
</form>
<form method="post" onsubmit="return confirm('Ochirish?')"><input type="hidden" name="del_tarif" value="<?php echo $t['id']; ?>"><button class="text-red-400 text-xs px-2">x</button></form>
</div><?php endforeach; ?>
</div>
<?php endforeach; ?>
</div>
</div>
</div>
<?php include 'layout_footer.php'; ?>
