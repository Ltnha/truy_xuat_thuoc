<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title><?php echo isset($tieuDeTrang) ? $tieuDeTrang : 'Hệ Thống Truy Xuất Dược Phẩm Blockchain'; ?></title>

    <!-- Tối ưu kết nối sớm tới các CDN -->
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Tải CSS Tailwind nội bộ từ bản build tĩnh (tốc độ cao) hoặc fallback CDN -->
    @if (file_exists(public_path('build/manifest.json')))
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        nenChinh: '#f2faf5',
                    }
                }
            }
        }
    </script>
    @endif

    <!-- CSS Bổ trợ Layout -->
    <link rel="stylesheet" href="{{ asset('assets/css/layoutNguoiTieuDung/headerNguoiTieuDung.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/layoutNguoiTieuDung/navbarNguoiTieuDung.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/layoutNguoiTieuDung/footerNguoiTieuDung.css') }}">

    <!-- CSS Riêng từng trang -->
    <?php if (isset($fileCssRieng)) { ?>
        <link rel="stylesheet" href="{{ asset($fileCssRieng) }}">
    <?php } ?>
    @stack('styles')
</head>

<body class="bg-nenChinh text-slate-900 font-sans min-h-screen flex flex-col antialiased">