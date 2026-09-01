<?php require 'config.php'; requireLogin();
$u=$_SESSION['user']; $isSuper=($u['role']=='super');
$id=intval($_GET['id'] ?? 0);
try{
 $s=db()->prepare("SELECT * FROM paid_participants WHERE id=?"); $s->execute([$id]); $r=$s->fetch();
 if($r){
  // Diller faqat OZINING va hali "kutilmoqda" holatidagi nomerini o'chira/bekor qila oladi.
  // Tasdiqlangan yoki boshqa dillerga tegishli nomerni faqat Bosh admin o'chira oladi.
  if($isSuper || ($r['dealer_id']==$u['id'] && $r['status']=='pending')){
   db()->prepare("UPDATE paid_participants SET trashed=1, trashed_at=NOW() WHERE id=?")->execute([$id]);
   logActivity('trash', "Chiqindiga: ".($r['name']??'')." ".($r['pretty_phone']??''));
  }
 }
}catch(Exception $e){}
header("Location: ".( ($_GET['from']??'')==='trash' ? 'trash.php' : 'participants.php'));
