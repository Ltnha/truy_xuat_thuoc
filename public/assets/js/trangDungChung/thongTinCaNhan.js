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
            if (formDoiMatKhau) formDoiMatKhau.reset();
            const inputPass = document.getElementById('matKhauHienTai');
            if (inputPass) inputPass.focus();
        });

        function dongModal() {
            modalDoiPass.classList.add('hidden');
        }

        if (nutDongModal) nutDongModal.addEventListener('click', dongModal);
        if (nutHuy) nutHuy.addEventListener('click', dongModal);
    }

    // Sao chép địa chỉ ví vào Clipboard
    const nutSaoChepVi = document.getElementById('nutSaoChepVi');
    const giaTriDiaChiVi = document.getElementById('giaTriDiaChiVi');

    if (nutSaoChepVi && giaTriDiaChiVi) {
        nutSaoChepVi.addEventListener('click', function () {
            const diaChi = giaTriDiaChiVi.textContent.trim();
            navigator.clipboard.writeText(diaChi).then(() => {
                const span = this.querySelector('span');
                const icon = this.querySelector('i');
                
                if (span) span.textContent = 'Đã sao chép!';
                if (icon) icon.className = 'fa-solid fa-check text-emerald-600';

                setTimeout(() => {
                    if (span) span.textContent = 'Sao chép ví';
                    if (icon) icon.className = 'fa-regular fa-copy';
                }, 1800);
            });
        });
    }

    // Kiểm tra tính hợp lệ Client-side trước khi gửi Form
    if (formDoiMatKhau) {
        formDoiMatKhau.addEventListener('submit', function (e) {
            const passHienTai = document.getElementById('matKhauHienTai').value;
            const passMoi = document.getElementById('matKhauMoi').value;
            const passXacNhan = document.getElementById('xacNhanMatKhauMoi').value;

            if (passMoi.length < 8) {
                e.preventDefault();
                alert("Mật khẩu mới phải có độ dài tối thiểu 8 ký tự!");
                return;
            }

            if (passMoi !== passXacNhan) {
                e.preventDefault();
                alert("Mật khẩu mới và xác nhận mật khẩu không trùng khớp!");
                return;
            }

            if (passMoi === passHienTai) {
                e.preventDefault();
                alert("Mật khẩu mới không được trùng với mật khẩu hiện tại!");
                return;
            }
        });
    }
});