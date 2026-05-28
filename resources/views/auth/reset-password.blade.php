<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Reset Password - SENTARA</title>

<script src="https://cdn.tailwindcss.com"></script>

<style>
body{
    background:#f5f7fb;
}

.left-panel{
    background: linear-gradient(180deg,#0f3fb9,#1546cf);
}

.input-box{
    width:100%;
    border:1px solid #dbe3ef;
    border-radius:10px;
    padding:14px 16px;
    outline:none;
}

.input-box:focus{
    border-color:#2563eb;
    box-shadow:0 0 0 3px rgba(37,99,235,.15);
}
</style>

</head>
<body class="min-h-screen flex items-center justify-center p-6">

<div class="bg-white rounded-3xl shadow-xl overflow-hidden max-w-5xl w-full grid md:grid-cols-2">

    <!-- LEFT -->
    <div class="left-panel text-white p-10 flex flex-col justify-between">

        <div>

            <img src="{{ asset('images/logo-sentara.png') }}"
                 class="w-56 mb-10"
                 alt="logo">

            <h1 class="text-3xl font-bold mb-4">
                Buat Password Baru
            </h1>

            <p class="text-blue-100 leading-7">
                Masukkan password baru Anda
                pada form di samping.
            </p>

        </div>

        <div class="text-center mt-12 text-8xl">
            🔐
        </div>

    </div>

    <!-- RIGHT -->
    <div class="p-10">

        <h2 class="text-4xl font-bold text-slate-800 mb-3">
            Reset Password
        </h2>

        <p class="text-slate-500 mb-8">
            Masukkan password baru untuk akun Anda.
        </p>

        @if ($errors->any())
            <div class="bg-red-100 text-red-600 p-3 rounded-xl mb-5">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('custom.password.update') }}">

            @csrf
           

            <input type="hidden"
                   name="token"
                   value="{{ $token ?? request()->route('token') }}">

            <input type="hidden"
                   name="email"
                   value="{{ $email ?? request()->email }}">

            <!-- PASSWORD -->

{{-- Password Baru --}}
<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 mb-2">
        Password Baru
    </label>

    <div class="relative">
        <input
            id="password"
            type="password"
            name="password"
            placeholder="Masukkan password baru"
            required
            class="w-full border rounded-lg px-4 py-3 pr-12 focus:ring-2 focus:ring-blue-500 outline-none"
        >

        <button
            type="button"
            onclick="togglePassword('password','eye1')"
            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500"
        >
            <span id="eye1">👁️</span>
        </button>
    </div>

    <p class="text-xs text-gray-400 mt-1">
        Minimal 8 karakter
    </p>
</div>


{{-- Konfirmasi Password --}}
<div class="mb-6">
    <label class="block text-sm font-medium text-gray-700 mb-2">
        Konfirmasi Password
    </label>

    <div class="relative">
        <input
            id="password_confirmation"
            type="password"
            name="password_confirmation"
            placeholder="Konfirmasi password baru"
            required
            class="w-full border rounded-lg px-4 py-3 pr-12 focus:ring-2 focus:ring-blue-500 outline-none"
        >

        <button
            type="button"
            onclick="togglePassword('password_confirmation','eye2')"
            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500"
        >
            <span id="eye2">👁️</span>
        </button>
    </div>
</div>

            <button
                class="w-full bg-blue-600 hover:bg-blue-700 transition text-white py-4 rounded-xl font-semibold shadow-lg">

                🔒 Reset Password

            </button>

        </form>

    </div>

</div>

<script>
function togglePassword(inputId, eyeId) {
    const input = document.getElementById(inputId);
    const eye = document.getElementById(eyeId);

    if (input.type === "password") {
        input.type = "text";
        eye.innerHTML = "🙈";
    } else {
        input.type = "password";
        eye.innerHTML = "👁️";
    }
}
</script>

</body>
</html>