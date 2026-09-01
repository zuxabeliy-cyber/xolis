<?php
require_once __DIR__.'/config.php'; requireLogin();
$u=$_SESSION['user']; $isSuper=($u['role']=='super');
if(!$isSuper){ header('Location: reports.php'); exit; }

include 'layout_header.php';
try{ $paid=db()->query("SELECT COALESCE(SUM(promo_count),0) FROM paid_participants WHERE is_paid=1 AND blacklisted=0 AND status='approved'")->fetchColumn(); }catch(Exception $e){ $paid=0; }
try{ $allListRaw=db()->query("SELECT p.phone, p.pretty_phone, p.name, p.operator_name, p.tarif_name, p.is_paid, p.promo_count, d.name as dealer_name FROM paid_participants p LEFT JOIN dealers d ON d.id=p.dealer_id WHERE p.blacklisted=0 AND p.status='approved'")->fetchAll(); }catch(Exception $e){ $allListRaw=[]; }
$allList=[]; foreach($allListRaw as $r){ $times = max(1,intval($r['promo_count'])); for($i=0;$i<$times;$i++){ $allList[]=$r; } }
?>
<div class="card p-6 md:p-8">
 <h3 class="font-black text-xl tracking-widest mb-2 text-center flex items-center justify-center gap-2"><?php echo icon('baraban','w-5 h-5'); ?> BARABAN</h3>
 <p class="text-center text-white/30 text-xs mb-5">Ishtirokchilar orasidan tasodifiy g'olibni aniqlash</p>
 <div class="grid grid-cols-3 gap-3 mb-6 max-w-sm mx-auto" id="poolToggle">
  <button type="button" class="pool-opt py-3 rounded-2xl text-sm font-bold transition bg-white text-black shadow-lg" data-pool="paid" onclick="setPool('paid')">O'YINDA</button>
  <button type="button" class="pool-opt py-3 rounded-2xl text-sm font-bold transition bg-white/5 border border-white/15" data-pool="free" onclick="setPool('free')">BAZADA</button>
  <button type="button" class="pool-opt py-3 rounded-2xl text-sm font-bold transition bg-white/5 border border-white/15" data-pool="all" onclick="setPool('all')">HAMMASI</button>
 </div>
 <p id="poolCount" class="text-center text-[#1fae76] font-black text-sm mb-4"><?php echo $paid; ?> ta ishtirokchi</p>
 <div id="drum">
  <div class="drum-inner">
   <span class="drum-name"><?php echo $paid; ?> ta tayyor</span>
   <span class="drum-phone">Aylantirishni bosing</span>
  </div>
 </div>
 <button id="spinBtn" onclick="spin()" class="block mx-auto mt-6 bg-gradient-to-r from-[#1fae76] to-[#e7c878] hover:from-[#37c98b] hover:to-[#f7dd85] text-black px-10 py-4 rounded-2xl font-black tracking-widest transition btn-glow text-base w-full max-w-sm">🎲 AYLANTRISH — G'OLIBNI ANIQLASH</button>
 <div id="win" class="mt-6 max-w-sm mx-auto"></div>
</div>

<script>
var allList = <?php echo json_encode($allList, JSON_UNESCAPED_UNICODE); ?>;
var pool = 'paid';
function currentList(){
 if(pool=='all') return allList;
 if(pool=='free') return allList.filter(function(x){ return x.is_paid==0; });
 return allList.filter(function(x){ return x.is_paid==1; });
}
function setPool(p){
 pool=p;
 document.querySelectorAll('.pool-opt').forEach(function(b){
  if(b.dataset.pool===p){ b.classList.add('bg-white','text-black','shadow-lg'); b.classList.remove('bg-white/5','border','border-white/15'); }
  else{ b.classList.remove('bg-white','text-black','shadow-lg'); b.classList.add('bg-white/5','border','border-white/15'); }
 });
 var list=currentList();
 var countEl=document.getElementById('poolCount');
 if(countEl) countEl.textContent=list.length+" ta ishtirokchi";
 document.querySelector('#drum .drum-name').textContent=list.length+" ta tayyor";
 document.querySelector('#drum .drum-phone').textContent='Aylantirishni bosing';
 document.getElementById('win').innerHTML='';
}
function spin(){
 var list=currentList();
 if(!list || list.length==0){ alert("Bu guruhda hali hech kim yo'q!"); return; }
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
   var f=list[Math.floor(Math.random()*list.length)];
   nameEl.textContent='🎉 '+f.name;
   phoneEl.textContent=f.pretty_phone;
   if(countEl) countEl.textContent=list.length+" ta ishtirokchi";
   win.innerHTML='<div style="background:linear-gradient(135deg,rgba(23,34,25,.9),rgba(13,16,12,.9));border:1px solid rgba(201,162,75,.35);padding:20px 24px;border-radius:20px;box-shadow:0 8px 32px rgba(31,174,118,.18)">'
    +'<p style="font-size:11px;color:rgba(201,162,75,.9);margin-bottom:6px;letter-spacing:.18em;font-family:\'IBM Plex Mono\',monospace">🏆 G\'OLIB ANIQLANDI</p>'
    +'<p style="font-weight:900;font-size:1.15rem;margin-bottom:4px;font-family:\'Fraunces\',serif">'+f.name+'</p>'
    +'<p style="font-size:12px;opacity:.6;font-family:\'IBM Plex Mono\',monospace">'+f.pretty_phone+' • '+f.tarif_name+'</p>'
    +'<a href="winner.php?phone='+f.phone+'" style="display:block;margin-top:14px;background:linear-gradient(90deg,#1fae76,#e7c878);color:#000;padding:13px 24px;border-radius:14px;font-weight:900;text-align:center;text-decoration:none">✅ G\'OLIBNI TASDIQLASH</a>'
    +'</div>';
   var boxRect=box.getBoundingClientRect();
   confettiBurst(boxRect.left+boxRect.width/2, boxRect.top+boxRect.height/2);
  }
 }
 tick();
}
</script>
<?php include 'layout_footer.php'; ?>
