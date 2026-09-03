<?php
include 'layout_header.php';
if(!$isSuper) exit;

$msg = '';
if($_SERVER['REQUEST_METHOD']=='POST'){
 foreach(['channel','bot_token','template','template_winner','admin_chat_id','daily_limit_count','admin_chat_link','session_timeout_min','report_group','report_bot_token'] as $k){
  if(isset($_POST[$k])){
   try{
    $st=db()->prepare("INSERT INTO settings (skey,svalue) VALUES (?,?) ON DUPLICATE KEY UPDATE svalue=VALUES(svalue)");
    $st->execute([$k, $_POST[$k]]);
   }catch(Exception $e){}
  }
 }
 try{
  $st=db()->prepare("INSERT INTO settings (skey,svalue) VALUES ('daily_limit_enabled',?) ON DUPLICATE KEY UPDATE svalue=VALUES(svalue)");
  $st->execute([isset($_POST['daily_limit_enabled']) ? '1' : '0']);
 }catch(Exception $e){}
 try{
  $st=db()->prepare("INSERT INTO settings (skey,svalue) VALUES ('baza_sends_channel',?) ON DUPLICATE KEY UPDATE svalue=VALUES(svalue)");
  $st->execute([isset($_POST['baza_sends_channel']) ? '1' : '0']);
 }catch(Exception $e){}
 $msg = "✅ Saqlandi! Endi yangi xabarlar shu shablonda ketadi";
}

$channel = getSetting('channel');
$bot_token = getSetting('bot_token');
$template = getSetting('template');
$template_winner = getSetting('template_winner');
$admin_chat_id = getSetting('admin_chat_id');
$admin_chat_link = getSetting('admin_chat_link');
$daily_limit_enabled = getSetting('daily_limit_enabled')=='1';
$daily_limit_count = getSetting('daily_limit_count') ?: '20';
$baza_sends_channel = getSetting('baza_sends_channel')=='1';
$session_timeout_min = getSetting('session_timeout_min') ?: '0';
$report_group = getSetting('report_group');
$report_bot_token = getSetting('report_bot_token');
$cron_token = getSetting('cron_token');
if($cron_token===''){ $cron_token=bin2hex(random_bytes(8)); try{ db()->prepare("INSERT INTO settings (skey,svalue) VALUES ('cron_token',?) ON DUPLICATE KEY UPDATE svalue=VALUES(svalue)")->execute([$cron_token]); }catch(Exception $e){} }
$cronUrl = (isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off'?'https':'http').'://'.($_SERVER['HTTP_HOST']??'').rtrim(dirname($_SERVER['PHP_SELF']),'/').'/api.php?action=cron_daily&token='.$cron_token;

if(!$template) $template = "1. Diller: {diller}
2. Ism: {ism}
3. Nomer: {nomer}
4. Operator: {operator}
5. Tarif: {tarif}";
if(!$template_winner) $template_winner = "✅ TASDIQLANDI!
1. Diller: {diller}
2. Ism: {ism}
3. Nomer: {nomer}
4. Operator: {operator}
5. Tarif: {tarif}";

?>
<h1 class="font-black text-2xl mb-1 flex items-center gap-2"><?php echo icon('gear','w-6 h-6'); ?> Sozlamalar - Kanal shablonini tahrirlash</h1>
<p class="text-white/30 text-xs mb-4">Kanalga boradigan xabarni o'zing xohlagandek yozib o'zgartirasan</p>

<?php if($msg): ?><div class="card p-4 mb-4 bg-[#7c6cff]/10 border border-[#7c6cff]/20 text-[#7c6cff] text-sm font-bold"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

<div class="grid lg:grid-cols-2 gap-4">
<div class="card p-5">
<h3 class="font-bold mb-3">📢 Kanal sozlamalari</h3>
<form method="post" class="space-y-4">
<div><label class="text-xs text-white/50">Kanal username (@ bilan)</label><input name="channel" value="<?php echo htmlspecialchars($channel); ?>" placeholder="@paynet_xoliss" class="w-full mt-1 p-3 rounded-xl bg-black/50 border border-white/10 text-white outline-none focus:border-white/20"></div>
<div><label class="text-xs text-white/50">Bot Token</label><input name="bot_token" value="<?php echo htmlspecialchars($bot_token); ?>" placeholder="123456:ABC..." class="w-full mt-1 p-3 rounded-xl bg-black/50 border border-white/10 text-white outline-none focus:border-white/20"></div>
<div><label class="text-xs text-white/50">Bosh admin shaxsiy Telegram Chat ID (ixtiyoriy)</label><p class="text-[10px] text-white/30 mt-0.5 mb-1">Diller yangi nomer qo'shganda shu ID'ga "⏳ Kutilmoqda" haqida shaxsiy xabar boradi. Bo'sh qoldirsangiz xabar yuborilmaydi.</p><input name="admin_chat_id" value="<?php echo htmlspecialchars($admin_chat_id); ?>" placeholder="masalan: 123456789" class="w-full p-3 rounded-xl bg-black/50 border border-white/10 text-white outline-none focus:border-white/20"></div>
<div><label class="text-xs text-white/50">Menyudagi "💬 Chat" tugmasi havolasi (ixtiyoriy)</label><p class="text-[10px] text-white/30 mt-0.5 mb-1">Bo'sh qoldirsangiz, yuqoridagi kanal linkiga (t.me/...) ochiladi.</p><input name="admin_chat_link" value="<?php echo htmlspecialchars($admin_chat_link); ?>" placeholder="https://t.me/username" class="w-full p-3 rounded-xl bg-black/50 border border-white/10 text-white outline-none focus:border-white/20"></div>

<div class="border-t border-white/5 pt-4">
<label class="text-xs font-bold text-white tracking-widest">🔀 "BAZAGA" tugmasi rejimi</label>
<p class="text-[10px] text-white/30 mt-1 mb-2">Bitta/Ko'p qo'shishda "BAZAGA" tugmasi asosiy (birinchi) bo'lib turadi. Bu yerda uning nima qilishini tanlaysiz.<br>• <b>Yoqilgan</b> — BAZAGA bosilganda kanalga yuboriladi.<br>• <b>O'chirilgan</b> — eski usul: O'YINGA bosilganda kanalga yuboriladi, faqat tugmalar o'rni almashgan (BAZAGA asosiyda turadi).</p>
<label class="flex items-center gap-2 cursor-pointer bg-black/30 p-3 rounded-xl"><input type="checkbox" name="baza_sends_channel" value="1" <?php echo $baza_sends_channel?'checked':''; ?> class="w-5 h-5 accent-[#7c6cff]"><span class="text-sm font-bold">BAZAGA bosilganda kanalga yuborilsin</span></label>
</div>

<div class="border-t border-white/5 pt-4">
<label class="text-xs font-bold text-[#7c6cff] tracking-widest">📝 1. Yangi ishtirokchi qo'shilganda kanalga boradigan xabar</label>
<p class="text-[10px] text-white/30 mt-1 mb-2">Kodlar: {diller} {ism} {nomer} {operator} {tarif}</p>
<textarea name="template" rows="6" class="w-full p-3 rounded-xl bg-black/50 border border-white/10 text-white font-mono text-sm outline-none focus:border-[#7c6cff]/50"><?php echo htmlspecialchars($template); ?></textarea>
</div>

<div class="border-t border-white/5 pt-4">
<label class="text-xs font-bold text-white tracking-widest">✅ 2. G'olib TASDIQLANGANDA kanalga boradigan xabar</label>
<p class="text-[10px] text-white/30 mt-1 mb-2">Kodlar: {diller} {ism} {nomer} {operator} {tarif}</p>
<textarea name="template_winner" rows="6" class="w-full p-3 rounded-xl bg-black/50 border border-white/10 text-white font-mono text-sm outline-none focus:border-white/20"><?php echo htmlspecialchars($template_winner); ?></textarea>
</div>

<div class="border-t border-white/5 pt-4">
<label class="text-xs font-bold text-white tracking-widest">🚦 Diller uchun kunlik qo'shish limiti</label>
<p class="text-[10px] text-white/30 mt-1 mb-2">Yoqilsa, har bir diller kuniga faqat shu sondagi nomer qo'sha oladi</p>
<div class="flex items-center gap-3 bg-black/30 p-3 rounded-xl">
<label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" name="daily_limit_enabled" value="1" <?php echo $daily_limit_enabled?'checked':''; ?> class="w-5 h-5 accent-[#7c6cff]"><span class="text-sm font-bold">Yoqish / O'chirish</span></label>
<input type="number" min="1" name="daily_limit_count" value="<?php echo htmlspecialchars($daily_limit_count); ?>" class="ml-auto w-24 p-2 rounded-lg bg-black/50 border border-white/10 text-white text-center outline-none">
<span class="text-xs text-white/40">ta / kuniga</span>
</div>
</div>
<button class="w-full bg-white text-black p-4 rounded-xl font-black tracking-widest btn-glow">💾 SAQLASH - SHABLONNI YANGILASH</button>
</form>
</div>

<div class="space-y-4">
<div class="card p-5 border-[#7c6cff]/30">
<h3 class="font-bold mb-1 text-sm">📣 Hisobot guruhi (Telegram)</h3>
<p class="text-[11px] text-white/40 mb-3">Hisobotlar shu <b>guruhga</b> boradi (kanaldan alohida). Botni guruhga <b>admin</b> qiling, guruhga bitta xabar yozing, so'ng <b>"Guruh ID aniqlash"</b> tugmasini bosing — ID o'zi topiladi.</p>
<form method="post" class="space-y-3">
<div><label class="text-xs text-white/50">Guruh ID yoki @username</label><input name="report_group" id="rg" value="<?php echo htmlspecialchars($report_group); ?>" placeholder="-1001234567890 yoki @guruh" class="w-full mt-1 p-3 rounded-xl bg-black/50 border border-white/10 text-white outline-none focus:border-[#7c6cff]/50 font-mono text-sm"></div>
<div><label class="text-xs text-white/50">Hisobot bot tokeni (ixtiyoriy — bo'sh qoldirsangiz asosiy bot ishlatiladi)</label><input name="report_bot_token" value="<?php echo htmlspecialchars($report_bot_token); ?>" placeholder="123456:ABC..." class="w-full mt-1 p-3 rounded-xl bg-black/50 border border-white/10 text-white outline-none focus:border-[#7c6cff]/50 font-mono text-xs"></div>
<div class="flex gap-2 flex-wrap">
<button class="btn btn-primary btn-sm">💾 Saqlash</button>
<button type="button" onclick="detectGroup(this)" class="btn btn-ghost btn-sm">🔎 Guruh ID aniqlash</button>
</div>
</form>
<div class="border-t border-white/5 pt-3 mt-3">
<p class="text-xs text-white/50 mb-2">📤 Guruhga qo'lda hisobot (00:00 dan oldin ham) — <b>istalgan davr</b></p>
<div class="space-y-2">
 <div class="flex gap-2 flex-wrap items-center"><input type="date" id="rdate" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" class="p-2 rounded-lg bg-black/50 border border-white/10 text-white text-sm outline-none"><button type="button" onclick="sendGroupReport('day')" class="btn btn-primary btn-sm">📅 Kunlik</button></div>
 <div class="flex gap-2 flex-wrap items-center"><input type="month" id="rmonth" value="<?php echo date('Y-m'); ?>" class="p-2 rounded-lg bg-black/50 border border-white/10 text-white text-sm outline-none"><button type="button" onclick="sendGroupReport('month')" class="btn btn-ghost btn-sm">🗓 Oylik</button></div>
 <div class="flex gap-2 flex-wrap items-center"><input type="date" id="rfrom" max="<?php echo date('Y-m-d'); ?>" class="p-2 rounded-lg bg-black/50 border border-white/10 text-white text-sm outline-none"><span class="text-white/30 text-xs">—</span><input type="date" id="rto" max="<?php echo date('Y-m-d'); ?>" class="p-2 rounded-lg bg-black/50 border border-white/10 text-white text-sm outline-none"><button type="button" onclick="sendGroupReport('range')" class="btn btn-ghost btn-sm">📆 Oraliq</button></div>
 <button type="button" onclick="sendGroupReport('all')" class="btn btn-ghost btn-sm">♾️ Hammasi (butun davr)</button>
</div>
<p id="rgmsg" class="text-[11px] mt-2"></p>
</div>
</div>
<div class="card p-5 border-[#7c6cff]/20">
<h3 class="font-bold mb-3 text-sm">🛠 Ma'lumot va xavfsizlik</h3>
<div class="space-y-3">
<div>
<p class="text-xs text-white/50 mb-2">📤 Hisobot va zaxira</p>
<div class="flex gap-2 flex-wrap">
<a href="api.php?action=backup" class="btn btn-ghost btn-sm">💾 Backup (JSON)</a>
<a href="api.php?action=export_csv" class="btn btn-ghost btn-sm">📊 Hammasi Excel</a>
</div>
<p class="text-[10px] text-white/30 mt-1">Hisobotlar (kunlik/oylik/hammasi) yuqoridagi <b>Hisobot guruhi</b> bo'limidan guruhga yuboriladi.</p>
</div>
<form method="post" class="border-t border-white/5 pt-3">
<label class="text-xs text-white/50">⏱ Sessiya muddati (daqiqa) — 0 = cheksiz</label>
<p class="text-[10px] text-white/30 mt-0.5 mb-1">Belgilangan vaqt harakatsizlikdan so'ng avtomatik chiqib ketadi.</p>
<div class="flex gap-2">
<input type="number" min="0" name="session_timeout_min" value="<?php echo htmlspecialchars($session_timeout_min); ?>" class="w-28 p-2 rounded-lg bg-black/50 border border-white/10 text-white text-center outline-none">
<button class="btn btn-ghost btn-sm">Saqlash</button>
</div>
</form>
<div class="border-t border-white/5 pt-3">
<p class="text-xs text-white/50 mb-1">🌙 Har kuni 00:00 — avtomatik guruh hisoboti (cron)</p>
<p class="text-[10px] text-white/30 mb-2">Hosting <b>"Cron Jobs"</b> bo'limida quyidagi manzilni har kuni <b>00:00</b> da chaqiring (jadval: <span class="font-mono text-white/50">0 0 * * *</span>). O'tgan kun hisoboti (matn + Excel fayllar) avtomatik <b>guruhga</b> ketadi:</p>
<input onclick="this.select()" readonly value="<?php echo htmlspecialchars($cronUrl); ?>" class="w-full p-2 rounded-lg bg-black/50 border border-white/10 text-[#7c6cff] text-[11px] font-mono outline-none">
</div>
</div>
</div>
<div class="card p-5">
<h3 class="font-bold mb-3 text-sm">💡 Qanday ishlatish</h3>
<div class="bg-black/30 p-3 rounded-xl text-xs space-y-1 font-mono">
<p>{diller} = Diller ismi (Boxodir)</p>
<p>{ism} = Ishtirokchi ismi</p>
<p>{nomer} = Telefon (+998...)</p>
<p>{operator} = Operator (Humans)</p>
<p>{tarif} = Tarif (30 000 UZS)</p>
</div>
<p class="text-[11px] text-white/30 mt-3">Misol:</p>
<div class="bg-[#7c6cff]/5 border border-[#7c6cff]/10 p-3 rounded-xl text-xs mt-2 whitespace-pre">🎉 Yangi ishtirokchi!
1. Diller: {diller}
2. Ism: {ism}
3. Nomer: {nomer}
4. Operator: {operator}
5. Tarif: {tarif}</div>
</div>

<div class="card p-5">
<h3 class="font-bold mb-3 text-sm">👁️ Hozirgi shablon qanday ko'rinadi</h3>
<div class="bg-black p-4 rounded-xl text-xs whitespace-pre-wrap border border-white/10"><?php echo htmlspecialchars($template); ?></div>
<p class="text-[10px] text-white/30 mt-3">Misol natija:</p>
<div class="bg-white/5 p-3 rounded-xl text-xs mt-1 whitespace-pre">1. Diller: Boxodir
2. Ism: Boxodir Olloxberdi
3. Nomer: +998 33 702 20 21
4. Operator: Humans
5. Tarif: 30 000 UZS</div>
</div>
</div>
</div>
<script>
function sendMonthReport(btn){ if(!confirm("Bu oy hisoboti Telegram kanalga yuborilsinmi?")) return; btn.disabled=true; var o=btn.textContent; btn.textContent='⏳...'; fetch('api.php?action=send_month_report').then(function(r){return r.json();}).then(function(d){ btn.disabled=false; btn.textContent=o; alert(d.ok?'✅ Yuborildi!':('⚠️ '+(d.msg||'Xatolik'))); }).catch(function(){ btn.disabled=false; btn.textContent=o; alert('⚠️ Tarmoq xatosi'); }); }
function detectGroup(btn){ btn.disabled=true; var o=btn.textContent; btn.textContent='⏳ Aniqlanmoqda...'; fetch('api.php?action=detect_group').then(function(r){return r.json();}).then(function(d){ btn.disabled=false; btn.textContent=o; if(d.ok){ document.getElementById('rg').value=d.id; alert('✅ Guruh topildi: '+d.id+'\nSaqlash tugmasini bosishga hojat yo\'q, avtomatik saqlandi.'); } else { alert('⚠️ '+(d.msg||'Topilmadi')); } }).catch(function(){ btn.disabled=false; btn.textContent=o; alert('⚠️ Tarmoq xatosi'); }); }
function sendGroupReport(mode){
 var q='action=send_group_report&mode='+mode;
 if(mode==='day'){ q+='&date='+encodeURIComponent(document.getElementById('rdate').value); }
 else if(mode==='month'){ q+='&ym='+encodeURIComponent(document.getElementById('rmonth').value); }
 else if(mode==='range'){ var f=document.getElementById('rfrom').value,t=document.getElementById('rto').value; if(!f||!t){ alert('Oraliq uchun ikkala sanani tanlang'); return; } q+='&from='+encodeURIComponent(f)+'&to='+encodeURIComponent(t); }
 var labels={day:'Kunlik',month:'Oylik',range:'Oraliq',all:'Hammasi (butun davr)'};
 if(!confirm(labels[mode]+" hisobot guruhga yuborilsinmi? (matn + Excel)")) return;
 var m=document.getElementById('rgmsg'); m.style.color=''; m.textContent='⏳ Yuborilmoqda... (bir necha soniya)';
 fetch('api.php?'+q).then(function(r){return r.json();}).then(function(d){ m.style.color=d.ok?'#7c6cff':'#f87171'; m.textContent=d.ok?'✅ Guruhga yuborildi (matn + Excel)!':('⚠️ '+(d.msg||'Xatolik')); }).catch(function(){ m.style.color='#f87171'; m.textContent='⚠️ Tarmoq xatosi'; });
}
</script>
<?php include 'layout_footer.php'; ?>