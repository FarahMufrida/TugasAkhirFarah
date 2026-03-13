<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SENTARA</title>

    @vite(['resources/css/app.css','resources/js/app.js'])

</head>

<body class="min-h-screen flex items-center justify-center bg-gray-100 px-6">

    <!-- WRAPPER CARD -->
    <div class="w-full max-w-6xl bg-white rounded-3xl shadow-2xl overflow-hidden flex">

        <!-- LEFT SIDE LOGIN -->
        <div class="w-1/2 flex flex-col justify-center px-14 py-16 bg-white">

            <h1 class="text-4xl font-bold text-blue-900 mb-2">
                SENTARA
            </h1>

            <p class="text-gray-500 mb-10">
                Login untuk mengakses Dashboard Analisis Sentimen Wisata Jember
            </p>

            <!-- Login Form -->
            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf

                <!-- Email -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2">
                        Email
                    </label>
                    <input type="email" name="email" required
                        class="w-full border border-gray-300 rounded-xl px-4 py-3
                               focus:ring-2 focus:ring-blue-400 focus:outline-none">
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2">
                        Password
                    </label>
                    <input type="password" name="password" required
                        class="w-full border border-gray-300 rounded-xl px-4 py-3
                               focus:ring-2 focus:ring-blue-400 focus:outline-none">
                </div>

                <!-- Button -->
                <button type="submit"
                    class="w-full bg-blue-700 hover:bg-blue-800 text-white py-3
                           rounded-xl font-semibold shadow-md transition">
                    Login
                </button>

                <!-- Register Link -->
                <p class="text-sm text-gray-500 text-center">
                    Belum punya akun?
                    <a href="{{ route('register') }}"
                       class="text-blue-700 font-semibold hover:underline">
                        Register
                    </a>
                </p>
            </form>
        </div>


        <!-- RIGHT SIDE SLIDER -->
        <div class="w-1/2 relative overflow-hidden">

            <!-- Slides -->
            <img src="{{ asset('images/papuma.jpg') }}"
                 class="slide absolute inset-0 w-full h-full object-cover">

            <img src="{{ asset('images/watuulo.jpeg') }}"
                 class="slide hidden absolute inset-0 w-full h-full object-cover">

            <img src="{{ asset('images/botani.jpg') }}"
                 class="slide hidden absolute inset-0 w-full h-full object-cover">

            <img src="{{ asset('images/gununggambir.jpeg') }}"
                 class="slide hidden absolute inset-0 w-full h-full object-cover">


            <!-- Overlay -->
            <div class="absolute inset-0 bg-black/40 flex items-center justify-center">
                <h2 class="text-white text-3xl font-bold text-center px-10">
                    Eksplorasi Wisata Kabupaten Jember
                </h2>
            </div>

            <!-- LEFT ARROW -->
            <button onclick="prevSlide()"
                class="absolute left-5 top-1/2 -translate-y-1/2
                       bg-white/40 hover:bg-white/70 text-white
                       px-4 py-2 rounded-full text-2xl font-bold transition">
                ❮
            </button>

            <!-- RIGHT ARROW -->
            <button onclick="nextSlide()"
                class="absolute right-5 top-1/2 -translate-y-1/2
                       bg-white/40 hover:bg-white/70 text-white
                       px-4 py-2 rounded-full text-2xl font-bold transition">
                ❯
            </button>

        </div>

    </div>


    <!-- SLIDER SCRIPT -->
    <script>
        let index = 0;
        const slides = document.querySelectorAll(".slide");

        function showSlide(i) {
            slides.forEach(slide => slide.classList.add("hidden"));
            slides[i].classList.remove("hidden");
        }

        function nextSlide() {
            index = (index + 1) % slides.length;
            showSlide(index);
        }

        function prevSlide() {
            index = (index - 1 + slides.length) % slides.length;
            showSlide(index);
        }

        // // Auto Slide setiap 3 detik
        // setInterval(nextSlide, 3000);
    </script>

</body>
</html>
