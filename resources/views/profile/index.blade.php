@extends('layouts.sentara')

@section('content')

<div class="min-h-screen bg-[#f5f7fb] px-6 py-8">

    <!-- HEADER -->
    <div class="flex items-center gap-4 mb-6">

        <div>
            <h1 class="text-3xl font-bold text-gray-800">
                Profile Saya
            </h1>

            <p class="text-gray-500 text-sm">
                Informasi akun pengguna
                <span class="text-blue-600 font-medium">
                    SENTARA
                </span>
            </p>
        </div>

    </div>

    <!-- PROFILE CARD -->
    <div class="bg-white rounded-2xl shadow-md p-6 flex items-center gap-6 mb-8 relative overflow-hidden">

        <div class="absolute right-0 bottom-0 w-64 h-64 bg-blue-100 opacity-30 rounded-full"></div>

        <!-- FOTO -->
        <div class="w-24 h-24 rounded-full bg-gradient-to-r from-blue-500 to-blue-700
                    flex items-center justify-center text-white text-3xl font-bold shadow-md">

            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}

        </div>

        <!-- INFO -->
        <div>

            <!-- NAMA -->
            <h2 class="text-2xl font-semibold text-gray-800">
                {{ Auth::user()->name }}
            </h2>

            <!-- ROLE -->
            <p class="text-gray-500 mt-1 capitalize">
                {{ Auth::user()->role }} - Dinas Pariwisata Jember
            </p>

            <!-- BADGE ROLE -->
            <span class="
            inline-block mt-3 text-xs px-3 py-1 rounded-full font-medium uppercase

            @if(Auth::user()->role == 'admin')
            bg-purple-100 text-purple-700
            @else
            bg-blue-100 text-blue-600
            @endif
            ">

                {{ Auth::user()->role }}

            </span>

        </div>

    </div>

    <!-- INFORMASI AKUN -->
    <div class="mb-4 flex items-center gap-2 text-blue-600 font-semibold">

        <span class="text-xl">📋</span>

        Informasi Akun

    </div>

    <!-- GRID -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

        <!-- NAMA -->
        <div class="bg-white rounded-xl shadow-sm p-5 text-center hover:shadow-md transition">

            <div class="w-12 h-12 mx-auto mb-3 bg-blue-100 rounded-full
            flex items-center justify-center text-blue-600">

                👤

            </div>

            <p class="text-gray-400 text-sm">
                Nama Lengkap
            </p>

            <p class="font-semibold text-gray-800 mt-1">
                {{ Auth::user()->name }}
            </p>

        </div>

        <!-- EMAIL -->
        <div class="bg-white rounded-xl shadow-sm p-5 text-center hover:shadow-md transition">

            <div class="w-12 h-12 mx-auto mb-3 bg-blue-100 rounded-full
            flex items-center justify-center text-blue-600">

                ✉️

            </div>

            <p class="text-gray-400 text-sm">
                Email
            </p>

            <p class="font-semibold text-gray-800 mt-1">
                {{ Auth::user()->email }}
            </p>

        </div>

        <!-- ROLE -->
        <div class="bg-white rounded-xl shadow-sm p-5 text-center hover:shadow-md transition">

            <div class="w-12 h-12 mx-auto mb-3 bg-blue-100 rounded-full
            flex items-center justify-center text-blue-600">

                <!-- ICON -->
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="w-5 h-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor">

                    <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"

                        d="M17 20h5v-1a4 4 0 00-3-3.87
                        M9 20H4v-1a4 4 0 013-3.87
                        m10-2.13a4 4 0 10-8 0
                        m8 0a4 4 0 01-8 0"/>

                </svg>

            </div>

            <p class="text-gray-400 text-sm">
                Role
            </p>

            <p class="font-semibold text-gray-800 mt-1 capitalize">
                {{ Auth::user()->role }}
            </p>

        </div>

        <!-- STATUS -->
        <div class="bg-white rounded-xl shadow-sm p-5 text-center hover:shadow-md transition">

            <div class="w-12 h-12 mx-auto mb-3 bg-green-100 rounded-full
            flex items-center justify-center text-green-600">

                ✔

            </div>

            <p class="text-gray-400 text-sm">
                Status Akun
            </p>

            <span class="inline-block mt-1 text-sm bg-green-100
            text-green-600 px-3 py-1 rounded-full">

                Aktif

            </span>

        </div>

    </div>

    <!-- INFO SISTEM -->
    <div class="bg-white rounded-xl shadow-sm p-6 flex items-center justify-between">

        <div>

            <div class="flex items-center gap-2 text-blue-600 font-semibold mb-2">

                <span class="text-xl">ℹ</span>

                Informasi Sistem

            </div>

            <p class="text-gray-500 text-sm max-w-xl">

                Akun ini digunakan untuk mengakses sistem SENTARA
                (Sistem Analisis Sentimen Ulasan Dinas Pariwisata Jember).

            </p>

        </div>

    </div>

    <!-- FOOTER -->
    <div class="flex justify-between text-sm text-gray-400 mt-6">

        <span>
            © 2025 SENTARA. All rights reserved.
        </span>

        <span>
            Dinas Pariwisata Jember
        </span>

    </div>

</div>

@endsection