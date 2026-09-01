<?php
session_start();
define('DB_HOST','localhost');
define('DB_NAME','6a52bb5545251_paynet');
define('DB_USER','6a52bb5545251_paynet');
define('DB_PASS','Andijon2@');
function db(){ static $p=null; if($p) return $p; $p=new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]); return $p; }
function isLogged(){ return isset($_SESSION['user']); }
function isSuper(){ return isLogged() && $_SESSION['user']['role']=='super'; }
function requireLogin(){ if(!isLogged()){ header("Location: login.php"); exit; } }
function getSetting($k){ try{ $s=db()->prepare("SELECT svalue FROM settings WHERE skey=?"); $s->execute([$k]); $r=$s->fetch(); return $r['svalue']??''; }catch(Exception $e){ return ''; } }
function sendToChannel($text){ $tok=getSetting('bot_token'); if(!$tok) $tok='8956274863:AAHhy99dkoeAK3RBzCQ4S78GtlWH3F8BLK8'; $chat=getSetting('channel'); if(!$chat) return false; $url="https://api.telegram.org/bot$tok/sendMessage"; $ch=curl_init($url); curl_setopt_array($ch,[CURLOPT_POST=>1,CURLOPT_POSTFIELDS=>['chat_id'=>$chat,'text'=>$text,'parse_mode'=>'HTML'],CURLOPT_RETURNTRANSFER=>1,CURLOPT_TIMEOUT=>8]); $r=curl_exec($ch); curl_close($ch); return $r; }
// "BAZAGA" tugmasi sozlamasi: yoqilgan bo'lsa - BAZAGA bosilganda kanalga ketadi; o'chirilgan bo'lsa - eski usul (O'YINDA kanalga ketadi), faqat BAZAGA tugmasi ekranda birinchi/asosiy bo'lib turadi
function bazaSendsChannel(){ return getSetting('baza_sends_channel')=='1'; }
// is_paid qiymatiga qarab shu yozuv kanalga yuborilishi kerakmi-yo'qmi, joriy sozlamaga qarab hal qiladi
function shouldSendToChannel($is_paid){ return bazaSendsChannel() ? ($is_paid==0) : ($is_paid==1); }
function prettyUz($phone){ $p=preg_replace('/\D/','',$phone); if(strlen($p)==9) $p='998'.$p; if(strlen($p)==12) return '+'.substr($p,0,3).' '.substr($p,3,2).' '.substr($p,5,3).' '.substr($p,8,2).' '.substr($p,10,2); return '+'.$p; }

// Avtomatik: bazani va parollarni xavfsiz holatga keltiradi (bir marta ishlaydi, keyin o'zi sekinlashmaydi)
function ensureSchema(){
 static $done=false; if($done) return; $done=true;
 try{
  $col=db()->query("SHOW COLUMNS FROM paid_participants LIKE 'status'")->fetch();
  if(!$col){ db()->exec("ALTER TABLE paid_participants ADD COLUMN status ENUM('pending','approved') NOT NULL DEFAULT 'approved'"); }
 }catch(Exception $e){}
 try{
  // status ga 'rejected' holatini ham qo'shamiz (rad etilganlarni tarix uchun saqlab qolish uchun)
  $col=db()->query("SHOW COLUMNS FROM paid_participants LIKE 'status'")->fetch();
  if($col && stripos($col['Type'],'rejected')===false){ db()->exec("ALTER TABLE paid_participants MODIFY COLUMN status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved'"); }
 }catch(Exception $e){}
 try{ $col=db()->query("SHOW COLUMNS FROM paid_participants LIKE 'approved_by'")->fetch(); if(!$col){ db()->exec("ALTER TABLE paid_participants ADD COLUMN approved_by INT NULL"); } }catch(Exception $e){}
 try{ $col=db()->query("SHOW COLUMNS FROM paid_participants LIKE 'approved_at'")->fetch(); if(!$col){ db()->exec("ALTER TABLE paid_participants ADD COLUMN approved_at DATETIME NULL"); } }catch(Exception $e){}
 try{ $col=db()->query("SHOW COLUMNS FROM paid_participants LIKE 'reject_reason'")->fetch(); if(!$col){ db()->exec("ALTER TABLE paid_participants ADD COLUMN reject_reason VARCHAR(255) NULL"); } }catch(Exception $e){}
 try{ $col=db()->query("SHOW COLUMNS FROM paid_participants LIKE 'is_blocked'")->fetch(); if(!$col){ db()->exec("ALTER TABLE paid_participants ADD COLUMN is_blocked TINYINT(1) NOT NULL DEFAULT 0"); } }catch(Exception $e){}
 try{ $col=db()->query("SHOW COLUMNS FROM paid_participants LIKE 'block_reason'")->fetch(); if(!$col){ db()->exec("ALTER TABLE paid_participants ADD COLUMN block_reason VARCHAR(255) NULL"); } }catch(Exception $e){}
 try{
  db()->exec("CREATE TABLE IF NOT EXISTS duplicate_attempts (id INT AUTO_INCREMENT PRIMARY KEY, phone VARCHAR(20), pretty_phone VARCHAR(30), dealer_id INT, attempted_name VARCHAR(100), attempted_operator VARCHAR(50), attempted_tarif VARCHAR(100), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
 }catch(Exception $e){}
 try{ $col=db()->query("SHOW COLUMNS FROM paid_participants LIKE 'promo_count'")->fetch(); if(!$col){ db()->exec("ALTER TABLE paid_participants ADD COLUMN promo_count INT NOT NULL DEFAULT 1"); } }catch(Exception $e){}
 try{ $col=db()->query("SHOW COLUMNS FROM dealers LIKE 'can_add'")->fetch(); if(!$col){ db()->exec("ALTER TABLE dealers ADD COLUMN can_add TINYINT(1) NOT NULL DEFAULT 1"); } }catch(Exception $e){}
 try{ $col=db()->query("SHOW COLUMNS FROM tarifs LIKE 'price'")->fetch(); if(!$col){ db()->exec("ALTER TABLE tarifs ADD COLUMN price DECIMAL(12,2) NOT NULL DEFAULT 0"); } }catch(Exception $e){}
 try{ $col=db()->query("SHOW COLUMNS FROM dealers LIKE 'monthly_target'")->fetch(); if(!$col){ db()->exec("ALTER TABLE dealers ADD COLUMN monthly_target DECIMAL(12,2) NOT NULL DEFAULT 0"); } }catch(Exception $e){}
 try{ db()->exec("CREATE TABLE IF NOT EXISTS dealer_payments (id INT AUTO_INCREMENT PRIMARY KEY, dealer_id INT NOT NULL, amount DECIMAL(12,2) NOT NULL, note VARCHAR(255), created_by INT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)"); }catch(Exception $e){}
 try{ $col=db()->query("SHOW COLUMNS FROM dealers LIKE 'last_seen_chat_id'")->fetch(); if(!$col){ db()->exec("ALTER TABLE dealers ADD COLUMN last_seen_chat_id INT NOT NULL DEFAULT 0"); } }catch(Exception $e){}
 try{ db()->prepare("INSERT IGNORE INTO tarifs (operator_name,name) VALUES ('Mobiuz','L 55')")->execute(); db()->prepare("INSERT IGNORE INTO tarifs (operator_name,name) VALUES ('Mobiuz','M 45')")->execute(); }catch(Exception $e){}
 try{ db()->exec("CREATE TABLE IF NOT EXISTS chat_messages (id INT AUTO_INCREMENT PRIMARY KEY, sender_id INT NOT NULL, sender_name VARCHAR(100), message TEXT, image_path VARCHAR(255) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)"); }catch(Exception $e){}
 // Chiqindi qutisi (soft delete): o'chirilgan nomerlar butunlay yo'qolmaydi, tiklash mumkin
 try{ $col=db()->query("SHOW COLUMNS FROM paid_participants LIKE 'trashed'")->fetch(); if(!$col){ db()->exec("ALTER TABLE paid_participants ADD COLUMN trashed TINYINT(1) NOT NULL DEFAULT 0"); } }catch(Exception $e){}
 try{ $col=db()->query("SHOW COLUMNS FROM paid_participants LIKE 'trashed_at'")->fetch(); if(!$col){ db()->exec("ALTER TABLE paid_participants ADD COLUMN trashed_at DATETIME NULL"); } }catch(Exception $e){}
 // Faollik jurnali (audit log)
 try{ db()->exec("CREATE TABLE IF NOT EXISTS activity_log (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NULL, user_name VARCHAR(100), action VARCHAR(50), detail VARCHAR(500), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)"); }catch(Exception $e){}
 // Baraban aylanishlar tarixi
 try{ db()->exec("CREATE TABLE IF NOT EXISTS spin_log (id INT AUTO_INCREMENT PRIMARY KEY, ym VARCHAR(7), pool VARCHAR(10), winners TEXT, created_by INT NULL, created_by_name VARCHAR(100), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)"); }catch(Exception $e){}
 try{
  if(getSetting('pwd_migrated')!='1'){
   $rows=db()->query("SELECT id,password FROM dealers")->fetchAll();
   foreach($rows as $r){
    $already = preg_match('/^\$2[aby]\$/', $r['password']);
    if(!$already){
     db()->prepare("UPDATE dealers SET password=? WHERE id=?")->execute([password_hash($r['password'],PASSWORD_DEFAULT),$r['id']]);
    }
   }
   db()->prepare("INSERT INTO settings (skey,svalue) VALUES ('pwd_migrated','1') ON DUPLICATE KEY UPDATE svalue='1'")->execute();
  }
 }catch(Exception $e){}
}
ensureSchema();

// Sana oralig'i filtri uchun umumiy SQL sharti yaratadi (from/to bo'sh bo'lsa cheklovsiz)
function buildDateCond($from,$to,$col='p.created_at'){
 $cond=''; $params=[];
 if($from){ $cond.=" AND $col >= ?"; $params[]=$from.' 00:00:00'; }
 if($to){ $cond.=" AND $col <= ?"; $params[]=$to.' 23:59:59'; }
 return [$cond,$params];
}

// Kutilmoqdagi (tasdiqlanmagan) nomerlar soni - menyudagi badge va bildirishnoma uchun
function pendingCount(){ try{ return (int)db()->query("SELECT COUNT(*) FROM paid_participants WHERE status='pending' AND trashed=0")->fetchColumn(); }catch(Exception $e){ return 0; } }

// Faollik jurnaliga yozuv qo'shadi (kim, qachon, nima qildi)
function logActivity($action,$detail=''){ try{ $u=$_SESSION['user']??null; db()->prepare("INSERT INTO activity_log (user_id,user_name,action,detail) VALUES (?,?,?,?)")->execute([$u['id']??null,$u['name']??'—',$action,mb_substr((string)$detail,0,500)]); }catch(Exception $e){} }
// Chiqindi qutisidagi (o'chirilgan) nomerlar soni
function trashCount(){ try{ return (int)db()->query("SELECT COUNT(*) FROM paid_participants WHERE trashed=1")->fetchColumn(); }catch(Exception $e){ return 0; } }

// Bitta dillerning jamlangan pul balansi (tasdiqlangan, bloklanmagan nomerlar bo'yicha, har bir nomer uchun tarif narxi 1 marta hisoblanadi - 1+1 aksiya pulga ta'sir qilmaydi)
function dealerBalance($dealerId,$from=null,$to=null){
 try{
  list($cond,$params)=buildDateCond($from,$to,'p.created_at');
  $sql="SELECT COALESCE(SUM(t.price),0) FROM paid_participants p JOIN tarifs t ON t.operator_name=p.operator_name AND t.name=p.tarif_name WHERE p.dealer_id=? AND p.status='approved' AND p.is_blocked=0 AND p.trashed=0 $cond";
  $s=db()->prepare($sql); $s->execute(array_merge([$dealerId],$params)); return (float)$s->fetchColumn();
 }catch(Exception $e){ return 0; }
}

// Barcha dillerlarning balanslari (Bosh admin ko'rinishi uchun), saralash bilan
function allDealerBalances($from=null,$to=null,$sort='balance',$dir='DESC'){
 try{
  list($cond,$params)=buildDateCond($from,$to,'p.created_at');
  $orderMap=['balance'=>'balance','cnt'=>'cnt','name'=>'d.name','monthly_target'=>'d.monthly_target'];
  $orderCol=$orderMap[$sort] ?? 'balance';
  $dir = strtoupper($dir)==='ASC' ? 'ASC' : 'DESC';
  $sql="SELECT d.id, d.name, d.role, d.monthly_target, COALESCE(SUM(t.price),0) as balance, COUNT(p.id) as cnt FROM dealers d LEFT JOIN paid_participants p ON p.dealer_id=d.id AND p.status='approved' AND p.is_blocked=0 AND p.trashed=0 $cond LEFT JOIN tarifs t ON t.operator_name=p.operator_name AND t.name=p.tarif_name WHERE d.role='diller' GROUP BY d.id ORDER BY $orderCol $dir";
  $s=db()->prepare($sql); $s->execute($params); return $s->fetchAll();
 }catch(Exception $e){ return []; }
}

// Umumiy jami balans (barcha dillerlar)
function totalBalance($from=null,$to=null){
 try{
  list($cond,$params)=buildDateCond($from,$to,'p.created_at');
  $sql="SELECT COALESCE(SUM(t.price),0) FROM paid_participants p JOIN tarifs t ON t.operator_name=p.operator_name AND t.name=p.tarif_name WHERE p.status='approved' AND p.is_blocked=0 AND p.trashed=0 $cond";
  $s=db()->prepare($sql); $s->execute($params); return (float)$s->fetchColumn();
 }catch(Exception $e){ return 0; }
}

// Ma'lum davr oralig'idagi summa (kunlik/haftalik o'sish ko'rsatkichi uchun)
function periodSum($fromDT,$toDT,$dealerId=null){
 try{
  $dcond=''; $params=[$fromDT,$toDT];
  if($dealerId){ $dcond=" AND p.dealer_id=?"; $params[]=$dealerId; }
  $sql="SELECT COALESCE(SUM(t.price),0) FROM paid_participants p JOIN tarifs t ON t.operator_name=p.operator_name AND t.name=p.tarif_name WHERE p.status='approved' AND p.is_blocked=0 AND p.trashed=0 AND p.created_at>=? AND p.created_at<=? $dcond";
  $s=db()->prepare($sql); $s->execute($params); return (float)$s->fetchColumn();
 }catch(Exception $e){ return 0; }
}

// Operatorlar bo'yicha jamlangan summa (dealerId berilsa - faqat o'sha diller bo'yicha)
function operatorBalances($dealerId=null,$from=null,$to=null){
 try{
  list($cond,$params)=buildDateCond($from,$to,'p.created_at');
  $dcond=''; $dparams=[];
  if($dealerId){ $dcond=" AND p.dealer_id=?"; $dparams[]=$dealerId; }
  $sql="SELECT p.operator_name, COALESCE(SUM(t.price),0) balance, COUNT(*) cnt FROM paid_participants p JOIN tarifs t ON t.operator_name=p.operator_name AND t.name=p.tarif_name WHERE p.status='approved' AND p.is_blocked=0 AND p.trashed=0 $dcond $cond GROUP BY p.operator_name ORDER BY balance DESC";
  $s=db()->prepare($sql); $s->execute(array_merge($dparams,$params)); return $s->fetchAll();
 }catch(Exception $e){ return []; }
}

// Bitta operatorning tariflari bo'yicha jamlangan summa (kartani ochganda)
function tarifBalancesForOperator($operatorName,$dealerId=null,$from=null,$to=null){
 try{
  list($cond,$params)=buildDateCond($from,$to,'p.created_at');
  $dcond=''; $dparams=[];
  if($dealerId){ $dcond=" AND p.dealer_id=?"; $dparams[]=$dealerId; }
  $sql="SELECT p.tarif_name, t.price, COALESCE(SUM(t.price),0) balance, COUNT(*) cnt FROM paid_participants p JOIN tarifs t ON t.operator_name=p.operator_name AND t.name=p.tarif_name WHERE p.operator_name=? AND p.status='approved' AND p.is_blocked=0 AND p.trashed=0 $dcond $cond GROUP BY p.tarif_name, t.price ORDER BY balance DESC";
  $s=db()->prepare($sql); $s->execute(array_merge([$operatorName],$dparams,$params)); return $s->fetchAll();
 }catch(Exception $e){ return []; }
}

// Eng ko'p pul keltirgan tariflar (top reyting)
function topTarifs($limit=5,$dealerId=null,$from=null,$to=null){
 try{
  list($cond,$params)=buildDateCond($from,$to,'p.created_at');
  $dcond=''; $dparams=[];
  if($dealerId){ $dcond=" AND p.dealer_id=?"; $dparams[]=$dealerId; }
  $sql="SELECT p.operator_name, p.tarif_name, t.price, COALESCE(SUM(t.price),0) balance, COUNT(*) cnt FROM paid_participants p JOIN tarifs t ON t.operator_name=p.operator_name AND t.name=p.tarif_name WHERE p.status='approved' AND p.is_blocked=0 AND p.trashed=0 $dcond $cond GROUP BY p.operator_name,p.tarif_name,t.price ORDER BY balance DESC LIMIT ".intval($limit);
  $s=db()->prepare($sql); $s->execute(array_merge($dparams,$params)); return $s->fetchAll();
 }catch(Exception $e){ return []; }
}

// Oxirgi N oy bo'yicha jamlangan summa (grafik uchun)
function monthlyRevenue($months=12,$dealerId=null){
 try{
  $dcond=''; $params=[$months];
  if($dealerId){ $dcond=" AND p.dealer_id=?"; $params[]=$dealerId; }
  $sql="SELECT DATE_FORMAT(p.created_at,'%Y-%m') ym, COALESCE(SUM(t.price),0) balance FROM paid_participants p JOIN tarifs t ON t.operator_name=p.operator_name AND t.name=p.tarif_name WHERE p.status='approved' AND p.is_blocked=0 AND p.trashed=0 AND p.created_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH) $dcond GROUP BY ym ORDER BY ym ASC";
  $s=db()->prepare($sql); $s->execute($params);
  $rows=$s->fetchAll(); $map=[]; foreach($rows as $r){ $map[$r['ym']]=(float)$r['balance']; }
  $out=[]; for($i=$months-1;$i>=0;$i--){ $ym=date('Y-m', strtotime("-$i months")); $out[]=['ym'=>$ym,'balance'=>$map[$ym]??0]; }
  return $out;
 }catch(Exception $e){ return []; }
}

// Bitta dillerning (yoki hammasining, $dealerId=null) batafsil nomerlar ro'yxati - tarix/eksport uchun
function dealerParticipantsDetailed($dealerId,$from=null,$to=null){
 try{
  list($cond,$params)=buildDateCond($from,$to,'p.created_at');
  $sql="SELECT p.name,p.pretty_phone,p.operator_name,p.tarif_name,t.price,p.created_at,p.promo_count FROM paid_participants p JOIN tarifs t ON t.operator_name=p.operator_name AND t.name=p.tarif_name WHERE p.dealer_id=? AND p.status='approved' AND p.is_blocked=0 AND p.trashed=0 $cond ORDER BY p.created_at DESC";
  $s=db()->prepare($sql); $s->execute(array_merge([$dealerId],$params)); return $s->fetchAll();
 }catch(Exception $e){ return []; }
}

// Dillerga qilingan naqd to'lovlar jami
function dealerPaidTotal($dealerId){
 try{ $s=db()->prepare("SELECT COALESCE(SUM(amount),0) FROM dealer_payments WHERE dealer_id=?"); $s->execute([$dealerId]); return (float)$s->fetchColumn(); }catch(Exception $e){ return 0; }
}

// Dillerga to'lov qilinganini qayd etish
function addDealerPayment($dealerId,$amount,$note,$by){
 try{ db()->prepare("INSERT INTO dealer_payments (dealer_id,amount,note,created_by) VALUES (?,?,?,?)")->execute([$dealerId,$amount,$note,$by]); return true; }catch(Exception $e){ return false; }
}

// Dillerning to'lovlar tarixi
function dealerPaymentsHistory($dealerId){
 try{ $s=db()->prepare("SELECT dp.*, d2.name as by_name FROM dealer_payments dp LEFT JOIN dealers d2 ON d2.id=dp.created_by WHERE dp.dealer_id=? ORDER BY dp.created_at DESC"); $s->execute([$dealerId]); return $s->fetchAll(); }catch(Exception $e){ return []; }
}

// Dillerning oylik maqsadini belgilash
function setDealerTarget($dealerId,$target){
 try{ db()->prepare("UPDATE dealers SET monthly_target=? WHERE id=?")->execute([$target,$dealerId]); return true; }catch(Exception $e){ return false; }
}

// Telegram (dillerlar chati)da o'qilmagan xabarlar soni - top headerdagi qizil nuqta uchun
function chatUnreadCount($dealerId){
 try{
  $s=db()->prepare("SELECT COUNT(*) FROM chat_messages WHERE id > (SELECT last_seen_chat_id FROM dealers WHERE id=?) AND sender_id != ?");
  $s->execute([$dealerId,$dealerId]); return (int)$s->fetchColumn();
 }catch(Exception $e){ return 0; }
}

// Dublikat urinishni qayd etish (kimdir allaqachon bor nomerni qayta yubormoqchi bo'lsa)
function logDuplicateAttempt($phone,$pretty,$dealerId,$name,$operator,$tarif){
 try{ db()->prepare("INSERT INTO duplicate_attempts (phone,pretty_phone,dealer_id,attempted_name,attempted_operator,attempted_tarif) VALUES (?,?,?,?,?,?)")->execute([$phone,$pretty,$dealerId,$name,$operator,$tarif]); }catch(Exception $e){}
}

// Diller nomer qo'shishi mumkinmi (1-Bosh admin tomonidan yopib qo'yilgan bo'lishi mumkin)
function dealerCanAdd($dealerId){
 try{ $s=db()->prepare("SELECT can_add FROM dealers WHERE id=?"); $s->execute([$dealerId]); $v=$s->fetchColumn(); return $v===false ? true : intval($v)===1; }catch(Exception $e){ return true; }
}

// Diller uchun kunlik limit tekshiruvi (Sozlamalarda yoqilgan bo'lsa)
// Qaytaradi: true = qo'shsa bo'ladi, false = limit tugagan
function checkDailyLimit($dealerId){
 if(getSetting('daily_limit_enabled')!='1') return true;
 $limit=intval(getSetting('daily_limit_count')); if($limit<=0) return true;
 try{
  $s=db()->prepare("SELECT COUNT(*) FROM paid_participants WHERE dealer_id=? AND DATE(created_at)=CURDATE() AND status!='rejected'");
  $s->execute([$dealerId]); $cnt=(int)$s->fetchColumn();
  return $cnt < $limit;
 }catch(Exception $e){ return true; }
}

// Bosh adminga yangi "kutilmoqda" nomer haqida shaxsiy Telegram xabari (Sozlamalarda admin_chat_id kiritilgan bo'lsa)
function notifyAdminPending($text){
 $chatId=getSetting('admin_chat_id'); if(!$chatId) return false;
 $tok=getSetting('bot_token'); if(!$tok) $tok='8956274863:AAHhy99dkoeAK3RBzCQ4S78GtlWH3F8BLK8';
 $url="https://api.telegram.org/bot$tok/sendMessage";
 $ch=curl_init($url); curl_setopt_array($ch,[CURLOPT_POST=>1,CURLOPT_POSTFIELDS=>['chat_id'=>$chatId,'text'=>$text,'parse_mode'=>'HTML'],CURLOPT_RETURNTRANSFER=>1,CURLOPT_TIMEOUT=>8]); $r=curl_exec($ch); curl_close($ch); return $r;
}

// ==================== OYLIK AJRATISH (har oy alohida) ====================
// Har bir oy alohida ko'riladi. Yangi oy 0 dan boshlanadi (ma'lumot o'chirilmaydi, faqat filtr).
function uzMonthName($mo){ $m=['01'=>'Yanvar','02'=>'Fevral','03'=>'Mart','04'=>'Aprel','05'=>'May','06'=>'Iyun','07'=>'Iyul','08'=>'Avgust','09'=>'Sentabr','10'=>'Oktabr','11'=>'Noyabr','12'=>'Dekabr']; return $m[$mo]??$mo; }
function monthLabel($ym){ if($ym==='all') return 'Barcha oylar'; if(!preg_match('/^(\d{4})-(\d{2})$/',$ym,$mm)) return $ym; return uzMonthName($mm[2]).' '.$mm[1]; }
function currentMonth(){ return date('Y-m'); }
// GET['ym'] dan tanlangan oyni oladi (default: joriy oy). 'all' = barcha oylar.
function selectedMonth(){ $m=$_GET['ym']??''; if($m==='all') return 'all'; if(preg_match('/^\d{4}-\d{2}$/',$m)) return $m; return date('Y-m'); }
// Tanlangan oy uchun SQL sharti ($col ustuni bo'yicha). 'all' bo'lsa - cheklovsiz.
function monthCond($ym,$col='p.created_at'){ if($ym==='all'||$ym==='') return ['',[]]; return [" AND DATE_FORMAT($col,'%Y-%m')=?",[$ym]]; }
// Bazada mavjud oylar ro'yxati (eng yangisi birinchi) + doim joriy oy
function availableMonths(){
 try{ $rows=db()->query("SELECT DISTINCT DATE_FORMAT(created_at,'%Y-%m') ym FROM paid_participants WHERE created_at IS NOT NULL ORDER BY ym DESC")->fetchAll(); $out=[]; foreach($rows as $r){ if(!empty($r['ym'])) $out[]=$r['ym']; } }catch(Exception $e){ $out=[]; }
 $cur=date('Y-m'); if(!in_array($cur,$out,true)) array_unshift($out,$cur);
 return $out;
}
// Sahifa tepasidagi oy tanlagich UI - bitta ochiladigan ro'yxat (dropdown). $extra - saqlanadigan boshqa GET parametrlar
function monthSelectorHtml($selected,$extra=[]){
 $months=availableMonths();
 $opts='';
 foreach($months as $m){ $opts.='<option value="'.htmlspecialchars($m).'"'.($selected===$m?' selected':'').'>'.htmlspecialchars(monthLabel($m)).'</option>'; }
 $opts.='<option value="all"'.($selected==='all'?' selected':'').'>Barcha oylar</option>';
 $ej=json_encode((object)$extra, JSON_UNESCAPED_UNICODE);
 $uid='ms'.substr(md5(uniqid('',true)),0,6);
 $h='<div class="card p-3 mb-4 flex items-center gap-2">';
 $h.='<span class="text-[11px] text-white/40 tracking-widest font-bold whitespace-nowrap">📅 OY:</span>';
 $h.='<select id="'.$uid.'" onchange="(function(v){var p=new URLSearchParams('.$ej.');p.set(\'ym\',v);window.location=window.location.pathname+\'?\'+p.toString();})(this.value)" class="flex-1 bg-[#16162a] border border-white/10 rounded-xl px-3 py-2.5 text-sm font-bold text-white outline-none focus:border-[#7c6cff]/50">'.$opts.'</select>';
 $h.='</div>';
 return $h;
}

// Ko'p qo'shish / Tez terish sahifalari uchun umumiy: qatorlar ro'yxatini bazaga qo'shadi
// (ikkala sahifa ham shu bitta funksiyani chaqiradi, mantiq bitta joyda saqlanadi)
function bulkInsertParticipants($rowsData, $createdAt){
 $add=0; $skipped=0;
 foreach($rowsData as $r){
  $name=trim($r['name']??''); if($name=='') $name='Nomalum';
  $ph=preg_replace('/\D/','',$r['phone']??''); if(strlen($ph)<9){ $skipped++; continue; } if(strlen($ph)==9) $ph='998'.$ph; $pretty=prettyUz($ph);
  $op=$r['operator']??''; $tar=$r['tarif']??''; $paid=intval($r['is_paid']??0); $did=intval($r['dealer_id']??0); if($did==0){ $skipped++; continue; }
  $promo = (!empty($r['promo_1_1'])) ? 2 : 1;
  try{
   $chk=db()->prepare("SELECT id FROM paid_participants WHERE phone=?"); $chk->execute([$ph]); if($chk->fetch()){ $skipped++; continue; }
   db()->prepare("INSERT INTO paid_participants (phone,pretty_phone,name,operator_name,tarif_name,is_paid,dealer_id,created_at,promo_count) VALUES (?,?,?,?,?,?,?,?,?)")->execute([$ph,$pretty,$name,$op,$tar,$paid,$did,$createdAt,$promo]);
   if(shouldSendToChannel($paid)){
    try{ $s=db()->prepare("SELECT name FROM dealers WHERE id=?"); $s->execute([$did]); $dname=$s->fetchColumn()?:''; }catch(Exception $e){ $dname=''; }
    $tpl=getSetting('template'); if(!$tpl) $tpl="1. Diller: {diller}\n2. Ism: {ism}\n3. Nomer: {nomer}\n4. Operator: {operator}\n5. Tarif: {tarif}";
    $txt=str_replace(['{diller}','{ism}','{nomer}','{operator}','{tarif}'],[$dname,$name,$pretty,$op,$tar],$tpl);
    sendToChannel($txt);
   } $add++;
  }catch(Exception $e){ $skipped++; }
 }
 return [$add,$skipped];
}
?>