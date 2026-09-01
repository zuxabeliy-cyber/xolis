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

echo json_encode([]);