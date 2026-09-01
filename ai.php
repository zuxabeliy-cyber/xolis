<?php include 'layout_header.php'; if(!$isSuper) exit;
$hasKey = aiEnabled();
?>
<div class="flex items-center justify-between gap-2 mb-3 flex-wrap">
 <div>
  <h1 class="font-black text-xl flex items-center gap-2">🤖 AI yordamchi</h1>
  <p class="text-white/30 text-xs mt-1">Tizim bo'yicha savol bering — statistika, dillerlar, nomerlar, ko'rsatmalar. O'zbek tilida javob beradi.</p>
 </div>
 <a href="settings.php" class="bg-white/5 border border-white/10 px-3 py-2 rounded-xl text-xs font-bold">⚙️ AI sozlamasi</a>
</div>

<?php if(!$hasKey): ?>
<div class="card p-8 text-center border-[#f5a623]/25">
 <p class="text-4xl mb-3">🔑</p>
 <p class="font-bold text-[#f5a623] mb-1">AI kaliti kiritilmagan</p>
 <p class="text-white/40 text-sm mb-4">AI yordamchi ishlashi uchun Sozlamalarda Anthropic (Claude) API kalitini kiriting.</p>
 <a href="settings.php" class="inline-block bg-[#7c6cff] text-white px-5 py-3 rounded-xl font-black">⚙️ Sozlamalarga o'tish</a>
</div>
<?php else: ?>

<div class="card flex flex-col" style="height:min(70vh,640px)">
 <div id="chatBox" class="flex-1 overflow-auto p-4 space-y-3">
  <div class="flex gap-2">
   <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#7c6cff] to-[#241b52] flex items-center justify-center shrink-0 text-sm">🤖</div>
   <div class="bg-white/5 border border-white/10 rounded-2xl rounded-tl-sm p-3 text-sm max-w-[85%]">Assalomu alaykum! Men PAYNET XOLIS yordamchisiman. Nimani bilmoqchisiz? Masalan: <b>"Bu oy nechta nomer qo'shildi?"</b> yoki <b>"Eng ko'p ulagan diller kim?"</b></div>
  </div>
 </div>
 <div class="p-3 border-t border-white/10">
  <div id="chips" class="flex gap-2 flex-wrap mb-2">
   <?php foreach(["Bu oy nechta ishtirokchi bor?","Eng ko'p ulagan diller kim?","Operatorlar bo'yicha holat qanday?","Kutilayotgan nomerlar nechta?"] as $c): ?>
   <button type="button" class="chip bg-white/5 border border-white/10 hover:bg-[#7c6cff]/15 px-3 py-1.5 rounded-full text-[11px] text-white/60"><?php echo htmlspecialchars($c); ?></button>
   <?php endforeach; ?>
  </div>
  <form id="chatForm" class="flex gap-2">
   <input id="chatInput" autocomplete="off" placeholder="Savolingizni yozing..." class="flex-1 p-3 rounded-xl bg-black/50 border border-white/10 text-white outline-none focus:border-[#7c6cff]/50">
   <button id="sendBtn" class="bg-[#7c6cff] text-white px-5 rounded-xl font-black btn-glow">➤</button>
  </form>
 </div>
</div>

<script>
var history = [];
var box = document.getElementById('chatBox');
var input = document.getElementById('chatInput');
var form = document.getElementById('chatForm');
var sendBtn = document.getElementById('sendBtn');

function esc(s){ var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
function addMsg(role, text){
 var wrap=document.createElement('div');
 wrap.className='flex gap-2'+(role==='user'?' flex-row-reverse':'');
 var av='<div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 text-sm '+(role==='user'?'bg-white/10':'bg-gradient-to-br from-[#7c6cff] to-[#241b52]')+'">'+(role==='user'?'🧑':'🤖')+'</div>';
 var bubbleCls = role==='user'
   ? 'bg-[#7c6cff] text-white rounded-2xl rounded-tr-sm'
   : 'bg-white/5 border border-white/10 rounded-2xl rounded-tl-sm';
 var bubble='<div class="'+bubbleCls+' p-3 text-sm max-w-[85%] whitespace-pre-wrap">'+esc(text)+'</div>';
 wrap.innerHTML=av+bubble;
 box.appendChild(wrap); box.scrollTop=box.scrollHeight;
 return wrap;
}
function addTyping(){
 var wrap=document.createElement('div'); wrap.className='flex gap-2'; wrap.id='typing';
 wrap.innerHTML='<div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#7c6cff] to-[#241b52] flex items-center justify-center shrink-0 text-sm">🤖</div><div class="bg-white/5 border border-white/10 rounded-2xl rounded-tl-sm p-3 text-sm text-white/40">✍️ yozmoqda...</div>';
 box.appendChild(wrap); box.scrollTop=box.scrollHeight;
}
function rmTyping(){ var t=document.getElementById('typing'); if(t) t.remove(); }

function send(text){
 text=(text||'').trim(); if(!text) return;
 input.value=''; addMsg('user',text);
 history.push({role:'user',content:text});
 sendBtn.disabled=true; input.disabled=true; addTyping();
 fetch('api.php?action=ai_chat',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({history:history})})
  .then(function(r){ return r.json(); })
  .then(function(d){
   rmTyping();
   if(d.ok){ addMsg('assistant',d.reply); history.push({role:'assistant',content:d.reply}); }
   else{ addMsg('assistant','⚠️ '+(d.msg||'Xatolik yuz berdi')); }
  })
  .catch(function(){ rmTyping(); addMsg('assistant','⚠️ Tarmoq xatosi. Qayta urinib ko\'ring.'); })
  .finally(function(){ sendBtn.disabled=false; input.disabled=false; input.focus(); });
}
form.addEventListener('submit',function(e){ e.preventDefault(); send(input.value); });
document.querySelectorAll('.chip').forEach(function(b){ b.addEventListener('click',function(){ send(b.textContent); }); });
</script>
<?php endif; ?>
<?php include 'layout_footer.php'; ?>
