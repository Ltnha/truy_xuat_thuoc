-- HỆ THỐNG TRUY XUẤT NGUỒN GỐC DƯỢC PHẨM (VERSION 3.2)
-- Database Schema chuẩn hóa kết hợp giữa v3 và v3.1

CREATE DATABASE IF NOT EXISTS truyxuatthuoc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE truyxuatthuoc;

-- 1. Bảng toChuc
CREATE TABLE toChuc (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tenToChuc VARCHAR(255) NOT NULL,
    loaiToChuc ENUM('NHA_SAN_XUAT','NHA_PHAN_PHOI','NHA_THUOC','CO_QUAN_QUAN_LY') NOT NULL,
    diaChi VARCHAR(255),
    soDienThoai VARCHAR(20),
    maSoThue VARCHAR(20) UNIQUE,
    soGiayPhepDuoc VARCHAR(50) NULL,
    ngayCapGiayPhep DATE NULL,
    ngayHetHanGiayPhep DATE NULL,
    coQuanCap VARCHAR(255) NULL,
    trangThaiDuyet ENUM('CHO_DUYET','DA_DUYET','TU_CHOI','BI_THU_HOI') NOT NULL DEFAULT 'CHO_DUYET'
);

-- 2. Bảng taiKhoan
CREATE TABLE taiKhoan (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tenDangNhap VARCHAR(50) NOT NULL UNIQUE,
    matKhau VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE,
    vaiTro ENUM('NHA_SAN_XUAT','NHA_PHAN_PHOI','NHA_THUOC','CO_QUAN_QUAN_LY','QUAN_TRI_VIEN') NOT NULL,
    trangThai ENUM('PENDING','ACTIVE','LOCKED','REVOKED') NOT NULL DEFAULT 'PENDING',
    toChucId BIGINT NULL,
    ngayTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (toChucId) REFERENCES toChuc(id)
);

-- 3. Bảng yeuCauDangKy (Đăng ký/Cập nhật thông tin tổ chức)
CREATE TABLE yeuCauDangKy (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    toChucId BIGINT NOT NULL,
    versionHoSo INT NOT NULL DEFAULT 1,
    ngayGui DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trangThai ENUM('CHO_DUYET','DA_DUYET','TU_CHOI') NOT NULL DEFAULT 'CHO_DUYET',
    nguoiDuyetId BIGINT NULL,
    ngayXuLy DATETIME NULL,
    lyDoTuChoi VARCHAR(500) NULL,
    FOREIGN KEY (toChucId) REFERENCES toChuc(id),
    FOREIGN KEY (nguoiDuyetId) REFERENCES taiKhoan(id)
);

-- 4. Bảng taiLieuDangKy (Hồ sơ đính kèm của tổ chức)
CREATE TABLE taiLieuDangKy (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    yeuCauDangKyId BIGINT NOT NULL,
    tenTaiLieu VARCHAR(255) NOT NULL,
    loaiTaiLieu VARCHAR(100),
    duongDan VARCHAR(500) NOT NULL,
    ngayTaiLen DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (yeuCauDangKyId) REFERENCES yeuCauDangKy(id)
);

-- 5. Bảng thuHoiQuyenToChuc (Lưu thông tin thu hồi quyền tổ chức)
CREATE TABLE thuHoiQuyenToChuc (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    toChucId BIGINT NOT NULL,
    nguoiThuHoiId BIGINT NOT NULL,
    lyDoThuHoi TEXT NOT NULL,
    ngayThuHoi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (toChucId) REFERENCES toChuc(id),
    FOREIGN KEY (nguoiThuHoiId) REFERENCES taiKhoan(id)
);

-- 6. Bảng nhatKyHeThong
CREATE TABLE nhatKyHeThong (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    taiKhoanId BIGINT NULL,
    doiTuongId BIGINT NULL,
    hanhDong VARCHAR(100) NOT NULL,
    ketQua ENUM('THANH_CONG','BI_TU_CHOI','LOI') NOT NULL DEFAULT 'THANH_CONG',
    thoiGian DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    chiTiet TEXT,
    FOREIGN KEY (taiKhoanId) REFERENCES taiKhoan(id),
    FOREIGN KEY (doiTuongId) REFERENCES taiKhoan(id)
);

-- 7. Bảng sanPham
CREATE TABLE sanPham (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    maSanPham CHAR(36) NOT NULL UNIQUE, -- Business ID mã hóa ánh xạ blockchain
    tenSanPham VARCHAR(255) NOT NULL,
    soDangKy VARCHAR(50) NOT NULL UNIQUE,
    thanhPhan VARCHAR(255),
    hamLuong VARCHAR(50),
    dangBaoChe VARCHAR(100),
    quyCachDongGoi VARCHAR(255),
    nhaSanXuatId BIGINT NOT NULL,
    trangThaiDuyet ENUM('CHO_DUYET','DA_DUYET','TU_CHOI') NOT NULL DEFAULT 'CHO_DUYET',
    FOREIGN KEY (nhaSanXuatId) REFERENCES toChuc(id)
);

-- 8. Bảng yeuCauDangKySanPham (Lưu thông tin kê khai tại thời điểm nộp)
CREATE TABLE yeuCauDangKySanPham (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    sanPhamId BIGINT NOT NULL,
    tenSanPham VARCHAR(255) NOT NULL,
    thanhPhan VARCHAR(255),
    hamLuong VARCHAR(50),
    dangBaoChe VARCHAR(100),
    quyCachDongGoi VARCHAR(255),
    versionHoSo INT NOT NULL DEFAULT 1,
    ngayGui DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trangThai ENUM('CHO_DUYET','DA_DUYET','TU_CHOI') NOT NULL DEFAULT 'CHO_DUYET',
    nguoiXuLyId BIGINT NULL,
    ngayXuLy DATETIME NULL,
    lyDoTuChoi VARCHAR(500) NULL,
    FOREIGN KEY (sanPhamId) REFERENCES sanPham(id),
    FOREIGN KEY (nguoiXuLyId) REFERENCES taiKhoan(id)
);

-- 9. Bảng loThuoc
CREATE TABLE loThuoc (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    maLo CHAR(36) NOT NULL UNIQUE, -- Business ID duy nhất trên blockchain
    maLoNghiepVu VARCHAR(50) NOT NULL UNIQUE,
    sanPhamId BIGINT NOT NULL,
    toChucId BIGINT NOT NULL, -- Nhà sản xuất đăng ký
    soLuong INT NOT NULL,
    ngaySanXuat DATE NOT NULL,
    hanSuDung DATE NOT NULL,
    trangThai ENUM('CREATED','IN_TRANSIT','RECALLED') NOT NULL DEFAULT 'CREATED',
    FOREIGN KEY (sanPhamId) REFERENCES sanPham(id),
    FOREIGN KEY (toChucId) REFERENCES toChuc(id),
    CONSTRAINT chk_lo_soluong CHECK (soLuong > 0),
    CONSTRAINT chk_lo_han CHECK (hanSuDung > ngaySanXuat)
);

-- 10. Bảng banLe (Đơn bán lẻ hàng loạt tại nhà thuốc)
CREATE TABLE banLe (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    maBanLe CHAR(36) NOT NULL UNIQUE,
    toChucId BIGINT NOT NULL, -- Nhà thuốc thực hiện
    ngayBan DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    soLuong INT NOT NULL,
    trangThai ENUM('PENDING','CONFIRMED','CANCELLED') NOT NULL DEFAULT 'CONFIRMED',
    FOREIGN KEY (toChucId) REFERENCES toChuc(id),
    CONSTRAINT chk_banle_soluong CHECK (soLuong > 0)
);

-- 11. Bảng donViSanPham
CREATE TABLE donViSanPham (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    maDonVi VARCHAR(50) NOT NULL UNIQUE,
    serialNumber VARCHAR(100) NOT NULL UNIQUE,
    loThuocId BIGINT NOT NULL,
    banLeId BIGINT NULL,
    trangThai ENUM('AVAILABLE','DISPENSED') NOT NULL DEFAULT 'AVAILABLE',
    ngayTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ngayBan DATETIME NULL,
    FOREIGN KEY (loThuocId) REFERENCES loThuoc(id),
    FOREIGN KEY (banLeId) REFERENCES banLe(id)
);

-- 12. Bảng qrCode
CREATE TABLE qrCode (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    giaTriQR VARCHAR(255) NOT NULL UNIQUE,
    loaiQR ENUM('BATCH','UNIT') NOT NULL,
    loThuocId BIGINT NULL,
    donViSanPhamId BIGINT NULL UNIQUE,
    ngayTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trangThai ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    FOREIGN KEY (loThuocId) REFERENCES loThuoc(id),
    FOREIGN KEY (donViSanPhamId) REFERENCES donViSanPham(id),
    CONSTRAINT chk_qr_loai CHECK (
        (loaiQR = 'BATCH' AND loThuocId IS NOT NULL AND donViSanPhamId IS NULL) OR
        (loaiQR = 'UNIT'  AND donViSanPhamId IS NOT NULL AND loThuocId IS NULL)
    )
);

-- 13. Bảng tonKhoLo
CREATE TABLE tonKhoLo (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    loThuocId BIGINT NOT NULL,
    toChucId BIGINT NOT NULL,
    soLuong INT NOT NULL DEFAULT 0,
    ngayCapNhat DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (loThuocId) REFERENCES loThuoc(id),
    FOREIGN KEY (toChucId) REFERENCES toChuc(id),
    UNIQUE KEY uq_ton_kho (loThuocId, toChucId),
    CONSTRAINT chk_tonkho_soluong CHECK (soLuong >= 0)
);

-- 14. Bảng chuyenGiao
CREATE TABLE chuyenGiao (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    maChuyenGiao CHAR(36) NOT NULL UNIQUE,
    loThuocId BIGINT NOT NULL,
    benGuiId BIGINT NOT NULL,
    benNhanId BIGINT NOT NULL,
    soLuong INT NOT NULL,
    thoiGianKhoiTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    thoiGianXacNhan DATETIME NULL,
    trangThai ENUM('PENDING','RECEIVED') NOT NULL DEFAULT 'PENDING',
    FOREIGN KEY (loThuocId) REFERENCES loThuoc(id),
    FOREIGN KEY (benGuiId) REFERENCES toChuc(id),
    FOREIGN KEY (benNhanId) REFERENCES toChuc(id),
    CONSTRAINT chk_soluong CHECK (soLuong > 0),
    CONSTRAINT chk_khac_to_chuc CHECK (benGuiId <> benNhanId),
    CONSTRAINT chk_xac_nhan CHECK (trangThai <> 'RECEIVED' OR thoiGianXacNhan IS NOT NULL)
);

-- 15. Bảng baoCaoNghiVan
CREATE TABLE baoCaoNghiVan (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    maBaoCao VARCHAR(50) NOT NULL UNIQUE,
    loThuocId BIGINT NULL, -- NULL nếu mã lô không tồn tại trên hệ thống
    maLoKhaiBao VARCHAR(50) NOT NULL,
    taiKhoanId BIGINT NULL, -- NULL nếu người tiêu dùng báo cáo ẩn danh
    lyDo TEXT NOT NULL,
    moTa TEXT NULL,
    ngayGui DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trangThai ENUM('DA_GUI','DANG_XU_LY','DA_XU_LY') NOT NULL DEFAULT 'DA_GUI',
    FOREIGN KEY (loThuocId) REFERENCES loThuoc(id),
    FOREIGN KEY (taiKhoanId) REFERENCES taiKhoan(id)
);

-- 16. Bảng minhChungBaoCao
CREATE TABLE minhChungBaoCao (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    baoCaoId BIGINT NOT NULL,
    tenFile VARCHAR(255) NOT NULL,
    loaiFile VARCHAR(50),
    duongDan VARCHAR(500) NOT NULL,
    ngayTaiLen DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (baoCaoId) REFERENCES baoCaoNghiVan(id)
);

-- 17. Bảng ketQuaXuLyBaoCao
CREATE TABLE ketQuaXuLyBaoCao (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    baoCaoId BIGINT NOT NULL UNIQUE,
    coQuanXuLyId BIGINT NOT NULL,
    quyetDinh ENUM('THU_HOI','TU_CHOI','GHI_NHAN') NOT NULL,
    lyDoXuLy VARCHAR(500) NULL,
    ghiChu VARCHAR(500) NULL,
    ngayXuLy DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (baoCaoId) REFERENCES baoCaoNghiVan(id),
    FOREIGN KEY (coQuanXuLyId) REFERENCES taiKhoan(id)
);

-- 18. Bảng thuHoiLoHang
CREATE TABLE thuHoiLoHang (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    loThuocId BIGINT NOT NULL,
    coQuanQuyetDinhId BIGINT NOT NULL,
    ketQuaXuLyBaoCaoId BIGINT NULL UNIQUE,
    lyDo TEXT NOT NULL,
    ngayThuHoi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (loThuocId) REFERENCES loThuoc(id),
    FOREIGN KEY (coQuanQuyetDinhId) REFERENCES taiKhoan(id),
    FOREIGN KEY (ketQuaXuLyBaoCaoId) REFERENCES ketQuaXuLyBaoCao(id)
);

-- 19. Bảng blockchainIdentity
CREATE TABLE blockchainIdentity (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    toChucId BIGINT NOT NULL UNIQUE,
    network VARCHAR(50) NOT NULL DEFAULT 'Polygon Testnet',
    address VARCHAR(42) NOT NULL UNIQUE,
    certificateId VARCHAR(100) NULL,
    trangThai ENUM('ACTIVE','INACTIVE','REVOKED') NOT NULL DEFAULT 'ACTIVE',
    FOREIGN KEY (toChucId) REFERENCES toChuc(id)
);

-- 20. Bảng blockchainTransaction (Nguồn tham chiếu duy nhất cho mọi TxHash)
CREATE TABLE blockchainTransaction (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    txHash VARCHAR(66) NOT NULL UNIQUE,
    blockNumber BIGINT NULL,
    eventName VARCHAR(100) NOT NULL,
    businessId VARCHAR(36) NOT NULL,
    thoiGian DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    network VARCHAR(50) NOT NULL DEFAULT 'Polygon Testnet',
    trangThai ENUM('PENDING','CONFIRMED','FAILED') NOT NULL DEFAULT 'PENDING',
    toChucId BIGINT NOT NULL,
    sanPhamId BIGINT NULL,
    loThuocId BIGINT NULL,
    chuyenGiaoId BIGINT NULL,
    donViSanPhamId BIGINT NULL,
    banLeId BIGINT NULL,
    FOREIGN KEY (toChucId) REFERENCES toChuc(id),
    FOREIGN KEY (sanPhamId) REFERENCES sanPham(id),
    FOREIGN KEY (loThuocId) REFERENCES loThuoc(id),
    FOREIGN KEY (chuyenGiaoId) REFERENCES chuyenGiao(id),
    FOREIGN KEY (donViSanPhamId) REFERENCES donViSanPham(id),
    FOREIGN KEY (banLeId) REFERENCES banLe(id)
);

-- 21. Các Index tối ưu truy vấn
CREATE INDEX idx_taikhoan_tochuc ON taiKhoan(toChucId);
CREATE INDEX idx_nhatky_taikhoan ON nhatKyHeThong(taiKhoanId);
CREATE INDEX idx_nhatky_doituong ON nhatKyHeThong(doiTuongId);
CREATE INDEX idx_nhatky_thoigian ON nhatKyHeThong(thoiGian);
CREATE INDEX idx_yeucaudk_tochuc ON yeuCauDangKy(toChucId);
CREATE INDEX idx_sanpham_nhasanxuat ON sanPham(nhaSanXuatId);
CREATE INDEX idx_yeucausp_sanpham ON yeuCauDangKySanPham(sanPhamId);
CREATE INDEX idx_lothuoc_sanpham ON loThuoc(sanPhamId);
CREATE INDEX idx_lothuoc_tochuc ON loThuoc(toChucId);
CREATE INDEX idx_donvi_lothuoc ON donViSanPham(loThuocId);
CREATE INDEX idx_donvi_banle ON donViSanPham(banLeId);
CREATE INDEX idx_tonkho_tochuc ON tonKhoLo(toChucId);
CREATE INDEX idx_chuyengiao_lothuoc ON chuyenGiao(loThuocId);
CREATE INDEX idx_chuyengiao_bengui ON chuyenGiao(benGuiId);
CREATE INDEX idx_chuyengiao_bennhan ON chuyenGiao(benNhanId);
CREATE INDEX idx_banle_tochuc ON banLe(toChucId);
CREATE INDEX idx_baocao_lothuoc ON baoCaoNghiVan(loThuocId);
CREATE INDEX idx_thuhoi_lothuoc ON thuHoiLoHang(loThuocId);
CREATE INDEX idx_bctx_lothuoc ON blockchainTransaction(loThuocId);
CREATE INDEX idx_bctx_tochuc ON blockchainTransaction(toChucId);
CREATE INDEX idx_bctx_businessid ON blockchainTransaction(businessId);

-- ============================================================================
-- DỮ LIỆU MẪU KIỂM THỦ (DEMO DATA) CHO HỆ THỐNG TRUY XUẤT THUỐC V3.2
-- ============================================================================

USE truyxuatthuoc;

START TRANSACTION;

-- 1. Bảng toChuc
INSERT INTO toChuc
    (id, tenToChuc, loaiToChuc, diaChi, soDienThoai, maSoThue, soGiayPhepDuoc, ngayCapGiayPhep, ngayHetHanGiayPhep, coQuanCap, trangThaiDuyet)
VALUES
    (1, 'Cong ty Co phan Duoc pham An Khang (DEMO)', 'NHA_SAN_XUAT', 'Khu cong nghiep Hoa Khanh, Da Nang', '02363888001', '0402190001', 'GP-N1-2025-001', '2025-01-15', '2030-01-14', 'Bo Y te', 'DA_DUYET'),
    (2, 'Cong ty TNHH Phan phoi Minh Chau (DEMO)', 'NHA_PHAN_PHOI', 'Quan Binh Thanh, TP Ho Chi Minh', '02838880002', '0318290002', 'GP-PP-2025-002', '2025-02-10', '2030-02-09', 'So Y te TP Ho Chi Minh', 'DA_DUYET'),
    (3, 'Nha thuoc Tan Tam (DEMO)', 'NHA_THUOC', 'Quan Hai Chau, Da Nang', '02363888003', '0402190003', 'GP-NT-2025-003', '2025-03-01', '2030-02-28', 'So Y te Da Nang', 'DA_DUYET'),
    (4, 'Co quan Quan ly Duoc Da Nang (DEMO)', 'CO_QUAN_QUAN_LY', 'Quan Hai Chau, Da Nang', '02363888004', '0402190004', NULL, NULL, NULL, 'Bo Y te', 'DA_DUYET'),
    (5, 'Cong ty Duoc Hoa Binh (DEMO - cho duyet)', 'NHA_SAN_XUAT', 'Thanh pho Hue', '02343888005', '3302190005', 'GP-N1-2026-005', '2026-05-10', '2031-05-09', 'So Y te Thua Thien Hue', 'CHO_DUYET'),
    (6, 'Duoc pham Song Han (DEMO)', 'NHA_SAN_XUAT', 'Da Nang', '02363900101', 'DEMO-EXP-2026-01', 'GP-DEMO-NSX-101', '2025-01-10', '2030-01-09', 'So Y te Da Nang', 'DA_DUYET'),
    (7, 'Duoc pham Cao Nguyen (DEMO)', 'NHA_SAN_XUAT', 'Dak Lak', '02623900102', 'DEMO-EXP-2026-02', 'GP-DEMO-NSX-102', '2025-02-10', '2030-02-09', 'So Y te Dak Lak', 'DA_DUYET'),
    (8, 'Phan phoi Duoc Mien Tay (DEMO)', 'NHA_PHAN_PHOI', 'Can Tho', '02923900103', 'DEMO-EXP-2026-03', 'GP-DEMO-PP-103', '2025-03-10', '2030-03-09', 'So Y te Can Tho', 'DA_DUYET'),
    (9, 'Nha thuoc Hoa Sen (DEMO)', 'NHA_THUOC', 'Can Tho', '02923900104', 'DEMO-EXP-2026-04', 'GP-DEMO-NT-104', '2025-04-10', '2030-04-09', 'So Y te Can Tho', 'DA_DUYET'),
    (10, 'Co quan Quan ly Duoc Can Tho (DEMO)', 'CO_QUAN_QUAN_LY', 'Can Tho', '02923900105', 'DEMO-EXP-2026-05', NULL, NULL, NULL, 'Bo Y te', 'DA_DUYET');

-- 2. Bảng taiKhoan
INSERT INTO taiKhoan
    (id, tenDangNhap, matKhau, email, vaiTro, trangThai, toChucId, ngayTao)
VALUES
    (1, 'admin.demo', '$2y$10$Zi0DJPfM42Uslc39G53WWuVLqd1L2sOOzGTsUWAsC/tpvKFiwje6q', 'admin.demo@example.test', 'QUAN_TRI_VIEN', 'ACTIVE', NULL, '2026-10-01 08:00:00'),
    (2, 'coquan.demo', '$2y$10$Zi0DJPfM42Uslc39G53WWuVLqd1L2sOOzGTsUWAsC/tpvKFiwje6q', 'coquan.demo@example.test', 'CO_QUAN_QUAN_LY', 'ACTIVE', 4, '2026-10-01 08:05:00'),
    (3, 'nsx.demo', '$2y$10$Zi0DJPfM42Uslc39G53WWuVLqd1L2sOOzGTsUWAsC/tpvKFiwje6q', 'nsx.demo@example.test', 'NHA_SAN_XUAT', 'ACTIVE', 1, '2026-10-01 08:10:00'),
    (4, 'npp.demo', '$2y$10$Zi0DJPfM42Uslc39G53WWuVLqd1L2sOOzGTsUWAsC/tpvKFiwje6q', 'npp.demo@example.test', 'NHA_PHAN_PHOI', 'ACTIVE', 2, '2026-10-01 08:15:00'),
    (5, 'nhathuoc.demo', '$2y$10$Zi0DJPfM42Uslc39G53WWuVLqd1L2sOOzGTsUWAsC/tpvKFiwje6q', 'nhathuoc.demo@example.test', 'NHA_THUOC', 'ACTIVE', 3, '2026-10-01 08:20:00'),
    (6, 'nsx.choduyet.demo', '$2y$10$Zi0DJPfM42Uslc39G53WWuVLqd1L2sOOzGTsUWAsC/tpvKFiwje6q', 'nsx.choduyet.demo@example.test', 'NHA_SAN_XUAT', 'PENDING', 5, '2026-10-05 09:00:00'),
    (7, 'songhan.demo', '$2y$12$nMNHxAtgI.RoS7caolZsq.tKiDDG0t3ye9mcwyrCKBQbbbd7T0jR2', 'songhan.demo@example.test', 'NHA_SAN_XUAT', 'ACTIVE', 6, '2026-10-01 09:00:00'),
    (8, 'caonguyen.demo', '$2y$12$nMNHxAtgI.RoS7caolZsq.tKiDDG0t3ye9mcwyrCKBQbbbd7T0jR2', 'caonguyen.demo@example.test', 'NHA_SAN_XUAT', 'ACTIVE', 7, '2026-10-02 09:00:00'),
    (9, 'mientay.demo', '$2y$12$nMNHxAtgI.RoS7caolZsq.tKiDDG0t3ye9mcwyrCKBQbbbd7T0jR2', 'mientay.demo@example.test', 'NHA_PHAN_PHOI', 'ACTIVE', 8, '2026-10-03 09:00:00'),
    (10, 'hoasen.demo', '$2y$12$nMNHxAtgI.RoS7caolZsq.tKiDDG0t3ye9mcwyrCKBQbbbd7T0jR2', 'hoasen.demo@example.test', 'NHA_THUOC', 'ACTIVE', 9, '2026-10-04 09:00:00'),
    (11, 'cantho.demo', '$2y$12$nMNHxAtgI.RoS7caolZsq.tKiDDG0t3ye9mcwyrCKBQbbbd7T0jR2', 'cantho.demo@example.test', 'CO_QUAN_QUAN_LY', 'ACTIVE', 10, '2026-10-05 09:00:00'),
    (12, 'admin.mo-rong.demo', '$2y$12$nMNHxAtgI.RoS7caolZsq.tKiDDG0t3ye9mcwyrCKBQbbbd7T0jR2', 'admin.mo-rong.demo@example.test', 'QUAN_TRI_VIEN', 'ACTIVE', NULL, '2026-10-06 09:00:00');

-- 3. Bảng yeuCauDangKy
INSERT INTO yeuCauDangKy
    (id, toChucId, versionHoSo, ngayGui, trangThai, nguoiDuyetId, ngayXuLy, lyDoTuChoi)
VALUES
    (1, 1, 1, '2026-09-01 08:00:00', 'DA_DUYET', 2, '2026-09-05 09:00:00', NULL),
    (2, 2, 1, '2026-09-02 08:00:00', 'DA_DUYET', 2, '2026-09-06 09:00:00', NULL),
    (3, 3, 1, '2026-09-03 08:00:00', 'DA_DUYET', 2, '2026-09-07 09:00:00', NULL),
    (4, 5, 1, '2026-10-05 09:00:00', 'CHO_DUYET', NULL, NULL, NULL),
    (5, 6, 1, '2026-10-01 09:00:00', 'DA_DUYET', 2, '2026-10-03 10:00:00', NULL),
    (6, 7, 1, '2026-10-02 09:00:00', 'DA_DUYET', 2, '2026-10-04 10:00:00', NULL),
    (7, 8, 1, '2026-10-03 09:00:00', 'DA_DUYET', 2, '2026-10-05 10:00:00', NULL),
    (8, 9, 1, '2026-10-04 09:00:00', 'DA_DUYET', 2, '2026-10-06 10:00:00', NULL);

-- 4. Bảng taiLieuDangKy
INSERT INTO taiLieuDangKy (id, yeuCauDangKyId, tenTaiLieu, loaiTaiLieu, duongDan, ngayTaiLen)
VALUES
    (1, 1, 'Giay chung nhan du dieu kien kinh doanh duoc', 'GIAY_PHEP', 'demo/ho-so/an-khang/giay-phep.pdf', '2026-09-01 08:00:00'),
    (2, 1, 'Giay chung nhan GMP', 'GMP', 'demo/ho-so/an-khang/gmp.pdf', '2026-09-01 08:00:00'),
    (3, 2, 'Giay chung nhan du dieu kien phan phoi', 'GIAY_PHEP', 'demo/ho-so/minh-chau/giay-phep.pdf', '2026-09-02 08:00:00'),
    (4, 3, 'Giay chung nhan du dieu kien nha thuoc', 'GIAY_PHEP', 'demo/ho-so/tan-tam/giay-phep.pdf', '2026-09-03 08:00:00'),
    (5, 4, 'Ho so dang ky doanh nghiep', 'HO_SO_DOANH_NGHIEP', 'demo/ho-so/hoa-binh/dang-ky.pdf', '2026-10-05 09:00:00');

-- 5. Bảng nhatKyHeThong
INSERT INTO nhatKyHeThong (id, taiKhoanId, doiTuongId, hanhDong, ketQua, thoiGian, chiTiet)
VALUES
    (1, 1, NULL, 'TAO_DU_LIEU_DEMO', 'THANH_CONG', '2026-10-01 08:30:00', 'Khoi tao bo du lieu demo cho kiem thu local.'),
    (2, 2, 3, 'PHE_DUYET_TO_CHUC', 'THANH_CONG', '2026-10-01 09:00:00', 'Phe duyet ho so to chuc An Khang.'),
    (3, 3, NULL, 'DANG_KY_LO', 'THANH_CONG', '2026-10-02 08:30:00', 'Dang ky lo demo AK-PARA-2026-001.'),
    (4, 4, NULL, 'XAC_NHAN_CHUYEN_GIAO', 'THANH_CONG', '2026-10-03 10:00:00', 'Nhan mot phan lo AK-PARA-2026-001.'),
    (5, 5, NULL, 'GHI_NHAN_BAN_LE', 'THANH_CONG', '2026-10-05 16:30:00', 'Ban 2 don vi thuoc demo cho khach hang.');

-- 6. Bảng sanPham
INSERT INTO sanPham
    (id, maSanPham, tenSanPham, soDangKy, thanhPhan, hamLuong, dangBaoChe, quyCachDongGoi, nhaSanXuatId, trangThaiDuyet)
VALUES
    (1, '11111111-1111-4111-8111-111111111111', 'Paracetamol An Khang 500 mg (DEMO)', 'VD-DEMO-2026-001', 'Paracetamol', '500 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, 'DA_DUYET'),
    (2, '22222222-2222-4222-8222-222222222222', 'Amoxicillin An Khang 500 mg (DEMO)', 'VD-DEMO-2026-002', 'Amoxicillin', '500 mg', 'Vien nang', 'Hop 10 vi x 10 vien', 1, 'DA_DUYET'),
    (3, '33333333-3333-4333-8333-333333333333', 'Vitamin C Hoa Binh 500 mg (DEMO)', 'VD-DEMO-2026-003', 'Acid ascorbic', '500 mg', 'Vien nen sủi', 'Hop 10 vi x 10 vien', 5, 'CHO_DUYET'),
    (54, '37001061-2935-4158-aad4-ebb6bcf1e746', 'Paracetamol 10 mg - Lo demo 001', 'VD-DEMO-EXP-2026-001', 'Paracetamol', '10 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, 'DA_DUYET'),
    (89, 'dc8f2acc-185c-409f-afc4-fe3e7f11be87', 'Acetylcysteine 10 mg - Lo demo 036', 'VD-DEMO-EXP-2026-036', 'Acetylcysteine', '10 mg', 'Gói bột', 'Hop 3 vi x 10 vien', 6, 'DA_DUYET'),
    (90, '9dad8268-135e-4810-a359-71be0144a94f', 'Vitamin C 20 mg - Lo demo 037', 'VD-DEMO-EXP-2026-037', 'Ascorbic acid', '20 mg', 'Vien nen', 'Hop 5 vi x 10 vien', 6, 'DA_DUYET');

-- 7. Bảng yeuCauDangKySanPham (Sửa toàn bộ lỗi ngày xử lý < ngày gửi)
INSERT INTO yeuCauDangKySanPham
    (id, sanPhamId, tenSanPham, thanhPhan, hamLuong, dangBaoChe, quyCachDongGoi, versionHoSo, ngayGui, trangThai, nguoiXuLyId, ngayXuLy, lyDoTuChoi)
VALUES
    (1, 1, 'Paracetamol An Khang 500 mg (DEMO)', 'Paracetamol', '500 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, '2026-09-08 08:00:00', 'DA_DUYET', 2, '2026-09-15 10:00:00', NULL),
    (2, 2, 'Amoxicillin An Khang 500 mg (DEMO)', 'Amoxicillin', '500 mg', 'Vien nang', 'Hop 10 vi x 10 vien', 1, '2026-09-09 08:00:00', 'DA_DUYET', 2, '2026-09-16 10:00:00', NULL),
    (3, 3, 'Vitamin C Hoa Binh 500 mg (DEMO)', 'Acid ascorbic', '500 mg', 'Vien nen sủi', 'Hop 10 vi x 10 vien', 1, '2026-10-05 09:00:00', 'CHO_DUYET', NULL, NULL, NULL),
    (54, 54, 'Paracetamol 10 mg - Lo demo 001', 'Paracetamol', '10 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, '2026-10-01 08:00:00', 'DA_DUYET', 2, '2026-10-03 09:00:00', NULL),
    (89, 89, 'Acetylcysteine 10 mg - Lo demo 036', 'Acetylcysteine', '10 mg', 'Gói bột', 'Hop 3 vi x 10 vien', 1, '2026-10-01 08:00:00', 'DA_DUYET', 2, '2026-10-02 09:00:00', NULL),
    (90, 90, 'Vitamin C 20 mg - Lo demo 037', 'Ascorbic acid', '20 mg', 'Vien nen', 'Hop 5 vi x 10 vien', 1, '2026-10-01 08:00:00', 'DA_DUYET', 2, '2026-10-03 09:00:00', NULL);

-- 8. Bảng loThuoc
INSERT INTO loThuoc
    (id, maLo, maLoNghiepVu, sanPhamId, toChucId, soLuong, ngaySanXuat, hanSuDung, trangThai)
VALUES
    (1, 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'AK-PARA-2026-001', 1, 1, 20, '2026-08-01', '2028-07-31', 'CREATED'),
    (2, 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'AK-AMOX-2026-002', 2, 1, 6, '2026-09-01', '2028-08-31', 'RECALLED'),
    (3, '264cdb4f-7a26-4390-a18d-b57bfbb4dafb', 'DEMO-EXP-2026-B001', 54, 1, 16, '2026-09-01', '2028-08-31', 'CREATED'),
    (4, 'e060159a-c060-4a7f-837c-dc509fc4acac', 'DEMO-EXP-2026-B002', 89, 6, 10, '2026-09-05', '2028-09-04', 'IN_TRANSIT'),
    (5, 'bf546894-2af5-476e-93a0-9f96183a2b11', 'DEMO-EXP-2026-B003', 90, 6, 8, '2026-08-15', '2028-08-14', 'RECALLED');

-- 9. Bảng banLe
INSERT INTO banLe
    (id, maBanLe, toChucId, ngayBan, soLuong, trangThai)
VALUES
    (1, 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', 3, '2026-10-05 16:30:00', 2, 'CONFIRMED'),
    (2, 'e12144ac-ec46-4496-a688-6e8c4ddc8d21', 9, '2026-10-07 14:00:00', 2, 'CONFIRMED'),
    (3, '457df10c-396d-4195-b5bc-86c2af9a7448', 9, '2026-10-08 14:00:00', 1, 'CONFIRMED');

-- 10. Bảng donViSanPham
INSERT INTO donViSanPham
    (id, maDonVi, serialNumber, loThuocId, banLeId, trangThai, ngayTao, ngayBan)
VALUES
    (1, 'AK-PARA-2026-001-000001', 'AK26PARA00000001', 1, 1, 'DISPENSED', '2026-10-02 08:30:00', '2026-10-05 16:30:00'),
    (2, 'AK-PARA-2026-001-000002', 'AK26PARA00000002', 1, 1, 'DISPENSED', '2026-10-02 08:30:00', '2026-10-05 16:30:00'),
    (3, 'AK-PARA-2026-001-000003', 'AK26PARA00000003', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL),
    (27, 'DEMO-EXP-2026-B001-0001', 'SN000000000001', 3, 2, 'DISPENSED', '2026-09-01 08:00:00', '2026-10-07 14:00:00'),
    (28, 'DEMO-EXP-2026-B001-0002', 'SN000000000002', 3, 2, 'DISPENSED', '2026-09-01 08:00:00', '2026-10-07 14:00:00'),
    (43, 'DEMO-EXP-2026-B002-0001', 'SN000000000017', 4, 3, 'DISPENSED', '2026-09-02 08:00:00', '2026-10-08 14:00:00');

-- 11. Bảng qrCode
INSERT INTO qrCode
    (id, giaTriQR, loaiQR, loThuocId, donViSanPhamId, ngayTao, trangThai)
VALUES
    (1, 'QR-BATCH-AK-PARA-2026-001', 'BATCH', 1, NULL, '2026-10-02 08:31:00', 'ACTIVE'),
    (2, 'QR-UNIT-AK26PARA00000001', 'UNIT', NULL, 1, '2026-10-02 08:31:00', 'ACTIVE'),
    (3, 'QR-UNIT-AK26PARA00000002', 'UNIT', NULL, 2, '2026-10-02 08:31:00', 'ACTIVE'),
    (4, 'QR-UNIT-DEMO-EXP-2026-B001-0001', 'UNIT', NULL, 27, '2026-09-01 08:30:00', 'ACTIVE'),
    (5, 'QR-UNIT-DEMO-EXP-2026-B002-0001', 'UNIT', NULL, 43, '2026-09-01 08:30:00', 'ACTIVE');

-- 12. Bảng tonKhoLo (Khớp chính xác từng con số theo lượng chuyển giao & bán lẻ)
INSERT INTO tonKhoLo (id, loThuocId, toChucId, soLuong, ngayCapNhat)
VALUES
    (1, 1, 1, 8, '2026-10-05 10:00:00'),  -- NSX An Khang còn 8 (20 tạo - 10 chuyển + 0 bán - 2 chờ)
    (2, 1, 2, 5, '2026-10-04 11:00:00'),  -- NPP Minh Châu còn 5 (10 nhận - 5 chuyển)
    (3, 1, 3, 3, '2026-10-05 16:30:00'),  -- Nhà thuốc Tận Tâm còn 3 (5 nhận - 2 đã bán)
    (4, 2, 1, 6, '2026-10-05 17:00:00'),  -- Lô 2 bị thu hồi tại nhà sản xuất
    (5, 3, 6, 8, '2026-10-08 10:00:00'),  -- NSX Sông Hàn còn 8 (16 tạo - 8 chuyển)
    (6, 3, 8, 6, '2026-10-08 10:00:00'),  -- NPP Miền Tây còn 6 (8 nhận - 2 chuyển)
    (7, 3, 9, 0, '2026-10-08 10:00:00'),  -- Nhà thuốc Hoa Sen còn 0 (2 nhận - 2 đã bán)
    (8, 4, 6, 5, '2026-10-08 10:00:00'),  -- NSX Sông Hàn còn 5 (10 tạo - 5 chuyển đang PENDING)
    (9, 4, 9, 0, '2026-10-08 10:00:00'),  -- Nhà thuốc Hoa Sen còn 0 (1 nhận - 1 đã bán)
    (10, 5, 6, 8, '2026-10-08 10:00:00'); -- Lô 5 bị thu hồi tại NSX Sông Hàn (đã sửa từ Tổ chức 7 về 6)

-- 13. Bảng chuyenGiao (Quy về 2 trạng thái PENDING và RECEIVED)
INSERT INTO chuyenGiao
    (id, maChuyenGiao, loThuocId, benGuiId, benNhanId, soLuong, thoiGianKhoiTao, thoiGianXacNhan, trangThai)
VALUES
    (1, 'dddddddd-dddd-4ddd-8ddd-dddddddddddd', 1, 1, 2, 10, '2026-10-02 10:00:00', '2026-10-03 10:00:00', 'RECEIVED'),
    (2, 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', 1, 2, 3, 5, '2026-10-03 11:00:00', '2026-10-04 11:00:00', 'RECEIVED'),
    (3, 'ffffffff-ffff-4fff-8fff-ffffffffffff', 1, 1, 2, 2, '2026-10-05 17:00:00', NULL, 'PENDING'),
    (4, '42d4abbd-0314-4f7c-a0ba-7bfc1a2f9663', 3, 6, 8, 8, '2026-09-03 10:00:00', '2026-09-04 10:00:00', 'RECEIVED'),
    (5, 'c821c892-24d4-438e-b2d7-77db36ce187a', 3, 8, 9, 2, '2026-09-05 11:00:00', '2026-09-06 10:00:00', 'RECEIVED'),
    (6, 'b7c773bd-b2cd-4c46-9742-ea4c529a2d4a', 4, 6, 8, 5, '2026-09-06 12:00:00', NULL, 'PENDING'),
    (7, 'd980c7c3-bcbc-4313-b417-372c2e46fc56', 4, 8, 9, 1, '2026-09-07 13:00:00', '2026-09-07 15:00:00', 'RECEIVED');

-- 14. Bảng baoCaoNghiVan (Chuẩn hóa Enum trạng thái & bổ sung báo cáo hàng giả)
INSERT INTO baoCaoNghiVan
    (id, maBaoCao, loThuocId, maLoKhaiBao, taiKhoanId, lyDo, moTa, ngayGui, trangThai)
VALUES
    (1, 'BC-DEMO-2026-0001', 2, 'AK-AMOX-2026-002', NULL, 'Nghi ngo chat luong lo thuoc', 'Bao cao an danh: phat hien vien thuoc co mau sac khong dong nhat.', '2026-10-05 08:00:00', 'DA_XU_LY'),
    (2, 'BC-DEMO-2026-0002', 1, 'AK-PARA-2026-001', 5, 'Niem phong thung hang co dau hieu bi rach', 'Bao cao cua nha thuoc, dang cho co quan quan ly xem xet.', '2026-10-06 09:00:00', 'DA_GUI'),
    (3, 'BC-DEMO-EXP-2026-0001', 5, 'DEMO-EXP-2026-B003', 10, 'Nghi ngo bao quan lo thuoc khong dung dieu kien.', 'Du lieu minh hoa.', '2026-09-08 10:00:00', 'DANG_XU_LY'),
    (4, 'BC-DEMO-EXP-2026-0002', 5, 'DEMO-EXP-2026-B003', 10, 'Phat hien sai lech trong ho so lo thuoc.', 'Du lieu minh hoa.', '2026-09-09 11:00:00', 'DA_XU_LY'),
    (5, 'BC-DEMO-FAKE-0001', NULL, 'LOT-FAKE-9999', NULL, 'Thuoc gia co ma lo khong ton tai', 'Khach hang quet QR phat hien ma lo khong co tren he thong.', '2026-10-10 14:00:00', 'DA_XU_LY');

-- 15. Bảng minhChungBaoCao
INSERT INTO minhChungBaoCao (id, baoCaoId, tenFile, loaiFile, duongDan, ngayTaiLen)
VALUES
    (1, 1, 'anh-mau-vien-thuoc-demo.jpg', 'image/jpeg', 'demo/bao-cao/BC-DEMO-2026-0001/anh-mau.jpg', '2026-10-05 08:00:00'),
    (2, 2, 'anh-thung-hang-demo.jpg', 'image/jpeg', 'demo/bao-cao/BC-DEMO-2026-0002/anh-thung.jpg', '2026-10-06 09:00:00'),
    (3, 4, 'minh-chung-demo-2.jpg', 'image/jpeg', 'demo/mo-rong/bao-cao/BC-DEMO-EXP-2026-0002/minh-chung.jpg', '2026-09-09 11:00:00');

-- 16. Bảng ketQuaXuLyBaoCao (Phân rõ vai trò xử lý của Cơ quan quản lý)
INSERT INTO ketQuaXuLyBaoCao
    (id, baoCaoId, coQuanXuLyId, quyetDinh, lyDoXuLy, ghiChu, ngayXuLy)
VALUES
    (1, 1, 2, 'THU_HOI', 'Ket qua kiem tra mau khong dat yeu cau.', 'Xu ly boi CQQL Da Nang', '2026-10-05 15:00:00'),
    (2, 4, 11, 'THU_HOI', 'Can cach ly lo hang de kiem tra.', 'Xu ly boi CQQL Can Tho', '2026-09-12 16:00:00'),
    (3, 5, 2, 'GHI_NHAN', 'Ghi nhan thong tin thuoc gia ngoai he thong', 'Chuyen ho so sang ben cong an', '2026-10-10 16:00:00');

-- 17. Bảng thuHoiLoHang (Khôi phục coQuanQuyetDinhId)
INSERT INTO thuHoiLoHang
    (id, loThuocId, coQuanQuyetDinhId, ketQuaXuLyBaoCaoId, lyDo, ngayThuHoi)
VALUES
    (1, 2, 2, 1, 'Thu hoi demo theo ket qua xu ly BC-DEMO-2026-0001.', '2026-10-05 17:00:00'),
    (2, 5, 11, 2, 'Thu hoi minh hoa theo ket qua xu ly bao cao demo.', '2026-09-12 17:00:00');

-- 18. Bảng blockchainIdentity
INSERT INTO blockchainIdentity
    (id, toChucId, network, address, certificateId, trangThai)
VALUES
    (1, 1, 'Polygon Testnet', '0x1111111111111111111111111111111111111111', 'DEMO-CERT-NSX-001', 'ACTIVE'),
    (2, 2, 'Polygon Testnet', '0x2222222222222222222222222222222222222222', 'DEMO-CERT-NPP-001', 'ACTIVE'),
    (3, 3, 'Polygon Testnet', '0x3333333333333333333333333333333333333333', 'DEMO-CERT-NT-001', 'ACTIVE'),
    (4, 4, 'Polygon Testnet', '0x4444444444444444444444444444444444444444', 'DEMO-CERT-CQ-001', 'ACTIVE'),
    (5, 6, 'Polygon Testnet', '0xc78c8a47d7fabc536420bec3b7ed0bc95598c201', 'DEMO-CERT-EXP-001', 'ACTIVE'),
    (6, 7, 'Polygon Testnet', '0xcaf40b8269540029e17267490dbf7690dd6ac41f', 'DEMO-CERT-EXP-002', 'ACTIVE'),
    (7, 8, 'Polygon Testnet', '0xe4805774a21fe5a653d7ddb2aa59483a949a81f9', 'DEMO-CERT-EXP-003', 'ACTIVE'),
    (8, 9, 'Polygon Testnet', '0x1e60991f6b1f4b0594227d9a8bbe36b84d1856ba', 'DEMO-CERT-EXP-004', 'ACTIVE'),
    (9, 10, 'Polygon Testnet', '0xb840087a6457de1ef6a838b26ffdabcfef5c069f', 'DEMO-CERT-EXP-005', 'ACTIVE');

-- 19. Bảng blockchainTransaction (Nguồn tham chiếu duy nhất duy trì toàn bộ Mã giao dịch TxHash)
INSERT INTO blockchainTransaction
    (id, txHash, blockNumber, eventName, businessId, thoiGian, network, trangThai, toChucId, sanPhamId, loThuocId, chuyenGiaoId, donViSanPhamId, banLeId)
VALUES
    (1, '0x1111111111111111111111111111111111111111111111111111111111111111', 100001, 'BatchRegistered', 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', '2026-10-02 08:30:00', 'Polygon Testnet', 'CONFIRMED', 1, 1, 1, NULL, NULL, NULL),
    (2, '0x2222222222222222222222222222222222222222222222222222222222222222', 100120, 'TransferReceived', 'dddddddd-dddd-4ddd-8ddd-dddddddddddd', '2026-10-03 10:00:00', 'Polygon Testnet', 'CONFIRMED', 2, NULL, 1, 1, NULL, NULL),
    (3, '0x3333333333333333333333333333333333333333333333333333333333333333', 100180, 'TransferReceived', 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', '2026-10-04 11:00:00', 'Polygon Testnet', 'CONFIRMED', 3, NULL, 1, 2, NULL, NULL),
    (4, '0x4444444444444444444444444444444444444444444444444444444444444444', 100240, 'BatchRecalled', 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', '2026-10-05 17:00:00', 'Polygon Testnet', 'CONFIRMED', 4, NULL, 2, NULL, NULL, NULL),
    (5, '0x5555555555555555555555555555555555555555555555555555555555555555', 100230, 'RetailSale', 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', '2026-10-05 16:30:00', 'Polygon Testnet', 'CONFIRMED', 3, NULL, 1, NULL, 1, 1),
    (6, '0x6666666666666666666666666666666666666666666666666666666666666666', NULL, 'TransferCreated', 'ffffffff-ffff-4fff-8fff-ffffffffffff', '2026-10-05 17:00:00', 'Polygon Testnet', 'PENDING', 1, NULL, 1, 3, NULL, NULL),
    (7, '0x7777777777777777777777777777777777777777777777777777777777777777', 100002, 'BatchRegistered', 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', '2026-10-02 09:00:00', 'Polygon Testnet', 'CONFIRMED', 1, 2, 2, NULL, NULL, NULL),
    (8, '0xfea5fa4b0e463c934a4406806b1ff4daf32ca0d16eca6772c9af65d910d5c4d5', 101000, 'BatchRegistered', '264cdb4f-7a26-4390-a18d-b57bfbb4dafb', '2026-09-01 08:30:00', 'Polygon Testnet', 'CONFIRMED', 1, 54, 3, NULL, NULL, NULL),
    (9, '0x171a31057f6de2f2aa1d9f31eae2b3b0be6209aa4f4a83fd2058604ab28a923b', 101010, 'BatchRegistered', 'e060159a-c060-4a7f-837c-dc509fc4acac', '2026-09-02 08:30:00', 'Polygon Testnet', 'CONFIRMED', 6, 89, 4, NULL, NULL, NULL),
    (10, '0xf3e6c6ec64554a99d2d9f059cb884c56b8f14b11259f55def09e1b8897bee83c', 101020, 'BatchRegistered', 'bf546894-2af5-476e-93a0-9f96183a2b11', '2026-09-03 08:30:00', 'Polygon Testnet', 'CONFIRMED', 6, 90, 5, NULL, NULL, NULL),
    (11, '0x5868d077f280a0333b520b3f74ac83cc95c0ca4ce8537efcf854dfd26853504b', 101100, 'TransferReceived', '42d4abbd-0314-4f7c-a0ba-7bfc1a2f9663', '2026-09-04 10:00:00', 'Polygon Testnet', 'CONFIRMED', 8, NULL, 3, 4, NULL, NULL),
    (12, '0x10070959ce4113c739013aa8aff26bb9ed167a9f26d9d2ec61a485d7a7b5e017', 101101, 'TransferReceived', 'c821c892-24d4-438e-b2d7-77db36ce187a', '2026-09-06 10:00:00', 'Polygon Testnet', 'CONFIRMED', 9, NULL, 3, 5, NULL, NULL),
    (13, '0x311b72917b5bc0a11b0fdd821511f986c452375056d68c228a6a52c66a8a5c79', 101200, 'RetailSale', 'e12144ac-ec46-4496-a688-6e8c4ddc8d21', '2026-10-07 14:00:00', 'Polygon Testnet', 'CONFIRMED', 9, NULL, 3, NULL, 27, 2),
    (14, '0x2b4894999a5371ed92e0ad7b9b63cc317dc897c9df3959fb230c309edf5f0323', 101201, 'RetailSale', '457df10c-396d-4195-b5bc-86c2af9a7448', '2026-10-08 14:00:00', 'Polygon Testnet', 'CONFIRMED', 9, NULL, 4, NULL, 43, 3),
    (15, '0xf2b4d7ad4883fe0395cdd284015c1455bd040aa3546cd8e285dec34806db34b0', 101300, 'BatchRecalled', 'bf546894-2af5-476e-93a0-9f96183a2b11', '2026-09-12 17:00:00', 'Polygon Testnet', 'CONFIRMED', 10, NULL, 5, NULL, NULL, NULL);

COMMIT;