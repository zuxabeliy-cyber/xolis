<?php
require_once __DIR__.'/config.php'; requireLogin();
$u=$_SESSION['user']; $isSuper=($u['role']=='super');
if(!$isSuper){ header('Location: reports.php'); exit; }

include 'layout_header.php';
$ym=selectedMonth();
list($mc,$mp)=monthCond($ym,'created_at');
list($mc2,$mp2)=monthCond($ym,'p.created_at');
try{ $st=db()->prepare("SELECT COALESCE(SUM(promo_count),0) FROM paid_participants WHERE is_paid=1 AND blacklisted=0 AND status='approved' AND trashed=0".$mc); $st->execute($mp); $paid=$st->fetchColumn(); }catch(Exception $e){ $paid=0; }
try{ $st=db()->prepare("SELECT p.phone, p.pretty_phone, p.name, p.operator_name, p.tarif_name, p.is_paid, p.promo_count, d.name as dealer_name FROM paid_participants p LEFT JOIN dealers d ON d.id=p.dealer_id WHERE p.blacklisted=0 AND p.status='approved' AND p.trashed=0".$mc2); $st->execute($mp2); $allListRaw=$st->fetchAll(); }catch(Exception $e){ $allListRaw=[]; }
$allList=[]; foreach($allListRaw as $r){ $times = max(1,intval($r['promo_count'])); for($i=0;$i<$times;$i++){ $allList[]=$r; } }
?>
<?php echo monthSelectorHtml($ym); ?>
<div class="card p-3 mb-4 flex items-center justify-center gap-2 text-center"><span class="text-xs text-white/40">Tanlangan davr:</span> <span class="text-sm font-black text-[#7c6cff]"><?php echo htmlspecialchars(monthLabel($ym)); ?></span> <span class="text-[11px] text-white/30">— faqat shu oy ishtirokchilaridan g'olib tanlanadi</span></div>
<div class="card p-6 md:p-8">
 <h3 class="font-black text-xl tracking-widest mb-2 text-center flex items-center justify-center gap-2"><?php echo icon('baraban','w-5 h-5'); ?> BARABAN</h3>
 <p class="text-center text-white/30 text-xs mb-5">Ishtirokchilar orasidan tasodifiy g'olibni aniqlash</p>
 <div class="grid grid-cols-3 gap-3 mb-6 max-w-sm mx-auto" id="poolToggle">
  <button type="button" class="pool-opt py-3 rounded-2xl text-sm font-bold transition bg-white text-black shadow-lg" data-pool="paid" onclick="setPool('paid')">O'YINDA</button>
  <button type="button" class="pool-opt py-3 rounded-2xl text-sm font-bold transition bg-white/5 border border-white/15" data-pool="free" onclick="setPool('free')">BAZADA</button>
  <button type="button" class="pool-opt py-3 rounded-2xl text-sm font-bold transition bg-white/5 border border-white/15" data-pool="all" onclick="setPool('all')">HAMMASI</button>
 </div>
 <p id="poolCount" class="text-center text-[#7c6cff] font-black text-sm mb-4"><?php echo $paid; ?> ta ishtirokchi</p>
 <div id="drum">
  <div class="drum-inner">
   <span class="drum-name"><?php echo $paid; ?> ta tayyor</span>
   <span class="drum-phone">Aylantirishni bosing</span>
  </div>
 </div>
 <div class="flex items-center justify-center gap-2 mt-6 mb-1 flex-wrap">
  <span class="text-xs text-white/40">G'oliblar soni:</span>
  <select id="winnerCount" class="bg-[#16162a] border border-white/10 rounded-xl px-3 py-2 text-sm text-white">
   <?php for($i=1;$i<=10;$i++): ?><option value="<?php echo $i; ?>"><?php echo $i; ?> ta</option><?php endfor; ?>
  </select>
  <button type="button" id="resetExcl" onclick="resetExcluded()" class="btn btn-ghost btn-xs" style="display:none">↩ Chiqarilganlarni tiklash (<span id="exclN">0</span>)</button>
  <a href="spins.php" class="btn btn-ghost btn-xs">🕘 Aylanishlar tarixi</a>
 </div>
 <button id="spinBtn" onclick="spin()" class="block mx-auto mt-2 bg-gradient-to-r from-[#7c6cff] to-[#ffcf7a] hover:from-[#9a8dff] hover:to-[#ffdd9a] text-black px-10 py-4 rounded-2xl font-black tracking-widest transition btn-glow text-base w-full max-w-sm">🎲 AYLANTRISH — G'OLIBNI ANIQLASH</button>
 <div id="win" class="mt-6 max-w-sm mx-auto"></div>
</div>

<div id="celebrate" onclick="closeCelebrate()" style="display:none;position:fixed;inset:0;z-index:9998;background:rgba(5,5,12,.93);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:24px;cursor:pointer">
 <p style="font-family:'IBM Plex Mono',monospace;letter-spacing:.3em;color:#f5a623;font-size:13px;margin-bottom:14px">🏆 G'OLIB ANIQLANDI</p>
 <div id="celebNames"></div>
 <p style="color:rgba(255,255,255,.4);font-size:12px;margin-top:28px">Yopish uchun bosing</p>
</div>

<script>
var allList = <?php echo json_encode($allList, JSON_UNESCAPED_UNICODE); ?>;
var ymSel = <?php echo json_encode($ym); ?>;
var pool = 'paid';
var excluded = {}; // chiqarib tashlangan telefonlar
function currentList(){
 var l;
 if(pool=='all') l=allList;
 else if(pool=='free') l=allList.filter(function(x){ return x.is_paid==0; });
 else l=allList.filter(function(x){ return x.is_paid==1; });
 return l.filter(function(x){ return !excluded[x.phone]; });
}
function uniqCount(list){ var u={}; list.forEach(function(x){ u[x.phone]=1; }); return Object.keys(u).length; }
function refreshExclUI(){
 var n=Object.keys(excluded).length;
 var b=document.getElementById('resetExcl'); var s=document.getElementById('exclN');
 if(s) s.textContent=n; if(b) b.style.display = n>0 ? '' : 'none';
}
function resetExcluded(){ excluded={}; refreshExclUI(); setPool(pool); }
function esc(s){ var d=document.createElement('div'); d.textContent=String(s==null?'':s); return d.innerHTML; }
function setPool(p){
 pool=p;
 document.querySelectorAll('.pool-opt').forEach(function(b){
  if(b.dataset.pool===p){ b.classList.add('bg-white','text-black','shadow-lg'); b.classList.remove('bg-white/5','border','border-white/15'); }
  else{ b.classList.remove('bg-white','text-black','shadow-lg'); b.classList.add('bg-white/5','border','border-white/15'); }
 });
 var list=currentList();
 var countEl=document.getElementById('poolCount');
 if(countEl) countEl.textContent=list.length+" ta ishtirokchi";
 document.querySelector('#drum .drum-name').textContent=uniqCount(list)+" ta tayyor";
 document.querySelector('#drum .drum-phone').textContent='Aylantirishni bosing';
 document.getElementById('win').innerHTML='';
}
// Ro'yxatdan n ta noyob (telefon bo'yicha) g'olib tanlaydi
function pickWinners(list,n){
 var res=[], seen={}, guard=0, maxUniq=uniqCount(list);
 n=Math.min(n,maxUniq);
 while(res.length<n && guard<20000){
  guard++;
  var r=list[Math.floor(Math.random()*list.length)];
  if(seen[r.phone]) continue;
  seen[r.phone]=1; res.push(r);
 }
 return res;
}
function excludeWinner(phone){ excluded[phone]=1; refreshExclUI(); renderWinners(window._lastWinners.filter(function(w){return w.phone!==phone;})); }
function renderWinners(winners){
 window._lastWinners=winners;
 var win=document.getElementById('win');
 if(!winners.length){ win.innerHTML='<p class="text-center text-white/30 text-sm">Hamma chiqarib tashlandi — qayta aylantiring.</p>'; return; }
 var medals=['🥇','🥈','🥉'];
 var html='<div style="background:linear-gradient(135deg,rgba(22,22,42,.9),rgba(10,10,18,.9));border:1px solid rgba(245,166,35,.35);padding:16px 18px;border-radius:20px;box-shadow:0 8px 32px rgba(124,108,255,.18)">';
 html+='<p style="font-size:11px;color:rgba(245,166,35,.9);margin-bottom:10px;letter-spacing:.18em;font-family:\'IBM Plex Mono\',monospace">🏆 G\'OLIB'+(winners.length>1?'LAR ('+winners.length+' ta)':'')+' ANIQLANDI</p>';
 winners.forEach(function(f,i){
  var medal = medals[i] || (i+1)+'.';
  html+='<div style="border-top:'+(i>0?'1px solid rgba(255,255,255,.08)':'none')+';padding:10px 0">'
   +'<p style="font-weight:900;font-size:1.05rem;font-family:\'Fraunces\',serif">'+medal+' '+esc(f.name)+'</p>'
   +'<p style="font-size:12px;opacity:.6;font-family:\'IBM Plex Mono\',monospace">'+esc(f.pretty_phone)+' • '+esc(f.tarif_name)+'</p>'
   +'<div style="display:flex;gap:8px;margin-top:8px">'
   +'<a href="winner.php?phone='+encodeURIComponent(f.phone)+'" style="flex:1;background:linear-gradient(90deg,#7c6cff,#ffcf7a);color:#000;padding:10px;border-radius:12px;font-weight:900;text-align:center;text-decoration:none;font-size:13px">✅ Tasdiqlash</a>'
   +'<button type="button" onclick="excludeWinner(\''+esc(f.phone)+'\')" style="background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.25);color:#fca5a5;padding:10px 12px;border-radius:12px;font-weight:800;font-size:13px" title="Chiqarib tashlash (qayta aylantirishda chiqmaydi)">🚫</button>'
   +'</div></div>';
 });
 if(winners.length>1){
  html+='<button type="button" onclick="confirmAll()" style="width:100%;margin-top:8px;background:#7c6cff;color:#fff;padding:11px;border-radius:12px;font-weight:900;border:none;cursor:pointer">✅ Hammasini tasdiqlash</button>';
 }
 html+='</div>';
 win.innerHTML=html;
}
function confirmAll(){
 var ws=window._lastWinners||[]; if(!ws.length) return;
 if(!confirm(ws.length+" ta g'olibni tasdiqlaysizmi?")) return;
 Promise.all(ws.map(function(w){ return fetch('winner.php?phone='+encodeURIComponent(w.phone)).catch(function(){}); }))
  .then(function(){ window.location='winners.php'; });
}
function playWin(){ try{ var A=new (window.AudioContext||window.webkitAudioContext)(); var notes=[523,659,784,1047]; notes.forEach(function(f,i){ var o=A.createOscillator(),g=A.createGain(); o.type='triangle'; o.frequency.value=f; o.connect(g); g.connect(A.destination); var t=A.currentTime+i*0.15; g.gain.setValueAtTime(0.0001,t); g.gain.exponentialRampToValueAtTime(0.3,t+0.03); g.gain.exponentialRampToValueAtTime(0.0001,t+0.45); o.start(t); o.stop(t+0.47); }); }catch(e){} }
function showCelebration(winners){
 var c=document.getElementById('celebrate'); var n=document.getElementById('celebNames'); if(!c) return;
 var medals=['🥇','🥈','🥉'];
 n.innerHTML=winners.map(function(w,i){ var big=winners.length>1?'1.7rem':'2.6rem'; return '<p style="font-family:\'Fraunces\',serif;font-weight:900;font-size:'+big+';line-height:1.15;margin:4px 0;background:linear-gradient(90deg,#7c6cff,#ffcf7a);-webkit-background-clip:text;background-clip:text;color:transparent">'+(medals[i]||'')+' '+esc(w.name)+'</p><p style="font-family:\'IBM Plex Mono\',monospace;color:rgba(255,255,255,.5);font-size:13px;margin-bottom:12px">'+esc(w.pretty_phone)+' • '+esc(w.tarif_name)+'</p>'; }).join('');
 c.style.display='flex'; playWin();
 var i=0; var iv=setInterval(function(){ confettiBurst(Math.random()*window.innerWidth, window.innerHeight*0.28); if(++i>5) clearInterval(iv); },400);
}
function closeCelebrate(){ var c=document.getElementById('celebrate'); if(c) c.style.display='none'; }
function logSpin(winners){
 try{ fetch('api.php?action=log_spin',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({ym:ymSel,pool:pool,winners:winners.map(function(w){return {name:w.name,phone:w.phone};})})}); }catch(e){}
}
function spin(){
 var list=currentList();
 if(!list || list.length==0){ alert("Bu guruhda hali hech kim yo'q!"); return; }
 var n=parseInt(document.getElementById('winnerCount').value)||1;
 var box=document.getElementById('drum');
 var nameEl=box.querySelector('.drum-name');
 var phoneEl=box.querySelector('.drum-phone');
 var win=document.getElementById('win');
 var btn=document.getElementById('spinBtn');
 var countEl=document.getElementById('poolCount');
 btn.disabled=true; btn.classList.add('opacity-50','pointer-events-none'); win.innerHTML='';
 box.classList.add('spinning');
 if(countEl) countEl.textContent='🎲 Aylanmoqda...';
 var start=Date.now(); var duration=15000;
 function tick(){
  var elapsed=Date.now()-start; var t=Math.min(elapsed/duration,1);
  var r=list[Math.floor(Math.random()*list.length)];
  nameEl.textContent=r.name; phoneEl.textContent=r.pretty_phone;
  if(t<1){
   var delay=40+Math.pow(t,3)*460;
   setTimeout(tick,delay);
  }else{
   box.classList.remove('spinning');
   btn.disabled=false; btn.classList.remove('opacity-50','pointer-events-none');
   var winners=pickWinners(list,n);
   var first=winners[0];
   nameEl.textContent='🎉 '+first.name;
   phoneEl.textContent=first.pretty_phone;
   if(countEl) countEl.textContent=list.length+" ta ishtirokchi";
   renderWinners(winners);
   logSpin(winners);
   showCelebration(winners);
   var boxRect=box.getBoundingClientRect();
   confettiBurst(boxRect.left+boxRect.width/2, boxRect.top+boxRect.height/2);
  }
 }
 tick();
}
</script>
<?php include 'layout_footer.php'; ?>
