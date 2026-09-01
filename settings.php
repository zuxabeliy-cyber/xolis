<?php
include 'layout_header.php';
if(!$isSuper) exit;

$msg = '';
if($_SERVER['REQUEST_METHOD']=='POST'){
 foreach(['channel','bot_token','template','template_winner','admin_chat_id','daily_limit_count','admin_chat_link','ai_api_key','ai_model'] as $k){
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
$ai_api_key = getSetting('ai_api_key');
$ai_model = getSetting('ai_model') ?: 'claude-opus-4-8';

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
<div class="card p-5 border-[#7c6cff]/25">
<h3 class="font-bold mb-1 text-sm flex items-center gap-2">🤖 AI yordamchi sozlamalari</h3>
<p class="text-[11px] text-white/30 mb-3">Anthropic (Claude) API kalitini kiriting — shundan keyin menyudagi "AI yordamchi" ishlaydi. Kalit <b>console.anthropic.com</b> saytidan olinadi. Kalitsiz AI ishlamaydi.</p>
<form method="post" class="space-y-3">
<div><label class="text-xs text-white/50">Anthropic API kalit</label><input name="ai_api_key" type="text" value="<?php echo htmlspecialchars($ai_api_key); ?>" placeholder="sk-ant-..." class="w-full mt-1 p-3 rounded-xl bg-black/50 border border-white/10 text-white outline-none focus:border-[#7c6cff]/50 font-mono text-xs"></div>
<div><label class="text-xs text-white/50">Model</label>
<select name="ai_model" class="w-full mt-1 p-3 rounded-xl bg-black/50 border border-white/10 text-white outline-none focus:border-[#7c6cff]/50">
<?php foreach(['claude-opus-4-8'=>'Claude Opus 4.8 (kuchli)','claude-sonnet-5'=>'Claude Sonnet 5 (tez, arzon)','claude-haiku-4-5'=>'Claude Haiku 4.5 (eng tez)'] as $mv=>$ml): ?>
<option value="<?php echo $mv; ?>" <?php echo $ai_model===$mv?'selected':''; ?>><?php echo $ml; ?></option>
<?php endforeach; ?>
</select></div>
<button class="w-full bg-[#7c6cff] text-white p-3 rounded-xl font-black tracking-widest btn-glow">💾 AI SOZLAMASINI SAQLASH</button>
<p class="text-[11px] <?php echo $ai_api_key?'text-[#7c6cff]':'text-white/30'; ?>"><?php echo $ai_api_key ? '✅ Kalit kiritilgan — AI yordamchi tayyor.' : '⚠️ Kalit hali kiritilmagan.'; ?></p>
</form>
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
<?php include 'layout_footer.php'; ?>