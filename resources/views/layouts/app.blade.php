<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">

        <!-- Top Navigation (Breeze) -->
        <!-- @include('layouts.navigation') -->

        <!-- Layout utama: Sidebar + Content -->
        <div class="flex">

            <!-- Sidebar SENTARA -->
            <aside class="w-58 min-h-screen bg-blue-900 text-white p-6">

                <!-- Logo -->
                <h1 class="text-2xl font-bold mb-8">
                    SENTARA
                </h1>

                <!-- Menu -->
                <nav>
                    <ul class="space-y-4 text-sm">

                        <li>
                            <a href="{{ route('dashboard') }}"
                               class="block hover:text-gray-300">
                                Dashboard
                            </a>
                        </li>

                        <li>
                            <a href="#"
                               class="block hover:text-gray-300">
                                Data Ulasan Mentah
                            </a>
                        </li>

                        <li>
                            <a href="#"
                               class="block hover:text-gray-300">
                                Hasil Analisis Sentimen
                            </a>
                        </li>

                        <li>
                            <a href="#"
                               class="block hover:text-gray-300">
                                Rekomendasi Layanan
                            </a>
                        </li>

                        <li>
                            <a href="#"
                               class="block hover:text-gray-300">
                                Riwayat Proses
                            </a>
                        </li>

                    </ul>
                </nav>
            </aside>

            <!-- Content Area -->
            <div class="flex-1">

                <!-- Page Heading -->
                @isset($header)
                    <header class="bg-white shadow">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main class="p-6">
                    @yield('content')
                </main>

            </div>
        </div>
    </div>
</body>

</html>
