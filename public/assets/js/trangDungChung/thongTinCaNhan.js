document.addEventListener('DOMContentLoaded', function () {
    const nutMoModal = document.getElementById('nutMoModalDoiPass');
    const modalDoiPass = document.getElementById('modalDoiMatKhau');
    const nutDongModal = document.getElementById('nutDongModalDoiPass');
    const nutHuy = document.getElementById('nutHuyDoiPass');
    const formDoiMatKhau = document.getElementById('formDoiMatKhau');

    // Mở / Đóng modal Đổi Mật Khẩu
    if (nutMoModal && modalDoiPass) {
        nutMoModal.addEventListener('click', () => {
            modalDoiPass.classList.remove('hidden');
            formDoiMatKhau.reset();
            document.getElementById('matKhauHienTai').focus();
        });

        function dongModal() {
            modalDoiPass.classList.add('hidden');
        }

        nutDongModal.addEventListener('click', dongModal);
        nutHuy.addEventListener('click', dongModal);
    }

    // Sao chép địa chỉ ví vào clipboard
    const nutSaoChepVi = document.getElementById('nutSaoChepVi');
    const giaTriDiaChiVi = document.getElementById('giaTriDiaChiVi');

    if (nutSaoChepVi && giaTriDiaChiVi) {
        nutSaoChepVi.addEventListener('click', function () {
            const diaChi = giaTriDiaChiVi.textContent.trim();
            navigator.clipboard.writeText(diaChi).then(() => {
                const span = this.querySelector('span');
                const icon = this.querySelector('i');
                
                span.textContent = 'Đã sao chép!';
                icon.className = 'fa-solid fa-check text-emerald-600';

                setTimeout(() => {
                    span.textContent = 'Sao chép ví';
                    icon.className = 'fa-regular fa-copy';
                }, 1800);
            });
        });
    }

    // Xử lý gửi Form Đổi Mật Khẩu
    if (formDoiMatKhau) {
        formDoiMatKhau.addEventListener('submit', function (e) {
            e.preventDefault();

            const passHienTai = document.getElementById('matKhauHienTai').value;
            const passMoi = document.getElementById('matKhauMoi').value;
            const passXacNhan = document.getElementById('xacNhanMatKhauMoi').value;

            if (passMoi.length < 8) {
                alert("Mật khẩu mới phải có độ dài tối thiểu 8 ký tự!");
                return;
            }

            if (passMoi !== passXacNhan) {
                alert("Mật khẩu mới và xác nhận mật khẩu không trùng khớp!");
                return;
            }

            if (passMoi === passHienTai) {
                alert("Mật khẩu mới không được trùng với mật khẩu hiện tại!");
                return;
            }

            alert("Đổi mật khẩu tài khoản thành công! Vui lòng ghi nhớ mật khẩu mới cho lần đăng nhập tiếp theo.");
            modalDoiPass.classList.add('hidden');
        });
    }
});