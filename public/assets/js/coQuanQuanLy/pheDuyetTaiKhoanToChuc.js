document.addEventListener('DOMContentLoaded', function () {
    // 1. Gắn sự kiện cho các nút "Xem hồ sơ" trong bảng Blade
    const modalXemHoSo = document.getElementById('modalXemHoSo');
    const modalDuyet = document.getElementById('modalXacNhanDuyet');
    const modalTuChoi = document.getElementById('modalTuChoiHoSo');

    // Mở Modal Xem Hồ Sơ & Đổ dữ liệu từ Blade data attribute
    document.querySelectorAll('.btn-xem-ho-so').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const ten = this.getAttribute('data-ten');
            const mst = this.getAttribute('data-mst');
            const loai = this.getAttribute('data-loai');

            // Cập nhật thông tin vào Modal
            const elTen = document.getElementById('panelTenToChucTitle');
            if (elTen) elTen.textContent = ten;

            // Gán ID tổ chức vào các Form Phê duyệt / Từ chối
            const formDuyet = document.getElementById('formDuyetToChucAction');
            if (formDuyet) formDuyet.action = `/co-quan/phe-duyet-to-chuc/${id}`;

            const formTuChoi = document.getElementById('formTuChoiToChucAction');
            if (formTuChoi) formTuChoi.action = `/co-quan/phe-duyet-to-chuc/${id}`;

            if (modalXemHoSo) modalXemHoSo.classList.remove('hidden');
        });
    });

    // 2. Đóng các Modal
    document.querySelectorAll('.dong-modal-hoso').forEach(btn => {
        btn.addEventListener('click', () => modalXemHoSo && modalXemHoSo.classList.add('hidden'));
    });

    document.querySelectorAll('.dong-modal-duyet').forEach(btn => {
        btn.addEventListener('click', () => modalDuyet && modalDuyet.classList.add('hidden'));
    });

    document.querySelectorAll('.dong-modal-tuchoi').forEach(btn => {
        btn.addEventListener('click', () => modalTuChoi && modalTuChoi.classList.add('hidden'));
    });

    // 3. Mở Modal Duyệt / Từ chối từ Panel
    const nutMoDuyet = document.getElementById('panelNutMoModalDuyet');
    if (nutMoDuyet) {
        nutMoDuyet.addEventListener('click', () => {
            if (modalDuyet) modalDuyet.classList.remove('hidden');
        });
    }

    const nutMoTuChoi = document.getElementById('panelNutMoModalTuChoi');
    if (nutMoTuChoi) {
        nutMoTuChoi.addEventListener('click', () => {
            if (modalTuChoi) modalTuChoi.classList.remove('hidden');
        });
    }
});