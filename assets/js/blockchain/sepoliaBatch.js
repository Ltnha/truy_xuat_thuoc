// Sepolia Batch Registration - JavaScript
 // Kết nối Form với Blockchain Service thông qua AJAX

 document.addEventListener('DOMContentLoaded', function() {

     // 1. TẢI DANH SÁCH LÔ TỪ DATABASE
     async function taiLoTuDB() {
         try {
             const response = await fetch('/api/lo-thuoc/danh-sach');
             if (!response.ok) throw new Error('Không tải được danh sách lô');

             const data = await response.json();
             const select = document.getElementById('chonLoTuDB');

             // Xóa option cũ (trừ option mặc định)
             select.innerHTML = '<option value="">-- Chọn lô thuốc --</option>';

             data.forEach(lo => {
                 const option = document.createElement('option');
                 option.value = lo.maLo;
                 option.textContent = `${lo.maLo} - ${lo.sanPhamTen}`;
                 select.appendChild(option);
             });
         } catch (error) {
             console.error('Lỗi tải lô từ DB:', error);
         }
     }

     // 2. XỬ LÊN BLOCKCHAIN (GHI NHẬN)
     async function xuLenBlockchain() {
         const maLo = document.getElementById('chonLoTuDB').value;
         const txHash = document.getElementById('txHashInput').value.trim();
         const confirmations = parseInt(document.getElementById('confInput').value) || 3;

         if (!maLo) {
             alert('Vui lòng chọn lô thuốc từ database');
             return;
         }

         if (!txHash) {
             alert('Vui lòng nhập mã giao trình (txHash)');
             return;
         }

         // Gửi request AJAX đến controller
         try {
             const response = await fetch('/blockchain/sepolia/synchronize', {
                 method: 'POST',
                 headers: {
                     'Content-Type': 'application/json',
                     'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                 },
                 body: JSON.stringify({
                     maLo: maLo,
                     txHash: txHash,
                     confirmations: confirmations
                 })
             });

             const result = await response.json();

             if (result.status === 'success') {
                 // Cập nhật giao diện kết quả
                 document.getElementById('trangThaiKQ').textContent = result.status_text;
                 document.getElementById('moTaKQ').textContent = result.message;
                 document.getElementById('txHashHienThi').textContent = result.txHash;
                 document.getElementById('blockNumberHienThi').textNumber = result.blockNumber || 'N/A';
                 document.getElementById('confirmationsHienThi').textContent = `${result.confirmations}/${result.requiredConfirmations || 3}`;

                 // Hiện section kết quả
                 document.getElementById('ketQuaSection').classList.remove('hidden');
             } else {
                 alert('Lỗi: ' + result.message);
             }
         } catch (error) {
             console.error('Lỗi kết nối blockchain:', error);
             alert('Không kết nối được với blockchain service');
         }
     }

     // 3. XỬ LÊN CHUYỂN GIAO (TRANSFER)
     async function xuLenChuyenGiao() {
         // TODO: Implement chuyển giao logic
         // Cần controller endpoint cho transfer
         alert('Chức năng chuyển giao sẽ được triển khai sau');
     }

     // 4. XỬ LÊN BUTTONS
     document.getElementById('dangKyBtn').addEventListener('click', xuLenBlockchain);

     // 5. TÍCH HỢP METAMASK SEPOLIA THẬT
     async function ghiNhanMetaMask() {
         if (typeof window.ethereum === 'undefined') {
             alert('MetaMask chưa được cài đặt. Vui lòng cài đặt MetaMask extension.');
             return;
         }
         try {
             const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
             const userAddress = accounts[0];
             const maLo = document.getElementById('chonLoTuDB').value;
             if (!maLo) { alert('Vui lòng chọn lô thuốc từ database'); return; }
             const response = await fetch('/blockchain/sepolia/synchronize', {
                 method: 'POST',
                 headers: {
                     'Content-Type': 'application/json',
                     'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                 },
                 body: JSON.stringify({ maLo: maLo, txHash: 'PENDING_METAMASK', confirmations: 0 })
             });
             const result = await response.json();
             if (result.status === 'success' || (result.txHash && result.txHash.includes('PENDING_METAMASK'))) {
                 document.getElementById('trangThaiKQ').textContent = 'PENDING_METAMASK';
                 document.getElementById('moTaKQ').textContent = 'Vui lòng ký giao dịch trên MetaMask để hoàn tất ghi nhận lên Sepolia.';
                 document.getElementById('txHashHienThi').textContent = result.txHash || 'PENDING_METAMASK';
                 document.getElementById('ketQuaSection').classList.remove('hidden');
                 window.confirmTxCallback = async function(realTxHash, blockNumber) {
                     const confirmResponse = await fetch('/api/blockchain/confirm-tx', {
                         method: 'POST',
                         headers: {
                             'Content-Type': 'application/json',
                             'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                         },
                         body: JSON.stringify({ businessId: maLo, txHash: realTxHash, blockNumber: blockNumber })
                     });
                     const confirmResult = await confirmResponse.json();
                     if (confirmResult.success) {
                         document.getElementById('trangThaiKQ').textContent = 'CONFIRMED';
                         document.getElementById('moTaKQ').textContent = 'Giao dịch đã được xác nhận trên Sepolia.';
                         document.getElementById('txHashHienThi').textContent = realTxHash;
                         document.getElementById('blockNumberHienThi').textContent = blockNumber || 'N/A';
                         document.getElementById('confirmationsHienThi').textContent = '3/3';
                     } else {
                         alert('Lỗi xác nhận giao dịch: ' + confirmResult.message);
                     }
                 };
             } else {
                 alert('Lỗi: ' + result.message);
             }
         } catch (error) {
             console.error('Lỗi MetaMask:', error);
             alert('Không thể kết nối MetaMask: ' + error.message);
         }
     }

     // Tự động tải lô từ DB khi load trang
     taiLoTuDB();
 });