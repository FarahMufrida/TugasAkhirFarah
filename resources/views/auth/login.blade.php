<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - SENTARA</title>

<link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins',sans-serif;
}

body{
    background:#f3f5fb;
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:40px;
}

/* CONTAINER */
.container{
    width:1350px;
    max-width:100%;
    height:720px;
    background:#fff;
    border-radius:25px;
    display:flex;
    overflow:hidden;
    box-shadow:0 30px 80px rgba(0,0,0,0.1);
}

/* LEFT */
.left{
    width:50%;
    padding:60px 80px;
    display:flex;
    flex-direction:column;
    justify-content:center;
}

/* LOGO */
.logo{
    text-align:center;
    margin-bottom:25px;
}

.logo img{
    width:200px;
}

/* TITLE */
.title{
    text-align:center;
    font-size:28px;
    font-weight:600;
}

.title span{
    color:#ff5c8d;
}

.subtitle{
    text-align:center;
    font-size:14px;
    color:#777;
    margin:12px 0 35px;
}

/* FORM */
.form-group{
    margin-bottom:20px;
}

label{
    font-size:13px;
    font-weight:500;
}

input{
    width:100%;
    padding:14px;
    border-radius:12px;
    border:1px solid #ddd;
    margin-top:6px;
    font-size:14px;
}

.input-group{
    position:relative;
}

.toggle{
    position:absolute;
    right:12px;
    top:14px;
    cursor:pointer;
}

.forgot{
    text-align:right;
    margin-top:6px;
}

.forgot a{
    font-size:12px;
    color:#2d5be3;
    text-decoration:none;
}

/* BUTTON */
.btn{
    width:100%;
    padding:14px;
    border:none;
    border-radius:12px;
    background:linear-gradient(90deg,#2d5be3,#4c7dff);
    color:#fff;
    font-weight:500;
    margin-top:20px;
    cursor:pointer;
    transition:0.3s;
}

.btn:hover{
    opacity:0.9;
}

.right{
    width:50%;
    position:relative;
    overflow:hidden;
}

/* GAMBAR */
.right img{
    position:absolute;
    top:0;
    right:0;
    width:100%;
    height:100%;
    object-fit:cover;

    /* SHAPE DIPERBAIKI */
    clip-path: ellipse(120% 100% at 100% 50%);
}

/* OVERLAY */
.overlay{
    position:absolute;
    width:100%;
    height:100%;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    text-align:center;
    color:#fff;
    background:rgba(0,0,0,0.25);
    padding:20px;
    z-index:2;           /* DI ATAS */
}

/* QUOTE */
.quote{
    font-size:42px;
    color:#ff5c8d;
    margin-bottom:10px;
}

/* TEXT */
.overlay p{
    font-size:30px;
    font-weight:500;
    line-height:1.5;
    max-width:420px;
}

.overlay span{
    color:#ff5c8d;
    font-weight:600;
}

/* GARIS */
.line{
    width:70px;
    height:3px;
    background:#fff;
    border-radius:10px;
    margin-top:18px;
    position:relative;
}

.line::after{
    content:'';
    position:absolute;
    width:12px;
    height:12px;
    border:2px solid #fff;
    border-left:none;
    border-top:none;
    transform:rotate(-45deg);
    right:-12px;
    top:-5px;
}

/* MOBILE */
@media(max-width:900px){
    .container{
        flex-direction:column;
        height:auto;
    }

    .left{
        width:100%;
        padding:40px;
    }

    .right{
        width:100%;
        height:260px;
    }

    .overlay p{
        font-size:20px;
    }

    .logo img{
        width:150px;
    }
}

@media(max-width:900px){
    .container{
        flex-direction:column;
        height:auto;
    }

    .left{
        width:100%;
        padding:40px;
    }

    .right{
        width:100%;
        height:260px;
    }

    .overlay p{
        font-size:20px;
    }

    .logo img{
        width:150px;
    }
}

/* HILANGKAN ICON PASSWORD BAWAAN BROWSER */
input[type="password"]::-ms-reveal,
input[type="password"]::-ms-clear {
    display: none;
}

input::-ms-reveal,
input::-ms-clear {
    display: none;
}

</style>
</head>

<body>

<div class="container">

    <!-- LEFT -->
    <div class="left">

        <div class="logo">
            <img src="{{ asset('images/logo-sentara.png') }}">
        </div>

        <div class="title">
            Selamat Datang <span>Kembali!</span>
        </div>

        <div class="subtitle">
            Login untuk mengakses Dashboard Analisis Sentimen Wisata Jember
        </div>

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="Masukkan email Anda" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <div class="input-group">
                    <input type="password" id="password" name="password" placeholder="Masukkan password Anda" required>
                    <span class="toggle" onclick="togglePass()">👁</span>
                </div>

                <div class="forgot">
                    <a href="{{ route('password.request') }}">Lupa password?</a>
                </div>
            </div>

            <button class="btn">Login</button>

        </form>

    </div>

    <!-- RIGHT -->
    <div class="right">

        <img src="{{ asset('images/papuma.jpg') }}" alt="bg">

        <div class="overlay">
           
            <p>
                Memahami opini,<br>
                meningkatkan <span>pariwisata</span><br>
                Jember.
            </p>

            
        </div>

    </div>

</div>

<script>
function togglePass(){
    let x = document.getElementById("password");
    x.type = x.type === "password" ? "text" : "password";
}
</script>

</body>
</html>