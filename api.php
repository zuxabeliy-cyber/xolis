<?php require 'config.php'; require_once 'stats_helper.php'; $a=$_GET['action']??'';

if($a=='export_csv'){
 try{ $rows=db()->query("SELECT p.*, d.name as dealer_name FROM paid_participants p LEFT JOIN dealers d ON d.id=p.dealer_id ORDER BY p.id DESC")->fetchAll(); }catch(Exception $e){ $rows=[]; }
 header('Content-Type: text/csv'); header('Content-Disposition: attachment; filename=paynet.csv');
 $out=fopen('php://output','w'); fputcsv($out,['Diller','Ism','Nomer','Operator','Tarif','Holat','Sana']);
 foreach($rows as $r) fputcsv($out,[$r['dealer_name'],$r['name'],$r['pretty_phone'],$r['operator_name'],$r['tarif_name'],$r['status'],$r['created_at']]);
 exit;
}

if($a=='pending_count'){
 if(!isLogged() || !isSuper()){ header('Content-Type: application/json'); echo json_encode(['count'=>0]); exit; }
 header('Content-Type: application/json');
 echo json_encode(['count'=>pendingCount()]); exit;
}

if($a=='export_stats_csv'){
 if(!isLogged() || !isSuper()){ exit; }
 $params = statsFilters();
 if(!empty($_GET['operator'])) $params['operator'] = $_GET['operator'];
 if(!empty($_GET['tarif'])) $params['tarif'] = $_GET['tarif'];
 $rows = getApprovedRows($params, 100000);
 header('Content-Type: text/csv'); header('Content-Disposition: attachment; filename=statistika.csv');
 $out=fopen('php://output','w'); fputcsv($out,['Diller','Ism','Nomer','Operator','Tarif','Turi','Holat','Sana']);
 foreach($rows as $r) fputcsv($out,[$r['dealer_name'],$r['name'],$r['pretty_phone'],$r['operator_name'],$r['tarif_name'],$r['is_paid']?"O'YINDA":'BAZADA',$r['status'],$r['created_at']]);
 exit;
}

if($a=='toggle_paid'){
 header('Content-Type: application/json');
 if(!isLogged() || !isSuper()){ echo json_encode(['ok'=>false]); exit; }
 $id=intval($_GET['id']??0); $val=intval($_GET['val']??0);
 if($id<=0){ echo json_encode(['ok'=>false]); exit; }
 try{
  $s=db()->prepare("SELECT * FROM paid_participants WHERE id=?"); $s->execute([$id]); $row=$s->fetch();
  if(!$row){ echo json_encode(['ok'=>false]); exit; }
  db()->prepare("UPDATE paid_participants SET is_paid=? WHERE id=?")->execute([$val,$id]);
  if($row['status']=='approved' && shouldSendToChannel($val)){
   try{ $dn=db()->prepare("SELECT name FROM dealers WHERE id=?"); $dn->execute([$row['dealer_id']]); $dname=$dn->fetchColumn()?:''; }catch(Exception $e){ $dname=''; }
   $tpl=getSetting('template'); if(!$tpl) $tpl="1. Diller: {diller}\n2. Ism: {ism}\n3. Nomer: {nomer}\n4. Operator: {operator}\n5. Tarif: {tarif}";
   $txt=str_replace(['{diller}','{ism}','{nomer}','{operator}','{tarif}'],[$dname,$row['name'],$row['pretty_phone'],$row['operator_name'],$row['tarif_name']],$tpl);
   sendToChannel($txt);
  }
  logActivity('toggle', ($row['name']??'').' '.($row['pretty_phone']??'').' → '.($val?"O'YINGA":'BAZAGA'));
  echo json_encode(['ok'=>true,'is_paid'=>$val]);
 }catch(Exception $e){ echo json_encode(['ok'=>false]); }
 exit;
}

if($a=='today_count'){
 header('Content-Type: application/json');
 if(!isLogged()){ echo json_encode(['count'=>0]); exit; }
 try{
  $did = intval($_GET['dealer_id'] ?? 0);
  if(!isSuper()) $did = $_SESSION['user']['id'];
  $w=""; $p=[];
  if($did){ $w=" AND dealer_id=?"; $p[]=$did; }
  $s=db()->prepare("SELECT COALESCE(SUM(promo_count),0) FROM paid_participants WHERE status='approved' AND DATE(created_at)=CURDATE() $w");
  $s->execute($p);
  echo json_encode(['count'=>(int)$s->fetchColumn()]);
 }catch(Exception $e){ echo json_encode(['count'=>0]); }
 exit;
}

// Operator/JAMI logosini yuklash (faqat Bosh admin)
if($a=='upload_logo'){
 header('Content-Type: application/json');
 if(!isLogged() || !isSuper()){ echo json_encode(['ok'=>false,'msg'=>"Ruxsat yo'q"]); exit; }
 $op = trim($_POST['op'] ?? '');
 $allowed = ['Beeline','Ucell','Uztelecom','Mobiuz','Humans','jami'];
 if(!in_array($op, $allowed, true)){ echo json_encode(['ok'=>false,'msg'=>"Noto'g'ri operator"]); exit; }
 if(empty($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK){
  echo json_encode(['ok'=>false,'msg'=>'Fayl yuklash xatosi: '.($_FILES['logo']['error']??'fayl yo\'q')]); exit;
 }
 $file = $_FILES['logo'];
 $mimeMap = ['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp','image/svg+xml'=>'svg'];
 // finfo orqali haqiqiy MIME tekshirish
 $finfo = finfo_open(FILEINFO_MIME_TYPE);
 $realMime = finfo_file($finfo, $file['tmp_name']);
 finfo_close($finfo);
 if(!isset($mimeMap[$realMime])){ echo json_encode(['ok'=>false,'msg'=>'Faqat PNG, JPG, WebP, SVG ruxsat etilgan']); exit; }
 if($file['size'] > 3*1024*1024){ echo json_encode(['ok'=>false,'msg'=>"Fayl 3MB dan kichik bo'lishi kerak"]); exit; }
 $ext = $mimeMap[$realMime];
 $logosDir = __DIR__.'/logos/';
 if(!is_dir($logosDir)){ mkdir($logosDir, 0755, true); }
 $slug = strtolower(preg_replace('/[^a-z0-9]/','',strtolower($op)));
 // Eski fayllarni o'chirish
 foreach(['png','jpg','jpeg','webp','svg'] as $e){ $f2=$logosDir.$slug.'.'.$e; if(file_exists($f2)) @unlink($f2); }
 $dest = $logosDir.$slug.'.'.$ext;
 if(move_uploaded_file($file['tmp_name'], $dest)){
  $v = time();
  echo json_encode(['ok'=>true,'url'=>'logos/'.$slug.'.'.$ext.'?v='.$v,'slug'=>$slug,'ext'=>$ext]);
 } else {
  echo json_encode(['ok'=>false,'msg'=>"Fayl saqlash xatosi. logos/ papkasiga yozish ruxsati bormi?"]);
 }
 exit;
}

// Baraban aylanishini tarixga yozish (faqat Bosh admin)
if($a=='log_spin'){
 header('Content-Type: application/json; charset=utf-8');
 if(!isLogged() || !isSuper()){ echo json_encode(['ok'=>false]); exit; }
 $b=json_decode(file_get_contents('php://input'),true);
 $ym=(isset($b['ym']) && preg_match('/^\d{4}-\d{2}$/',$b['ym']))?$b['ym']:date('Y-m');
 $pool=in_array(($b['pool']??''),['paid','free','all'],true)?$b['pool']:'paid';
 $names=[]; foreach((array)($b['winners']??[]) as $w){ $names[]=trim(($w['name']??'').' ('.($w['phone']??'').')'); }
 $txt=implode(', ',array_slice($names,0,50));
 try{ db()->prepare("INSERT INTO spin_log (ym,pool,winners,created_by,created_by_name) VALUES (?,?,?,?,?)")->execute([$ym,$pool,$txt,$_SESSION['user']['id'],$_SESSION['user']['name']]); }catch(Exception $e){}
 logActivity('spin', count($names)." g'olib (".$pool."): ".$txt);
 echo json_encode(['ok'=>true]); exit;
}

// Ko'p tanlab chiqindiga tashlash (soft delete)
if($a=='bulk_trash'){
 header('Content-Type: application/json; charset=utf-8');
 if(!isLogged()){ echo json_encode(['ok'=>false]); exit; }
 $b=json_decode(file_get_contents('php://input'),true);
 $ids=array_filter(array_map('intval',(array)($b['ids']??[])));
 if(!$ids){ echo json_encode(['ok'=>false,'msg'=>"Tanlanmagan"]); exit; }
 $isS=isSuper(); $uid=$_SESSION['user']['id']; $done=0;
 foreach($ids as $id){
  try{
   $s=db()->prepare("SELECT dealer_id,status FROM paid_participants WHERE id=?"); $s->execute([$id]); $r=$s->fetch();
   if(!$r) continue;
   if($isS || ($r['dealer_id']==$uid && $r['status']=='pending')){
    db()->prepare("UPDATE paid_participants SET trashed=1, trashed_at=NOW() WHERE id=?")->execute([$id]); $done++;
   }
  }catch(Exception $e){}
 }
 logActivity('bulk_trash',"$done ta nomer chiqindiga tashlandi");
 echo json_encode(['ok'=>true,'done'=>$done]); exit;
}

// Ko'p tanlab O'YINGA/BAZAGA ko'chirish (faqat Bosh admin)
if($a=='bulk_toggle'){
 header('Content-Type: application/json; charset=utf-8');
 if(!isLogged() || !isSuper()){ echo json_encode(['ok'=>false]); exit; }
 $b=json_decode(file_get_contents('php://input'),true);
 $ids=array_filter(array_map('intval',(array)($b['ids']??[])));
 $val=intval($b['val']??0)?1:0;
 if(!$ids){ echo json_encode(['ok'=>false,'msg'=>"Tanlanmagan"]); exit; }
 $done=0;
 foreach($ids as $id){ try{ db()->prepare("UPDATE paid_participants SET is_paid=? WHERE id=? AND trashed=0")->execute([$val,$id]); $done++; }catch(Exception $e){} }
 logActivity('bulk_toggle',"$done ta → ".($val?"O'YINGA":'BAZAGA'));
 echo json_encode(['ok'=>true,'done'=>$done]); exit;
}

// Oylik hisobotni Telegram kanalga yuborish (faqat Bosh admin)
if($a=='send_month_report'){
 header('Content-Type: application/json; charset=utf-8');
 if(!isLogged() || !isSuper()){ echo json_encode(['ok'=>false,'msg'=>"Ruxsat yo'q"]); exit; }
 $ym=(isset($_GET['ym']) && preg_match('/^\d{4}-\d{2}$/',$_GET['ym']))?$_GET['ym']:date('Y-m');
 $start=$ym.'-01'; $end=date('Y-m-t',strtotime($start));
 try{
  $tot=(int)db()->query("SELECT COALESCE(SUM(promo_count),0) FROM paid_participants WHERE status='approved' AND trashed=0 AND DATE_FORMAT(created_at,'%Y-%m')='$ym'")->fetchColumn();
  $game=(int)db()->query("SELECT COALESCE(SUM(promo_count),0) FROM paid_participants WHERE status='approved' AND trashed=0 AND is_paid=1 AND blacklisted=0 AND DATE_FORMAT(created_at,'%Y-%m')='$ym'")->fetchColumn();
  $baza=(int)db()->query("SELECT COALESCE(SUM(promo_count),0) FROM paid_participants WHERE status='approved' AND trashed=0 AND is_paid=0 AND DATE_FORMAT(created_at,'%Y-%m')='$ym'")->fetchColumn();
  $ops=db()->query("SELECT operator_name, COUNT(*) c FROM paid_participants WHERE status='approved' AND trashed=0 AND DATE_FORMAT(created_at,'%Y-%m')='$ym' GROUP BY operator_name ORDER BY c DESC")->fetchAll();
  $dls=db()->query("SELECT d.name, COUNT(p.id) c FROM dealers d LEFT JOIN paid_participants p ON p.dealer_id=d.id AND p.status='approved' AND p.trashed=0 AND DATE_FORMAT(p.created_at,'%Y-%m')='$ym' WHERE d.role='diller' GROUP BY d.id HAVING c>0 ORDER BY c DESC LIMIT 5")->fetchAll();
 }catch(Exception $e){ echo json_encode(['ok'=>false,'msg'=>"Baza xatosi"]); exit; }
 $som=totalBalance($start,$end);
 $t="📊 <b>".monthLabel($ym)." — HISOBOT</b>\n\n";
 $t.="👥 Jami ishtirokchi: <b>$tot</b>\n🎯 O'YINDA: <b>$game</b>\n🗂 BAZADA: <b>$baza</b>\n💰 Summa: <b>".number_format($som,0,'.',' ')." so'm</b>\n";
 if($ops){ $t.="\n📡 <b>Operatorlar:</b>\n"; foreach($ops as $o){ $t.="• ".$o['operator_name'].": ".$o['c']."\n"; } }
 if($dls){ $medals=['🥇','🥈','🥉','4.','5.']; $t.="\n🏆 <b>Top dillerlar:</b>\n"; foreach($dls as $i=>$d){ $t.=($medals[$i]??($i+1).'.')." ".$d['name'].": ".$d['c']."\n"; } }
 $r=sendToChannel($t);
 if($r===false){ echo json_encode(['ok'=>false,'msg'=>"Kanal sozlanmagan (Sozlama)"]); exit; }
 logActivity('report',"Oylik hisobot Telegramga: ".$ym);
 echo json_encode(['ok'=>true]); exit;
}

// Kunlik yakunni Telegramga yuborish (cron uchun, token bilan himoyalangan - login shart emas)
if($a=='cron_daily'){
 $tok=getSetting('cron_token');
 if($tok==='' || (($_GET['token']??'')!==$tok)){ http_response_code(403); echo 'forbidden'; exit; }
 $d=date('Y-m-d');
 try{ $cnt=(int)db()->query("SELECT COALESCE(SUM(promo_count),0) FROM paid_participants WHERE status='approved' AND trashed=0 AND DATE(created_at)='$d'")->fetchColumn(); }catch(Exception $e){ $cnt=0; }
 try{ $game=(int)db()->query("SELECT COALESCE(SUM(promo_count),0) FROM paid_participants WHERE status='approved' AND trashed=0 AND is_paid=1 AND DATE(created_at)='$d'")->fetchColumn(); }catch(Exception $e){ $game=0; }
 $t="🌙 <b>Kunlik yakun</b> — ".date('d.m.Y')."\n\n➕ Bugun qo'shildi: <b>$cnt</b> ta\n🎯 Shundan O'YINGA: <b>$game</b> ta";
 sendToChannel($t);
 echo 'ok'; exit;
}

// Backup: butun bazani JSON qilib yuklab olish (faqat Bosh admin)
if($a=='backup'){
 if(!isLogged() || !isSuper()){ exit; }
 $out=['exported_at'=>date('c'),'participants'=>[],'dealers'=>[],'tarifs'=>[],'winners'=>[]];
 try{ $out['participants']=db()->query("SELECT phone,pretty_phone,name,operator_name,tarif_name,is_paid,dealer_id,status,is_blocked,trashed,promo_count,created_at FROM paid_participants")->fetchAll(); }catch(Exception $e){}
 try{ $out['dealers']=db()->query("SELECT id,login,name,role,monthly_target,can_add FROM dealers")->fetchAll(); }catch(Exception $e){}
 try{ $out['tarifs']=db()->query("SELECT operator_name,name,price FROM tarifs")->fetchAll(); }catch(Exception $e){}
 try{ $out['winners']=db()->query("SELECT phone,name,dealer_id,created_at FROM winners")->fetchAll(); }catch(Exception $e){}
 logActivity('backup','Backup yuklab olindi');
 header('Content-Type: application/json; charset=utf-8');
 header('Content-Disposition: attachment; filename="paynet_backup_'.date('Y-m-d_His').'.json"');
 echo json_encode($out, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT); exit;
}

echo json_encode([]);