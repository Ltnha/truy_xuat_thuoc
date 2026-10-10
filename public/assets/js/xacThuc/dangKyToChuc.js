document.addEventListener('DOMContentLoaded', function () {
    const danhSachThe = document.querySelectorAll('.the-loai-to-chuc');
    const inputLoaiToChuc = document.getElementById('giaTriLoaiToChuc');

    // Chuyển đổi 3 thẻ chọn loại tổ chức
    danhSachThe.forEach(the => {
        the.addEventListener('click', function () {
            danhSachThe.forEach(t => {
                t.classList.remove('border-emerald-700', 'bg-emerald-50/50');
                t.classList.add('border-slate-200', 'bg-white');
                const icon = t.querySelector('.icon-check');
                icon.className = 'fa-regular fa-circle text-slate-300 text-lg mt-auto icon-check';
            });

            this.classList.remove('border-slate-200', 'bg-white');
            this.classList.add('border-emerald-700', 'bg-emerald-50/50');
            const currentIcon = this.querySelector('.icon-check');
            currentIcon.className = 'fa-solid fa-circle-check text-emerald-700 text-lg mt-auto icon-check';

            inputLoaiToChuc.value = this.getAttribute('data-loai');
        });
    });

    // Kéo thả và chọn file đính kèm
    const khungKeoTha = document.getElementById('khuVucKeoThaFile');
    const inputTep = document.getElementById('tepDinhKem');
    const tenTepHienThi = document.getElementById('tenTepHienThi');

    khungKeoTha.addEventListener('click', () => inputTep.click());

    inputTep.addEventListener('change', function () {
        if (this.files && this.files.length > 0) {
            tenTepHienThi.textContent = `${this.files.length} tệp đã chọn (tệp đầu tiên: ${this.files[0].name})`;
        }
    });

    // Validate before posting the form to the server.
    const formDangKy = document.getElementById('formDangKyToChuc');
    const loiForm = document.getElementById('loiFormDangKy');
    const loiFormChiTiet = document.getElementById('loiFormChiTiet');

    formDangKy.addEventListener('submit', function (e) {
        const p1 = document.getElementById('matKhauToChuc').value;
        const p2 = document.getElementById('xacNhanMatKhau').value;

        if (p1 !== p2) {
            e.preventDefault();
            loiForm.classList.remove('hidden');
            loiFormChiTiet.textContent = "Mật khẩu xác nhận không khớp. Vui lòng kiểm tra lại.";
            return;
        }

        if (p1.length < 8) {
            e.preventDefault();
            loiForm.classList.remove('hidden');
            loiFormChiTiet.textContent = "Mật khẩu phải chứa ít nhất 8 ký tự.";
            return;
        }

        loiForm.classList.add('hidden');
    });
});