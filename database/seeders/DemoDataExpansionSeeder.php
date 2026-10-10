<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataExpansionSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('toChuc')->where('maSoThue', 'DEMO-EXP-2026-01')->exists()) {
            $this->command?->info('Demo expansion data already exists; nothing was added.');

            return;
        }

        DB::transaction(function (): void {
            $organizations = [
                ['tenToChuc' => 'Duoc pham Song Han (DEMO)', 'loaiToChuc' => 'NHA_SAN_XUAT', 'diaChi' => 'Da Nang', 'soDienThoai' => '02363900101', 'maSoThue' => 'DEMO-EXP-2026-01', 'soGiayPhepDuoc' => 'GP-DEMO-NSX-101', 'ngayCapGiayPhep' => '2025-01-10', 'ngayHetHanGiayPhep' => '2030-01-09', 'coQuanCap' => 'So Y te Da Nang', 'trangThaiDuyet' => 'DA_DUYET'],
                ['tenToChuc' => 'Duoc pham Cao Nguyen (DEMO)', 'loaiToChuc' => 'NHA_SAN_XUAT', 'diaChi' => 'Dak Lak', 'soDienThoai' => '02623900102', 'maSoThue' => 'DEMO-EXP-2026-02', 'soGiayPhepDuoc' => 'GP-DEMO-NSX-102', 'ngayCapGiayPhep' => '2025-02-10', 'ngayHetHanGiayPhep' => '2030-02-09', 'coQuanCap' => 'So Y te Dak Lak', 'trangThaiDuyet' => 'DA_DUYET'],
                ['tenToChuc' => 'Phan phoi Duoc Mien Tay (DEMO)', 'loaiToChuc' => 'NHA_PHAN_PHOI', 'diaChi' => 'Can Tho', 'soDienThoai' => '02923900103', 'maSoThue' => 'DEMO-EXP-2026-03', 'soGiayPhepDuoc' => 'GP-DEMO-PP-103', 'ngayCapGiayPhep' => '2025-03-10', 'ngayHetHanGiayPhep' => '2030-03-09', 'coQuanCap' => 'So Y te Can Tho', 'trangThaiDuyet' => 'DA_DUYET'],
                ['tenToChuc' => 'Nha thuoc Hoa Sen (DEMO)', 'loaiToChuc' => 'NHA_THUOC', 'diaChi' => 'Can Tho', 'soDienThoai' => '02923900104', 'maSoThue' => 'DEMO-EXP-2026-04', 'soGiayPhepDuoc' => 'GP-DEMO-NT-104', 'ngayCapGiayPhep' => '2025-04-10', 'ngayHetHanGiayPhep' => '2030-04-09', 'coQuanCap' => 'So Y te Can Tho', 'trangThaiDuyet' => 'DA_DUYET'],
                ['tenToChuc' => 'Co quan Quan ly Duoc Can Tho (DEMO)', 'loaiToChuc' => 'CO_QUAN_QUAN_LY', 'diaChi' => 'Can Tho', 'soDienThoai' => '02923900105', 'maSoThue' => 'DEMO-EXP-2026-05', 'soGiayPhepDuoc' => null, 'ngayCapGiayPhep' => null, 'ngayHetHanGiayPhep' => null, 'coQuanCap' => 'Bo Y te', 'trangThaiDuyet' => 'CHO_DUYET'],
            ];

            $organizationIds = [];
            foreach ($organizations as $organization) {
                $organizationIds[] = DB::table('toChuc')->insertGetId($organization);
            }

            $reviewerId = DB::table('taiKhoan')
                ->where('vaiTro', 'CO_QUAN_QUAN_LY')
                ->orderBy('id')
                ->value('id');

            if ($reviewerId === null) {
                throw new \RuntimeException('An existing regulator account is required to create demo approval records.');
            }

            $registrationIds = [];
            foreach ($organizationIds as $index => $organizationId) {
                $approved = $index < 4;
                $registrationIds[] = DB::table('yeuCauDangKy')->insertGetId([
                    'toChucId' => $organizationId,
                    'ngayGui' => sprintf('2026-10-%02d', $index + 1),
                    'versionHoSo' => 1,
                    'trangThai' => $approved ? 'DA_DUYET' : 'CHO_DUYET',
                    'nguoiDuyetId' => $approved ? $reviewerId : null,
                    'ngayXuLy' => $approved ? sprintf('2026-10-%02d', $index + 3) : null,
                    'lyDoTuChoi' => null,
                ]);
            }

            foreach ($registrationIds as $index => $registrationId) {
                DB::table('taiLieuDangKy')->insert([
                    'yeuCauDangKyId' => $registrationId,
                    'tenTaiLieu' => 'Ho so doanh nghiep demo ' . ($index + 1),
                    'loaiTaiLieu' => $index === 0 ? 'GIAY_PHEP' : 'HO_SO_DOANH_NGHIEP',
                    'duongDan' => 'demo/mo-rong/to-chuc-' . ($index + 1) . '/ho-so.pdf',
                    'ngayTaiLen' => sprintf('2026-10-%02d', $index + 1),
                ]);
            }

            $accountDefinitions = [
                ['username' => 'songhan.demo', 'role' => 'NHA_SAN_XUAT', 'organization' => 0, 'status' => 'ACTIVE', 'permissions' => [1, 3, 4, 5]],
                ['username' => 'caonguyen.demo', 'role' => 'NHA_SAN_XUAT', 'organization' => 1, 'status' => 'ACTIVE', 'permissions' => [1, 3, 4, 5]],
                ['username' => 'mientay.demo', 'role' => 'NHA_PHAN_PHOI', 'organization' => 2, 'status' => 'ACTIVE', 'permissions' => [1, 5]],
                ['username' => 'hoasen.demo', 'role' => 'NHA_THUOC', 'organization' => 3, 'status' => 'ACTIVE', 'permissions' => [1, 5]],
                ['username' => 'cantho.demo', 'role' => 'CO_QUAN_QUAN_LY', 'organization' => 4, 'status' => 'PENDING', 'permissions' => [1]],
                ['username' => 'admin.mo-rong.demo', 'role' => 'QUAN_TRI_VIEN', 'organization' => null, 'status' => 'ACTIVE', 'permissions' => [1, 2, 3, 4, 5, 6]],
            ];

            $accountIds = [];
            $passwordHash = Hash::make('Demo@12345');
            foreach ($accountDefinitions as $index => $definition) {
                $accountIds[] = DB::table('taiKhoan')->insertGetId([
                    'tenDangNhap' => $definition['username'],
                    'matKhau' => $passwordHash,
                    'email' => $definition['username'] . '@example.test',
                    'soDienThoai' => '09000001' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                    'vaiTro' => $definition['role'],
                    'trangThai' => $definition['status'],
                    'toChucId' => $definition['organization'] === null ? null : $organizationIds[$definition['organization']],
                    'ngayTao' => sprintf('2026-10-%02d 09:00:00', $index + 1),
                ]);

                foreach ($definition['permissions'] as $permissionId) {
                    DB::table('taiKhoan_quyenHan')->insert([
                        'taiKhoanId' => end($accountIds),
                        'quyenHanId' => $permissionId,
                    ]);
                }

                DB::table('nhatKyHeThong')->insert([
                    'taiKhoanId' => end($accountIds),
                    'hanhDong' => 'TAO_TAI_KHOAN_DEMO',
                    'thoiGian' => sprintf('2026-10-%02d 09:30:00', $index + 1),
                    'noiDung' => 'Tao tai khoan mo rong du lieu demo.',
                    'diaChiIP' => '192.0.2.' . (30 + $index),
                ]);
            }

            $ingredients = [
                ['Paracetamol', 'Paracetamol'],
                ['Loratadine', 'Loratadine'],
                ['Cetirizine', 'Cetirizine dihydrochloride'],
                ['Omeprazole', 'Omeprazole'],
                ['Metformin', 'Metformin hydrochloride'],
                ['Amlodipine', 'Amlodipine besylate'],
                ['Losartan', 'Losartan potassium'],
                ['Atorvastatin', 'Atorvastatin calcium'],
                ['Captopril', 'Captopril'],
                ['Azithromycin', 'Azithromycin'],
                ['Cefixime', 'Cefixime trihydrate'],
                ['Ambroxol', 'Ambroxol hydrochloride'],
                ['Montelukast', 'Montelukast sodium'],
                ['Domperidone', 'Domperidone'],
                ['Folic acid', 'Folic acid'],
                ['Acetylcysteine', 'Acetylcysteine'],
                ['Vitamin C', 'Ascorbic acid'],
                ['Doxycycline', 'Doxycycline hyclate'],
                ['Diclofenac', 'Diclofenac sodium'],
                ['Ferrous fumarate', 'Ferrous fumarate'],
            ];
            $strengths = ['10 mg', '20 mg', '25 mg', '50 mg', '100 mg', '250 mg', '500 mg'];
            $packages = ['Hop 3 vi x 10 vien', 'Hop 5 vi x 10 vien', 'Hop 10 vi x 10 vien', 'Hop 30 goi bot', 'Hop 2 vi x 7 vien nang'];
            $productIds = [];
            $productManufacturers = [];

            for ($index = 0; $index < 53; $index++) {
                $status = $index < 45 ? 'DA_DUYET' : ($index < 50 ? 'CHO_DUYET' : 'TU_CHOI');
                $manufacturerId = $index < 35
                    ? 1
                    : ($index < 45 ? $organizationIds[0] : $organizationIds[1]);
                [$name, $ingredient] = $ingredients[$index % count($ingredients)];
                $productId = DB::table('sanPham')->insertGetId([
                    'maSanPham' => (string) Str::uuid(),
                    'tenSanPham' => sprintf('%s %s - Lo demo %03d', $name, $strengths[$index % count($strengths)], $index + 1),
                    'hoatChat' => $ingredient . ' ' . $strengths[$index % count($strengths)],
                    'quyCachDongGoi' => $packages[$index % count($packages)],
                    'soDangKy' => sprintf('VD-DEMO-EXP-2026-%03d', $index + 1),
                    'nhaSanXuatId' => $manufacturerId,
                    'trangThaiDuyet' => $status,
                ]);
                $productIds[] = $productId;
                $productManufacturers[$productId] = $manufacturerId;

                $pending = $status === 'CHO_DUYET';
                DB::table('yeuCauDangKySanPham')->insert([
                    'sanPhamId' => $productId,
                    'versionHoSo' => 1,
                    'ngayGui' => sprintf('2026-10-%02d', ($index % 9) + 1),
                    'trangThai' => $status,
                    'nguoiXuLyId' => $pending ? null : $reviewerId,
                    'ngayXuLy' => $pending ? null : sprintf('2026-10-%02d', (($index + 2) % 9) + 1),
                    'lyDoTuChoi' => $status === 'TU_CHOI' ? 'Ho so demo can bo sung thong tin.' : null,
                ]);
            }

            foreach ($organizationIds as $index => $organizationId) {
                $address = '0x' . bin2hex(random_bytes(20));
                DB::table('blockchainIdentity')->insert([
                    'toChucId' => $organizationId,
                    'network' => 'Polygon Testnet',
                    'address' => $address,
                    'certificateId' => sprintf('DEMO-CERT-EXP-%03d', $index + 1),
                    'trangThai' => $index === 4 ? 'INACTIVE' : 'ACTIVE',
                ]);
            }

            $lotDefinitions = [
                ['product' => 0, 'code' => 'DEMO-EXP-2026-B001', 'quantity' => 16, 'made' => '2026-09-01', 'expires' => '2028-08-31', 'status' => 'CREATED'],
                ['product' => 35, 'code' => 'DEMO-EXP-2026-B002', 'quantity' => 10, 'made' => '2026-09-05', 'expires' => '2028-09-04', 'status' => 'IN_TRANSIT'],
                ['product' => 36, 'code' => 'DEMO-EXP-2026-B003', 'quantity' => 8, 'made' => '2026-08-15', 'expires' => '2028-08-14', 'status' => 'RECALLED'],
            ];

            $lotIds = [];
            $lotCodes = [];
            $lotHashes = [];
            foreach ($lotDefinitions as $definition) {
                $lotCode = $definition['code'];
                $lotHashes[] = '0x' . bin2hex(random_bytes(32));
                $lotIds[] = DB::table('loThuoc')->insertGetId([
                    'maLo' => (string) Str::uuid(),
                    'maLoNghiepVu' => $lotCode,
                    'sanPhamId' => $productIds[$definition['product']],
                    'toChucId' => $productManufacturers[$productIds[$definition['product']]],
                    'soLuong' => $definition['quantity'],
                    'ngaySanXuat' => $definition['made'],
                    'hanSuDung' => $definition['expires'],
                    'trangThai' => $definition['status'],
                ]);
                $lotCodes[] = $lotCode;
            }

            $saleIds = [];
            $saleHashes = ['0x' . bin2hex(random_bytes(32)), '0x' . bin2hex(random_bytes(32))];
            foreach ([2, 1] as $index => $quantity) {
                $saleIds[] = DB::table('banLe')->insertGetId([
                    'maBanLe' => (string) Str::uuid(),
                    'toChucId' => $organizationIds[3],
                    'ngayBan' => sprintf('2026-10-%02d 14:00:00', $index + 7),
                    'soLuong' => $quantity,
                    'trangThai' => 'CONFIRMED',
                    'txHash' => $saleHashes[$index],
                ]);
            }

            $unitIds = [];
            $unitSerials = [];
            $unitLotIndexes = [];
            $unitCounter = 0;
            foreach ([16, 10] as $lotIndex => $quantity) {
                for ($number = 1; $number <= $quantity; $number++) {
                    $unitCounter++;
                    $serial = sprintf('%s-%04d', $lotCodes[$lotIndex], $number);
                    $sold = $unitCounter <= 2 || $unitCounter === 17;
                    $saleIndex = $unitCounter === 17 ? 1 : 0;
                    $unitId = DB::table('donViSanPham')->insertGetId([
                        'maDonVi' => $serial,
                        'serialNumber' => 'SN' . str_pad((string) $unitCounter, 12, '0', STR_PAD_LEFT),
                        'loThuocId' => $lotIds[$lotIndex],
                        'banLeId' => $sold ? $saleIds[$saleIndex] : null,
                        'trangThai' => $sold ? 'DISPENSED' : 'AVAILABLE',
                        'ngayTao' => sprintf('2026-09-%02d 08:00:00', $lotIndex + 1),
                        'ngayBan' => $sold ? sprintf('2026-10-%02d 14:00:00', $saleIndex + 7) : null,
                        'txHash' => $sold ? $saleHashes[$saleIndex] : null,
                    ]);
                    $unitIds[] = $unitId;
                    $unitSerials[] = $serial;
                }
            }

            foreach ($lotIds as $index => $lotId) {
                DB::table('qrCode')->insert([
                    'giaTriQR' => 'QR-BATCH-' . $lotCodes[$index],
                    'loaiQR' => 'BATCH',
                    'loThuocId' => $lotId,
                    'donViSanPhamId' => null,
                    'ngayTao' => '2026-09-01 08:30:00',
                    'trangThai' => 'ACTIVE',
                ]);
            }

            foreach ($unitIds as $index => $unitId) {
                DB::table('qrCode')->insert([
                    'giaTriQR' => 'QR-UNIT-' . $unitSerials[$index],
                    'loaiQR' => 'UNIT',
                    'loThuocId' => null,
                    'donViSanPhamId' => $unitId,
                    'ngayTao' => '2026-09-01 08:30:00',
                    'trangThai' => 'ACTIVE',
                ]);
            }

            $transferDefinitions = [
                ['lot' => 0, 'from' => 0, 'to' => 2, 'quantity' => 6, 'status' => 'RECEIVED', 'created' => '2026-09-03 10:00:00', 'confirmed' => '2026-09-04 10:00:00'],
                ['lot' => 0, 'from' => 2, 'to' => 3, 'quantity' => 2, 'status' => 'PENDING', 'created' => '2026-09-05 11:00:00', 'confirmed' => null],
                ['lot' => 1, 'from' => 0, 'to' => 2, 'quantity' => 4, 'status' => 'IN_TRANSIT', 'created' => '2026-09-06 12:00:00', 'confirmed' => null],
                ['lot' => 1, 'from' => 2, 'to' => 3, 'quantity' => 1, 'status' => 'REJECTED', 'created' => '2026-09-07 13:00:00', 'confirmed' => null],
            ];

            $transferIds = [];
            $transferHashes = [];
            foreach ($transferDefinitions as $index => $definition) {
                $hash = '0x' . bin2hex(random_bytes(32));
                $transferHashes[] = $hash;
                $transferIds[] = DB::table('chuyenGiao')->insertGetId([
                    'maChuyenGiao' => (string) Str::uuid(),
                    'loThuocId' => $lotIds[$definition['lot']],
                    'benGuiId' => $organizationIds[$definition['from']],
                    'benNhanId' => $organizationIds[$definition['to']],
                    'soLuong' => $definition['quantity'],
                    'thoiGianKhoiTao' => $definition['created'],
                    'thoiGianXacNhan' => $definition['confirmed'],
                    'trangThai' => $definition['status'],
                    'txHash' => $hash,
                ]);
            }

            foreach ([
                [0, 0, 6], [0, 2, 8], [0, 3, 1],
                [1, 0, 4], [1, 2, 4], [1, 3, 1],
                [2, 1, 8],
            ] as [$lotIndex, $organizationIndex, $quantity]) {
                DB::table('tonKhoLo')->insert([
                    'loThuocId' => $lotIds[$lotIndex],
                    'toChucId' => $organizationIds[$organizationIndex],
                    'soLuong' => $quantity,
                    'ngayCapNhat' => '2026-10-08 10:00:00',
                ]);
            }

            $recallHash = '0x' . bin2hex(random_bytes(32));
            $historyHashes = [
                $lotHashes[0],
                $transferHashes[0],
                $transferHashes[0],
                $lotHashes[1],
                $transferHashes[2],
                $recallHash,
                $recallHash,
            ];
            foreach ([
                [0, '2026-09-01 08:30:00', 'DANG_KY_LO', 'Nha san xuat dang ky lo demo B001.'],
                [0, '2026-09-03 10:00:00', 'TAO_CHUYEN_GIAO', 'Khoi tao chuyen giao lo B001 cho nha phan phoi.'],
                [0, '2026-09-04 10:00:00', 'XAC_NHAN_CHUYEN_GIAO', 'Nha phan phoi xac nhan mot phan lo B001.'],
                [1, '2026-09-05 08:30:00', 'DANG_KY_LO', 'Nha san xuat dang ky lo demo B002.'],
                [1, '2026-09-06 12:00:00', 'TAO_CHUYEN_GIAO', 'Lo B002 dang duoc van chuyen.'],
                [2, '2026-09-08 09:00:00', 'BAO_CAO_NGHI_VAN', 'Tiep nhan bao cao kiem tra lo demo B003.'],
                [2, '2026-09-09 15:00:00', 'THU_HOI_LO', 'Lo demo B003 duoc danh dau thu hoi.'],
            ] as $index => [$lotIndex, $time, $event, $description]) {
                DB::table('lichSuLoThuoc')->insert([
                    'loThuocId' => $lotIds[$lotIndex],
                    'thoiGian' => $time,
                    'suKien' => $event,
                    'moTa' => $description,
                    'txHash' => $historyHashes[$index],
                ]);
            }

            foreach ([
                [$lotIds[0], 'QR-UNIT-' . $unitSerials[2], '2026-09-10 10:00:00'],
                [$lotIds[2], 'QR-BATCH-' . $lotCodes[2], '2026-09-11 11:00:00'],
            ] as [$lotId, $code, $time]) {
                DB::table('traCuu')->insert([
                    'loThuocId' => $lotId,
                    'maQRHoacSerial' => $code,
                    'thoiGianTraCuu' => $time,
                    'diaChiIP' => '198.51.100.' . random_int(1, 200),
                ]);
            }

            $reportIds = [];
            foreach ([
                ['code' => 'BC-DEMO-EXP-2026-0001', 'status' => 'PROCESSING', 'reason' => 'Nghi ngo bao quan lo thuoc khong dung dieu kien.'],
                ['code' => 'BC-DEMO-EXP-2026-0002', 'status' => 'APPROVED', 'reason' => 'Phat hien sai lech trong ho so lo thuoc.'],
            ] as $index => $report) {
                $reportIds[] = DB::table('baoCaoNghiVan')->insertGetId([
                    'maBaoCao' => $report['code'],
                    'loThuocId' => $lotIds[2],
                    'taiKhoanId' => $accountIds[3],
                    'lyDo' => $report['reason'],
                    'moTa' => 'Du lieu minh hoa, khong phai ket luan kiem dinh that.',
                    'ngayGui' => sprintf('2026-09-%02d', $index + 8),
                    'trangThai' => $report['status'],
                ]);

                DB::table('minhChungBaoCao')->insert([
                    'baoCaoId' => end($reportIds),
                    'tenFile' => 'minh-chung-demo-' . ($index + 1) . '.jpg',
                    'loaiFile' => 'image/jpeg',
                    'duongDan' => 'demo/mo-rong/bao-cao/' . $report['code'] . '/minh-chung.jpg',
                    'ngayTaiLen' => sprintf('2026-09-%02d', $index + 8),
                ]);
            }

            $resolutionId = DB::table('ketQuaXuLyBaoCao')->insertGetId([
                'baoCaoId' => $reportIds[1],
                'nguoiXuLyId' => $reviewerId,
                'quyetDinh' => 'THU_HOI',
                'ngayXuLy' => '2026-09-12',
                'lyDoXuLy' => 'Can cach ly lo hang de kiem tra.',
                'ghiChu' => 'Du lieu demo.',
            ]);

            DB::table('thuHoiLoHang')->insert([
                'loThuocId' => $lotIds[2],
                'ketQuaXuLyBaoCaoId' => $resolutionId,
                'lyDo' => 'Thu hoi minh hoa theo ket qua xu ly bao cao demo.',
                'ngayThuHoi' => '2026-09-12',
                'trangThai' => 'COMPLETED',
                'txHash' => $recallHash,
            ]);

            $transactionRecords = [];
            foreach ($lotIds as $index => $lotId) {
                $transactionRecords[] = [
                    'txHash' => $lotHashes[$index],
                    'blockNumber' => 101000 + $index * 10,
                    'eventName' => $index === 2 ? 'BatchRecalled' : 'BatchRegistered',
                    'businessId' => $lotCodes[$index],
                    'thoiGian' => sprintf('2026-09-%02d 08:30:00', $index + 1),
                    'network' => 'Polygon Testnet',
                    'trangThai' => 'CONFIRMED',
                    'toChucId' => $productManufacturers[$productIds[$lotDefinitions[$index]['product']]],
                    'loThuocId' => $lotId,
                    'chuyenGiaoId' => null,
                    'donViSanPhamId' => null,
                    'banLeId' => null,
                ];
            }

            foreach ($transferIds as $index => $transferId) {
                if ($transferDefinitions[$index]['status'] === 'REJECTED') {
                    continue;
                }

                $definition = $transferDefinitions[$index];
                $transactionRecords[] = [
                    'txHash' => $transferHashes[$index],
                    'blockNumber' => 101100 + $index,
                    'eventName' => 'TransferCreated',
                    'businessId' => DB::table('chuyenGiao')->where('id', $transferId)->value('maChuyenGiao'),
                    'thoiGian' => $definition['created'],
                    'network' => 'Polygon Testnet',
                    'trangThai' => $definition['status'] === 'RECEIVED' ? 'CONFIRMED' : 'PENDING',
                    'toChucId' => $organizationIds[$definition['from']],
                    'loThuocId' => $lotIds[$definition['lot']],
                    'chuyenGiaoId' => $transferId,
                    'donViSanPhamId' => null,
                    'banLeId' => null,
                ];
            }

            foreach ($saleIds as $index => $saleId) {
                $transactionRecords[] = [
                    'txHash' => $saleHashes[$index],
                    'blockNumber' => 101200 + $index,
                    'eventName' => 'RetailSale',
                    'businessId' => DB::table('banLe')->where('id', $saleId)->value('maBanLe'),
                    'thoiGian' => sprintf('2026-10-%02d 14:00:00', $index + 7),
                    'network' => 'Polygon Testnet',
                    'trangThai' => 'CONFIRMED',
                    'toChucId' => $organizationIds[3],
                    'loThuocId' => $lotIds[$index],
                    'chuyenGiaoId' => null,
                    'donViSanPhamId' => $index === 0 ? $unitIds[0] : $unitIds[16],
                    'banLeId' => $saleId,
                ];
            }

            $transactionRecords[] = [
                'txHash' => $recallHash,
                'blockNumber' => 101300,
                'eventName' => 'BatchRecalled',
                'businessId' => $lotCodes[2],
                'thoiGian' => '2026-09-12 15:00:00',
                'network' => 'Polygon Testnet',
                'trangThai' => 'CONFIRMED',
                'toChucId' => $organizationIds[4],
                'loThuocId' => $lotIds[2],
                'chuyenGiaoId' => null,
                'donViSanPhamId' => null,
                'banLeId' => null,
            ];
            DB::table('blockchainTransaction')->insert($transactionRecords);
        });

        $this->command?->info('Added an expanded, linked demo dataset without removing existing records.');
    }
}
