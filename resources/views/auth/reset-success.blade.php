<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Berhasil Direset</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#f5f7fb] min-h-screen flex items-center justify-center px-4">

    <div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-8 text-center">

        <div class="flex justify-center mb-5">
            <div class="w-20 h-20 rounded-full bg-green-100 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
        </div>

        <h2 class="text-2xl font-bold text-[#1e3a8a] mb-3">
            Password Berhasil Direset
        </h2>

        <p class="text-gray-500 mb-6">
            Password akun Anda berhasil diperbarui. Silakan login kembali menggunakan password baru.
        </p>

        <a
            href="{{ url('/login') }}"
            class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold transition duration-200"
        >
            Kembali ke Login
        </a>

    </div>

</body>
</html>