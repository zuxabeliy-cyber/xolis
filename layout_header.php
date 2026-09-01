<?php require_once __DIR__.'/config.php'; requireLogin(); $u=$_SESSION['user']; $isSuper=($u['role']=='super'); $myCount=0; try{ $s=db()->prepare("SELECT COUNT(*) FROM paid_participants WHERE dealer_id=?"); $s->execute([$u['id']]); $myCount=$s->fetchColumn(); }catch(Exception $e){} $displayRole = $isSuper ? 'BOSH ADMIN' : 'DILLER'; $current = basename($_SERVER['PHP_SELF']); function navClass($file){ global $current; return $current==$file ? 'bg-white/[0.07] border-white/10 text-white shadow-[inset_2px_0_0_0_#1fae76]' : 'hover:bg-white/[0.04] border-transparent text-white/60 hover:text-white'; }
function icon($name,$class='w-4 h-4'){
 $p=[
  'menu'=>'<path d="M3 6h18M3 12h18M3 18h18"/>',
  'chart'=>'<path d="M4 20V10M12 20V4M20 20v-7"/>',
  'list'=>'<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
  'wallet'=>'<path d="M3 7a2 2 0 0 1 2-2h13a1 1 0 0 1 1 1v3"/><path d="M3 7v10a2 2 0 0 0 2 2h14a1 1 0 0 0 1-1v-4"/><path d="M17 12h3a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-3a2 2 0 0 1 0-4Z"/>',
  'dial'=>'<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="3"/><path d="M12 3v2M12 19v2"/>',
  'plus'=>'<path d="M12 5v14M5 12h14"/>',
  'package'=>'<path d="M21 8 12 3 3 8v8l9 5 9-5Z"/><path d="M3 8l9 5 9-5M12 13v8"/>',
  'trophy'=>'<path d="M8 4h8v4a4 4 0 0 1-8 0V4Z"/><path d="M8 5H5a2 2 0 0 0 0 4h1M16 5h3a2 2 0 0 1 0 4h-1"/><path d="M12 13v3M9 20h6M9.5 16.5c0 1.2 1 2 2.5 2s2.5-.8 2.5-2"/>',
  'message'=>'<path d="M4 4h16v12H8l-4 4V4Z"/>',
  'users'=>'<circle cx="9" cy="8" r="3"/><path d="M3 20c0-3 2.7-5 6-5s6 2 6 5"/><circle cx="17" cy="9" r="2.3"/><path d="M15.5 20c.2-2.2 1.8-4 4.5-4"/>',
  'radio'=>'<circle cx="12" cy="12" r="2.3"/><path d="M8.5 8.5a5 5 0 0 0 0 7M15.5 8.5a5 5 0 0 1 0 7M5.5 5.5a9 9 0 0 0 0 13M18.5 5.5a9 9 0 0 1 0 13"/>',
  'gear'=>'<circle cx="12" cy="12" r="3"/><path d="M19 12a7 7 0 0 0-.1-1.2l2-1.5-2-3.4-2.3.8a7 7 0 0 0-2-1.2L14 3h-4l-.6 2.5a7 7 0 0 0-2 1.2l-2.3-.8-2 3.4 2 1.5A7 7 0 0 0 5 12c0 .4 0 .8.1 1.2l-2 1.5 2 3.4 2.3-.8a7 7 0 0 0 2 1.2L10 21h4l.6-2.5a7 7 0 0 0 2-1.2l2.3.8 2-3.4-2-1.5c.1-.4.1-.8.1-1.2Z"/>',
  'user'=>'<circle cx="12" cy="8" r="3.5"/><path d="M5 20c0-3.5 3-6 7-6s7 2.5 7 6"/>',
  'logout'=>'<path d="M9 4H5a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h4M16 16l4-4-4-4M20 12H9"/>',
  'crown'=>'<path d="m3 8 3 3 5-6 5 6 3-3-1.5 10h-13L3 8Z"/>',
  'shield'=>'<path d="M12 3 5 6v5c0 4.5 3 7.5 7 9 4-1.5 7-4.5 7-9V6l-7-3Z"/>',
  'download'=>'<path d="M12 4v11M8 11l4 4 4-4M4 19h16"/>',
  'print'=>'<path d="M6 9V4h12v5M6 18h12v-6H6v6ZM6 14H4a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-2"/>',
  'edit'=>'<path d="M4 20h4L18.5 9.5a2.1 2.1 0 0 0-3-3L5 17v3Z"/><path d="M13.5 7.5l3 3"/>',
  'hourglass'=>'<path d="M6 3h12M6 21h12M7 3c0 5 5 5 5 9s-5 4-5 9M17 3c0 5-5 5-5 9s5 4 5 9"/>',
  'keyboard'=>'<rect x="3" y="6" width="18" height="12" rx="2"/><path d="M7 10h.01M11 10h.01M15 10h.01M17 10h.01M7 14h10"/>',
  'trash'=>'<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/>',
  'undo'=>'<path d="M9 14 4 9l5-5M4 9h11a5 5 0 0 1 0 10h-1"/>',
  'baraban'=>'<circle cx="12" cy="12" r="8"/><path d="M12 4v2M12 18v2"/><circle cx="12" cy="12" r="2.5"/>',
 ];
 $body = $p[$name] ?? $p['dial'];
 return '<svg class="'.$class.' shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">'.$body.'</svg>';
}
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<script src="https://cdn.tailwindcss.com"></script><script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,700;9..144,900&family=Manrope:wght@500;700;800;900&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
<style>
*{scrollbar-width:thin;scrollbar-color:rgba(31,174,118,.35) transparent}
::-webkit-scrollbar{width:8px;height:8px}
::-webkit-scrollbar-thumb{background:rgba(31,174,118,.3);border-radius:8px}
body{background:#0d100c;color:#efece3;font-family:Manrope,system-ui,sans-serif;letter-spacing:-.01em}
h1,h3{font-family:'Fraunces',serif;letter-spacing:-.01em}
h2{font-family:'IBM Plex Mono',monospace !important;letter-spacing:.14em !important}
.card{position:relative;background:linear-gradient(155deg,rgba(23,34,25,.55),rgba(13,16,12,.55));backdrop-filter:blur(14px) saturate(130%);-webkit-backdrop-filter:blur(14px) saturate(130%);border:1px solid rgba(239,236,227,.08);border-radius:20px;box-shadow:0 4px 24px -8px rgba(0,0,0,.55)}
.card::before{content:'';position:absolute;inset:0;border-radius:inherit;padding:1px;background:linear-gradient(155deg,rgba(201,162,75,.16),transparent 40%,transparent 70%,rgba(31,174,118,.14));-webkit-mask:linear-gradient(#fff 0 0) content-box,linear-gradient(#fff 0 0);-webkit-mask-composite:xor;mask-composite:exclude;pointer-events:none}
.card-hover{transition:.35s cubic-bezier(.2,.8,.2,1)}
.card-hover:hover{border-color:rgba(31,174,118,.4);transform:translateY(-3px);box-shadow:0 14px 34px -12px rgba(31,174,118,.28)}
.bg-mesh{background:radial-gradient(at 12% 0%, rgba(31,174,118,.13) 0px, transparent 45%),radial-gradient(at 100% 15%, rgba(201,162,75,.06) 0px, transparent 40%),radial-gradient(at 50% 100%, rgba(11,61,44,.16) 0px, transparent 48%), #0d100c;background-attachment:fixed}
input,select,textarea{color:#fff !important}
input::placeholder{color:rgba(255,255,255,.35) !important}
.btn-glow{box-shadow:0 4px 20px -4px rgba(31,174,118,.35);transition:.2s}
.btn-glow:hover{box-shadow:0 6px 26px -4px rgba(31,174,118,.55);transform:translateY(-1px)}
.grad-text{background:linear-gradient(90deg,#1fae76,#e7c878,#1fae76);background-size:200% auto;-webkit-background-clip:text;background-clip:text;color:transparent;animation:gradflow 6s ease-in-out infinite;font-family:'IBM Plex Mono',monospace}
@keyframes gradflow{ 0%,100%{ background-position:0% center; } 50%{ background-position:100% center; } }

/* === BARABAN — vault dial === */
@keyframes drumspin{ from{ transform:rotate(0deg); } to{ transform:rotate(2160deg); } }
@keyframes drumpulse{
 0%,100%{ box-shadow:0 0 0 0 rgba(201,162,75,.35), 0 0 70px -10px rgba(31,174,118,.35), inset 0 0 40px rgba(0,0,0,.7); }
 50%{ box-shadow:0 0 0 14px rgba(201,162,75,0), 0 0 90px -10px rgba(31,174,118,.5), inset 0 0 40px rgba(0,0,0,.7); }
}
@keyframes drumglow{
 0%,100%{ opacity:.6; }
 50%{ opacity:.95; }
}
@keyframes badgepulse{ 0%,100%{ transform:scale(1); } 50%{ transform:scale(1.15); } }
.badge-live{animation:badgepulse 1.8s ease-in-out infinite}

#drum{
 position:relative; width:320px; height:320px;
 max-width:88vw; max-height:88vw;
 margin:0 auto; border-radius:50%;
 background:#0d100c;
 border:3px solid #c9a24b;
 display:flex; align-items:center; justify-content:center;
 overflow:hidden;
 box-shadow:0 0 80px -15px rgba(31,174,118,.35), inset 0 0 50px rgba(0,0,0,.75), 0 0 0 9px rgba(201,162,75,.1), 0 0 0 10px rgba(201,162,75,.35);
 transition:.3s;
}
#drum::before{
 content:''; position:absolute; inset:0; border-radius:50%; z-index:0;
 background:
  repeating-conic-gradient(from 0deg, rgba(239,236,227,.9) 0deg 1.2deg, transparent 1.2deg 9deg),
  conic-gradient(from 0deg, #1fae76 0%, #172219 28%, #0d100c 50%, #c9a24b 75%, #172219 92%, #1fae76 100%);
 -webkit-mask:radial-gradient(circle, transparent 63%, #000 64%, #000 100%);
 mask:radial-gradient(circle, transparent 63%, #000 64%, #000 100%);
 opacity:.7;
}
#drum::after{
 content:''; position:absolute; top:-3px; left:50%; translate:-50% 0; z-index:2;
 width:0; height:0; border-left:9px solid transparent; border-right:9px solid transparent; border-top:14px solid #c9a24b;
 filter:drop-shadow(0 2px 4px rgba(0,0,0,.6));
}
#drum.spinning{ animation:drumpulse 1.1s ease-in-out infinite; }
#drum.spinning::before{ animation:drumspin 15s cubic-bezier(.15,.65,.1,1) forwards, drumglow 1.1s ease-in-out infinite; opacity:1; }
#drum .drum-inner{
 position:relative; z-index:1;
 width:calc(100% - 44px); height:calc(100% - 44px);
 border-radius:50%;
 background:radial-gradient(circle at 40% 35%, #172219, #0d100c 70%);
 display:flex; flex-direction:column; align-items:center; justify-content:center;
 text-align:center; padding:20px;
 border:1px solid rgba(201,162,75,.25);
}
#drum .drum-name{ font-size:1.2rem; font-weight:900; line-height:1.2; word-break:break-word; max-width:90%; font-family:'Fraunces',serif; }
#drum .drum-phone{ font-size:.85rem; opacity:.55; margin-top:8px; font-family:'IBM Plex Mono',monospace; }

/* Umumiy animatsiyalar */
@keyframes fadeUp{ from{ opacity:0; transform:translateY(16px); } to{ opacity:1; transform:translateY(0); } }
@keyframes fadeIn{ from{ opacity:0; } to{ opacity:1; } }
@keyframes floaty{ 0%,100%{ transform:translateY(0); } 50%{ transform:translateY(-4px); } }
.card{ animation:fadeUp .6s cubic-bezier(.2,.8,.2,1) both; }
.card:nth-of-type(2n){ animation-delay:.05s; }
.card:nth-of-type(3n){ animation-delay:.1s; }
.anim-logo{ animation:floaty 3.2s ease-in-out infinite; }
a,button{ transition:.25s cubic-bezier(.2,.8,.2,1); }
button:active,.btn-glow:active{ transform:scale(.96); }
#m.slide-in{ animation:fadeIn .2s; }
nav a{ transition:.25s cubic-bezier(.2,.8,.2,1); }
nav a:hover{ transform:translateX(4px); background:rgba(31,174,118,.06); }
::view-transition-old(root),::view-transition-new(root){ animation-duration:.25s; }
::selection{ background:rgba(31,174,118,.35); color:#fff; }
@keyframes shimmer{ 0%{ background-position:-200% 0; } 100%{ background-position:200% 0; } }
.shimmer-border{ position:relative; }
.shimmer-border::after{ content:''; position:absolute; inset:0; border-radius:inherit; padding:1px; background:linear-gradient(90deg,transparent,rgba(31,174,118,.5),transparent); background-size:200% 100%; -webkit-mask:linear-gradient(#fff 0 0) content-box,linear-gradient(#fff 0 0); -webkit-mask-composite:xor; mask-composite:exclude; animation:shimmer 3s linear infinite; pointer-events:none; }
.sticky.top-0{ animation:fadeIn .4s; }

/* === Jadvallar (sitewide) === */
table{width:100%; border-collapse:separate; border-spacing:0; font-size:.85rem}
table th{ font-family:'IBM Plex Mono',monospace; font-weight:600; font-size:.65rem; letter-spacing:.1em; text-transform:uppercase; color:rgba(239,236,227,.4); text-align:left; padding:12px 14px; border-bottom:1px solid rgba(201,162,75,.18); white-space:nowrap; background:rgba(201,162,75,.04); }
table td{ padding:12px 14px; border-bottom:1px solid rgba(255,255,255,.055); vertical-align:middle; }
table tr{ transition:background .18s ease; }
table tr:hover{ background:rgba(31,174,118,.06); }
table tr:last-child td{ border-bottom:none; }

/* === Tugma va nishonchalar === */
.btn-glow{ position:relative; overflow:hidden; }
.btn-glow::after{ content:''; position:absolute; inset:0; background:linear-gradient(115deg,transparent 30%,rgba(255,255,255,.22) 45%,transparent 60%); background-size:250% 250%; background-position:120% 0; transition:background-position .6s ease; pointer-events:none; }
.btn-glow:hover::after{ background-position:-20% 0; }
::placeholder{opacity:.5}

/* === Konfetti === */
.confetti-piece{ position:fixed; top:-12px; z-index:9999; pointer-events:none; border-radius:2px; will-change:transform,opacity; }
@keyframes confettiFall{ to{ transform:translateY(105vh) rotate(var(--rot)); opacity:.15; } }
@media (prefers-reduced-motion:reduce){ *{ animation-duration:.001ms !important; animation-iteration-count:1 !important; transition-duration:.001ms !important; } }
</style>
</head><body class="bg-mesh min-h-screen">
<div class="sticky top-0 z-30 bg-[#0d100c]/85 backdrop-blur-xl border-b border-white/5 p-3 flex justify-between items-center">
<div class="flex items-center gap-3"><button onclick="document.getElementById('m').classList.remove('-translate-x-full');document.getElementById('o').classList.remove('hidden')" class="w-11 h-11 bg-white/5 rounded-xl border border-white/10 relative flex items-center justify-center text-white/70"><?php echo icon('menu','w-5 h-5'); ?></button><img src="logo.png" class="anim-logo w-10 h-10 object-contain drop-shadow-[0_0_10px_rgba(31,174,118,.35)]"><div><div class="flex items-center gap-1.5"><h1 class="font-bold text-[14px] tracking-wide"><?php echo htmlspecialchars($u['name']); ?></h1><span class="inline-flex items-center gap-1 text-[8px] font-bold px-1.5 py-[2px] rounded-full <?php echo $isSuper ? 'bg-[#c9a24b]/10 text-[#c9a24b] border border-[#c9a24b]/20' : 'bg-[#1fae76]/10 text-[#1fae76] border border-[#1fae76]/20'; ?>"><?php echo icon($isSuper?'crown':'shield','w-2.5 h-2.5'); ?><?php echo $isSuper ? 'Admin' : 'Diller'; ?></span><a href="chat.php" title="Telegram" class="relative w-6 h-6 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center text-white/60 shrink-0"><?php echo icon('message','w-3.5 h-3.5'); ?><span id="chatDot" class="hidden absolute -top-1 -right-1 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-[#0d100c] badge-live"></span></a></div><p class="text-[9px] text-white/25 tracking-[0.15em] mt-0.5"><span class="grad-text">PAYNET XOLIS</span></p></div></div>
<div class="flex items-center gap-2"><div class="w-9 h-9 rounded-full bg-gradient-to-br from-[#1fae76] to-[#0b3d2c] ring-1 ring-[#c9a24b]/40 flex items-center justify-center font-black text-white text-sm"><?php echo mb_strtoupper(mb_substr($u['name'],0,1)); ?></div></div>
</div>
<div id="o" onclick="this.classList.add('hidden');document.getElementById('m').classList.add('-translate-x-full')" class="fixed inset-0 bg-black/60 z-40 hidden"></div>
<div id="m" class="fixed top-0 left-0 h-full w-[300px] bg-[#0d100c] border-r border-white/5 z-50 transform -translate-x-full transition-transform duration-300 overflow-auto">
<div class="p-5 bg-[#172219] border-b border-white/5 flex items-center justify-between">
<div class="flex gap-3 items-center"><img src="logo.png" class="w-12 h-12 object-contain"><div><div class="flex items-center gap-1.5"><p class="font-bold"><?php echo htmlspecialchars($u['name']); ?></p><span class="inline-flex items-center text-[#c9a24b] <?php echo $isSuper?'':'text-[#1fae76]'; ?>"><?php echo icon($isSuper?'crown':'shield','w-3.5 h-3.5'); ?></span></div><p class="text-xs font-bold text-[#1fae76]" style="font-family:'IBM Plex Mono',monospace;letter-spacing:.1em"><?php echo $isSuper ? 'BOSH ADMIN' : 'DILLER'; ?></p></div></div>
<a href="logout.php" title="Chiqish" class="w-9 h-9 rounded-xl bg-red-500/10 border border-red-500/15 text-red-300 flex items-center justify-center shrink-0"><?php echo icon('logout','w-4 h-4'); ?></a>
</div>
<nav class="p-3 space-y-1">
<a href="reports.php" class="flex gap-3 p-3 rounded-xl border items-center <?php echo navClass('reports.php'); ?>"><?php echo icon('chart'); ?> Statistika</a>
<a href="participants.php" class="flex gap-3 p-3 rounded-xl border items-center <?php echo navClass('participants.php'); ?>"><?php echo icon('list'); ?> Ro'yxat</a>
<a href="balance.php" class="flex gap-3 p-3 rounded-xl border items-center <?php echo navClass('balance.php'); ?>"><?php echo icon('wallet'); ?> So'm</a>
<?php if($isSuper): ?><a href="index.php" class="flex gap-3 p-3 rounded-xl border items-center <?php echo navClass('index.php'); ?>"><?php echo icon('baraban'); ?> Baraban</a><?php endif; ?>
<a href="add.php" class="flex gap-3 p-3 rounded-xl border items-center <?php echo navClass('add.php'); ?>"><?php echo icon('plus'); ?> Qo'shish</a>
<?php if($isSuper): ?><a href="bulk_add.php" class="flex gap-3 p-3 rounded-xl border items-center <?php echo navClass('bulk_add.php'); ?>"><?php echo icon('package'); ?> ALL+</a>
<a href="winners.php" class="flex gap-3 p-3 rounded-xl border items-center <?php echo navClass('winners.php'); ?>"><?php echo icon('trophy'); ?> G'oliblar</a><?php endif; ?>
<a href="chat.php" class="flex gap-3 p-3 rounded-xl border justify-between items-center <?php echo navClass('chat.php'); ?>"><span class="flex gap-3 items-center"><?php echo icon('message'); ?> Telegram</span></a>
<?php if($isSuper): ?><a href="dealers.php" class="flex gap-3 p-3 rounded-xl border items-center <?php echo navClass('dealers.php'); ?>"><?php echo icon('users'); ?> Dillerlar</a>
<a href="operators.php" class="flex gap-3 p-3 rounded-xl border items-center <?php echo navClass('operators.php'); ?>"><?php echo icon('radio'); ?> Tarif</a>
<a href="settings.php" class="flex gap-3 p-3 rounded-xl border items-center <?php echo navClass('settings.php'); ?>"><?php echo icon('gear'); ?> Sozlama</a><?php endif; ?>
<a href="profile.php" class="flex gap-3 p-3 rounded-xl border items-center <?php echo navClass('profile.php'); ?>"><?php echo icon('user'); ?> Profil</a>
</nav></div>
<script>
function pollChatDot(){
 fetch('chat.php?action=unread_count').then(function(r){ return r.json(); }).then(function(d){
  var dot=document.getElementById('chatDot'); if(!dot) return;
  if(d.count>0){ dot.classList.remove('hidden'); } else { dot.classList.add('hidden'); }
 }).catch(function(){});
}
pollChatDot();
setInterval(pollChatDot, 15000);

function confettiBurst(x, y){
 if(window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
 x = x || window.innerWidth/2; y = y || window.innerHeight/2;
 var colors=['#1fae76','#c9a24b','#e7c878','#37c98b','#efece3'];
 for(var i=0;i<70;i++){
  var el=document.createElement('div');
  el.className='confetti-piece';
  var size=4+Math.random()*6;
  el.style.width=size+'px'; el.style.height=(size*(Math.random()>.5?1:2.2))+'px';
  el.style.background=colors[Math.floor(Math.random()*colors.length)];
  el.style.left=(x+(Math.random()-.5)*140)+'px'; el.style.top=(y-10)+'px';
  var rot=(Math.random()*720-360)+'deg';
  el.style.setProperty('--rot', rot);
  var dur=1.8+Math.random()*1.4;
  el.style.animation='confettiFall '+dur+'s cubic-bezier(.2,.6,.4,1) forwards';
  el.style.transform='translateY(0) rotate(0deg)';
  el.style.opacity='1';
  document.body.appendChild(el);
  (function(node,d){ setTimeout(function(){ node.remove(); }, d*1000+50); })(el,dur);
 }
}
</script>
<div class="max-w-7xl mx-auto p-4">