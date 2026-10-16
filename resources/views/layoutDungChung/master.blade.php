<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="contract-address" content="{{ env('BLOCKCHAIN_CONTRACT_ADDRESS') }}">
    <title>@yield('title', 'Quản Trị Hệ Thống - PharmaChain')</title>
    <!-- Tối ưu kết nối sớm tới các CDN -->
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Tải CSS Tailwind nội bộ từ bản build tĩnh (tốc độ cao) hoặc fallback CDN -->
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif
    @stack('styles')
</head>
<body class="bg-slate-50 text-slate-900 font-sans">
    <div class="flex min-h-screen">
        <!-- Sidebar dùng chung bên trái -->
        @include('layoutDungChung.sidebarDungChung')

        <!-- Khu vực nội dung bên phải -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Topbar dùng chung phía trên -->
            @include('layoutDungChung.topbarDungChung')

            <!-- Nội dung thay đổi của từng trang quản lý -->
            <main class="flex-1 p-6">
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>