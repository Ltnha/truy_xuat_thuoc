document.addEventListener('DOMContentLoaded', function () {
    const danhSachThe = document.querySelectorAll('.the-loai-to-chuc');
    const inputLoaiToChuc = document.getElementById('giaTriLoaiToChuc');

    // 1. Chuyển đổi chọn thẻ loại tổ chức
    if (danhSachThe.length > 0 && inputLoaiToChuc) {
        danhSachThe.forEach(the => {
            the.addEventListener('click', function () {
                danhSachThe.forEach(t => {
                    t.classList.remove('border-emerald-700', 'bg-emerald-50/50');
                    t.classList.add('border-slate-200', 'bg-white');
                    const icon = t.querySelector('.icon-check');
                    if (icon) {
                        icon.className = 'fa-regular fa-circle text-slate-300 text-lg mt-auto icon-check';
                    }
                });

                this.classList.remove('border-slate-200', 'bg-white');
                this.classList.add('border-emerald-700', 'bg-emerald-50/50');
                const currentIcon = this.querySelector('.icon-check');
                if (currentIcon) {
                    currentIcon.className = 'fa-solid fa-circle-check text-emerald-700 text-lg mt-auto icon-check';
                }

                inputLoaiToChuc.value = this.getAttribute('data-loai');
            });
        });
    }

    // 2. Kéo thả và chọn tệp đính kèm
    const khungKeoTha = document.getElementById('khuVucKeoThaFile');
    const inputTep = document.getElementById('tepDinhKem');
    const tenTepHienThi = document.getElementById('tenTepHienThi');

    if (khungKeoTha && inputTep) {
        khungKeoTha.addEventListener('click', () => inputTep.click());

        inputTep.addEventListener('change', function () {
            if (this.files && this.files.length > 0) {
                let listNames = Array.from(this.files)
                    .map(f => `${f.name} (${(f.size / 1024 / 1024).toFixed(2)} MB)`)
                    .join(', ');

                if (tenTepHienThi) {
                    tenTepHienThi.innerHTML = `<i class="fa-solid fa-file-circle-check text-emerald-700 mr-1.5"></i> ${listNames}`;
                }
            }
        });
    }

    // 3. Kiểm tra phía Client trước khi Submit
    const formDangKy = document.getElementById('formDangKyToChuc');
    const loiForm = document.getElementById('loiFormDangKy');
    const loiFormChiTiet = document.getElementById('loiFormChiTiet');

    if (formDangKy) {
        formDangKy.addEventListener('submit', function (e) {
            const p1 = document.getElementById('matKhauToChuc').value;
            const p2 = document.getElementById('xacNhanMatKhau').value;

            // Kiểm tra mật khẩu xác nhận
            if (p1 !== p2) {
                e.preventDefault(); // CHẶN SUBMIT VÌ LỖI MẬT KHẨU KHÔNG KHỚP
                if (loiForm && loiFormChiTiet) {
                    loiForm.classList.remove('hidden');
                    loiFormChiTiet.textContent = "Mật khẩu xác nhận không khớp. Vui lòng kiểm tra lại.";
                }
                return;
            }

            // Kiểm tra độ dài mật khẩu
            if (p1.length < 8) {
                e.preventDefault(); // CHẶN SUBMIT VÌ ĐỘ DÀI MẬT KHẨU DƯỚI 8 KÝ TỰ
                if (loiForm && loiFormChiTiet) {
                    loiForm.classList.remove('hidden');
                    loiFormChiTiet.textContent = "Mật khẩu phải chứa ít nhất 8 ký tự.";
                }
                return;
            }

            // Nếu hợp lệ, KHÔNG gọi e.preventDefault() -> Trình duyệt gửi form thật lên Laravel Server!
        });
    }
});