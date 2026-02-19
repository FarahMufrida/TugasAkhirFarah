<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SENTARA Dashboard</title>

    @vite(['resources/css/app.css','resources/js/app.js'])
</head>

<body class="bg-gray-100">

    <!-- WRAPPER -->
   <div class="min-h-screen">

    <!-- SIDEBAR FIX -->
    <aside class="w-64 fixed top-0 left-0 h-screen
        bg-gradient-to-b from-blue-900 to-blue-700
        text-white flex flex-col">

        <!-- Logo -->
        <div class="px-6 py-6 text-2xl font-bold tracking-wide">
            SENTARA
        </div>

        <!-- Menu -->
        <nav class="flex-1 px-4 space-y-2 text-sm font-medium">
            <a href="{{ route('dashboard') }}" class="block px-4 py-2 rounded-lg hover:bg-blue-800">
                Dashboard
            </a>

            <a href="{{ route('ulasan.index') }}" class="block px-4 py-2 rounded-lg hover:bg-blue-800">
                Data Ulasan
            </a>

            <a href="{{ route('analisis.index') }}"
            class="block px-4 py-2 rounded-lg hover:bg-blue-800">
            Hasil Analisis Sentimen
            </a>


           <a href="{{ route('rekomendasi.index') }}"
            class="block px-4 py-2 rounded-lg hover:bg-blue-800">
            Rekomendasi Layanan
            </a>

        </nav>

        <!-- Logout -->
        <div class="px-6 pb-6">
            <form method="POST" action="{{ route('logout') }}"
                onsubmit="return confirm('Apakah Anda yakin ingin keluar?')">
                @csrf
                <button class="w-full bg-blue-800 hover:bg-blue-900 px-4 py-2 rounded-lg">
                Logout
                    </button>
            </form>
        </div>
    </aside>

    <!-- CONTENT FIX -->
    <div class="ml-64 min-h-screen p-10">
        @yield('content')
    </div>

</div>
