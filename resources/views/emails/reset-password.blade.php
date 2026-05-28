<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Reset Password SENTARA</title>

<style>
body{
    margin:0;
    padding:0;
    background:#f4f7ff;
    font-family:Arial, sans-serif;
}

.wrapper{
    width:100%;
    padding:40px 15px;
}

.card{
    max-width:650px;
    margin:auto;
    background:white;
    border-radius:20px;
    overflow:hidden;
    box-shadow:0 10px 30px rgba(0,0,0,.08);
}

.header{
    background:linear-gradient(135deg,#3B82F6,#4F46E5);
    padding:35px;
    text-align:center;
    color:white;
}

.logo{
    font-size:34px;
    font-weight:bold;
    letter-spacing:2px;
}

.subtitle{
    margin-top:10px;
    opacity:.9;
}

.content{
    padding:40px;
    text-align:center;
}

.icon{
    font-size:70px;
    margin-bottom:10px;
}

.title{
    font-size:34px;
    font-weight:bold;
    color:#1e293b;
}

.desc{
    color:#64748b;
    line-height:1.7;
    margin-top:20px;
}

.btn{
    display:inline-block;
    margin-top:30px;
    background:#3B82F6;
    color:white !important;
    text-decoration:none;
    padding:15px 40px;
    border-radius:10px;
    font-weight:bold;
    font-size:16px;
}

.info-box{
    margin-top:35px;
    background:#eef4ff;
    padding:20px;
    border-radius:12px;
    text-align:left;
}

.info-box strong{
    color:#2563eb;
}

.footer{
    padding:25px;
    text-align:center;
    color:#94a3b8;
    font-size:14px;
}

.link-box{
    margin-top:25px;
    background:#0f172a;
    color:white;
    padding:20px;
    border-radius:12px;
    word-break:break-all;
    font-size:13px;
}

</style>
</head>

<body>

<div class="wrapper">

<div class="card">

<div class="header">

<div class="logo">
SENTARA
</div>

<div class="subtitle">
Sistem Analisis Sentimen Pariwisata Jember
</div>

</div>


<div class="content">

<div class="icon">
🔐
</div>

<div class="title">
Reset Password
</div>

<p class="desc">

Halo,<br><br>

Kami menerima permintaan reset password untuk akun Anda.
Klik tombol di bawah untuk membuat password baru.

</p>

<a class="btn" href="{{ $actionUrl }}">
Reset Password
</a>

<div class="info-box">

⏰ Link reset password akan kadaluarsa dalam
<strong>60 menit</strong>.

<br><br>

Jika Anda tidak meminta reset password,
abaikan email ini.

</div>


<div class="link-box">

Jika tombol tidak berfungsi, salin link berikut:

<br><br>

{{ $actionUrl }}

</div>

</div>


<div class="footer">

© {{ date('Y') }} SENTARA <br>

Email otomatis — jangan membalas email ini.

</div>

</div>

</div>

</body>
</html>