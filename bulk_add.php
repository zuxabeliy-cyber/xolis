<?php include 'layout_header.php'; if(!$isSuper) exit;
try{ $ops=db()->query("SELECT name FROM operators")->fetchAll(); }catch(Exception $e){ $ops=[]; }
try{ $tAll=db()->query("SELECT operator_name, name FROM tarifs")->fetchAll(); }catch(Exception $e){ $tAll=[]; }
$tm=[]; foreach($tAll as $t){ $tm[$t['operator_name']][]=$t['name']; }
try{ $dils=db()->query("SELECT * FROM dealers WHERE role='diller'")->fetchAll(); }catch(Exception $e){ $dils=[]; }
try{ $muRows=db()->query("SELECT operator_name, tarif_name, COUNT(*) c FROM paid_participants WHERE status='approved' GROUP BY operator_name, tarif_name ORDER BY c DESC")->fetchAll(); }catch(Exception $e){ $muRows=[]; }
$mostUsed=[]; foreach($muRows as $r){ if(!isset($mostUsed[$r['operator_name']])) $mostUsed[$r['operator_name']]=$r['tarif_name']; }
$msg='';
if($_POST && isset($_POST['rows'])){
 $cdate=trim($_POST['created_date'] ?? '');
 $createdAt = ($cdate!=='' && preg_match('/^\d{4}-\d{2}-\d{2}$/',$cdate)) ? ($cdate.' '.date('H:i:s')) : date('Y-m-d H:i:s');
 list($add,$skipped) = bulkInsertParticipants($_POST['rows'], $createdAt);
 logActivity('import', "$add ta qo'lda qo'shildi (ALL+)");
 $msg="<div class='card p-3 mb-3 bg-white/5'>✅ $add ta qo'shildi".($skipped>0?" • ⚠️ $skipped ta o'tkazib yuborildi (nomer bo'sh/takroriy yoki diller tanlanmagan)":'')."</div>";
}
// ==== Excel / CSV import ====
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_FILES['csv']) && $_FILES['csv']['error']===UPLOAD_ERR_OK){
 $impDealer=intval($_POST['imp_dealer']??0);
 $impPaid=intval($_POST['imp_paid']??0);
 $cdate=trim($_POST['imp_date']??'');
 $createdAt=($cdate!=='' && preg_match('/^\d{4}-\d{2}-\d{2}$/',$cdate))?($cdate.' '.date('H:i:s')):date('Y-m-d H:i:s');
 $raw=file_get_contents($_FILES['csv']['tmp_name']);
 $raw=preg_replace('/^\xEF\xBB\xBF/','',$raw); // BOM olib tashlash
 $lines=preg_split('/\r\n|\r|\n/',$raw); $rowsData=[]; $ln=0;
 foreach($lines as $line){ $line=rtrim($line); if(trim($line)==='') continue; $ln++;
  $delim=(strpos($line,';')!==false)?';':((strpos($line,"\t")!==false)?"\t":',');
  $c=str_getcsv($line,$delim);
  if($ln==1 && preg_match('/phone|nomer|telefon|ism|name|operator/i',$line)) continue; // sarlavha qatori
  $name=trim($c[0]??''); $phone=trim($c[1]??''); $op=trim($c[2]??''); $tar=trim($c[3]??'');
  if($phone==='' && preg_match('/\d{7,}/',$name)){ $phone=$name; $name=''; } // ustunlar almashgan bo'lsa
  if($phone==='') continue;
  $rowsData[]=['name'=>$name,'phone'=>$phone,'operator'=>$op,'tarif'=>$tar,'is_paid'=>$impPaid,'dealer_id'=>$impDealer];
 }
 if($impDealer>0 && $rowsData){ list($add,$skipped)=bulkInsertParticipants($rowsData,$createdAt); logActivity('import',"$add ta CSV import qilindi"); $msg="<div class='card p-3 mb-3 bg-white/5'>📥 CSV import: <b>$add</b> ta qo'shildi".($skipped>0?" • ⚠️ $skipped ta o'tkazildi (bo'sh/takroriy)":'')."</div>"; }
 else $msg="<div class='card p-3 mb-3 bg-red-500/10 border border-red-500/20 text-red-300'>⚠️ Diller tanlang va faylda kamida bitta to'g'ri nomer bo'lsin.</div>";
}
?>
<h1 class="font-black text-xl mb-3 flex items-center gap-2"><?php echo icon('package','w-5 h-5'); ?> ALL+</h1><?php echo $msg; ?>
<p class="text-white/30 text-xs -mt-2 mb-4">Qatorlarni qo'lda to'ldiring. Har bir qatorni alohida tahrirlash mumkin.</p>

<div class="card p-4 mb-3">
<p class="text-xs text-white/40 mb-2">🔁 Barchasiga bir xil qo'llash <span class="text-white/25">(ixtiyoriy — tanlamasangiz har bir qator o'zicha qoladi)</span></p>
<div class="grid md:grid-cols-4 gap-2">
<select id="bulkDealer" class="p-3 rounded-xl bg-[#16162a] border border-white/10 text-sm text-white"><option value="">— Diller (har xil) —</option><?php foreach($dils as $d): ?><option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option><?php endforeach; ?></select>
<select id="bulkOperator" class="p-3 rounded-xl bg-[#16162a] border border-white/10 text-sm text-white"><option value="">— Operator (har xil) —</option><?php foreach($ops as $o): ?><option value="<?php echo htmlspecialchars($o['name']); ?>"><?php echo htmlspecialchars($o['name']); ?></option><?php endforeach; ?></select>
<select id="bulkTarif" class="p-3 rounded-xl bg-[#16162a] border border-white/10 text-sm text-white"><option value="">— Tarif (har xil) —</option></select>
<select id="bulkPaid" class="p-3 rounded-xl bg-[#16162a] border border-white/10 text-sm text-white"><option value="">— Baza/O'yin (har xil) —</option><option value="0">BAZAGA</option><option value="1">O'YINGA</option></select>
</div>
</div>

<div class="card p-4 mb-3 border-[#7c6cff]/20">
<h3 class="font-bold text-sm mb-1">📥 Excel / CSV dan import</h3>
<p class="text-[11px] text-white/40 mb-3">Fayl ustunlari tartibi: <b>Ism, Nomer, Operator, Tarif</b> (vergul, nuqta-vergul yoki tab bilan ajratilgan). Diller va Baza/O'yin butun fayl uchun tanlanadi.</p>
<form method="post" enctype="multipart/form-data" class="grid md:grid-cols-4 gap-2 items-end">
<div><label class="text-[10px] text-white/40">Diller</label><select name="imp_dealer" required class="w-full mt-1 p-3 rounded-xl bg-[#16162a] border border-white/10 text-sm text-white"><option value="">Diller tanla</option><?php foreach($dils as $d): ?><option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option><?php endforeach; ?></select></div>
<div><label class="text-[10px] text-white/40">Baza/O'yin</label><select name="imp_paid" class="w-full mt-1 p-3 rounded-xl bg-[#16162a] border border-white/10 text-sm text-white"><option value="0">BAZAGA</option><option value="1">O'YINGA</option></select></div>
<div><label class="text-[10px] text-white/40">Sana (ixtiyoriy)</label><input type="date" name="imp_date" max="<?php echo date('Y-m-d'); ?>" class="w-full mt-1 p-3 rounded-xl bg-black/50 border border-white/10 text-white text-sm outline-none"></div>
<div><label class="text-[10px] text-white/40">CSV fayl</label><input type="file" name="csv" accept=".csv,text/csv,.txt" required class="w-full mt-1 text-xs text-white/70"></div>
<button class="btn btn-primary md:col-span-4">📥 Import qilish</button>
</form>
</div>

<div class="card p-4"><form method="post" id="bulkForm">
<div class="mb-3"><label class="text-xs text-white/40">Sana (ixtiyoriy — bo'sh qoldirsangiz bugungi kun, hammasi shu sanaga qo'shiladi)</label><input type="date" name="created_date" max="<?php echo date('Y-m-d'); ?>" class="w-full mt-1 p-3 rounded-xl bg-black/50 border border-white/10 text-white outline-none"></div>
<div class="flex justify-between items-center mb-2"><span id="rowCount" class="text-xs text-white/30">0 ta qator</span></div>
<div id="rows" class="space-y-2"></div>
<button type="button" onclick="addRow()" class="w-full mt-3 bg-white/5 border border-white/10 p-3 rounded-xl text-sm">+ Qator</button>
<button class="btn btn-primary w-full mt-3 py-4">✅ QO'SHISH</button>
</form></div>
<script>
const tm=<?php echo json_encode($tm, JSON_UNESCAPED_UNICODE); ?>; const ops=<?php echo json_encode(array_column($ops,'name'), JSON_UNESCAPED_UNICODE); ?>; const dils=<?php echo json_encode(array_map(fn($d)=>['id'=>$d['id'],'name'=>$d['name']], $dils), JSON_UNESCAPED_UNICODE); ?>; const mostUsed=<?php echo json_encode($mostUsed, JSON_UNESCAPED_UNICODE); ?>; let idx=0;
const codeMap={'90':'Beeline','91':'Beeline','92':'Beeline','93':'Ucell','94':'Ucell','50':'Ucell','95':'Uztelecom','99':'Uztelecom','70':'Uztelecom','77':'Uztelecom','97':'Mobiuz','88':'Mobiuz','87':'Mobiuz','33':'Humans'};
function fmt(v){ v=v.replace(/\D/g,''); if(v.startsWith('998')) v=v.substring(3); if(v.length>9) v=v.substring(0,9); let f='+998'; if(v.length>0) f+=' '+v.substring(0,2); if(v.length>2) f+=' '+v.substring(2,5); if(v.length>5) f+=' '+v.substring(5,7); if(v.length>7) f+=' '+v.substring(7,9); return f; }
function updRowCount(){ document.getElementById('rowCount').textContent = document.querySelectorAll('#rows > div').length+" ta qator"; }
function autoFmt(el){
 const raw=el.value.replace(/\D/g,'').replace(/^998/,'');
 el.value=fmt(el.value);
 if(raw.length>=2){
  const code=raw.substring(0,2); const opName=codeMap[code];
  if(opName){ const row=el.closest('div'); const opSel=row.querySelector('.op'); if(opSel && opSel.value!==opName){ opSel.value=opName; upd(opSel); } }
 }
}
function removeRow(btn){ btn.closest('#rows > div').remove(); updRowCount(); }
function phoneKeydown(e, phoneInput){
 if(e.key==='Enter'){ e.preventDefault(); const row=phoneInput.closest('div'); if(row.nextElementSibling){ row.nextElementSibling.querySelector('input[name*="[phone]"]').focus(); } else { addRow(); document.getElementById('rows').lastElementChild.querySelector('input[name*="[phone]"]').focus(); } }
}
function addRow(){
 const c=document.getElementById('rows'); const d=document.createElement('div'); d.className='grid md:grid-cols-8 gap-2 bg-black/20 p-2 rounded-xl border border-white/5 items-center';
 d.innerHTML = `<input name="rows[${idx}][name]" placeholder="Ism" class="p-2 rounded-lg bg-[#16162a] border border-white/10 text-sm text-white">`+
  `<input name="rows[${idx}][phone]" placeholder="+998" oninput="autoFmt(this)" onkeydown="phoneKeydown(event,this)" class="p-2 rounded-lg bg-[#16162a] border border-white/10 text-sm font-mono text-white">`+
  `<select name="rows[${idx}][operator]" onchange="upd(this)" class="op p-2 rounded-lg bg-[#16162a] border border-white/10 text-sm text-white">${ops.map(o=>`<option>${o}</option>`).join('')}</select>`+
  `<select name="rows[${idx}][tarif]" class="tar p-2 rounded-lg bg-[#16162a] border border-white/10 text-sm text-white"></select>`+
  `<select name="rows[${idx}][is_paid]" class="p-2 rounded-lg bg-[#16162a] text-sm text-white"><option value="0">BAZAGA</option><option value="1">O'YINGA</option></select>`+
  `<select name="rows[${idx}][dealer_id]" required class="p-2 rounded-lg bg-[#16162a] border border-white/10 text-sm text-white"><option value="">Diller</option>${dils.map(x=>`<option value="${x.id}">${x.name}</option>`).join('')}</select>`+
  `<label class="flex items-center gap-1 text-[10px] text-[#7c6cff] cursor-pointer"><input type="checkbox" name="rows[${idx}][promo_1_1]" value="1" class="w-4 h-4 accent-[#7c6cff]">1+1</label>`+
  `<button type="button" onclick="removeRow(this)" class="text-red-400 hover:text-red-300 text-lg font-black" title="Qatorni o'chirish">✕</button>`;
 c.appendChild(d);
 const bd=document.getElementById('bulkDealer').value; const dealerSel=d.querySelector('select[name*="[dealer_id]"]'); if(bd) dealerSel.value=bd;
 const bo=document.getElementById('bulkOperator').value; const opSel=d.querySelector('.op'); if(bo) opSel.value=bo;
 upd(opSel);
 const bt=document.getElementById('bulkTarif').value; const tarSel=d.querySelector('.tar'); if(bt && [...tarSel.options].some(o=>o.value===bt)) tarSel.value=bt;
 const bp=document.getElementById('bulkPaid').value; const paidSel=d.querySelector('select[name*="[is_paid]"]'); if(bp!=='') paidSel.value=bp;
 idx++; updRowCount();
 return d;
}
function upd(s){ const r=s.closest('div'); const t=r.querySelector('.tar'); t.innerHTML=''; (tm[s.value]||[]).forEach(v=>{ let o=document.createElement('option'); o.value=v; o.textContent=v; t.appendChild(o); }); if(mostUsed[s.value] && [...t.options].some(o=>o.value===mostUsed[s.value])){ t.value=mostUsed[s.value]; } }

// Barchasiga bir xil qo'llash paneli
document.getElementById('bulkDealer').addEventListener('change', e=>{
 const v=e.target.value; if(!v) return;
 document.querySelectorAll('select[name*="[dealer_id]"]').forEach(s=>s.value=v);
});
document.getElementById('bulkOperator').addEventListener('change', e=>{
 const v=e.target.value;
 const bulkTar=document.getElementById('bulkTarif');
 bulkTar.innerHTML='<option value="">— Tarif (har xil) —</option>';
 (tm[v]||[]).forEach(t=>{ let o=document.createElement('option'); o.value=t; o.textContent=t; bulkTar.appendChild(o); });
 if(mostUsed[v]) bulkTar.value=mostUsed[v];
 if(!v) return;
 document.querySelectorAll('.op').forEach(s=>{ s.value=v; upd(s); });
});
document.getElementById('bulkTarif').addEventListener('change', e=>{
 const v=e.target.value; if(!v) return;
 document.querySelectorAll('.tar').forEach(s=>{ if([...s.options].some(o=>o.value===v)) s.value=v; });
});
document.getElementById('bulkPaid').addEventListener('change', e=>{
 const v=e.target.value; if(v==='') return;
 document.querySelectorAll('select[name*="[is_paid]"]').forEach(s=>s.value=v);
});
for(let i=0;i<3;i++) addRow();
</script><?php include 'layout_footer.php'; ?>
