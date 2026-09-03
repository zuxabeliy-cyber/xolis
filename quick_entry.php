<?php include 'layout_header.php'; if(!$isSuper) exit;
try{ $ops=db()->query("SELECT name FROM operators")->fetchAll(); }catch(Exception $e){ $ops=[]; }
try{ $tAll=db()->query("SELECT operator_name, name FROM tarifs")->fetchAll(); }catch(Exception $e){ $tAll=[]; }
$tm=[]; foreach($tAll as $t){ $tm[$t['operator_name']][]=$t['name']; }
try{ $dils=db()->query("SELECT * FROM dealers WHERE role='diller'")->fetchAll(); }catch(Exception $e){ $dils=[]; }
$msg='';
if($_POST && isset($_POST['rows'])){
 $cdate=trim($_POST['created_date'] ?? '');
 $createdAt = ($cdate!=='' && preg_match('/^\d{4}-\d{2}-\d{2}$/',$cdate)) ? ($cdate.' '.date('H:i:s')) : date('Y-m-d H:i:s');
 list($add,$skipped) = bulkInsertParticipants($_POST['rows'], $createdAt);
 $msg="<div class='card p-3 mb-3 bg-white/5'>✅ $add ta qo'shildi".($skipped>0?" • ⚠️ $skipped ta o'tkazib yuborildi (nomer bo'sh/takroriy yoki diller tanlanmagan)":'')."</div>";
}
?>
<h1 class="font-black text-xl mb-3 flex items-center gap-2"><?php echo icon('keyboard','w-5 h-5'); ?> Tez terish</h1><?php echo $msg; ?>

<div class="card p-4 mb-4 border-[#7c6cff]/20">
<h3 class="font-bold mb-3 text-sm">⚙️ Standart qiymatlar <span class="text-white/30 font-normal">— yangi qatorlarga avtomatik qo'yiladi</span></h3>
<div class="grid md:grid-cols-5 gap-2">
<select id="defDealer" class="p-3 rounded-xl bg-[#16162a] border border-white/10 text-white text-sm"><option value="">Diller tanla...</option><?php foreach($dils as $d): ?><option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option><?php endforeach; ?></select>
<select id="defOp" class="p-3 rounded-xl bg-[#16162a] border border-white/10 text-white text-sm"><?php foreach($ops as $o): ?><option><?php echo htmlspecialchars($o['name']); ?></option><?php endforeach; ?></select>
<select id="defTar" class="p-3 rounded-xl bg-[#16162a] border border-white/10 text-white text-sm"><option value="">Tarif — hammasiga bir xil</option></select>
<select id="defPaid" class="p-3 rounded-xl bg-[#16162a] border border-white/10 text-white text-sm"><option value="0">BAZAGA</option><option value="1">O'YINGA</option></select>
<label class="flex items-center gap-2 bg-[#16162a] border border-white/10 rounded-xl px-3 text-xs text-[#7c6cff] cursor-pointer"><input type="checkbox" id="defPromo" class="w-4 h-4 accent-[#7c6cff]">🎁 Hammasiga 1+1</label>
</div>
<p class="text-[10px] text-white/25 mt-2">Diller, operator va tarifni bir marta tanlang — pastda qo'shiladigan har bir yangi qatorga (qo'lda ham, joylashtirishda ham) shu qiymatlar avtomatik qo'yiladi. Kerak bo'lsa, pastda har bir qatorni alohida ham o'zgartirish mumkin.</p>
</div>

<div class="card p-4 mb-4">
<h3 class="font-bold mb-2 text-sm">🚀 Tez joylashtirish <span class="text-white/30 font-normal">— nomerlarni qatorma-qator joylashtiring</span></h3>
<p class="text-[11px] text-white/30 mb-2">Har qatorga bitta nomer. Xohlasangiz ism bilan birga: <code class="text-[#7c6cff]">Ism, +998901234567</code> — yoki shunchaki nomerning o'zi.</p>
<textarea id="pasteBox" rows="4" placeholder="Aziz, +998901234567&#10;+998931112233&#10;Botir, 998971234567" class="w-full p-3 rounded-xl bg-black/50 border border-white/10 text-white text-sm font-mono outline-none focus:border-[#7c6cff]/50"></textarea>
<button type="button" onclick="pasteToRows()" class="mt-2 bg-[#7c6cff] text-white px-5 py-2.5 rounded-xl text-xs font-black">⬇️ Qatorlarga aylantirish</button>
</div>

<div class="card p-4"><form method="post" id="bulkForm">
<div class="mb-3"><label class="text-xs text-white/40">Sana (ixtiyoriy — bo'sh qoldirsangiz bugungi kun, hammasi shu sanaga qo'shiladi)</label><input type="date" name="created_date" max="<?php echo date('Y-m-d'); ?>" class="w-full mt-1 p-3 rounded-xl bg-black/50 border border-white/10 text-white outline-none"></div>
<div class="flex justify-between items-center mb-2"><span id="rowCount" class="text-xs text-white/30">0 ta qator</span></div>
<div id="rows" class="space-y-2"></div>
<button type="button" onclick="addRow()" class="w-full mt-3 bg-white/5 border border-white/10 p-3 rounded-xl text-sm">+ Qator</button>
<button class="w-full mt-3 bg-white text-black p-4 rounded-xl font-black">QO'SHISH</button>
</form></div>
<script>
const tm=<?php echo json_encode($tm, JSON_UNESCAPED_UNICODE); ?>; const ops=<?php echo json_encode(array_column($ops,'name'), JSON_UNESCAPED_UNICODE); ?>; const dils=<?php echo json_encode(array_map(fn($d)=>['id'=>$d['id'],'name'=>$d['name']], $dils), JSON_UNESCAPED_UNICODE); ?>; let idx=0;
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
function addRow(prefill){
 prefill = prefill || {};
 const c=document.getElementById('rows'); const d=document.createElement('div'); d.className='grid md:grid-cols-8 gap-2 bg-black/20 p-2 rounded-xl border border-white/5 items-center';
 const defDealer=document.getElementById('defDealer').value; const defOp=document.getElementById('defOp').value; const defPaid=document.getElementById('defPaid').value; const defPromo=document.getElementById('defPromo').checked; const defTar=document.getElementById('defTar').value;
 const nameVal = prefill.name||''; const phoneVal = prefill.phone ? fmt(prefill.phone) : '';
 d.innerHTML = `<input name="rows[${idx}][name]" value="${nameVal.replace(/"/g,'&quot;')}" placeholder="Ism" class="p-2 rounded-lg bg-[#16162a] border border-white/10 text-sm text-white">`+
  `<input name="rows[${idx}][phone]" value="${phoneVal}" placeholder="+998" oninput="autoFmt(this)" onkeydown="phoneKeydown(event,this)" class="p-2 rounded-lg bg-[#16162a] border border-white/10 text-sm font-mono text-white">`+
  `<select name="rows[${idx}][operator]" onchange="upd(this)" class="op p-2 rounded-lg bg-[#16162a] border border-white/10 text-sm text-white">${ops.map(o=>`<option ${o===defOp?'selected':''}>${o}</option>`).join('')}</select>`+
  `<select name="rows[${idx}][tarif]" class="tar p-2 rounded-lg bg-[#16162a] border border-white/10 text-sm text-white"></select>`+
  `<select name="rows[${idx}][is_paid]" class="p-2 rounded-lg bg-[#16162a] text-sm text-white"><option value="0" ${defPaid==='0'?'selected':''}>BAZAGA</option><option value="1" ${defPaid==='1'?'selected':''}>O'YINGA</option></select>`+
  `<select name="rows[${idx}][dealer_id]" required class="p-2 rounded-lg bg-[#16162a] border border-white/10 text-sm text-white"><option value="">Diller</option>${dils.map(x=>`<option value="${x.id}" ${String(x.id)===defDealer?'selected':''}>${x.name}</option>`).join('')}</select>`+
  `<label class="flex items-center gap-1 text-[10px] text-[#7c6cff] cursor-pointer"><input type="checkbox" name="rows[${idx}][promo_1_1]" value="1" ${defPromo?'checked':''} class="w-4 h-4 accent-[#7c6cff]">1+1</label>`+
  `<button type="button" onclick="removeRow(this)" class="text-red-400 hover:text-red-300 text-lg font-black" title="Qatorni o'chirish">✕</button>`;
 c.appendChild(d);
 const opSel=d.querySelector('.op');
 if(prefill.opOverride){ opSel.value=prefill.opOverride; upd(opSel); }
 else { upd(opSel); const tarSel=d.querySelector('.tar'); if(defTar && [...tarSel.options].some(o=>o.value===defTar)){ tarSel.value=defTar; } }
 idx++; updRowCount();
 return d;
}
function upd(s){ const r=s.closest('div'); const t=r.querySelector('.tar'); t.innerHTML=''; (tm[s.value]||[]).forEach(v=>{ let o=document.createElement('option'); o.value=v; o.textContent=v; t.appendChild(o); }); }
function updDefTar(){
 const o=document.getElementById('defOp').value; const t=document.getElementById('defTar');
 t.innerHTML='<option value="">Tarif — hammasiga bir xil</option>';
 (tm[o]||[]).forEach(v=>{ let op=document.createElement('option'); op.value=v; op.textContent=v; t.appendChild(op); });
}
document.getElementById('defOp').addEventListener('change', updDefTar); updDefTar();
function pasteToRows(){
 const raw=document.getElementById('pasteBox').value; if(!raw.trim()) return;
 const lines=raw.split('\n').map(l=>l.trim()).filter(l=>l);
 lines.forEach(line=>{
  let name='', phoneRaw=line;
  if(line.includes(',')){ const parts=line.split(','); name=parts[0].trim(); phoneRaw=parts.slice(1).join(',').trim(); }
  const digits=phoneRaw.replace(/\D/g,'').replace(/^998/,'');
  if(digits.length<9) return;
  const code=digits.substring(0,2); const opName=codeMap[code];
  addRow({name:name, phone:digits, opOverride:opName});
 });
 document.getElementById('pasteBox').value='';
}
for(let i=0;i<3;i++) addRow();
</script><?php include 'layout_footer.php'; ?>
