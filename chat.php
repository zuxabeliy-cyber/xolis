<?php require_once __DIR__.'/config.php'; requireLogin(); $u=$_SESSION['user'];
$uploadDir = __DIR__.'/chat_uploads';
if(!is_dir($uploadDir)){ @mkdir($uploadDir, 0775, true); }

// ---- AJAX / API qismi (sahifa qayta yuklanmasdan ishlaydi) ----
$action = $_GET['action'] ?? ($_POST['action'] ?? '');
if($action){
 header('Content-Type: application/json; charset=utf-8');
 if($action==='unread_count'){
  echo json_encode(['count'=>chatUnreadCount($u['id'])]); exit;
 }
 if($action==='send'){
  $text = trim($_POST['message'] ?? '');
  $imgPath = null;
  if(isset($_FILES['image']) && $_FILES['image']['error']===UPLOAD_ERR_OK){
   $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
   $allowed = ['jpg','jpeg','png','gif','webp'];
   if(in_array($ext, $allowed, true) && $_FILES['image']['size'] <= 6*1024*1024){
    $fname = 'c_'.time().'_'.bin2hex(random_bytes(5)).'.'.$ext;
    if(move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir.'/'.$fname)){
     $imgPath = 'chat_uploads/'.$fname;
    }
   }
  }
  if($text==='' && !$imgPath){ echo json_encode(['ok'=>false,'error'=>'empty']); exit; }
  try{
   db()->prepare("INSERT INTO chat_messages (sender_id,sender_name,message,image_path) VALUES (?,?,?,?)")->execute([$u['id'],$u['name'],$text,$imgPath]);
   echo json_encode(['ok'=>true]);
  }catch(Exception $e){ echo json_encode(['ok'=>false,'error'=>'db']); }
  exit;
 }
 if($action==='list'){
  $afterId = intval($_GET['after_id'] ?? 0);
  try{
   if($afterId>0){
    $st=db()->prepare("SELECT id,sender_id,sender_name,message,image_path,created_at FROM chat_messages WHERE id>? ORDER BY id ASC LIMIT 200");
    $st->execute([$afterId]);
   } else {
    $st=db()->query("SELECT id,sender_id,sender_name,message,image_path,created_at FROM chat_messages ORDER BY id DESC LIMIT 60");
   }
   $rows = $st->fetchAll();
   if($afterId<=0) $rows = array_reverse($rows);
   try{ $maxId=(int)db()->query("SELECT MAX(id) FROM chat_messages")->fetchColumn(); if($maxId>0){ db()->prepare("UPDATE dealers SET last_seen_chat_id=? WHERE id=?")->execute([$maxId,$u['id']]); } }catch(Exception $e){}
   echo json_encode(['ok'=>true,'rows'=>$rows,'me'=>$u['id']]);
  }catch(Exception $e){ echo json_encode(['ok'=>false,'rows'=>[]]); }
  exit;
 }
 echo json_encode(['ok'=>false]); exit;
}

include 'layout_header.php';
?>
<div class="flex items-center justify-between mb-3">
<h1 class="font-black text-xl flex items-center gap-2"><?php echo icon('message','w-5 h-5'); ?> Telegram</h1>
<span class="text-[10px] text-white/30">Hamma diller va adminlar shu yerda yozishadi</span>
</div>

<div class="card p-0 overflow-hidden flex flex-col" style="height:70vh">
 <div id="chatBody" class="flex-1 overflow-auto p-4 space-y-3"></div>
 <div id="imgPreviewWrap" class="hidden px-4 pb-2"><div class="relative inline-block"><img id="imgPreview" class="h-20 rounded-lg border border-white/10 object-cover"><button type="button" onclick="clearImage()" class="absolute -top-2 -right-2 bg-red-500 text-white w-6 h-6 rounded-full text-xs font-black">✕</button></div></div>
 <form id="chatForm" class="p-3 border-t border-white/5 flex gap-2 items-end bg-black/20 backdrop-blur">
  <label class="w-11 h-11 shrink-0 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center cursor-pointer hover:bg-white/10 transition">
   📎<input type="file" id="chatImage" accept="image/*" class="hidden" onchange="previewImage(this)">
  </label>
  <textarea id="chatText" rows="1" placeholder="Xabar yozing..." class="flex-1 p-3 rounded-xl bg-black/50 border border-white/10 text-white outline-none resize-none focus:border-[#7c6cff]/50"></textarea>
  <button type="submit" class="w-11 h-11 shrink-0 rounded-xl bg-[#7c6cff] text-white font-black btn-glow">➤</button>
 </form>
</div>

<script>
let lastId = 0;
const meId = <?php echo intval($u['id']); ?>;
function escapeHtml(s){ const d=document.createElement('div'); d.textContent=String(s); return d.innerHTML; }
function fmtTime(dt){ const d=new Date(dt.replace(' ','T')); if(isNaN(d)) return dt; return d.toLocaleString('uz-UZ',{day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'}); }

function renderMsg(m){
 const mine = parseInt(m.sender_id)===meId;
 const body = document.getElementById('chatBody');
 const wrap = document.createElement('div');
 wrap.className = 'flex ' + (mine ? 'justify-end' : 'justify-start');
 let inner = `<div class="max-w-[75%] ${mine?'bg-[#7c6cff] text-white':'bg-white/[0.06] border border-white/10 text-white'} rounded-2xl px-4 py-2.5">`;
 if(!mine) inner += `<p class="text-[10px] font-black ${mine?'text-black/60':'text-[#7c6cff]'} mb-0.5">${escapeHtml(m.sender_name)}</p>`;
 if(m.image_path) inner += `<img src="${escapeHtml(m.image_path)}" class="rounded-xl mb-1 max-h-64 object-cover cursor-pointer" onclick="window.open('${escapeHtml(m.image_path)}','_blank')">`;
 if(m.message) inner += `<p class="text-sm whitespace-pre-wrap break-words">${escapeHtml(m.message)}</p>`;
 inner += `<p class="text-[9px] ${mine?'text-black/50':'text-white/30'} mt-1 text-right">${fmtTime(m.created_at)}</p></div>`;
 wrap.innerHTML = inner;
 body.appendChild(wrap);
}

function loadInitial(){
 fetch('chat.php?action=list').then(r=>r.json()).then(d=>{
  if(!d.ok) return;
  const body=document.getElementById('chatBody'); body.innerHTML='';
  d.rows.forEach(renderMsg);
  d.rows.forEach(m=>{ if(m.id>lastId) lastId=m.id; });
  body.scrollTop = body.scrollHeight;
 });
}
function poll(){
 fetch('chat.php?action=list&after_id='+lastId).then(r=>r.json()).then(d=>{
  if(!d.ok || !d.rows.length) return;
  const body=document.getElementById('chatBody');
  const atBottom = body.scrollHeight - body.scrollTop - body.clientHeight < 60;
  d.rows.forEach(m=>{ renderMsg(m); if(m.id>lastId) lastId=m.id; });
  if(atBottom) body.scrollTop = body.scrollHeight;
 }).catch(()=>{});
}
loadInitial();
setInterval(poll, 3000);

let pendingFile = null;
function previewImage(inp){
 if(!inp.files || !inp.files[0]) return;
 pendingFile = inp.files[0];
 const url = URL.createObjectURL(pendingFile);
 document.getElementById('imgPreview').src = url;
 document.getElementById('imgPreviewWrap').classList.remove('hidden');
}
function clearImage(){ pendingFile=null; document.getElementById('chatImage').value=''; document.getElementById('imgPreviewWrap').classList.add('hidden'); }

document.getElementById('chatForm').addEventListener('submit', function(e){
 e.preventDefault();
 const text = document.getElementById('chatText').value.trim();
 if(!text && !pendingFile) return;
 const fd = new FormData();
 fd.append('action','send');
 fd.append('message', text);
 if(pendingFile) fd.append('image', pendingFile);
 fetch('chat.php', {method:'POST', body:fd}).then(r=>r.json()).then(d=>{
  if(d.ok){ document.getElementById('chatText').value=''; clearImage(); poll(); }
 });
});
document.getElementById('chatText').addEventListener('keydown', function(e){
 if(e.key==='Enter' && !e.shiftKey){ e.preventDefault(); document.getElementById('chatForm').requestSubmit(); }
});
</script>
<?php include 'layout_footer.php'; ?>
