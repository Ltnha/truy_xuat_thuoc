<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'PharmaChain')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        nenChinh: '#f2faf5',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                },
            },
        };
    </script>
    @stack('styles')
</head>
<body class="bg-nenChinh text-slate-900 font-sans min-h-screen flex antialiased">
    @include('partials.sidebar')

    <div class="flex-1 flex flex-col min-w-0">
        @include('partials.topbar')
        <main class="flex-1 p-6 sm:p-8 max-w-7xl w-full mx-auto">
            @include('partials.thongBao')
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
