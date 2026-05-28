<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lupa Password - SENTARA</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6">

<div class="bg-white shadow-2xl rounded-3xl overflow-hidden grid md:grid-cols-2 max-w-5xl w-full">

    <div class="bg-blue-700 text-white p-10 flex flex-col justify-center">
        <img src="{{ asset('images/logo-sentara.png') }}" class="w-48 mb-8">

        <h1 class="text-3xl font-bold mb-4">
            Lupa Password?
        </h1>

        <p class="text-blue-100 leading-relaxed">
            Masukkan email akun Anda untuk menerima link reset password.
        </p>
    </div>

    <div class="p-10">

        <h2 class="text-2xl font-bold text-gray-800 mb-2">
            Reset Password
        </h2>

        <p class="text-gray-500 mb-8">
            Kami akan mengirim link reset password ke email Anda.
        </p>

        @if (session('status'))
            <div class="bg-green-100 text-green-700 p-4 rounded-xl mb-6">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="mb-6">
                <label class="block mb-2 font-medium text-gray-700">
                    Email
                </label>

                <input
                    type="email"
                    name="email"
                    class="w-full border rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="contoh@email.com"
                    required
                >
            </div>

            <button
                type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-xl transition"
            >
                Kirim Link Reset
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="{{ route('login') }}" class="text-blue-600 hover:underline">
                Kembali ke Login
            </a>
        </div>

    </div>

</div>

</body>
</html>