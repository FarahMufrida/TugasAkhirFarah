<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SENTARA Dashboard</title>

<?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css','resources/js/app.js']); ?>

<!-- FEATHER ICON -->
<script src="https://unpkg.com/feather-icons"></script>

</head>

<body class="bg-gray-100 overflow-x-hidden">

<div class="h-auto ">

<!-- SIDEBAR -->
<aside id="sidebar"
class="fixed top-0 left-0 h-full w-64
bg-gradient-to-b from-blue-900 to-blue-700
text-white flex flex-col
transform -translate-x-full md:translate-x-0
transition duration-300 z-50">

    <!-- LOGO -->
    <div class="flex justify-center mt-6 mb-8">
        <img src="<?php echo e(asset('images/tulisan-sentara.png')); ?>"
             class="w-44"
             alt="Logo SENTARA">
    </div>

    <!-- MENU -->
    <nav class="flex-1 px-6 space-y-3 text-sm font-medium">

        <!-- DASHBOARD -->
        <a href="<?php echo e(route('dashboard')); ?>"
        class="flex items-center gap-4 px-4 py-3 rounded-xl hover:bg-blue-800 transition">

            <div class="w-10 h-10 flex items-center justify-center bg-blue-800 rounded-lg">
                <i data-feather="grid" class="w-5 h-5"></i>
            </div>

            <span class="text-[15px] leading-5">
                Dashboard Sentimen
            </span>
        </a>

        <!-- DATA ULASAN -->
        <a href="<?php echo e(route('ulasan.index')); ?>"
        class="flex items-center gap-4 px-4 py-3 rounded-xl hover:bg-blue-800 transition">

            <div class="w-10 h-10 flex items-center justify-center bg-blue-800 rounded-lg">
                <i data-feather="file-text" class="w-5 h-5"></i>
            </div>

            <span class="text-[15px] leading-5">
                Data Ulasan
            </span>
        </a>

        <!-- RIWAYAT -->
        <a href="<?php echo e(route('riwayat.index')); ?>"
        class="flex items-center gap-4 px-4 py-3 rounded-xl hover:bg-blue-800 transition">

            <div class="w-10 h-10 flex items-center justify-center bg-blue-800 rounded-lg">
                <i data-feather="clock" class="w-5 h-5"></i>
            </div>

            <span class="text-[15px] leading-5">
                Riwayat Analisis
            </span>
        </a>

        <!-- KHUSUS ADMIN -->
        <?php if(auth()->user()->role == 'admin'): ?>

        <a href="<?php echo e(route('kelola-pengguna.index')); ?>"
        class="flex items-center gap-4 px-4 py-3 rounded-xl hover:bg-blue-800 transition">

            <div class="w-10 h-10 flex items-center justify-center bg-blue-800 rounded-lg">
                <i data-feather="users" class="w-5 h-5"></i>
            </div>

            <span class="text-[15px] leading-5">
                Kelola Pengguna
            </span>
        </a>

        <?php endif; ?>

    </nav>

    <!-- USER -->
    <div class="px-6 pb-6">

        <div class="relative">

            <!-- USER BUTTON -->
            <button onclick="toggleUserMenu()"
            class="w-full flex items-center gap-3
            bg-blue-800 hover:bg-blue-900
            px-4 py-3 rounded-2xl transition">

                <!-- ICON -->
                <div class="w-11 h-11 rounded-full bg-blue-700
                flex items-center justify-center">

                    <i data-feather="user"
                    class="w-5 h-5"></i>

                </div>

                <!-- TEXT -->
                <div class="text-left flex-1">

                    <p class="text-sm font-semibold capitalize">
                        <?php echo e(auth()->user()->name); ?>

                    </p>

                    <p class="text-xs text-blue-200 capitalize">
                        <?php echo e(auth()->user()->role); ?>

                    </p>

                </div>

                <!-- ARROW -->
                <i data-feather="chevron-up"
                class="w-4 h-4 text-blue-200"></i>

            </button>

            <!-- DROPDOWN -->
            <div id="userMenu"
            class="hidden absolute bottom-16 left-0 w-full
            bg-white text-gray-700 rounded-2xl shadow-2xl overflow-hidden">

                <!-- PROFILE -->
                <a href="<?php echo e(route('profile')); ?>"
                class="flex items-center gap-3 px-4 py-4 text-sm hover:bg-gray-100 transition">

                    <i data-feather="user"
                    class="w-4 h-4"></i>

                    Profile

                </a>

                <!-- LOGOUT -->
            <form id="logoutForm"
            method="POST"
            action="<?php echo e(route('logout')); ?>">

                <?php echo csrf_field(); ?>

                <button type="submit"
                class="w-full flex items-center gap-3 px-4 py-4 text-sm hover:bg-gray-100 transition text-left">

                    <i data-feather="log-out"
                    class="w-4 h-4"></i>

                    Logout

                </button>

            </form>

            </div>

        </div>

    </div>

</aside>

<!-- CONTENT -->
<div class="flex-1 p-4 md:p-6 md:ml-64">

    <!-- MOBILE BUTTON -->
    <button onclick="toggleSidebar()"
    class="md:hidden mb-4 bg-blue-600 text-white px-4 py-2 rounded-lg shadow">

        ☰

    </button>

    <?php echo $__env->yieldContent('content'); ?>

</div>

</div>

<!-- MODAL LOGOUT -->
<div id="logoutModal"
class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4">

    <div class="bg-white rounded-2xl p-6 w-96 text-center shadow-2xl">

        <!-- ICON -->
        <div class="w-16 h-16 rounded-full 
        flex items-center justify-center mx-auto mb-5">

            <i data-feather="log-out"
            class="w-7 h-7 text-red-500"></i>

        </div>

        <!-- TITLE -->
        <h3 class="text-xl font-bold text-gray-800 mb-2">
            Konfirmasi Logout
        </h3>

        <!-- TEXT -->
        <p class="text-sm text-gray-500 mb-6">
            Apakah Anda ingin keluar dari sistem?
        </p>

        <!-- BUTTON -->
        <div class="flex justify-center gap-3">

            <button onclick="closeModal()"
            class="px-5 py-2 rounded-xl bg-gray-200 hover:bg-gray-300 transition">

                Tidak

            </button>

            <button onclick="submitLogout()"
            class="px-5 py-2 rounded-xl bg-red-500 hover:bg-red-600 text-white transition">

                Ya

            </button>

        </div>

    </div>

</div>

<!-- SCRIPT -->
<script>

function toggleSidebar() {
    document.getElementById('sidebar')
    .classList.toggle('-translate-x-full');
}

function toggleUserMenu() {
    document.getElementById('userMenu')
    .classList.toggle('hidden');
}

function confirmLogout() {
    document.getElementById('logoutModal')
    .classList.remove('hidden');
}

function closeModal() {
    document.getElementById('logoutModal')
    .classList.add('hidden');
}

function submitLogout() {
    document.getElementById('logoutForm').submit();
}

/* CLOSE DROPDOWN KETIKA KLIK LUAR */
window.addEventListener('click', function(e){

    const button = e.target.closest('button');
    const menu = document.getElementById('userMenu');

    if(!e.target.closest('#userMenu') &&
       !e.target.closest('[onclick="toggleUserMenu()"]')) {

        menu.classList.add('hidden');
    }

});

feather.replace();

</script>

</body>
</html><?php /**PATH C:\laragon\www\SistemAnalisisSentimen\resources\views/layouts/sentara.blade.php ENDPATH**/ ?>