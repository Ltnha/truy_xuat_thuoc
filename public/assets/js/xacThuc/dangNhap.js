document.addEventListener('DOMContentLoaded', function () {
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

    // Leave credential verification to the server.
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