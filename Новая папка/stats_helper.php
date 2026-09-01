<?php
// Statistika uchun umumiy filtr/so'rov funksiyalari (reports.php va api.php birga ishlatadi) - FIXED: promo_count hisobga olingan

function statsFilters(){
 return [
  'f' => $_GET['f'] ?? 'all',
  'days' => intval($_GET['days'] ?? 0),
  'from' => $_GET['from'] ?? '',
  'to' => $_GET['to'] ?? '',
  'dealer_id' => intval($_GET['dealer_id'] ?? 0),
 ];
}

function buildStatsWhere($params){
 $w=""; $p=[];
 $f = $params['f'] ?? 'all';
 $days = intval($params['days'] ?? 0);
 $from = $params['from'] ?? ''; $to = $params['to'] ?? '';
 if($f=='today') $w.=" AND DATE(p.created_at)=CURDATE()";
 elseif($f=='yesterday') $w.=" AND DATE(p.created_at)=CURDATE() - INTERVAL 1 DAY";
 elseif($f=='week') $w.=" AND p.created_at>=DATE_SUB(NOW(), INTERVAL 7 DAY)";
 elseif($f=='month') $w.=" AND p.created_at>=DATE_SUB(NOW(), INTERVAL 30 DAY)";
 elseif($f=='year') $w.=" AND p.created_at>=DATE_SUB(NOW(), INTERVAL 1 YEAR)";
 elseif($f=='days' && $days>=1 && $days<=10){ $w.=" AND p.created_at>=DATE_SUB(NOW(), INTERVAL ? DAY)"; $p[]=$days; }
 elseif($f=='range' && preg_match('/^\d{4}-\d{2}-\d{2}$/',$from) && preg_match('/^\d{4}-\d{2}-\d{2}$/',$to)){ $w.=" AND DATE(p.created_at) BETWEEN ? AND ?"; $p[]=$from; $p[]=$to; }
 if(!empty($params['dealer_id'])){ $w.=" AND p.dealer_id=?"; $p[]=intval($params['dealer_id']); }
 if(!empty($params['operator'])){ $w.=" AND p.operator_name=?"; $p[]=$params['operator']; }
 if(!empty($params['tarif'])){ $w.=" AND p.tarif_name=?"; $p[]=$params['tarif']; }
 return [$w,$p];
}

function getApprovedRows($params, $limit=5000){
 list($w,$p) = buildStatsWhere($params);
 $w .= " AND p.status='approved'";
 $sql = "SELECT p.*, d.name as dealer_name FROM paid_participants p LEFT JOIN dealers d ON d.id=p.dealer_id WHERE 1 $w ORDER BY p.id DESC LIMIT ".intval($limit);
 try{ $st=db()->prepare($sql); $st->execute($p); return $st->fetchAll(); }catch(Exception $e){ return []; }
}

function getQualityByDealer($params){
 list($w,$p) = buildStatsWhere($params);
 $sql = "SELECT p.dealer_id, d.name as dealer_name,
   SUM(CASE WHEN p.status='rejected' THEN 1 ELSE 0 END) as rejected,
   SUM(CASE WHEN p.is_blocked=1 THEN 1 ELSE 0 END) as blocked
   FROM paid_participants p LEFT JOIN dealers d ON d.id=p.dealer_id WHERE 1 $w GROUP BY p.dealer_id";
 try{ $st=db()->prepare($sql); $st->execute($p); $rows=$st->fetchAll(); }catch(Exception $e){ return []; }
 $out=[];
 foreach($rows as $r){ $out[$r['dealer_name']?:'Nomalum'] = ['rejected'=>(int)$r['rejected'],'blocked'=>(int)$r['blocked']]; }
 return $out;
}

function prevPeriodRange($f, $days=0, $from='', $to=''){
 $today = new DateTime('today');
 if($f=='today'){
  $c1=$today->format('Y-m-d'); $c2=$c1;
  $pv=(clone $today)->modify('-1 day')->format('Y-m-d');
  return ['cur'=>[$c1,$c2],'prev'=>[$pv,$pv]];
 }
 if($f=='yesterday'){
  $c=(clone $today)->modify('-1 day')->format('Y-m-d');
  $pv=(clone $today)->modify('-2 day')->format('Y-m-d');
  return ['cur'=>[$c,$c],'prev'=>[$pv,$pv]];
 }
 if($f=='week'){
  $c1=(clone $today)->modify('-6 day')->format('Y-m-d'); $c2=$today->format('Y-m-d');
  $p2=(clone $today)->modify('-7 day')->format('Y-m-d'); $p1=(clone $today)->modify('-13 day')->format('Y-m-d');
  return ['cur'=>[$c1,$c2],'prev'=>[$p1,$p2]];
 }
 if($f=='month'){
  $c1=(clone $today)->modify('-29 day')->format('Y-m-d'); $c2=$today->format('Y-m-d');
  $p2=(clone $today)->modify('-30 day')->format('Y-m-d'); $p1=(clone $today)->modify('-59 day')->format('Y-m-d');
  return ['cur'=>[$c1,$c2],'prev'=>[$p1,$p2]];
 }
 if($f=='days' && $days>=1 && $days<=10){
  $c1=(clone $today)->modify('-'.($days-1).' day')->format('Y-m-d'); $c2=$today->format('Y-m-d');
  $p2=(clone $today)->modify('-'.$days.' day')->format('Y-m-d'); $p1=(clone $today)->modify('-'.($days*2-1).' day')->format('Y-m-d');
  return ['cur'=>[$c1,$c2],'prev'=>[$p1,$p2]];
 }
 return null;
}

function countInRange($dateFrom, $dateTo, $dealerId=0){
 $w=" AND DATE(p.created_at) BETWEEN ? AND ? AND p.status='approved'"; $p=[$dateFrom,$dateTo];
 if($dealerId){ $w.=" AND p.dealer_id=?"; $p[]=$dealerId; }
 try{ $st=db()->prepare("SELECT COALESCE(SUM(promo_count),0) FROM paid_participants p WHERE 1 $w"); $st->execute($p); return (int)$st->fetchColumn(); }catch(Exception $e){ return 0; }
}

// Dashboard bilan bir xil hisoblash uchun - promo_count ni hisobga oladi
function getTotalCountWithPromo($params){
 list($w,$p) = buildStatsWhere($params);
 $w .= " AND p.status='approved'";
 $sql = "SELECT COALESCE(SUM(p.promo_count),0) as c FROM paid_participants p WHERE 1 $w";
 try{ $st=db()->prepare($sql); $st->execute($p); return (int)$st->fetchColumn(); }catch(Exception $e){ return 0; }
}
