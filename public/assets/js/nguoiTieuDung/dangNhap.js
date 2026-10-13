document.addEventListener('DOMContentLoaded', function () {
    // Tự động nhận diện và điền tên đăng nhập nếu vừa bấm "Đăng nhập ngay" từ trang đăng ký
    const taiKhoanVuaTao = sessionStorage.getItem('pharma_tai_khoan_vua_tao');
    if (taiKhoanVuaTao) {
        const oTenDangNhap = document.getElementById('tenDangNhap');
        const oMatKhau = document.getElementById('matKhau');
        
        if (oTenDangNhap) {
            oTenDangNhap.value = taiKhoanVuaTao;
        }
        if (oMatKhau) {
            oMatKhau.focus(); // Tự động trỏ con trỏ chuột vào ô mật khẩu
        }
        sessionStorage.removeItem('pharma_tai_khoan_vua_tao'); // Xóa để không bị điền lại ở các lần sau
    }
    // ═══════════════════════════════

    const nutAnHien = document.getElementById('nutAnHienMatKhau');
    const oMatKhau = document.getElementById('matKhau');
    const iconAnHien = document.getElementById('iconAnHien');

    // Chức năng ẩn / hiện mật khẩu
    if (nutAnHien && oMatKhau && iconAnHien) {
        nutAnHien.addEventListener('click', function () {
            if (oMatKhau.type === 'password') {
                oMatKhau.type = 'text';
                iconAnHien.classList.remove('fa-eye');
                iconAnHien.classList.add('fa-eye-slash');
            } else {
                oMatKhau.type = 'password';
                iconAnHien.classList.remove('fa-eye-slash');
                iconAnHien.classList.add('fa-eye');
            }
        });
    }

    // Xử lý gửi form mô phỏng
    const formDangNhap = document.getElementById('formDangNhap');
    const thongBaoLoi = document.getElementById('thongBaoLoi');
    const noiDungLoi = document.getElementById('noiDungLoi');

    formDangNhap.addEventListener('submit', function (e) {
        const u = document.getElementById('tenDangNhap').value.trim();
        const p = document.getElementById('matKhau').value.trim();

        if (u === "" || p === "") {
            e.preventDefault();
            thongBaoLoi.classList.remove('hidden');
            noiDungLoi.textContent = "Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.";
        }
    });
});