<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SENTARA Dashboard</title>

@vite(['resources/css/app.css','resources/js/app.js'])

<script src="https://unpkg.com/feather-icons"></script>
</head>

<body class="bg-gray-100">

<div class="min-h-screen">

<!-- SIDEBAR -->
<aside id="sidebar"
class="fixed top-0 left-0 h-full w-64
bg-gradient-to-b from-blue-900 to-blue-700
text-white flex flex-col
transform -translate-x-full md:translate-x-0
transition duration-300 z-50">

    <!-- LOGO -->
    <div class="flex justify-center mt-6 mb-8">
        <img src="{{ asset('images/tulisan-sentara.png') }}"
             class="w-44">
    </div>

    <!-- MENU -->
    <nav class="flex-1 px-6 space-y-3 text-sm font-medium">

        <!-- DASHBOARD -->
        <a href="{{ route('dashboard') }}"
        class="flex items-center gap-4 px-4 py-3 rounded-xl hover:bg-blue-800 transition">

            <div class="w-10 h-10 flex items-center justify-center bg-blue-800 rounded-lg">
                <i data-feather="grid" class="w-5 h-5"></i>
            </div>

            <span class="text-[15px]">Dashboard Sentimen</span>
        </a>

        <!-- DATA ULASAN -->
        <a href="{{ route('ulasan.index') }}"
        class="flex items-center gap-4 px-4 py-3 rounded-xl hover:bg-blue-800 transition">

            <div class="w-10 h-10 flex items-center justify-center bg-blue-800 rounded-lg">
                <i data-feather="file-text" class="w-5 h-5"></i>
            </div>

            <span class="text-[15px]">Data Ulasan</span>
        </a>

        <!-- RIWAYAT -->
        <a href="{{ route('riwayat.index') }}"
        class="flex items-center gap-4 px-4 py-3 rounded-xl hover:bg-blue-800 transition">

            <div class="w-10 h-10 flex items-center justify-center bg-blue-800 rounded-lg">
                <i data-feather="clock" class="w-5 h-5"></i>
            </div>

            <span class="text-[15px]">Riwayat Analisis</span>
        </a>

    </nav>

    <!-- USER SECTION -->
    <div class="px-6 pb-6">

        <div class="relative">

            <!-- USER BUTTON -->
            <button onclick="toggleUserMenu()"
            class="w-full flex items-center gap-3 bg-blue-800 hover:bg-blue-900 px-4 py-3 rounded-xl transition">

                <div class="w-10 h-10 flex items-center justify-center bg-blue-700 rounded-full">
                    <i data-feather="user" class="w-5 h-5"></i>
                </div>

                <div class="text-left">
                    <p class="text-sm font-semibold">
                        {{ auth()->user()->name }}
                    </p>
                    <p class="text-xs text-blue-200">Staff</p>
                </div>
            </button>

            <!-- DROPDOWN -->
            <div id="userMenu"
            class="hidden absolute bottom-16 left-0 w-full
            bg-white text-gray-700 rounded-xl shadow-lg overflow-hidden">

                <!-- PROFILE -->
                <a href="{{ route('profile') }}"
                class="flex items-center gap-3 px-4 py-3 text-sm hover:bg-gray-100">
                    <i data-feather="user" class="w-4 h-4"></i>
                    Profile
                </a>

                <!-- LOGOUT -->
                <form id="logoutForm" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="button"
                    onclick="confirmLogout()"
                    class="w-full flex items-center gap-3 px-4 py-3 text-sm hover:bg-gray-100">
                        <i data-feather="log-out" class="w-4 h-4"></i>
                        Logout
                    </button>
                </form>

            </div>

        </div>

    </div>

</aside>

<!-- CONTENT -->
<div class="flex-1 p-4 md:p-6 md:ml-64 ml-0">

    <button onclick="toggleSidebar()"
    class="md:hidden mb-4 bg-blue-600 text-white px-4 py-2 rounded-lg shadow">
        ☰
    </button>

    @yield('content')

</div>

</div>

<!-- MODAL LOGOUT -->
<div id="logoutModal"
class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">

    <div class="bg-white rounded-xl p-6 w-80 text-center shadow-lg">

        <h3 class="text-lg font-semibold mb-2">
            Konfirmasi Logout
        </h3>

        <p class="text-sm text-gray-600 mb-4">
            Apakah Anda ingin keluar?
        </p>

        <div class="flex justify-center gap-3">

            <button onclick="closeModal()"
            class="px-4 py-2 bg-gray-200 rounded-lg">
                Tidak
            </button>

            <button onclick="submitLogout()"
            class="px-4 py-2 bg-red-500 text-white rounded-lg">
                Ya
            </button>

        </div>

    </div>

</div>

<!-- SCRIPT -->
<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('-translate-x-full');
}

function toggleUserMenu() {
    document.getElementById('userMenu').classList.toggle('hidden');
}

function confirmLogout(){
    document.getElementById('logoutModal').classList.remove('hidden');
}

function closeModal(){
    document.getElementById('logoutModal').classList.add('hidden');
}

function submitLogout(){
    document.getElementById('logoutForm').submit();
}

feather.replace();
</script>

</body>
</html>
