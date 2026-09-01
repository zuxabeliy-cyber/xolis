<?php require 'config.php'; $err='';
if($_POST){
 $l=trim($_POST['login']); $p=trim($_POST['pass']);
 if(!isset($_SESSION['fail_count'])) $_SESSION['fail_count']=0;
 if($_SESSION['fail_count']>=5){ sleep(2); }
 try{
  $st=db()->prepare("SELECT * FROM dealers WHERE login=?"); $st->execute([$l]); $u=$st->fetch();
  if($u && password_verify($p,$u['password'])){
   session_regenerate_id(true);
   unset($_SESSION['fail_count']);
   $_SESSION['user']=$u; header("Location: reports.php"); exit;
  }
 }catch(Exception $e){}
 $_SESSION['fail_count']=($_SESSION['fail_count']??0)+1;
 $err="Login yoki parol xato!";
}
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,900&family=Manrope:wght@500;700;800;900&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
<style>
*{font-family:Manrope,system-ui,sans-serif}
.brand-word{font-family:'Fraunces',serif;letter-spacing:-.02em}
.eyebrow{font-family:'IBM Plex Mono',monospace;letter-spacing:.24em}
input{color:#fff !important; background:rgba(0,0,0,.45) !important} input::placeholder{color:rgba(255,255,255,.4) !important}
@keyframes floaty{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}
@keyframes fadeUp{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)}}
@keyframes glowPulse{0%,100%{opacity:.5}50%{opacity:1}}
body{background:radial-gradient(at 20% 8%, rgba(124,108,255,.15) 0px, transparent 45%),radial-gradient(at 88% 92%, rgba(245,166,35,.08) 0px, transparent 42%),radial-gradient(at 50% 50%, rgba(36,27,82,.14) 0px, transparent 60%), #0a0a12; background-attachment:fixed;}
.login-card{position:relative;background:linear-gradient(160deg,rgba(22,22,42,.65),rgba(10,10,18,.65));backdrop-filter:blur(16px) saturate(130%);-webkit-backdrop-filter:blur(16px) saturate(130%);box-shadow:0 0 90px -10px rgba(124,108,255,.2), 0 25px 60px -20px rgba(0,0,0,.75);animation:fadeUp .6s cubic-bezier(.2,.8,.2,1) both;transition:.45s cubic-bezier(.2,.8,.2,1);}
.login-card::before{content:'';position:absolute;inset:0;border-radius:inherit;padding:1px;background:linear-gradient(160deg,rgba(245,166,35,.3),transparent 45%,transparent 65%,rgba(124,108,255,.25));-webkit-mask:linear-gradient(#fff 0 0) content-box,linear-gradient(#fff 0 0);-webkit-mask-composite:xor;mask-composite:exclude;pointer-events:none}
.btn-glow{box-shadow:0 4px 24px -4px rgba(124,108,255,.45);transition:.25s cubic-bezier(.2,.8,.2,1)}
.btn-glow:hover{box-shadow:0 10px 36px -4px rgba(124,108,255,.65);transform:translateY(-2px)}
.btn-glow:active{transform:scale(.97)}
input{transition:.2s}
.logo-ring{position:relative;width:180px;height:165px;margin:0 auto}
.logo-ring::before{content:'';position:absolute;inset:-6px;border-radius:50%;background:conic-gradient(from 0deg,#7c6cff,transparent 30%,transparent 70%,#f5a623);animation:glowPulse 2.4s ease-in-out infinite;filter:blur(10px)}
#gateBtn{transition:.35s cubic-bezier(.2,.8,.2,1);}
#formWrap{max-height:0;opacity:0;overflow:hidden;transition:max-height .5s cubic-bezier(.2,.8,.2,1), opacity .4s ease, margin-top .4s ease;}
#formWrap.open{max-height:500px;opacity:1;margin-top:22px;}
#gateBtn.hide{max-height:0;opacity:0;margin:0;overflow:hidden;transform:scale(.9);}
@media (prefers-reduced-motion:reduce){ *{ animation-duration:.001ms !important; animation-iteration-count:1 !important; transition-duration:.001ms !important; } }
</style>
</head><body class="flex items-center justify-center min-h-screen p-4">
<div class="login-card border border-white/10 p-8 rounded-[32px] w-full max-w-sm text-center">

<div class="logo-ring"><img src="logo_full.png" class="relative w-full h-full mx-auto object-contain drop-shadow-[0_0_22px_rgba(124,108,255,.4)]" style="animation:floaty 3.2s ease-in-out infinite"></div>
<p class="eyebrow text-[10px] text-white/30 mt-1">DILLER PANEL</p>

<?php if($err): ?><div class="bg-red-500/10 border border-red-500/20 text-red-300 p-3 rounded-xl mt-5 text-sm"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

<div id="gateBtn" class="mt-7 <?php echo $err ? 'hide' : ''; ?>">
<button type="button" onclick="openForm()" class="w-full bg-[#7c6cff] hover:bg-[#9a8dff] text-white p-4 rounded-2xl font-black tracking-widest btn-glow">KIRISH</button>
</div>

<div id="formWrap" class="<?php echo $err ? 'open' : ''; ?>">
<form method="post" class="space-y-3 text-left">
<input name="login" required placeholder="Login" class="w-full p-4 rounded-2xl border border-white/10 outline-none focus:border-[#7c6cff] focus:shadow-[0_0_0_3px_rgba(124,108,255,.15)] text-white placeholder-white/30">
<input name="pass" type="password" required placeholder="Parol" class="w-full p-4 rounded-2xl border border-white/10 outline-none focus:border-[#7c6cff] focus:shadow-[0_0_0_3px_rgba(124,108,255,.15)] text-white placeholder-white/30">
<button class="w-full bg-[#7c6cff] hover:bg-[#9a8dff] text-white p-4 rounded-2xl font-black tracking-widest btn-glow">KIRISH</button>
</form>
</div>

</div>
<script>
function openForm(){
 document.getElementById('gateBtn').classList.add('hide');
 document.getElementById('formWrap').classList.add('open');
 setTimeout(function(){ var f=document.querySelector('#formWrap input[name="login"]'); if(f) f.focus(); },350);
}
</script>
</body></html>
