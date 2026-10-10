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
            modalDoiPass.classList.add('flex');
            formDoiMatKhau.reset();
            document.getElementById('matKhauHienTai').focus();
        });

        function dongModal() {
            modalDoiPass.classList.add('hidden');
            modalDoiPass.classList.remove('flex');
        }

        nutDongModal?.addEventListener('click', dongModal);
        nutHuy?.addEventListener('click', dongModal);
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

});