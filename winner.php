<?php require 'config.php'; requireLogin();
$ph=preg_replace('/\D/','',$_GET['phone']??'');
try{
 $s=db()->prepare("SELECT p.*, d.name as dealer_name FROM paid_participants p LEFT JOIN dealers d ON d.id=p.dealer_id WHERE p.phone=?");
 $s->execute([$ph]); $r=$s->fetch();
 if($r){
  db()->prepare("UPDATE paid_participants SET blacklisted=1 WHERE phone=?")->execute([$ph]);
  db()->prepare("INSERT IGNORE INTO winners (phone,name,dealer_id) VALUES (?,?,?)")->execute([$ph,$r['name'],$r['dealer_id']]);
  $tpl=getSetting('template_winner'); if(!$tpl) $tpl="✅ TASDIQLANDI!\n1. Diller: {diller}\n2. Ism: {ism}\n3. Nomer: {nomer}\n4. Operator: {operator}\n5. Tarif: {tarif}";
  $txt=str_replace(['{diller}','{ism}','{nomer}','{operator}','{tarif}'],[$r['dealer_name'],$r['name'],$r['pretty_phone'],$r['operator_name'],$r['tarif_name']],$tpl);
  sendToChannel($txt);
  logActivity('winner', "G'olib: ".$r['name']." ".$r['pretty_phone']." (".$r['operator_name'].")");
 }
}catch(Exception $e){}
header("Location: winners.php");
