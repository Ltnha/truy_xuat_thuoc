<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Quản Trị Hệ Thống - PharmaChain')</title>
    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Tailwind CSS & FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { nenChinh: '#f2faf5' }, fontFamily: { sans: ['Inter', 'sans-serif'] } } }
        }
    </script>
    <!-- ethers.js v6 CDN – dùng để tương tác MetaMask & Smart Contract Sepolia -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ethers/6.13.4/ethers.umd.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
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