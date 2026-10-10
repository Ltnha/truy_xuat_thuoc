document.addEventListener('DOMContentLoaded', function () {
    const key = 'pharma_lich_su_tra_cuu';
    const root = document.querySelector('[data-url-tra-cuu]');
    const loading = document.getElementById('khungDangTai');
    const table = document.getElementById('khungBangLichSu');
    const body = document.getElementById('thanBangLichSu');
    const empty = document.getElementById('khungLichSuRong');
    const errorBox = document.getElementById('loiTaiLichSu');
    const removeButtons = [
        document.getElementById('nutXoaLichSu'),
        document.getElementById('nutXoaLichSuMobile'),
    ].filter(Boolean);

    function displayError(message) {
        errorBox.textContent = message;
        errorBox.classList.remove('hidden');
    }

    function getHistory() {
        try {
            const value = JSON.parse(localStorage.getItem(key) || '[]');
            if (!Array.isArray(value)) {
                throw new Error('Lịch sử lưu trên trình duyệt không đúng định dạng.');
            }
            return value.filter(item => typeof item?.ma === 'string' && item.ma.trim() !== '');
        } catch (error) {
            displayError(error.message || 'Không đọc được lịch sử lưu trên trình duyệt.');
            return [];
        }
    }

    function statusBadge(status) {
        const style = {
            VALID: ['HỢP LỆ', 'bg-emerald-100 text-emerald-800 border-emerald-200'],
            RECALLED: ['ĐÃ THU HỒI', 'bg-red-100 text-red-700 border-red-200'],
            EXPIRED: ['QUÁ HẠN', 'bg-amber-100 text-amber-700 border-amber-200'],
            INVALID: ['CHƯA ĐƯỢC DUYỆT', 'bg-amber-100 text-amber-700 border-amber-200'],
        }[status] || ['KHÔNG TÌM THẤY', 'bg-slate-100 text-slate-600 border-slate-200'];
        const badge = document.createElement('span');
        badge.className = `rounded-full border px-2.5 py-1 text-[11px] font-extrabold ${style[1]}`;
        badge.textContent = style[0];
        return badge;
    }

    async function render() {
        const history = getHistory();
        loading.classList.remove('hidden');
        table.classList.add('hidden');
        empty.classList.add('hidden');
        body.replaceChildren();

        if (history.length === 0) {
            loading.classList.add('hidden');
            empty.classList.remove('hidden');
            removeButtons.forEach(button => button.classList.add('hidden'));
            return;
        }

        try {
            const results = await Promise.all(history.map(async item => {
                const response = await fetch(`/api/truy-xuat/${encodeURIComponent(item.ma)}`, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                if (!response.ok) {
                    throw new Error(`Không thể kiểm tra lại mã ${item.ma}.`);
                }
                return { item, data: await response.json() };
            }));

            results.forEach(({ item, data }) => {
                const row = document.createElement('tr');
                const nameCell = document.createElement('td');
                nameCell.className = 'py-3.5 px-4 font-semibold text-slate-900';
                nameCell.textContent = data.tenThuoc;

                const codeCell = document.createElement('td');
                codeCell.className = 'py-3.5 px-4 font-mono font-bold text-cyan-800';
                codeCell.textContent = item.ma;

                const statusCell = document.createElement('td');
                statusCell.className = 'py-3.5 px-4';
                statusCell.appendChild(statusBadge(data.trangThai));

                const timeCell = document.createElement('td');
                timeCell.className = 'py-3.5 px-4 text-slate-500 font-mono text-[11px]';
                timeCell.textContent = item.thoiGian || '--';

                const actionCell = document.createElement('td');
                actionCell.className = 'py-3.5 px-4 text-center';
                const link = document.createElement('a');
                const url = new URL(root.dataset.urlTraCuu, window.location.origin);
                url.searchParams.set('maDinhDanh', item.ma);
                link.href = url.toString();
                link.className = 'rounded-lg bg-emerald-50 px-3 py-1.5 text-[11px] font-bold text-emerald-700';
                link.textContent = 'Xem kết quả';
                actionCell.appendChild(link);

                row.append(nameCell, codeCell, statusCell, timeCell, actionCell);
                body.appendChild(row);
            });

            table.classList.remove('hidden');
            removeButtons.forEach(button => button.classList.remove('hidden'));
        } catch (error) {
            displayError(error.message || 'Không thể cập nhật trạng thái lịch sử tra cứu.');
        } finally {
            loading.classList.add('hidden');
            if (body.children.length === 0 && history.length === 0 && errorBox.classList.contains('hidden')) {
                empty.classList.remove('hidden');
            }
        }
    }

    removeButtons.forEach(button => button.addEventListener('click', function () {
        if (window.confirm('Xóa lịch sử tra cứu đã lưu trên trình duyệt này?')) {
            localStorage.removeItem(key);
            render();
        }
    }));

    render();
});
