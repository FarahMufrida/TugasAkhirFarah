<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - SENTARA</title>

    @vite(['resources/css/app.css','resources/js/app.js'])

    <style>
        body{
            background:#f5f7fb;
            font-family:'Poppins', sans-serif;
        }

        .main-wrapper{
            min-height:100vh;
            padding:40px 20px;
            position:relative;
            overflow:hidden;
            display:flex;
            align-items:center;
            justify-content:center;
        }

        /* BACKGROUND */
        .circle-top{
            position:absolute;
            top:-120px;
            right:-120px;
            width:350px;
            height:350px;
            background:#edf3ff;
            border-radius:50%;
            z-index:0;
        }

        .circle-bottom{
            position:absolute;
            bottom:-150px;
            left:-150px;
            width:320px;
            height:320px;
            background:#edf3ff;
            border-radius:50%;
            z-index:0;
        }

        .content{
            position:relative;
            z-index:2;
            width:100%;
            max-width:950px;
        }

        /* CARD */
        .card-box{
            background:#fff;
            border-radius:26px;
            padding:50px;
            box-shadow:0 10px 35px rgba(0,0,0,0.06);
        }

        /* ICON */
        .icon-wrapper{
            width:110px;
            height:110px;
            background:#edf3ff;
            border-radius:50%;
            display:flex;
            align-items:center;
            justify-content:center;
            margin:0 auto 25px;
        }

        /* TITLE */
        .title{
            text-align:center;
            font-size:54px;
            font-weight:700;
            color:#0d1b52;
            line-height:1.2;
        }

        .subtitle{
            text-align:center;
            font-size:22px;
            color:#475569;
            margin-top:18px;
            line-height:1.7;
        }

        .subtitle span{
            color:#2563eb;
            font-weight:700;
        }

        /* ALERT */
        .info-box{
            margin-top:35px;
            background:#f4f8ff;
            border:1px solid #dbeafe;
            border-radius:18px;
            padding:22px 24px;
            display:flex;
            align-items:flex-start;
            gap:16px;
        }

        .info-box p{
            color:#334155;
            font-size:18px;
            line-height:1.6;
        }

        /* SECTION */
        .section-title{
            display:flex;
            align-items:center;
            gap:12px;
            margin-top:45px;
            margin-bottom:30px;
        }

        .section-title h2{
            font-size:34px;
            font-weight:700;
            color:#0d1b52;
        }

        /* STEP */
        .step{
            display:flex;
            gap:20px;
            margin-bottom:28px;
        }

        .step-number{
            min-width:52px;
            height:52px;
            background:#2563eb;
            color:#fff;
            border-radius:50%;
            display:flex;
            align-items:center;
            justify-content:center;
            font-weight:700;
            font-size:22px;
        }

        .step-content h3{
            font-size:28px;
            font-weight:700;
            color:#0f172a;
            margin-bottom:6px;
        }

        .step-content p{
            color:#475569;
            font-size:20px;
            line-height:1.7;
        }

        /* CONTACT */
        .contact-box{
            margin-top:40px;
            background:#f8fbff;
            border:1px solid #e2e8f0;
            border-radius:22px;
            padding:32px;
        }

        .contact-header{
            display:flex;
            align-items:center;
            gap:18px;
            margin-bottom:18px;
        }

        .contact-icon{
            width:70px;
            height:70px;
            background:#edf3ff;
            border-radius:50%;
            display:flex;
            align-items:center;
            justify-content:center;
        }

        .contact-header h3{
            font-size:30px;
            font-weight:700;
            color:#2563eb;
        }

        .contact-header p{
            font-size:18px;
            color:#475569;
            margin-top:4px;
        }

        .contact-list{
            display:flex;
            flex-wrap:wrap;
            gap:24px;
            align-items:center;
            margin-top:18px;
            padding-left:88px;
        }

        .contact-item{
            display:flex;
            align-items:center;
            gap:12px;
            font-size:22px;
            font-weight:600;
            color:#0f172a;
        }

        /* FOOTER */
        .footer{
            display:flex;
            justify-content:space-between;
            margin-top:28px;
            color:#64748b;
            font-size:18px;
            padding:0 8px;
        }

        /* MOBILE */
        @media(max-width:768px){

            .main-wrapper{
                padding:20px 14px;
            }

            .card-box{
                padding:28px 22px;
                border-radius:22px;
            }

            .icon-wrapper{
                width:90px;
                height:90px;
            }

            .title{
                font-size:36px;
            }

            .subtitle{
                font-size:17px;
                line-height:1.7;
            }

            .info-box{
                padding:18px;
            }

            .info-box p{
                font-size:15px;
            }

            .section-title h2{
                font-size:25px;
            }

            .step{
                gap:14px;
            }

            .step-number{
                min-width:42px;
                height:42px;
                font-size:18px;
            }

            .step-content h3{
                font-size:21px;
            }

            .step-content p{
                font-size:15px;
            }

            .contact-box{
                padding:22px;
            }

            .contact-header{
                align-items:flex-start;
            }

            .contact-header h3{
                font-size:24px;
            }

            .contact-header p{
                font-size:15px;
            }

            .contact-list{
                padding-left:0;
                flex-direction:column;
                align-items:flex-start;
                gap:18px;
            }

            .contact-item{
                font-size:16px;
                word-break:break-word;
            }

            .footer{
                flex-direction:column;
                gap:10px;
                text-align:center;
                font-size:14px;
            }

        }
    </style>
</head>

<body>

<div class="main-wrapper">

    <div class="circle-top"></div>
    <div class="circle-bottom"></div>

    <div class="content">

        <!-- CARD -->
        <div class="card-box">

            <!-- ICON -->
            <div class="icon-wrapper">

                <svg xmlns="http://www.w3.org/2000/svg"
                    width="55"
                    height="55"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="#2563eb"
                    stroke-width="2">

                    <path stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M16 11V7a4 4 0 10-8 0v4m-2 0h12a2 2 0 012 2v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5a2 2 0 012-2z"/>

                    <circle cx="18.5" cy="18.5" r="3.5" stroke="#2563eb"/>
                    <path d="M18.5 17v3M17 18.5h3" stroke="#2563eb"/>
                </svg>

            </div>

            <!-- TITLE -->
            <h1 class="title">
                Lupa Password?
            </h1>

            <p class="subtitle">
                Untuk keamanan akun, pengaturan ulang password hanya dapat dilakukan
                <br>
                <span>oleh Administrator.</span>
            </p>

            <!-- INFO -->
            <div class="info-box">

                <svg xmlns="http://www.w3.org/2000/svg"
                    width="34"
                    height="34"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="#2563eb"
                    stroke-width="2">

                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 16v-4"/>
                    <path d="M12 8h.01"/>

                </svg>

                <p>
                    Silakan hubungi Administrator sistem untuk memperbarui password akun Anda.
                </p>

            </div>

            <!-- SECTION TITLE -->
            <div class="section-title">

                <svg xmlns="http://www.w3.org/2000/svg"
                    width="40"
                    height="40"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="#2563eb"
                    stroke-width="2">

                    <path stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M17 20h5V4H2v16h5"/>
                    <path stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M9 20h6"/>
                    <circle cx="12" cy="8" r="3"/>
                    <path d="M7 16c1.333-2 3-3 5-3s3.667 1 5 3"/>

                </svg>

                <h2>Cara Memperbarui Password</h2>

            </div>

            <!-- STEP -->
            <div class="step">

                <div class="step-number">1</div>

                <div class="step-content">
                    <h3>Hubungi Administrator</h3>
                    <p>
                        Sampaikan kepada Administrator bahwa Anda lupa password akun SENTARA.
                    </p>
                </div>

            </div>

            <div class="step">

                <div class="step-number">2</div>

                <div class="step-content">
                    <h3>Verifikasi Identitas</h3>
                    <p>
                        Administrator akan memverifikasi identitas Anda sebagai pengguna sistem.
                    </p>
                </div>

            </div>

            <div class="step">

                <div class="step-number">3</div>

                <div class="step-content">
                    <h3>Password Baru</h3>
                    <p>
                        Administrator akan membuat password baru dan memberikannya kepada Anda.
                    </p>
                </div>

            </div>

            <!-- CONTACT -->
            <div class="contact-box">

                <div class="contact-header">

                    <div class="contact-icon">

                        <svg xmlns="http://www.w3.org/2000/svg"
                            width="38"
                            height="38"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="#2563eb"
                            stroke-width="2">

                            <path stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4-.917L2 17l1.083-3.25A6.963 6.963 0 012 10c0-3.866 3.582-7 8-7s8 3.134 8 7z"/>

                            <path d="M15 8h.01"/>
                            <path d="M9 8h.01"/>
                            <path d="M8 12c1 1 2 1.5 4 1.5s3-.5 4-1.5"/>

                        </svg>

                    </div>

                    <div>
                        <h3>Kontak Administrator</h3>
                        <p>
                            Silakan hubungi administrator melalui kontak resmi berikut:
                        </p>
                    </div>

                </div>

                <div class="contact-list">

                    <!-- PHONE -->
                    <div class="contact-item">

                        <svg xmlns="http://www.w3.org/2000/svg"
                            width="26"
                            height="26"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="#2563eb"
                            stroke-width="2">

                            <path stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M3 5a2 2 0 012-2h2.28a2 2 0 011.94 1.515l.57 2.28a2 2 0 01-.45 1.91l-1.27 1.27a16 16 0 006.59 6.59l1.27-1.27a2 2 0 011.91-.45l2.28.57A2 2 0 0121 16.72V19a2 2 0 01-2 2h-1C9.163 21 3 14.837 3 7V5z"/>

                        </svg>

                        0881-0267-1527

                    </div>

                    <!-- EMAIL -->
                    <div class="contact-item">

                        <svg xmlns="http://www.w3.org/2000/svg"
                            width="26"
                            height="26"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="#2563eb"
                            stroke-width="2">

                            <path stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M4 4h16v16H4z"/>

                            <path stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M22 6l-10 7L2 6"/>

                        </svg>

                        e31231226@student.polije.ac.id

                    </div>

                </div>

            </div>

        </div>

        <!-- FOOTER -->
        <div class="footer">
            <div>© 2025 SENTARA. All rights reserved.</div>
            <div>Dinas Pariwisata Jember</div>
        </div>

    </div>

</div>

</body>
</html>