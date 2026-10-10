<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'PharmaChain — Truy xuất nguồn gốc dược phẩm')</title>
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
    <link rel="stylesheet" href="{{ asset('assets/css/cong/headerNguoiTieuDung.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/cong/navbarNguoiTieuDung.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/cong/footerNguoiTieuDung.css') }}">
    @stack('styles')
</head>
<body class="bg-nenChinh text-slate-900 font-sans min-h-screen flex flex-col antialiased">
    @include('partials.congNavbar')
    @include('partials.thongBao')
    @yield('content')
    @include('partials.congFooter')
    <script src="{{ asset('assets/js/cong/headerNguoiTieuDung.js') }}" defer></script>
    <script src="{{ asset('assets/js/cong/navbarNguoiTieuDung.js') }}" defer></script>
    <script src="{{ asset('assets/js/cong/footerNguoiTieuDung.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
