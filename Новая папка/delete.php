<?php require 'config.php'; requireLogin();
$u=$_SESSION['user']; $isSuper=($u['role']=='super');
$id=intval($_GET['id'] ?? 0);
try{
 $s=db()->prepare("SELECT * FROM paid_participants WHERE id=?"); $s->execute([$id]); $r=$s->fetch();
 if($r){
  // Diller faqat OZINING va hali "kutilmoqda" holatidagi nomerini o'chira/bekor qila oladi.
  // Tasdiqlangan yoki boshqa dillerga tegishli nomerni faqat Bosh admin o'chira oladi.
  if($isSuper || ($r['dealer_id']==$u['id'] && $r['status']=='pending')){
   db()->prepare("DELETE FROM paid_participants WHERE id=?")->execute([$id]);
  }
 }
}catch(Exception $e){}
header("Location: participants.php");
