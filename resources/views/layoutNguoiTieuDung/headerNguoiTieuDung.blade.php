<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($tieuDeTrang) ? $tieuDeTrang : 'Hệ Thống Truy Xuất Dược Phẩm Blockchain'; ?></title>

    <!-- Google Font Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Tailwind CSS CDN với biến màu nền dùng chung -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        nenChinh: '#f2faf5',
                        /* Biến màu nền chính tái sử dụng cho mọi trang */
                    }
                }
            }
        }
    </script>

    <!-- CSS Bổ trợ Layout -->
    <link rel="stylesheet" href="{{ asset('assets/css/layoutNguoiTieuDung/headerNguoiTieuDung.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/layoutNguoiTieuDung/navbarNguoiTieuDung.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/layoutNguoiTieuDung/footerNguoiTieuDung.css') }}">

    <!-- CSS Riêng từng trang -->
    <?php if (isset($fileCssRieng)) { ?>
        <link rel="stylesheet" href="{{ asset($fileCssRieng) }}">
    <?php } ?>
    @stack('styles')

    <!-- ethers.js v6 CDN – dùng để tương tác MetaMask & Smart Contract Sepolia -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ethers/6.13.4/ethers.umd.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</head>

<body class="bg-nenChinh text-slate-900 font-sans min-h-screen flex flex-col antialiased">