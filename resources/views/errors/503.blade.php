<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Under Maintenance - {{ config('app.name', 'SMART-HUT') }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                        display: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#f2fcf5',
                            100: '#e1f8e8',
                            200: '#c3efd2',
                            300: '#94e0b0',
                            400: '#5cc788',
                            500: '#34aa6b',
                            600: '#258a55',
                            700: '#1f6e46', // Dark Green
                            800: '#1b573a',
                            900: '#174731',
                            950: '#0c281c',
                        },
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-20px)' },
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-primary-50 font-sans text-gray-800 antialiased min-h-screen flex flex-col w-full">
    
    <!-- Spacer -->
    <div class="flex-1"></div>

    <!-- Main Content -->
    <main class="w-full flex items-center justify-center px-4 py-8">
        <div class="w-full max-w-2xl mx-auto text-center">
            
            <!-- Splash Image -->
            <div class="flex justify-center mb-10">
                <div class="inline-flex justify-center items-center">
                    <img src="{{ asset('img/under-maintenance.png') }}" alt="Under Maintenance" class="h-48 md:h-64 w-auto object-contain drop-shadow-xl">
                </div>
            </div>

            <h1 class="font-display text-4xl md:text-5xl font-bold text-primary-900 mb-5 tracking-tight text-center mx-auto">
                Sistem Dalam Perbaikan
            </h1>
            
            <p class="text-lg text-primary-800/80 mb-10 max-w-lg mx-auto text-center leading-relaxed">
                Mohon maaf, sistem <strong>{{ config('app.name', 'SMART-HUT') }}</strong> saat ini sedang dalam pemeliharaan rutin. Kami akan segera kembali melayani Anda!
            </p>
            
            <div class="flex justify-center w-full mx-auto">
                <button onclick="window.location.reload()" class="inline-flex items-center justify-center px-8 py-3.5 bg-primary-600 text-white font-semibold rounded-xl hover:bg-primary-700 hover:shadow-lg hover:shadow-primary-600/30 transition-all duration-300 gap-2 group">
                    <svg class="w-5 h-5 group-hover:-rotate-180 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Coba Muat Ulang
                </button>
            </div>
        </div>
    </main>
    
    <!-- Footer -->
    <footer class="w-full text-center py-8 text-sm font-medium text-primary-700/60 mt-auto flex-1 flex items-end justify-center">
        <div class="w-full pb-4">
            &copy; <?php echo date('Y'); ?> Dinas Kehutanan Provinsi Jawa Timur
        </div>
    </footer>

</body>
</html>
