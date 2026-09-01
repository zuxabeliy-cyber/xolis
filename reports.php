<?php include 'layout_header.php';
require_once 'stats_helper.php';
$params = statsFilters();
if(!$isSuper) $params['dealer_id'] = $u['id'];
$f=$params['f']; $days=$params['days']; $did=$params['dealer_id']; $from=$params['from']; $to=$params['to'];
$ym = selectedMonth();
$cmpMonth=null;
if($ym!=='all' && preg_match('/^\d{4}-\d{2}$/',$ym)){
 $curStart=$ym.'-01'; $curEnd=date('Y-m-t',strtotime($curStart));
 $prevStart=date('Y-m-01',strtotime($curStart.' -1 month')); $prevEnd=date('Y-m-t',strtotime($prevStart));
 $curC=countInRange($curStart,$curEnd,$did); $prevC=countInRange($prevStart,$prevEnd,$did);
 $pctM=$prevC>0?round((($curC-$prevC)/$prevC)*100,1):($curC>0?100:0);
 $cmpMonth=['cur'=>$curC,'prev'=>$prevC,'pct'=>$pctM,'curLabel'=>monthLabel($ym),'prevLabel'=>monthLabel(substr($prevStart,0,7))];
}
$initialOp = $_GET['op'] ?? '';
$knownOps = ['Beeline','Ucell','Uztelecom','Mobiuz','Humans'];
if($initialOp !== '__other__' && !in_array($initialOp, $knownOps, true)) $initialOp = '';

$rows = getApprovedRows($params, 100000);
try{ $allDealers=db()->query("SELECT id,name FROM dealers WHERE role='diller' ORDER BY name")->fetchAll(); }catch(Exception $e){ $allDealers=[]; }
$quality = getQualityByDealer($params);

$cmp = null;
$pr = prevPeriodRange($f, $days, $from, $to);
if($pr){
 $curCnt = countInRange($pr['cur'][0], $pr['cur'][1], $did);
 $prevCnt = countInRange($pr['prev'][0], $pr['prev'][1], $did);
 $pct = $prevCnt>0 ? round((($curCnt-$prevCnt)/$prevCnt)*100,1) : ($curCnt>0?100:0);
 $cmp = ['cur'=>$curCnt,'prev'=>$prevCnt,'pct'=>$pct];
}

$exportBase = "api.php?action=export_stats_csv&f=".urlencode($f)."&days=".intval($days)."&from=".urlencode($from)."&to=".urlencode($to)."&dealer_id=".intval($did)."&ym=".urlencode($ym);

// JAMI logo URL
$jamiLogoUrl = '';
foreach(['png','jpg','jpeg','webp','svg'] as $ext){
 $p2 = __DIR__.'/logos/jami.'.$ext;
 if(file_exists($p2)){ $jamiLogoUrl = 'logos/jami.'.$ext.'?v='.filemtime($p2); break; }
}
// Operator logo URLs (for cache busting after upload)
$opLogoUrls = [];
$opFileMap = ['Beeline'=>'beeline','Ucell'=>'ucell','Uztelecom'=>'uztelecom','Mobiuz'=>'mobiuz','Humans'=>'humans'];
foreach($opFileMap as $opName=>$slug){
 foreach(['png','jpg','jpeg','webp','svg'] as $ext){
  $p2=__DIR__.'/logos/'.$slug.'.'.$ext;
  if(file_exists($p2)){ $opLogoUrls[$opName]='logos/'.$slug.'.'.$ext.'?v='.filemtime($p2); break; }
 }
}
?>
<div class="flex flex-wrap justify-between items-start gap-2 mb-1">
<div><h1 class="font-black text-2xl flex items-center gap-2"><?php echo icon('chart','w-6 h-6'); ?> Zamonaviy statistika</h1><p class="text-white/30 text-xs mt-1">Avval operatorni tanlang — o'sha operatorning to'liq statistikasi ochiladi • <span class="text-[#7c6cff]">Baraban bilan bir xil hisob (promo_count bilan)</span></p></div>
<div class="flex gap-2"><?php if($isSuper): ?><a id="exportBtn" href="<?php echo $exportBase; ?>" class="bg-white text-black px-4 py-2 rounded-xl text-xs font-black whitespace-nowrap">⬇️ Excel yuklab olish</a><?php endif; ?>
<button onclick="window.print()" class="bg-white/10 border border-white/15 text-white px-4 py-2 rounded-xl text-xs font-black whitespace-nowrap">🖨️ PDF / Chop etish</button></div>
</div>
<style>@media print{ nav,.sticky,#modalOverlay,#logoUploadModal,button,form{ display:none !important; } body{ background:#fff !important; color:#000 !important; } .card{ break-inside:avoid; border:1px solid #ccc !important; background:#fff !important; box-shadow:none !important; } }</style>

<?php echo monthSelectorHtml($ym, array_filter(['dealer_id'=>$did?:null])); ?>
<?php if($cmpMonth): ?>
<div class="card p-4 mb-4 flex items-center gap-4 flex-wrap">
 <span class="text-sm text-white/40 font-bold tracking-widest">📊 SOLISHTIRUV:</span>
 <div class="flex items-center gap-3">
  <div class="text-center"><p class="text-[10px] text-white/30"><?php echo htmlspecialchars($cmpMonth['prevLabel']); ?></p><p class="font-black text-lg text-white/60"><?php echo $cmpMonth['prev']; ?> ta</p></div>
  <span class="text-white/20 text-xl">→</span>
  <div class="text-center"><p class="text-[10px] text-[#7c6cff]"><?php echo htmlspecialchars($cmpMonth['curLabel']); ?></p><p class="font-black text-lg text-[#7c6cff]"><?php echo $cmpMonth['cur']; ?> ta</p></div>
 </div>
 <span class="text-sm font-black px-3 py-1 rounded-full border <?php echo $cmpMonth['pct']>=0?'text-[#7c6cff] bg-[#7c6cff]/10 border-[#7c6cff]/20':'text-red-300 bg-red-500/10 border-red-500/20'; ?>"><?php echo $cmpMonth['pct']>=0?'▲':'▼'; ?> <?php echo abs($cmpMonth['pct']); ?>%</span>
 <span class="text-[11px] text-white/30">o'tgan oyga nisbatan</span>
</div>
<?php endif; ?>
<div class="card p-4 my-4 flex flex-wrap gap-2 items-center">
<span class="text-[11px] text-white/40 tracking-widest font-bold whitespace-nowrap">DAVR:</span>
<select onchange="periodGo(this.value)" class="bg-[#16162a] border border-white/10 rounded-xl px-3 py-2.5 text-sm font-bold text-white outline-none focus:border-[#7c6cff]/50 flex-1 min-w-[150px]">
 <option value="f=all" <?php echo $f=='all'?'selected':''; ?>>Hammasi</option>
 <option value="f=today" <?php echo $f=='today'?'selected':''; ?>>Bugun</option>
 <option value="f=yesterday" <?php echo $f=='yesterday'?'selected':''; ?>>Kecha</option>
 <option value="f=week" <?php echo $f=='week'?'selected':''; ?>>1 hafta</option>
 <option value="f=month" <?php echo $f=='month'?'selected':''; ?>>1 oy</option>
 <option value="f=year" <?php echo $f=='year'?'selected':''; ?>>Yillik</option>
 <optgroup label="Oxirgi kunlar">
 <?php for($i=1;$i<=10;$i++): ?><option value="f=days&days=<?php echo $i; ?>" <?php echo ($f=='days'&&$days==$i)?'selected':''; ?>>Oxirgi <?php echo $i; ?> kun</option><?php endfor; ?>
 </optgroup>
</select>
<?php if($isSuper): ?>
<select onchange="dealerGo(this.value)" class="bg-[#16162a] border border-white/10 rounded-xl px-3 py-2.5 text-sm font-bold text-white outline-none focus:border-[#7c6cff]/50 flex-1 min-w-[150px]">
 <option value="0">Barcha dillerlar</option>
 <?php foreach($allDealers as $d): ?><option value="<?php echo $d['id']; ?>" <?php echo $did==$d['id']?'selected':''; ?>><?php echo htmlspecialchars($d['name']); ?></option><?php endforeach; ?>
</select>
<?php endif; ?>
<script>
var _did=<?php echo intval($did); ?>, _ym=<?php echo json_encode($ym); ?>, _f=<?php echo json_encode($f); ?>, _days=<?php echo intval($days); ?>;
function periodGo(v){ var p=new URLSearchParams(v); p.set('dealer_id',_did); p.set('ym',_ym); window.location='?'+p.toString(); }
function dealerGo(v){ var p=new URLSearchParams(); p.set('f',_f); if(_f==='days')p.set('days',_days); p.set('ym',_ym); p.set('dealer_id',v); window.location='?'+p.toString(); }
</script>
</div>

<form class="card p-3 mb-4 flex flex-wrap gap-3 items-end" method="get">
<input type="hidden" name="dealer_id" value="<?php echo $did; ?>"><input type="hidden" name="f" value="range">
<div><label class="text-[10px] text-white/30 block mb-1 tracking-widest">DAN</label><input type="date" name="from" value="<?php echo htmlspecialchars($from); ?>" max="<?php echo date('Y-m-d'); ?>" class="p-2.5 rounded-lg bg-black/50 border border-white/10 text-white text-xs outline-none"></div>
<div><label class="text-[10px] text-white/30 block mb-1 tracking-widest">GACHA</label><input type="date" name="to" value="<?php echo htmlspecialchars($to); ?>" max="<?php echo date('Y-m-d'); ?>" class="p-2.5 rounded-lg bg-black/50 border border-white/10 text-white text-xs outline-none"></div>
<button class="bg-[#7c6cff]/15 text-[#7c6cff] border border-[#7c6cff]/25 px-4 py-2.5 rounded-xl text-xs font-black">📅 Sana oralig'ini qidirish</button>
<?php if($f=='range' && $from && $to): ?><span class="text-[11px] text-white/30"><?php echo $from; ?> — <?php echo $to; ?> oralig'i tanlangan</span><?php endif; ?>
</form>

<?php if($cmp): ?>
<div class="card p-3 mb-4 flex items-center gap-3 flex-wrap">
<span class="text-xs text-white/40">Avvalgi shu davrga nisbatan:</span>
<span class="text-sm font-black <?php echo $cmp['pct']>=0?'text-[#7c6cff]':'text-red-300'; ?>"><?php echo $cmp['pct']>=0?'▲':'▼'; ?> <?php echo abs($cmp['pct']); ?>%</span>
<span class="text-xs text-white/30">(joriy: <b class="text-white/60"><?php echo $cmp['cur']; ?></b> ta • avvalgi: <b class="text-white/60"><?php echo $cmp['prev']; ?></b> ta)</span>
</div>
<?php endif; ?>

<?php if($isSuper): ?>
<div class="card p-3 mb-4"><input id="statSearch" placeholder="🔍 Istalgan narsani qidiring: diller, operator yoki tarif nomi..." class="w-full p-3 rounded-xl bg-black/50 border border-white/10 text-white outline-none focus:border-[#7c6cff]/50" oninput="filterStats(this.value)"></div>
<?php endif; ?>

<h2 class="font-black text-sm text-white/40 mb-3 tracking-widest">1️⃣ OPERATORNI TANLANG</h2>
<div id="operatorCards" class="grid grid-cols-3 md:grid-cols-6 gap-3 mb-2"></div>
<p class="text-[11px] text-white/25 mb-6">Har bir operator ustiga bosing — o'sha operatorning to'liq statistikasi (tariflari, dillerlari, dinamika) pastda ochiladi. "JAMI" — barcha operatorlar birgalikda.</p>

<div class="flex items-center justify-between mb-3 flex-wrap gap-2">
<h2 id="viewTitle" class="font-black text-lg"></h2>
<div class="flex gap-2">
<button onclick="openModalFor('all','Joriy ro\'yxat')" class="bg-white/5 border border-white/10 px-4 py-2 rounded-xl text-xs font-bold">📋 Joriy ro'yxatni ko'rish</button>
<button id="backBtn" onclick="selectOperator(null)" class="hidden bg-[#7c6cff]/15 text-[#7c6cff] border border-[#7c6cff]/25 px-4 py-2 rounded-xl text-xs font-black">⬅ Ortga (Jami)</button>
</div>
</div>

<div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
<div class="card p-4 text-center card-hover"><p class="text-[10px] text-white/30 tracking-widest">JAMI</p><p id="cardTotal" class="text-2xl font-black mt-1">0</p></div>
<div class="card p-4 text-center card-hover border-[#7c6cff]/15"><p class="text-[10px] text-[#7c6cff] tracking-widest">O'YINDA</p><p id="cardPaid" class="text-2xl font-black text-[#7c6cff] mt-1">0</p></div>
<div class="card p-4 text-center card-hover"><p class="text-[10px] text-white/30 tracking-widest">BAZADA</p><p id="cardFree" class="text-2xl font-black mt-1">0</p></div>
<div class="card p-4 text-center card-hover"><p class="text-[10px] text-white/30 tracking-widest">DILLERLAR</p><p id="cardDealers" class="text-2xl font-black mt-1">0</p></div>
<div class="card p-4 text-center card-hover border-[#7c6cff]/15"><p class="text-[10px] text-[#7c6cff] tracking-widest">🔁 KONVERSIYA</p><p id="cardConversion" class="text-2xl font-black text-[#7c6cff] mt-1">0%</p><p class="text-[9px] text-white/25">Bazadan o'yinga</p></div>
</div>

<?php if($isSuper && $f=='all' && !$from): ?>
<div class="card p-3 mb-6 flex items-center gap-3 flex-wrap">
<span class="badge-live w-2.5 h-2.5 rounded-full bg-[#7c6cff]"></span>
<span class="text-xs text-white/40">Bugungi jonli hisoblagich:</span>
<span id="liveTodayCount" class="text-lg font-black text-[#7c6cff]">—</span>
<span class="text-[10px] text-white/25">(har 20 soniyada avtomatik yangilanadi)</span>
</div>
<?php endif; ?>

<div class="card p-4 mb-6"><h3 class="font-bold mb-3 text-sm">📈 Kumulyativ o'sish (jami)</h3><canvas id="cumChart" height="120"></canvas></div>

<div id="operatorRankBlock" class="card p-5 mb-6">
<div class="flex justify-between items-center mb-1"><h3 class="font-black text-sm">📡 TOP Operatorlar reytingi</h3><button onclick="toggleSort('op')" class="text-[10px] text-white/40 hover:text-[#7c6cff] font-bold">⇅ <span id="opSortLabel">Ko'p→Kam</span></button></div>
<p class="text-[10px] text-white/30 mb-3">Nomga bosing — o'sha operator tanlanadi. 👁 — to'liq ro'yxat</p>
<div id="opList" class="space-y-2"></div>
<div class="mt-3"><canvas id="opChart" height="170"></canvas></div>
</div>

<div class="grid lg:grid-cols-2 gap-4 mb-6">
<div class="card p-5">
<div class="flex justify-between items-center mb-1"><h3 class="font-black text-sm">🏷 TOP Tariflar reytingi</h3><button onclick="toggleSort('tarif')" class="text-[10px] text-white/40 hover:text-[#7c6cff] font-bold">⇅ <span id="tarifSortLabel">Ko'p→Kam</span></button></div>
<p class="text-[10px] text-white/30 mb-3">Nomga bosing yoki 👁 — to'liq ro'yxat</p>
<div id="tarifList" class="space-y-2 max-h-[420px] overflow-auto pr-1"></div>
<div class="mt-3"><canvas id="tarifChart" height="170"></canvas></div>
</div>
<?php if($isSuper): ?>
<div class="card p-5">
<h3 class="font-black text-sm mb-1">🏆 TOP Dillerlar reytingi</h3><p class="text-[10px] text-white/30 mb-3">Qaysi diller qancha ulaganini ko'ring — nomga yoki 👁 bosing</p>
<div id="dealerRankList" class="space-y-2 max-h-[420px] overflow-auto pr-1"></div>
</div>
<?php endif; ?>
</div>

<?php if($isSuper): ?>
<div class="card p-5 mb-6">
<h3 id="matrixTitle" class="font-black text-sm mb-1">🧮 Diller × Operator jadvali</h3><p class="text-[10px] text-white/30 mb-3">Har bir katakka bosib to'liq ro'yxatni ko'ring</p>
<div id="matrixWrap" class="overflow-auto max-h-[420px]"></div>
</div>
<?php endif; ?>

<h2 class="font-black text-sm text-white/40 mb-3 tracking-widest">👥 HAR BIR DILLER — ALOHIDA STATISTIKA</h2>
<div class="grid lg:grid-cols-2 gap-4 mb-6" id="dealerCardsWrap"></div>

<div class="card overflow-auto"><table class="w-full text-sm"><thead><tr class="bg-black/50 text-white/30 text-[11px] tracking-widest"><th class="p-3 text-left">DILLER</th><th>ISM</th><th>NOMER</th><th>OPERATOR / TARIF</th><th>VAQT / SANA</th><th>TURI</th></tr></thead><tbody id="fullTableBody">
<?php foreach($rows as $r): ?>
<tr class="border-b border-white/5 hover:bg-white/[0.03]" data-op="<?php echo htmlspecialchars($r['operator_name']); ?>" data-search="<?php echo mb_strtolower(($r['dealer_name']?:'').' '.$r['operator_name'].' '.$r['tarif_name']); ?>">
<td class="p-3 font-bold text-white/70 text-xs"><?php echo htmlspecialchars($r['dealer_name']); ?></td>
<td class="p-3"><b><?php echo htmlspecialchars($r['name']); ?></b> <?php echo intval($r['promo_count'])==2 ? '<span class="ml-1 text-[9px] bg-[#7c6cff]/15 text-[#7c6cff] px-1 py-0.5 rounded-full">x2</span>' : ''; ?></td>
<td class="font-mono text-xs"><?php echo htmlspecialchars($r['pretty_phone']); ?></td>
<td class="text-xs"><b><?php echo htmlspecialchars($r['operator_name']); ?></b> / <?php echo htmlspecialchars($r['tarif_name']); ?></td>
<td class="text-xs"><?php echo date('d.m.Y', strtotime($r['created_at'])); ?><br><span class="text-white/40"><?php echo date('H:i:s', strtotime($r['created_at'])); ?></span></td>
<td><span class="text-[10px] px-2 py-1 rounded-full <?php echo $r['is_paid']?'bg-white text-black':'bg-white/10 text-white/40'; ?>"><?php echo $r['is_paid']?"O'YINDA":'BAZADA'; ?></span></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>

<!-- Modal: To'liq ro'yxat -->
<div id="modalOverlay" class="fixed inset-0 bg-black/70 z-[999] hidden flex items-center justify-center p-4" onclick="if(event.target===this) closeModal()">
<div class="card w-full max-w-3xl max-h-[85vh] overflow-hidden flex flex-col">
<div class="p-4 border-b border-white/10 flex justify-between items-center gap-2">
<h3 id="modalTitle" class="font-black text-sm"></h3>
<div class="flex gap-2">
<?php if($isSuper): ?><button onclick="exportModal()" class="bg-white text-black px-3 py-1.5 rounded-lg text-[11px] font-black">⬇️ Excel</button><?php endif; ?>
<button onclick="closeModal()" class="bg-white/5 border border-white/10 px-3 py-1.5 rounded-lg text-[11px] font-bold">✕</button>
</div>
</div>
<div id="modalBody" class="overflow-auto p-3"></div>
</div>
</div>

<!-- Modal: Logo yuklash (faqat Bosh admin) -->
<?php if($isSuper): ?>
<div id="logoUploadModal" class="fixed inset-0 bg-black/75 z-[1000] hidden flex items-center justify-center p-4" onclick="if(event.target===this) closeLogoUpload()">
<div class="card w-full max-w-sm p-6">
 <div class="flex justify-between items-center mb-4">
  <h3 id="logoUploadTitle" class="font-black text-sm">Logo almashtirish</h3>
  <button onclick="closeLogoUpload()" class="text-white/40 hover:text-white text-lg font-bold leading-none">✕</button>
 </div>
 <p class="text-xs text-white/40 mb-4">PNG, JPG, WebP, SVG — max 3MB</p>
 <label class="block w-full cursor-pointer">
  <div class="border-2 border-dashed border-white/20 hover:border-[#7c6cff]/50 rounded-2xl p-8 text-center transition" id="logoDropZone">
   <div class="text-3xl mb-2">📁</div>
   <p class="text-sm text-white/50">Fayl tanlash yoki bu yerga tashlang</p>
   <p id="logoFileName" class="text-xs text-[#7c6cff] mt-2 font-bold"></p>
  </div>
  <input type="file" id="logoUploadInput" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="hidden">
 </label>
 <button id="logoUploadSaveBtn" onclick="uploadLogo()" class="w-full mt-4 bg-[#7c6cff] text-white py-3 rounded-xl font-black disabled:opacity-40 disabled:pointer-events-none" disabled>⬆️ Yuklash</button>
 <p id="logoUploadMsg" class="text-xs text-center mt-3"></p>
</div>
</div>
<?php endif; ?>

<script>
const allRows = <?php echo json_encode(array_map(function($r){ return [
  'dealer'=>$r['dealer_name']?:'Nomalum',
  'op'=>$r['operator_name'],
  'tar'=>$r['tarif_name'],
  'paid'=>(int)$r['is_paid'],
  'date'=>$r['created_at'],
  'name'=>$r['name'],
  'phone'=>$r['pretty_phone'],
  'promo'=>intval($r['promo_count'] ?? 1),
 ]; }, $rows), JSON_UNESCAPED_UNICODE); ?>;
const qualityData = <?php echo json_encode($quality, JSON_UNESCAPED_UNICODE); ?>;
const exportBase = <?php echo json_encode($exportBase); ?>;
const isSuperView = <?php echo $isSuper ? 'true' : 'false'; ?>;
const jamiLogoUrl = <?php echo json_encode($jamiLogoUrl); ?>;
const opLogoUrlsInit = <?php echo json_encode($opLogoUrls, JSON_UNESCAPED_UNICODE); ?>;

const operatorOrder = ['Beeline','Ucell','Uztelecom','Mobiuz','Humans'];
const operatorMeta = {
 'Beeline':  {color:'#FFC900', text:'#1a1a1a', letter:'B', logo: opLogoUrlsInit['Beeline'] || 'logos/beeline.png'},
 'Ucell':    {color:'#F5821F', text:'#ffffff', letter:'U', logo: opLogoUrlsInit['Ucell'] || 'logos/ucell.png'},
 'Uztelecom':{color:'#0072BC', text:'#ffffff', letter:'T', logo: opLogoUrlsInit['Uztelecom'] || 'logos/uztelecom.png'},
 'Mobiuz':   {color:'#8E44AD', text:'#ffffff', letter:'M', logo: opLogoUrlsInit['Mobiuz'] || 'logos/mobiuz.png'},
 'Humans':   {color:'#FF4C79', text:'#ffffff', letter:'H', logo: opLogoUrlsInit['Humans'] || 'logos/humans.png'}
};
const knownOpsSet = new Set(operatorOrder);
const medals = ['🥇','🥈','🥉'];
const palette = ['#7c6cff','#f5a623','#9a8dff','#ffcf7a','#241b52','#c67f14','#c3bbff','#8a5a1a'];

let charts = {};
let activeOperator = <?php echo json_encode($initialOp !== '' ? $initialOp : null); ?>;
let searchTerm = '';
let sortMode = {op:'desc', tarif:'desc'};
let currentLogoOp = null;

function escapeHtml(s){ const d=document.createElement('div'); d.textContent=String(s); return d.innerHTML; }
function jsEsc(s){ return String(s).replace(/\\/g,'\\\\').replace(/'/g,"\\'"); }
function fmtDate(d){ return d.slice(0,10); }

function opLogoFallback(imgEl, op){
 const meta = operatorMeta[op]; if(!meta) return;
 const box = imgEl.parentElement;
 box.style.background = meta.color;
 box.innerHTML = '<span style="color:'+meta.text+';font-weight:900;font-size:18px">'+escapeHtml(meta.letter)+'</span>';
}
function matchesSearch(r){
 if(!searchTerm) return true;
 return (r.dealer+' '+r.op+' '+r.tar).toLowerCase().indexOf(searchTerm)!==-1;
}
function matchesOperator(r){
 if(!activeOperator) return true;
 if(activeOperator==='__other__') return !knownOpsSet.has(r.op);
 return r.op===activeOperator;
}
function searchFiltered(){ return allRows.filter(matchesSearch); }
function viewRows(){ return searchFiltered().filter(matchesOperator); }

function aggregate(rows){
 const byOp={}, byTarif={}, byDealer={};
 let paid=0, free=0, total=0;
 rows.forEach(r=>{
  const w = r.promo || 1;
  byOp[r.op]=(byOp[r.op]||0)+w;
  byTarif[r.tar]=(byTarif[r.tar]||0)+w;
  if(!byDealer[r.dealer]) byDealer[r.dealer]={total:0,paid:0,free:0,ops:{},tarifs:{},last:r.date};
  byDealer[r.dealer].total+=w;
  if(r.paid){ byDealer[r.dealer].paid+=w; paid+=w; } else { byDealer[r.dealer].free+=w; free+=w; }
  byDealer[r.dealer].ops[r.op]=(byDealer[r.dealer].ops[r.op]||0)+w;
  byDealer[r.dealer].tarifs[r.tar]=(byDealer[r.dealer].tarifs[r.tar]||0)+w;
  if(r.date > byDealer[r.dealer].last) byDealer[r.dealer].last=r.date;
  total+=w;
 });
 return {byOp,byTarif,byDealer,paid,free,total};
}
function sortDesc(obj){ return Object.entries(obj).sort((a,b)=>b[1]-a[1]); }
function destroyChart(id){ if(charts[id]){ charts[id].destroy(); delete charts[id]; } }

// ===================== OPERATOR CARDS =====================
function renderOperatorCards(){
 const wrap=document.getElementById('operatorCards'); if(!wrap) return;
 const rows = searchFiltered();
 const agg = aggregate(rows);
 let html='';
 const activeAll = !activeOperator;

 // JAMI card
 const jamiImg = jamiLogoUrl
  ? `<img src="${escapeHtml(jamiLogoUrl)}" class="w-full h-full object-cover" alt="JAMI" onerror="this.parentElement.innerHTML='🌐'">`
  : '🌐';
 html += `<div class="card card-hover p-4 text-center cursor-pointer transition relative ${activeAll?'ring-2 ring-white/60':''}" onclick="selectOperator(null)">
  ${isSuperView?`<button class="absolute top-2 right-2 z-10 w-6 h-6 bg-black/60 hover:bg-[#7c6cff]/30 border border-white/15 hover:border-[#7c6cff]/60 rounded-full text-[10px] flex items-center justify-center transition" onclick="event.stopPropagation();openLogoUpload('jami')" title="JAMI logosini almashtirish">✏️</button>`:''}
  <div class="w-16 h-16 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center mx-auto mb-2 font-black text-xl overflow-hidden" id="opicon-jami">${jamiImg}</div>
  <p class="text-xl font-black mt-1">${agg.total}</p>
  <p class="text-[9px] text-white/30 mt-0.5">JAMI</p>
 </div>`;

 // Operator cards
 operatorOrder.forEach(op=>{
  const cnt = agg.byOp[op]||0;
  const meta = operatorMeta[op];
  const active = activeOperator===op;
  html += `<div class="card card-hover p-4 text-center cursor-pointer transition relative ${active?'ring-2':''}" style="${active?`box-shadow:0 0 0 2px ${meta.color}`:''}" onclick="selectOperator('${jsEsc(op)}')" title="${escapeHtml(op)}">
   ${isSuperView?`<button class="absolute top-2 right-2 z-10 w-6 h-6 bg-black/60 hover:bg-[#7c6cff]/30 border border-white/15 hover:border-[#7c6cff]/60 rounded-full text-[10px] flex items-center justify-center transition" onclick="event.stopPropagation();openLogoUpload('${jsEsc(op)}')" title="${escapeHtml(op)} logosini almashtirish">✏️</button>`:''}
   <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-2 overflow-hidden bg-white/5" id="opicon-${jsEsc(op)}"><img src="${meta.logo}" class="w-full h-full object-cover" alt="${escapeHtml(op)}" onerror="opLogoFallback(this,'${jsEsc(op)}')"></div>
   <p class="text-xl font-black mt-1">${cnt}</p>
  </div>`;
 });

 // Other operators
 const otherTotal = Object.entries(agg.byOp).filter(([k])=>!knownOpsSet.has(k)).reduce((s,e)=>s+e[1],0);
 if(otherTotal>0){
  const active = activeOperator==='__other__';
  html += `<div class="card card-hover p-4 text-center cursor-pointer transition ${active?'ring-2 ring-white/60':''}" onclick="selectOperator('__other__')">
   <div class="w-16 h-16 rounded-2xl bg-white/10 flex items-center justify-center mx-auto mb-2 font-black text-xl">❓</div>
   <p class="text-xl font-black mt-1">${otherTotal}</p>
  </div>`;
 }
 wrap.innerHTML = html;
}

function selectOperator(name){
 activeOperator = (activeOperator===name) ? null : name;
 try{
  const url = new URL(window.location.href);
  if(activeOperator) url.searchParams.set('op', activeOperator); else url.searchParams.delete('op');
  window.history.replaceState({}, '', url);
 }catch(e){}
 renderAll();
}

// ===================== LOGO UPLOAD =====================
function openLogoUpload(op){
 currentLogoOp = op;
 const inp = document.getElementById('logoUploadInput');
 const msg = document.getElementById('logoUploadMsg');
 const fn = document.getElementById('logoFileName');
 const btn = document.getElementById('logoUploadSaveBtn');
 if(inp) inp.value='';
 if(msg) msg.textContent='';
 if(fn) fn.textContent='';
 if(btn) btn.disabled=true;
 document.getElementById('logoUploadTitle').textContent = (op==='jami'?'JAMI':op)+' — logosini almashtirish';
 document.getElementById('logoUploadModal').classList.remove('hidden');
}
function closeLogoUpload(){
 document.getElementById('logoUploadModal').classList.add('hidden');
 currentLogoOp=null;
}
document.addEventListener('DOMContentLoaded',function(){
 const inp = document.getElementById('logoUploadInput');
 if(!inp) return;
 inp.addEventListener('change',function(){
  const fn = document.getElementById('logoFileName');
  const btn = document.getElementById('logoUploadSaveBtn');
  if(this.files[0]){
   if(fn) fn.textContent=this.files[0].name;
   if(btn) btn.disabled=false;
  }
 });
 // Drag & drop
 const zone = document.getElementById('logoDropZone');
 if(zone){
  zone.addEventListener('dragover',function(e){ e.preventDefault(); zone.style.borderColor='rgba(124,108,255,.7)'; });
  zone.addEventListener('dragleave',function(){ zone.style.borderColor=''; });
  zone.addEventListener('drop',function(e){
   e.preventDefault(); zone.style.borderColor='';
   const f=e.dataTransfer.files[0];
   if(f){ inp.files=e.dataTransfer.files; inp.dispatchEvent(new Event('change')); }
  });
 }
});
async function uploadLogo(){
 const inp = document.getElementById('logoUploadInput');
 const btn = document.getElementById('logoUploadSaveBtn');
 const msg = document.getElementById('logoUploadMsg');
 if(!inp || !inp.files[0] || !currentLogoOp) return;
 btn.disabled=true; btn.textContent='⏳ Yuklanmoqda...';
 msg.textContent=''; msg.style.color='';
 try{
  const form = new FormData();
  form.append('op', currentLogoOp);
  form.append('logo', inp.files[0]);
  const r = await fetch('api.php?action=upload_logo',{method:'POST',body:form});
  const d = await r.json();
  if(d.ok){
   // Icon yangilash
   const iconEl = document.getElementById('opicon-'+currentLogoOp);
   if(iconEl){
    if(currentLogoOp==='jami'){
     if(d.url.endsWith('.svg')){
      iconEl.innerHTML=`<img src="${d.url}" class="w-full h-full object-cover" alt="JAMI" onerror="this.parentElement.innerHTML='🌐'">`;
     } else {
      iconEl.innerHTML=`<img src="${d.url}" class="w-full h-full object-cover" alt="JAMI" onerror="this.parentElement.innerHTML='🌐'">`;
     }
    } else {
     if(operatorMeta[currentLogoOp]) operatorMeta[currentLogoOp].logo = d.url;
     iconEl.innerHTML=`<img src="${d.url}" class="w-full h-full object-cover" alt="${escapeHtml(currentLogoOp)}" onerror="opLogoFallback(this,'${jsEsc(currentLogoOp)}')">`;
    }
   }
   msg.textContent='✅ Muvaffaqiyatli yuklandi!'; msg.style.color='#7c6cff';
   setTimeout(()=>closeLogoUpload(), 1200);
  } else {
   msg.textContent='❌ Xato: '+(d.msg||"Noma'lum xato"); msg.style.color='#f87171';
   btn.disabled=false; btn.textContent='⬆️ Yuklash';
  }
 }catch(e){
  msg.textContent='❌ Tarmoq xatosi: '+e.message; msg.style.color='#f87171';
  btn.disabled=false; btn.textContent='⬆️ Yuklash';
 }
}

// ===================== RANKING LIST =====================
function renderRankingList(containerId, entries, total, onClickType){
 const el=document.getElementById(containerId); if(!el) return;
 if(!entries.length){ el.innerHTML='<p class="text-white/20 text-sm text-center py-6">Ma\'lumot yo\'q</p>'; return; }
 let html='';
 entries.forEach((e,i)=>{
  const k=e[0], v=e[1]; const pct = total>0 ? Math.round(v/total*1000)/10 : 0;
  const medal = medals[i] ? medals[i]+' ' : (i+1)+'. ';
  const nameAction = onClickType==='operator' ? `selectOperator('${jsEsc(k)}')` : `openModalFor('${onClickType}','${jsEsc(k)}')`;
  html += `<div class="stat-row bg-black/30 border border-white/5 p-3 rounded-xl transition">
   <div class="flex justify-between items-center mb-1.5">
    <span class="text-sm font-bold cursor-pointer hover:text-[#7c6cff]" onclick="${nameAction}">${medal}${escapeHtml(k)}</span>
    <span class="text-xs flex items-center gap-2"><b class="text-[#7c6cff]">${v}</b> <span class="text-white/30">ta • ${pct}%</span>
    <button onclick="event.stopPropagation();openModalFor('${onClickType}','${jsEsc(k)}')" class="text-white/30 hover:text-[#7c6cff]" title="To'liq ro'yxat">👁</button></span>
   </div>
   <div class="w-full h-1.5 bg-white/5 rounded-full overflow-hidden cursor-pointer" onclick="${nameAction}"><div class="h-full bg-gradient-to-r from-[#7c6cff] to-[#ffcf7a] rounded-full" style="width:${pct}%"></div></div>
  </div>`;
 });
 el.innerHTML=html;
}
function toggleSort(which){ sortMode[which]=sortMode[which]==='desc'?'asc':'desc'; const lbl=document.getElementById(which+'SortLabel'); if(lbl) lbl.textContent=sortMode[which]==='desc'?"Ko'p→Kam":"Kam→Ko'p"; renderAll(); }

// ===================== MODAL =====================
function openModalFor(type, value){
 const base=viewRows(); let rows;
 if(type==='all'){ rows=base; }
 else{ rows=base.filter(r=>{ if(type==='operator') return r.op===value; if(type==='tarif') return r.tar===value; if(type==='dealer') return r.dealer===value; return true; }); }
 const titleValue = type==='all' ? (activeOperator?activeOperator:'Barcha operatorlar') : value;
 const totalP = rows.reduce((s,r)=>s+(r.promo||1),0);
 document.getElementById('modalTitle').textContent=`${titleValue} — to'liq ro'yxat (${totalP} ta, promo bilan)`;
 let html='<table class="w-full text-xs"><thead><tr class="bg-black/50 text-white/30 tracking-widest"><th class="p-2 text-left">DILLER</th><th class="p-2 text-left">ISM</th><th class="p-2 text-left">NOMER</th><th class="p-2 text-left">OPERATOR/TARIF</th><th class="p-2 text-left">SANA</th></tr></thead><tbody>';
 rows.slice(0,1000).forEach(r=>{ html+=`<tr class="border-b border-white/5"><td class="p-2 font-bold text-white/70">${escapeHtml(r.dealer)}</td><td class="p-2">${escapeHtml(r.name)} ${r.promo==2?'<span class="text-[8px] bg-[#7c6cff]/20 text-[#7c6cff] px-1 rounded">x2</span>':''}</td><td class="p-2 font-mono">${escapeHtml(r.phone)}</td><td class="p-2">${escapeHtml(r.op)} / ${escapeHtml(r.tar)}</td><td class="p-2">${r.date}</td></tr>`; });
 html+='</tbody></table>';
 if(rows.length>1000) html+=`<p class="text-white/30 text-xs p-2">... va yana ${rows.length-1000} ta (Excel orqali to'liq yuklab oling)</p>`;
 document.getElementById('modalBody').innerHTML=html;
 document.getElementById('modalOverlay').classList.remove('hidden');
 window._modalExport={type,value};
}
function openMatrixCell(dealer, colVal){
 const mode=activeOperator?'tarif':'operator';
 const rows=viewRows().filter(r=>r.dealer===dealer&&(mode==='tarif'?r.tar===colVal:r.op===colVal));
 document.getElementById('modalTitle').textContent=`${dealer} × ${colVal} — ${rows.reduce((s,r)=>s+(r.promo||1),0)} ta`;
 let html=`<table class="w-full text-xs"><thead><tr class="bg-black/50 text-white/30"><th class="p-2 text-left">ISM</th><th class="p-2 text-left">NOMER</th><th class="p-2 text-left">${mode==='tarif'?'OPERATOR':'TARIF'}</th><th class="p-2 text-left">SANA</th></tr></thead><tbody>`;
 rows.forEach(r=>{ html+=`<tr class="border-b border-white/5"><td class="p-2">${escapeHtml(r.name)}</td><td class="p-2 font-mono">${escapeHtml(r.phone)}</td><td class="p-2">${escapeHtml(mode==='tarif'?r.op:r.tar)}</td><td class="p-2">${r.date}</td></tr>`; });
 html+='</tbody></table>';
 document.getElementById('modalBody').innerHTML=html;
 document.getElementById('modalOverlay').classList.remove('hidden');
 window._modalExport=null;
}
function closeModal(){ document.getElementById('modalOverlay').classList.add('hidden'); }
function exportModal(){
 if(!window._modalExport) return;
 const {type,value}=window._modalExport;
 let url=exportBase;
 if(activeOperator&&activeOperator!=='__other__') url+='&operator='+encodeURIComponent(activeOperator);
 if(type==='tarif') url+='&tarif='+encodeURIComponent(value);
 window.location=url;
}

// ===================== CHARTS =====================
function renderCumulative(rows){
 const byDate={};
 rows.forEach(r=>{ const d=fmtDate(r.date); byDate[d]=(byDate[d]||0)+(r.promo||1); });
 const dates=Object.keys(byDate).sort();
 destroyChart('cum');
 if(!dates.length) return;
 let running=0; const cum=dates.map(d=>{ running+=byDate[d]; return running; });
 charts.cum=new Chart(document.getElementById('cumChart'),{type:'line',data:{labels:dates,datasets:[{label:"Jami (running total)",data:cum,borderColor:'#7c6cff',backgroundColor:'rgba(124,108,255,.1)',fill:true,tension:.25,pointRadius:0}]},options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:'rgba(255,255,255,.05)'}},x:{grid:{display:false},ticks:{color:'#aaa',font:{size:9},maxRotation:60}}}}});
}
function renderConversion(rows){
 const el=document.getElementById('cardConversion'); if(!el) return;
 const agg=aggregate(rows); const total=agg.total, paid=agg.paid;
 el.textContent=(total>0?Math.round((paid/total)*1000)/10:0)+'%';
}

// ===================== MATRIX =====================
function renderMatrix(rows){
 const wrap=document.getElementById('matrixWrap'); if(!wrap) return;
 const agg=aggregate(rows);
 const mode=activeOperator?'tarif':'operator';
 const titleEl=document.getElementById('matrixTitle');
 if(titleEl) titleEl.textContent=mode==='tarif'?`🧮 Diller × Tarif jadvali (${activeOperator==='__other__'?'Boshqa':activeOperator})`:'🧮 Diller × Operator jadvali';
 const cols=mode==='tarif'?sortDesc(agg.byTarif).slice(0,10).map(e=>e[0]):sortDesc(agg.byOp).slice(0,8).map(e=>e[0]);
 const dealers=Object.keys(agg.byDealer).sort((a,b)=>agg.byDealer[b].total-agg.byDealer[a].total);
 if(!dealers.length){ wrap.innerHTML='<p class="text-white/20 text-sm text-center py-6">Ma\'lumot yo\'q</p>'; return; }
 let html='<table class="w-full text-xs"><thead><tr class="bg-black/50 text-white/40"><th class="p-2 text-left">DILLER</th>'+cols.map(c=>`<th class="p-2 text-center">${escapeHtml(c)}</th>`).join('')+'<th class="p-2 text-center">JAMI</th></tr></thead><tbody>';
 dealers.forEach(d=>{
  html+=`<tr class="border-b border-white/5 hover:bg-white/[0.03]"><td class="p-2 font-bold text-[#7c6cff]">${escapeHtml(d)}</td>`;
  cols.forEach(c=>{
   const src=mode==='tarif'?agg.byDealer[d].tarifs:agg.byDealer[d].ops;
   const cval=src[c]||0;
   html+=`<td class="p-2 text-center ${cval>0?'cursor-pointer hover:text-[#7c6cff] font-bold':'text-white/15'}" ${cval>0?`onclick="openMatrixCell('${jsEsc(d)}','${jsEsc(c)}')"`:''}>` +(cval||'-')+'</td>';
  });
  html+=`<td class="p-2 text-center font-bold text-[#7c6cff]">${agg.byDealer[d].total}</td></tr>`;
 });
 html+='</tbody></table>';
 wrap.innerHTML=html;
}

// ===================== DEALER CARDS =====================
function renderDealerCards(rows){
 const wrap=document.getElementById('dealerCardsWrap'); if(!wrap) return;
 const agg=aggregate(rows);
 const names=Object.keys(agg.byDealer).sort((a,b)=>agg.byDealer[b].total-agg.byDealer[a].total);
 if(!names.length){ wrap.innerHTML='<div class="card p-10 text-center text-white/30 lg:col-span-2">Ma\'lumot topilmadi</div>'; return; }
 let html='';
 names.forEach((name,idx)=>{
  const s=agg.byDealer[name];
  const opE=sortDesc(s.ops), tarE=sortDesc(s.tarifs);
  const topOp=opE[0], topTar=tarE[0];
  const q=qualityData[name]||{rejected:0,blocked:0};
  const lastDt=new Date(s.last.replace(' ','T'));
  const daysSince=isNaN(lastDt)?null:Math.floor((Date.now()-lastDt.getTime())/86400000);
  const activeDot=(daysSince!==null&&daysSince<=3)?'<span class="w-2 h-2 rounded-full bg-[#7c6cff] inline-block mr-1"></span>':'<span class="w-2 h-2 rounded-full bg-white/20 inline-block mr-1"></span>';
  html+=`<div class="dealer-card card p-5 card-hover" data-search="${escapeHtml(name.toLowerCase())}">
   <div class="flex justify-between items-center mb-3"><b class="text-base text-[#7c6cff] cursor-pointer hover:underline" onclick="openModalFor('dealer','${jsEsc(name)}')">${escapeHtml(name)}</b><span class="bg-white text-black text-[11px] px-3 py-1 rounded-full font-black">${s.total} ta</span></div>
   <div class="grid grid-cols-2 gap-2 mb-3 text-[11px]"><div class="bg-[#7c6cff]/10 p-2 rounded-lg text-center">O'yinda: <b class="text-[#7c6cff] text-sm">${s.paid}</b></div><div class="bg-white/5 p-2 rounded-lg text-center">Bazada: <b class="text-sm">${s.free}</b></div></div>
   <div class="bg-black/30 border border-white/5 rounded-xl p-3 mb-3 text-[11px] space-y-1">
    <p>📡 Eng ko'p ulagan operator: <b class="text-[#7c6cff]">${topOp?escapeHtml(topOp[0]):'-'}</b> ${topOp?`(${topOp[1]} ta)`:''}</p>
    <p>🏷 Eng ko'p sotgan tarif: <b class="text-[#7c6cff]">${topTar?escapeHtml(topTar[0]):'-'}</b> ${topTar?`(${topTar[1]} ta)`:''}</p>
    <p class="text-white/30">${activeDot}Oxirgi qo'shgani: ${escapeHtml(s.last)} ${daysSince!==null?`<span class="text-white/20">(${daysSince} kun oldin)</span>`:''}</p>
    ${(q.rejected>0||q.blocked>0)?`<p class="text-red-300/70">⚠️ Rad etilgan: ${q.rejected} ta • Bloklangan: ${q.blocked} ta</p>`:''}
   </div>
   <div class="grid grid-cols-2 gap-3">
    <div><p class="text-[10px] text-white/30 mb-1 text-center">Operatorlar</p><canvas id="dop${idx}" height="140"></canvas></div>
    <div><p class="text-[10px] text-white/30 mb-1 text-center">Tariflar</p><canvas id="dtar${idx}" height="140"></canvas></div>
   </div>
  </div>`;
 });
 wrap.innerHTML=html;
 names.forEach((name,idx)=>{
  const s=agg.byDealer[name];
  const opE=sortDesc(s.ops).slice(0,6), tarE=sortDesc(s.tarifs).slice(0,6);
  destroyChart('dop'+idx); destroyChart('dtar'+idx);
  charts['dop'+idx]=new Chart(document.getElementById('dop'+idx),{type:'doughnut',data:{labels:opE.map(e=>e[0]),datasets:[{data:opE.map(e=>e[1]),backgroundColor:palette}]},options:{plugins:{legend:{display:false}}}});
  charts['dtar'+idx]=new Chart(document.getElementById('dtar'+idx),{type:'doughnut',data:{labels:tarE.map(e=>e[0]),datasets:[{data:tarE.map(e=>e[1]),backgroundColor:palette}]},options:{plugins:{legend:{display:false}}}});
 });
}

// ===================== LIVE COUNTER =====================
function renderLiveCounter(){
 const el=document.getElementById('liveTodayCount'); if(!el) return;
 function tick(){ fetch('api.php?action=today_count&dealer_id=<?php echo intval($did); ?>').then(r=>r.json()).then(d=>{ el.textContent=(d.count||0)+' ta'; }).catch(()=>{}); }
 tick(); setInterval(tick,20000);
}
renderLiveCounter();

// ===================== RENDER ALL =====================
function renderAll(){
 renderOperatorCards();

 const titleEl=document.getElementById('viewTitle');
 if(activeOperator==='__other__') titleEl.textContent="📊 Boshqa operatorlar — to'liq statistika";
 else if(activeOperator) titleEl.textContent=`📊 ${activeOperator} — to'liq statistika`;
 else titleEl.textContent="📊 Barcha operatorlar statistikasi";
 document.getElementById('backBtn').classList.toggle('hidden',!activeOperator);
 const opRankBlock=document.getElementById('operatorRankBlock');
 if(opRankBlock) opRankBlock.classList.toggle('hidden',!!activeOperator);

 const rows=viewRows();
 const agg=aggregate(rows);

 document.getElementById('cardTotal').textContent=agg.total;
 document.getElementById('cardPaid').textContent=agg.paid;
 document.getElementById('cardFree').textContent=agg.free;
 document.getElementById('cardDealers').textContent=Object.keys(agg.byDealer).length;

 let opEntries=sortDesc(agg.byOp); if(sortMode.op==='asc') opEntries=opEntries.slice().reverse();
 let tarEntries=sortDesc(agg.byTarif); if(sortMode.tarif==='asc') tarEntries=tarEntries.slice().reverse();
 if(!activeOperator){ renderRankingList('opList',opEntries,agg.total,'operator'); }
 renderRankingList('tarifList',tarEntries.slice(0,30),agg.total,'tarif');

 destroyChart('op'); destroyChart('tarif');
 if(!activeOperator&&opEntries.length){ charts.op=new Chart(document.getElementById('opChart'),{type:'doughnut',data:{labels:opEntries.map(e=>e[0]),datasets:[{data:opEntries.map(e=>e[1]),backgroundColor:palette}]},options:{plugins:{legend:{position:'bottom',labels:{color:'#aaa',font:{size:10}}}}}}); }
 if(tarEntries.length){ const top8=sortDesc(agg.byTarif).slice(0,8); charts.tarif=new Chart(document.getElementById('tarifChart'),{type:'bar',data:{labels:top8.map(e=>e[0]),datasets:[{label:'Soni',data:top8.map(e=>e[1]),backgroundColor:'#7c6cff',borderRadius:6}]},options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:'rgba(255,255,255,.05)'}},x:{grid:{display:false},ticks:{color:'#aaa',font:{size:9}}}}}}); }

 renderCumulative(rows);
 renderConversion(rows);

 if(document.getElementById('dealerRankList')){
  const dealerTotals=Object.fromEntries(Object.entries(agg.byDealer).map(([k,v])=>[k,v.total]));
  renderRankingList('dealerRankList',sortDesc(dealerTotals),agg.total,'dealer');
 }
 if(isSuperView) renderMatrix(rows);
 renderDealerCards(rows);

 document.querySelectorAll('#fullTableBody tr[data-search]').forEach(function(el){
  const matchSearch=searchTerm===''||el.dataset.search.indexOf(searchTerm)!==-1;
  const matchOp=!activeOperator||(activeOperator==='__other__'?!knownOpsSet.has(el.dataset.op):el.dataset.op===activeOperator);
  el.style.display=(matchSearch&&matchOp)?'':'none';
 });
}
function filterStats(q){ searchTerm=q.trim().toLowerCase(); renderAll(); }

renderAll();
</script>
<?php include 'layout_footer.php'; ?>