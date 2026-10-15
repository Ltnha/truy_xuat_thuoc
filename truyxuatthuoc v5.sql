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
    trangThai ENUM('PENDING','ACTIVE','LOCKED') NOT NULL DEFAULT 'PENDING',
    toChucId BIGINT NULL,
    ngayTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (toChucId) REFERENCES toChuc(id)
);

-- 3. Bảng yeuCauDangKy (Lưu lịch sử & Snapshot thông tin từng lần nộp)
CREATE TABLE yeuCauDangKy (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    toChucId BIGINT NOT NULL,
    tenToChuc VARCHAR(255) NULL,
    loaiToChuc ENUM('NHA_SAN_XUAT','NHA_PHAN_PHOI','NHA_THUOC','CO_QUAN_QUAN_LY') NULL,
    diaChi VARCHAR(255) NULL,
    soDienThoai VARCHAR(20) NULL,
    maSoThue VARCHAR(20) NULL,
    soGiayPhepDuoc VARCHAR(50) NULL,
    coQuanCap VARCHAR(255) NULL,
    ngayCapGiayPhep DATE NULL,
    ngayHetHanGiayPhep DATE NULL,
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
    soDangKy VARCHAR(50) NOT NULL,
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
    maBaoCao VARCHAR(255) NOT NULL UNIQUE,
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
    loThuocId BIGINT NOT NULL UNIQUE,
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
    toChucId BIGINT NOT NULL,
    khoaRiengMaHoa VARCHAR(255) NOT NULL,
    network VARCHAR(50) NOT NULL DEFAULT 'sepolia Testnet',
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
    doiTuongToChucId BIGINT NULL,
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
CREATE INDEX idx_lothuoc_trangthai ON loThuoc(trangThai);
CREATE INDEX idx_chuyengiao_trangthai ON chuyenGiao(trangThai);
CREATE INDEX idx_donvi_trangthai ON donViSanPham(trangThai);
CREATE INDEX idx_baocao_trangthai ON baoCaoNghiVan(trangThai);
CREATE INDEX idx_yeucaudk_trangthai ON yeuCauDangKy(trangThai);
CREATE INDEX idx_yeucausp_trangthai ON yeuCauDangKySanPham(trangThai);
CREATE INDEX idx_nhatky_ketqua ON nhatKyHeThong(ketQua);
CREATE INDEX idx_bctx_eventname ON blockchainTransaction(eventName);
-- ============================================================================
-- DỮ LIỆU MẪU KIỂM THỦ (DEMO DATA) CHO HỆ THỐNG TRUY XUẤT THUỐC V3.2
-- ============================================================================

-- ============================================================================
-- CHUYỂN ĐỔI TOÀN BỘ DỮ LIỆU INSERT TỪ V3.1 SANG CẤU TRÚC V4 (v3.2)
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

-- 2. Bảng taiKhoan (Đã lược bỏ soDienThoai theo v4)
INSERT INTO taiKhoan
    (id, tenDangNhap, matKhau, email, vaiTro, trangThai, toChucId, ngayTao)
VALUES
    (1, 'admin.demo', '$2y$10$Zi0DJPfM42Uslc39G53WWuVLqd1L2sOOzGTsUWAsC/tpvKFiwje6q', 'admin.demo@example.test', 'QUAN_TRI_VIEN', 'ACTIVE', NULL, '2026-10-01 08:00:00'),
    (2, 'coquan.demo', '$2y$10$Zi0DJPfM42Uslc39G53WWuVLqd1L2sOOzGTsUWAsC/tpvKFiwje6q', 'coquan.demo@example.test', 'CO_QUAN_QUAN_LY', 'ACTIVE', 4, '2026-10-01 08:05:00'),
    (3, 'nsx.demo', '$2y$10$Zi0DJPfM42Uslc39G53WWuVLqd1L2sOOzGTsUWAsC/tpvKFiwje6q', 'nsx.demo@example.test', 'NHA_SAN_XUAT', 'ACTIVE', 1, '2026-10-01 08:10:00'),
    (4, 'npp.demo', '$2y$10$Zi0DJPfM42Uslc39G53WWuVLqd1L2sOOzGTsUWAsC/tpvKFiwje6q', 'npp.demo@example.test', 'NHA_PHAN_PHOI', 'ACTIVE', 2, '2026-10-01 08:15:00'),
    (5, 'nhathuoc.demo', '$2y$10$Zi0DJPfM42Uslc39G53WWuVLqd1L2sOOzGTsUWAsC/tpvKFiwje6q', 'nhathuoc.demo@example.test', 'NHA_THUOC', 'ACTIVE', 3, '2026-10-01 08:20:00'),
    (6, 'nsx.choduyet.demo', '$2y$10$Zi0DJPfM42Uslc39G53WWuVLqd1L2sOOzGTsUWAsC/tpvKFiwje6q', 'nsx.choduyet.demo@example.test', 'NHA_SAN_XUAT', 'PENDING', 5, '2026-10-05 09:00:00'),
    (7, 'songhan.demo', '$2y$10$Zi0DJPfM42Uslc39G53WWuVLqd1L2sOOzGTsUWAsC/tpvKFiwje6q', 'songhan.demo@example.test', 'NHA_SAN_XUAT', 'ACTIVE', 6, '2026-10-01 09:00:00'),
    (8, 'caonguyen.demo', '$2y$10$Zi0DJPfM42Uslc39G53WWuVLqd1L2sOOzGTsUWAsC/tpvKFiwje6q', 'caonguyen.demo@example.test', 'NHA_SAN_XUAT', 'ACTIVE', 7, '2026-10-02 09:00:00'),
    (9, 'mientay.demo', '$2y$10$Zi0DJPfM42Uslc39G53WWuVLqd1L2sOOzGTsUWAsC/tpvKFiwje6q', 'mientay.demo@example.test', 'NHA_PHAN_PHOI', 'ACTIVE', 8, '2026-10-03 09:00:00'),
    (10, 'hoasen.demo', '$2y$10$Zi0DJPfM42Uslc39G53WWuVLqd1L2sOOzGTsUWAsC/tpvKFiwje6q', 'hoasen.demo@example.test', 'NHA_THUOC', 'ACTIVE', 9, '2026-10-04 09:00:00'),
    (11, 'cantho.demo', '$2y$10$Zi0DJPfM42Uslc39G53WWuVLqd1L2sOOzGTsUWAsC/tpvKFiwje6q', 'cantho.demo@example.test', 'CO_QUAN_QUAN_LY', 'PENDING', 10, '2026-10-05 09:00:00'),
    (12, 'admin.mo-rong.demo', '$2y$12$6/7/OIsgR39uGvQyD.2Oq.76cM8Jq3xN4A/8fP7mD2z1L3uX4kK1C', 'admin.mo-rong.demo@example.test', 'QUAN_TRI_VIEN', 'ACTIVE', NULL, '2026-10-06 09:00:00');

-- 3. Bảng nhatKyHeThong
INSERT INTO nhatKyHeThong (id, taiKhoanId, doiTuongId, hanhDong, ketQua, thoiGian, chiTiet)
VALUES
    (1, 1, NULL, 'TAO_DU_LIEU_DEMO', 'THANH_CONG', '2026-10-01 08:30:00', 'Khoi tao bo du lieu demo cho kiem thu local.'),
    (2, 2, 3, 'PHE_DUYET_TO_CHUC', 'THANH_CONG', '2026-10-01 09:00:00', 'Phe duyet ho so cac to chuc demo.'),
    (3, 3, NULL, 'DANG_KY_LO', 'THANH_CONG', '2026-10-02 08:30:00', 'Dang ky lo demo AK-PARA-2026-001.'),
    (4, 4, NULL, 'XAC_NHAN_CHUYEN_GIAO', 'THANH_CONG', '2026-10-03 10:00:00', 'Nhan mot phan lo AK-PARA-2026-001.'),
    (5, 5, NULL, 'GHI_NHAN_BAN_LE', 'THANH_CONG', '2026-10-05 16:30:00', 'Ban 2 don vi thuoc demo.'),
    (6, 7, NULL, 'TAO_TAI_KHOAN_DEMO', 'THANH_CONG', '2026-10-01 09:30:00', 'Tao tai khoan mo rong du lieu demo.'),
    (7, 8, NULL, 'TAO_TAI_KHOAN_DEMO', 'THANH_CONG', '2026-10-02 09:30:00', 'Tao tai khoan mo rong du lieu demo.'),
    (8, 9, NULL, 'TAO_TAI_KHOAN_DEMO', 'THANH_CONG', '2026-10-03 09:30:00', 'Tao tai khoan mo rong du lieu demo.'),
    (9, 10, NULL, 'TAO_TAI_KHOAN_DEMO', 'THANH_CONG', '2026-10-04 09:30:00', 'Tao tai khoan mo rong du lieu demo.'),
    (10, 11, NULL, 'TAO_TAI_KHOAN_DEMO', 'THANH_CONG', '2026-10-05 09:30:00', 'Tao tai khoan mo rong du lieu demo.'),
    (11, 12, NULL, 'TAO_TAI_KHOAN_DEMO', 'THANH_CONG', '2026-10-06 09:30:00', 'Tao tai khoan mo rong du lieu demo.');

-- 4. Bảng yeuCauDangKy
-- 3. Bảng yeuCauDangKy (Lưu lịch sử & Snapshot thông tin từng lần nộp)
INSERT INTO yeuCauDangKy
    (id, toChucId, tenToChuc, loaiToChuc, diaChi, soDienThoai, maSoThue, soGiayPhepDuoc, coQuanCap, ngayCapGiayPhep, ngayHetHanGiayPhep, versionHoSo, ngayGui, trangThai, nguoiDuyetId, ngayXuLy, lyDoTuChoi)
VALUES
    (1, 1, 'Cong ty Co phan Duoc pham An Khang (DEMO)', 'NHA_SAN_XUAT', 'Khu cong nghiep Hoa Khanh, Da Nang', '02363888001', '0402190001', 'GP-N1-2025-001', 'Bo Y te', '2025-01-15', '2030-01-14', 1, '2026-09-01 00:00:00', 'DA_DUYET', 2, '2026-09-05 00:00:00', NULL),
    (2, 2, 'Cong ty TNHH Phan phoi Minh Chau (DEMO)', 'NHA_PHAN_PHOI', 'Quan Binh Thanh, TP Ho Chi Minh', '02838880002', '0318290002', 'GP-PP-2025-002', 'So Y te TP Ho Chi Minh', '2025-02-10', '2030-02-09', 1, '2026-09-02 00:00:00', 'DA_DUYET', 2, '2026-09-06 00:00:00', NULL),
    (3, 3, 'Nha thuoc Tan Tam (DEMO)', 'NHA_THUOC', 'Quan Hai Chau, Da Nang', '02363888003', '0402190003', 'GP-NT-2025-003', 'So Y te Da Nang', '2025-03-01', '2030-02-28', 1, '2026-09-03 00:00:00', 'DA_DUYET', 2, '2026-09-07 00:00:00', NULL),
    (4, 4, 'Co quan Quan ly Duoc Da Nang (DEMO)', 'CO_QUAN_QUAN_LY', 'Quan Hai Chau, Da Nang', '02363888004', '0402190004', NULL, 'Bo Y te', NULL, NULL, 1, '2026-09-01 00:00:00', 'DA_DUYET', 1, '2026-09-04 00:00:00', NULL),
    (5, 5, 'Cong ty Duoc Hoa Binh (DEMO - cho duyet)', 'NHA_SAN_XUAT', 'Thanh pho Hue', '02343888005', '3302190005', 'GP-N1-2026-005', 'So Y te Thua Thien Hue', '2026-05-10', '2031-05-09', 1, '2026-10-05 00:00:00', 'CHO_DUYET', NULL, NULL, NULL),
    (6, 6, 'Duoc pham Song Han (DEMO)', 'NHA_SAN_XUAT', 'Da Nang', '02363900101', 'DEMO-EXP-2026-01', 'GP-DEMO-NSX-101', 'So Y te Da Nang', '2025-01-10', '2030-01-09', 1, '2026-10-01 00:00:00', 'DA_DUYET', 2, '2026-10-03 00:00:00', NULL),
    (7, 7, 'Duoc pham Cao Nguyen (DEMO)', 'NHA_SAN_XUAT', 'Dak Lak', '02623900102', 'DEMO-EXP-2026-02', 'GP-DEMO-NSX-102', 'So Y te Dak Lak', '2025-02-10', '2030-02-09', 1, '2026-10-02 00:00:00', 'DA_DUYET', 2, '2026-10-04 00:00:00', NULL),
    (8, 8, 'Phan phoi Duoc Mien Tay (DEMO)', 'NHA_PHAN_PHOI', 'Can Tho', '02923900103', 'DEMO-EXP-2026-03', 'GP-DEMO-PP-103', 'So Y te Can Tho', '2025-03-10', '2030-03-09', 1, '2026-10-03 00:00:00', 'DA_DUYET', 2, '2026-10-05 00:00:00', NULL),
    (9, 9, 'Nha thuoc Hoa Sen (DEMO)', 'NHA_THUOC', 'Can Tho', '02923900104', 'DEMO-EXP-2026-04', 'GP-DEMO-NT-104', 'So Y te Can Tho', '2025-04-10', '2030-04-09', 1, '2026-10-04 00:00:00', 'DA_DUYET', 2, '2026-10-06 00:00:00', NULL),
    (10, 10, 'Co quan Quan ly Duoc Can Tho (DEMO)', 'CO_QUAN_QUAN_LY', 'Can Tho', '02923900105', 'DEMO-EXP-2026-05', NULL, 'Bo Y te', NULL, NULL, 1, '2026-10-05 00:00:00', 'CHO_DUYET', NULL, NULL, NULL);

-- 5. Bảng taiLieuDangKy
INSERT INTO taiLieuDangKy (id, yeuCauDangKyId, tenTaiLieu, loaiTaiLieu, duongDan, ngayTaiLen)
VALUES
    (1, 1, 'Giay chung nhan du dieu kien kinh doanh duoc', 'GIAY_PHEP', 'demo/ho-so/an-khang/giay-phep.pdf', '2026-09-01 00:00:00'),
    (2, 1, 'Giay chung nhan GMP', 'GMP', 'demo/ho-so/an-khang/gmp.pdf', '2026-09-01 00:00:00'),
    (3, 2, 'Giay chung nhan du dieu kien phan phoi', 'GIAY_PHEP', 'demo/ho-so/minh-chau/giay-phep.pdf', '2026-09-02 00:00:00'),
    (4, 3, 'Giay chung nhan du dieu kien nha thuoc', 'GIAY_PHEP', 'demo/ho-so/tan-tam/giay-phep.pdf', '2026-09-03 00:00:00'),
    (5, 5, 'Ho so dang ky doanh nghiep', 'HO_SO_DOANH_NGHIEP', 'demo/ho-so/hoa-binh/dang-ky.pdf', '2026-10-05 00:00:00'),
    (6, 6, 'Ho so doanh nghiep demo 1', 'GIAY_PHEP', 'demo/mo-rong/to-chuc-1/ho-so.pdf', '2026-10-01 00:00:00'),
    (7, 7, 'Ho so doanh nghiep demo 2', 'HO_SO_DOANH_NGHIEP', 'demo/mo-rong/to-chuc-2/ho-so.pdf', '2026-10-02 00:00:00'),
    (8, 8, 'Ho so doanh nghiep demo 3', 'HO_SO_DOANH_NGHIEP', 'demo/mo-rong/to-chuc-3/ho-so.pdf', '2026-10-03 00:00:00'),
    (9, 9, 'Ho so doanh nghiep demo 4', 'HO_SO_DOANH_NGHIEP', 'demo/mo-rong/to-chuc-4/ho-so.pdf', '2026-10-04 00:00:00'),
    (10, 10, 'Ho so doanh nghiep demo 5', 'HO_SO_DOANH_NGHIEP', 'demo/mo-rong/to-chuc-5/ho-so.pdf', '2026-10-05 00:00:00');

-- 6. Bảng sanPham (Đã chuyển đổi hoatChat sang thanhPhan, hamLuong, dangBaoChe)
INSERT INTO sanPham
    (id, maSanPham, tenSanPham, soDangKy, thanhPhan, hamLuong, dangBaoChe, quyCachDongGoi, nhaSanXuatId, trangThaiDuyet)
VALUES
    (1, '11111111-1111-4111-8111-111111111111', 'Paracetamol An Khang 500 mg (DEMO)', 'VD-DEMO-2026-001', 'Paracetamol', '500 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, 'DA_DUYET'),
    (2, '22222222-2222-4222-8222-222222222222', 'Amoxicillin An Khang 500 mg (DEMO)', 'VD-DEMO-2026-002', 'Amoxicillin', '500 mg', 'Vien nang', 'Hop 10 vi x 10 vien', 1, 'DA_DUYET'),
    (3, '33333333-3333-4333-8333-333333333333', 'Vitamin C Hoa Binh 500 mg (DEMO)', 'VD-DEMO-2026-003', 'Acid ascorbic', '500 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 5, 'CHO_DUYET'),
    (4, '40000000-0000-4000-8000-000000000004', 'Loratadine An Khang 10 mg (DEMO)', 'VD-DEMO-2026-004', 'Loratadine', '10 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, 'DA_DUYET'),
    (5, '40000000-0000-4000-8000-000000000005', 'Cetirizine An Khang 10 mg (DEMO)', 'VD-DEMO-2026-005', 'Cetirizine dihydrochloride', '10 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, 'DA_DUYET'),
    (6, '40000000-0000-4000-8000-000000000006', 'Omeprazole An Khang 20 mg (DEMO)', 'VD-DEMO-2026-006', 'Omeprazole', '20 mg', 'Vien nang', 'Hop 3 vi x 10 vien nang', 1, 'DA_DUYET'),
    (7, '40000000-0000-4000-8000-000000000007', 'Esomeprazole An Khang 20 mg (DEMO)', 'VD-DEMO-2026-007', 'Esomeprazole', '20 mg', 'Vien nang', 'Hop 3 vi x 10 vien nang', 1, 'DA_DUYET'),
    (8, '40000000-0000-4000-8000-000000000008', 'Metformin An Khang 500 mg (DEMO)', 'VD-DEMO-2026-008', 'Metformin hydrochloride', '500 mg', 'Vien nen', 'Hop 6 vi x 10 vien', 1, 'DA_DUYET'),
    (9, '40000000-0000-4000-8000-000000000009', 'Amlodipine An Khang 5 mg (DEMO)', 'VD-DEMO-2026-009', 'Amlodipine', '5 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, 'DA_DUYET'),
    (10, '40000000-0000-4000-8000-000000000010', 'Losartan An Khang 50 mg (DEMO)', 'VD-DEMO-2026-010', 'Losartan potassium', '50 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, 'DA_DUYET'),
    (11, '40000000-0000-4000-8000-000000000011', 'Atorvastatin An Khang 10 mg (DEMO)', 'VD-DEMO-2026-011', 'Atorvastatin calcium', '10 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, 'DA_DUYET'),
    (12, '40000000-0000-4000-8000-000000000012', 'Simvastatin An Khang 10 mg (DEMO)', 'VD-DEMO-2026-012', 'Simvastatin', '10 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, 'DA_DUYET'),
    (13, '40000000-0000-4000-8000-000000000013', 'Captopril An Khang 25 mg (DEMO)', 'VD-DEMO-2026-013', 'Captopril', '25 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, 'DA_DUYET'),
    (14, '40000000-0000-4000-8000-000000000014', 'Enalapril An Khang 5 mg (DEMO)', 'VD-DEMO-2026-014', 'Enalapril maleate', '5 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, 'DA_DUYET'),
    (15, '40000000-0000-4000-8000-000000000015', 'Bisoprolol An Khang 2.5 mg (DEMO)', 'VD-DEMO-2026-015', 'Bisoprolol fumarate', '2.5 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, 'DA_DUYET'),
    (16, '40000000-0000-4000-8000-000000000016', 'Hydrochlorothiazide An Khang 25 mg (DEMO)', 'VD-DEMO-2026-016', 'Hydrochlorothiazide', '25 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, 'DA_DUYET'),
    (17, '40000000-0000-4000-8000-000000000017', 'Gliclazide An Khang 30 mg (DEMO)', 'VD-DEMO-2026-017', 'Gliclazide', '30 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, 'DA_DUYET'),
    (18, '40000000-0000-4000-8000-000000000018', 'Glimepiride An Khang 2 mg (DEMO)', 'VD-DEMO-2026-018', 'Glimepiride', '2 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, 'DA_DUYET'),
    (19, '40000000-0000-4000-8000-000000000019', 'Dapagliflozin An Khang 10 mg (DEMO)', 'VD-DEMO-2026-019', 'Dapagliflozin', '10 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, 'DA_DUYET'),
    (20, '40000000-0000-4000-8000-000000000020', 'Aspirin An Khang 81 mg (DEMO)', 'VD-DEMO-2026-020', 'Acetylsalicylic acid', '81 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, 'DA_DUYET'),
    (21, '40000000-0000-4000-8000-000000000021', 'Clopidogrel An Khang 75 mg (DEMO)', 'VD-DEMO-2026-021', 'Clopidogrel', '75 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, 'DA_DUYET'),
    (22, '40000000-0000-4000-8000-000000000022', 'Diclofenac An Khang 50 mg (DEMO)', 'VD-DEMO-2026-022', 'Diclofenac sodium', '50 mg', 'Vien nen', 'Hop 5 vi x 10 vien', 1, 'DA_DUYET'),
    (23, '40000000-0000-4000-8000-000000000023', 'Meloxicam An Khang 7.5 mg (DEMO)', 'VD-DEMO-2026-023', 'Meloxicam', '7.5 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, 'DA_DUYET'),
    (24, '40000000-0000-4000-8000-000000000024', 'Celecoxib An Khang 100 mg (DEMO)', 'VD-DEMO-2026-024', 'Celecoxib', '100 mg', 'Vien nang', 'Hop 3 vi x 10 vien nang', 1, 'DA_DUYET'),
    (25, '40000000-0000-4000-8000-000000000025', 'Dexamethasone An Khang 0.5 mg (DEMO)', 'VD-DEMO-2026-025', 'Dexamethasone', '0.5 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, 'DA_DUYET'),
    (26, '40000000-0000-4000-8000-000000000026', 'Prednisolone An Khang 5 mg (DEMO)', 'VD-DEMO-2026-026', 'Prednisolone', '5 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, 'DA_DUYET'),
    (27, '40000000-0000-4000-8000-000000000027', 'Azithromycin An Khang 250 mg (DEMO)', 'VD-DEMO-2026-027', 'Azithromycin', '250 mg', 'Vien nen', 'Hop 1 vi x 6 vien', 1, 'DA_DUYET'),
    (28, '40000000-0000-4000-8000-000000000028', 'Cefixime An Khang 200 mg (DEMO)', 'VD-DEMO-2026-028', 'Cefixime', '200 mg', 'Vien nen', 'Hop 2 vi x 10 vien', 1, 'DA_DUYET'),
    (29, '40000000-0000-4000-8000-000000000029', 'Cefuroxime An Khang 250 mg (DEMO)', 'VD-DEMO-2026-029', 'Cefuroxime axetil', '250 mg', 'Vien nen', 'Hop 2 vi x 10 vien', 1, 'DA_DUYET'),
    (30, '40000000-0000-4000-8000-000000000030', 'Clarithromycin An Khang 500 mg (DEMO)', 'VD-DEMO-2026-030', 'Clarithromycin', '500 mg', 'Vien nen', 'Hop 2 vi x 7 vien', 1, 'DA_DUYET'),
    (31, '40000000-0000-4000-8000-000000000031', 'Doxycycline An Khang 100 mg (DEMO)', 'VD-DEMO-2026-031', 'Doxycycline hyclate', '100 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, 'DA_DUYET'),
    (32, '40000000-0000-4000-8000-000000000032', 'Metronidazole An Khang 250 mg (DEMO)', 'VD-DEMO-2026-032', 'Metronidazole', '250 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, 'DA_DUYET'),
    (33, '40000000-0000-4000-8000-000000000033', 'Fluconazole An Khang 150 mg (DEMO)', 'VD-DEMO-2026-033', 'Fluconazole', '150 mg', 'Vien nang', 'Hop 1 vi x 1 vien nang', 1, 'DA_DUYET'),
    (34, '40000000-0000-4000-8000-000000000034', 'Acyclovir An Khang 200 mg (DEMO)', 'VD-DEMO-2026-034', 'Acyclovir', '200 mg', 'Vien nen', 'Hop 5 vi x 5 vien', 1, 'DA_DUYET'),
    (35, '40000000-0000-4000-8000-000000000035', 'Ambroxol An Khang 30 mg (DEMO)', 'VD-DEMO-2026-035', 'Ambroxol hydrochloride', '30 mg', 'Vien nen', 'Hop 2 vi x 10 vien', 1, 'DA_DUYET'),
    (36, '40000000-0000-4000-8000-000000000036', 'Acetylcysteine An Khang 200 mg (DEMO)', 'VD-DEMO-2026-036', 'Acetylcysteine', '200 mg', 'Bột uống', 'Hop 30 goi bot', 1, 'DA_DUYET'),
    (37, '40000000-0000-4000-8000-000000000037', 'Montelukast An Khang 10 mg (DEMO)', 'VD-DEMO-2026-037', 'Montelukast', '10 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, 'DA_DUYET'),
    (38, '40000000-0000-4000-8000-000000000038', 'Salbutamol An Khang 2 mg (DEMO)', 'VD-DEMO-2026-038', 'Salbutamol sulfate', '2 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, 'DA_DUYET'),
    (39, '40000000-0000-4000-8000-000000000039', 'Domperidone An Khang 10 mg (DEMO)', 'VD-DEMO-2026-039', 'Domperidone', '10 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, 'DA_DUYET'),
    (40, '40000000-0000-4000-8000-000000000040', 'Ondansetron An Khang 4 mg (DEMO)', 'VD-DEMO-2026-040', 'Ondansetron', '4 mg', 'Vien nen', 'Hop 2 vi x 10 vien', 1, 'DA_DUYET'),
    (41, '40000000-0000-4000-8000-000000000041', 'Loperamide An Khang 2 mg (DEMO)', 'VD-DEMO-2026-041', 'Loperamide hydrochloride', '2 mg', 'Vien nen', 'Hop 2 vi x 10 vien', 1, 'DA_DUYET'),
    (42, '40000000-0000-4000-8000-000000000042', 'Racecadotril An Khang 100 mg (DEMO)', 'VD-DEMO-2026-042', 'Racecadotril', '100 mg', 'Vien nang', 'Hop 2 vi x 10 vien nang', 1, 'DA_DUYET'),
    (43, '40000000-0000-4000-8000-000000000043', 'Ferrous fumarate An Khang 200 mg (DEMO)', 'VD-DEMO-2026-043', 'Ferrous fumarate', '200 mg', 'Vien nen', 'Hop 5 vi x 10 vien', 1, 'DA_DUYET'),
    (44, '40000000-0000-4000-8000-000000000044', 'Folic acid An Khang 5 mg (DEMO)', 'VD-DEMO-2026-044', 'Folic acid', '5 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, 'DA_DUYET'),
    (45, '40000000-0000-4000-8000-000000000045', 'Calcium carbonate An Khang 500 mg (DEMO)', 'VD-DEMO-2026-045', 'Calcium carbonate', '500 mg', 'Vien nen', 'Hop 5 vi x 10 vien', 1, 'DA_DUYET'),
    (46, '40000000-0000-4000-8000-000000000046', 'Zinc gluconate An Khang 10 mg (DEMO)', 'VD-DEMO-2026-046', 'Zinc gluconate', '10 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, 'DA_DUYET'),
    (47, '40000000-0000-4000-8000-000000000047', 'Thiamine An Khang 100 mg (DEMO)', 'VD-DEMO-2026-047', 'Thiamine hydrochloride', '100 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, 'DA_DUYET'),
    (48, '40000000-0000-4000-8000-000000000048', 'Pyridoxine An Khang 50 mg (DEMO)', 'VD-DEMO-2026-048', 'Pyridoxine hydrochloride', '50 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, 'DA_DUYET'),
    (49, '40000000-0000-4000-8000-000000000049', 'Cholecalciferol An Khang 1000 IU (DEMO)', 'VD-DEMO-2026-049', 'Cholecalciferol', '1000 IU', 'Vien nang mềm', 'Hop 3 vi x 10 vien nang mem', 1, 'DA_DUYET'),
    (50, '40000000-0000-4000-8000-000000000050', 'Oral rehydration salts An Khang (DEMO)', 'VD-DEMO-2026-050', 'Natri clorid, kali clorid, natri citrat, glucose', 'Standard', 'Bột uống', 'Hop 20 goi bot pha dung dich', 1, 'DA_DUYET'),
    (51, '40000000-0000-4000-8000-000000000051', 'Cyanocobalamin An Khang 500 mcg (DEMO)', 'VD-DEMO-2026-051', 'Cyanocobalamin', '500 mcg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, 'DA_DUYET'),
    (52, '40000000-0000-4000-8000-000000000052', 'Levofloxacin An Khang 500 mg (DEMO)', 'VD-DEMO-2026-052', 'Levofloxacin', '500 mg', 'Vien nen', 'Hop 1 vi x 10 vien', 1, 'DA_DUYET'),
    (53, '40000000-0000-4000-8000-000000000053', 'Cefuroxime An Khang 500 mg (DEMO)', 'VD-DEMO-2026-053', 'Cefuroxime axetil', '500 mg', 'Vien nen', 'Hop 2 vi x 10 vien', 1, 'DA_DUYET'),
    (54, '37001061-2935-4158-aad4-ebb6bcf1e746', 'Paracetamol 10 mg - Lo demo 001', 'VD-DEMO-EXP-2026-001', 'Paracetamol', '10 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, 'DA_DUYET'),
    (55, 'f58aa3db-ffba-43e9-918e-dae8035d687a', 'Loratadine 20 mg - Lo demo 002', 'VD-DEMO-EXP-2026-002', 'Loratadine', '20 mg', 'Vien nen', 'Hop 5 vi x 10 vien', 1, 'DA_DUYET'),
    (56, '963afc41-f1f6-4c5e-a777-483ca312c38b', 'Cetirizine 25 mg - Lo demo 003', 'VD-DEMO-EXP-2026-003', 'Cetirizine dihydrochloride', '25 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, 'DA_DUYET'),
    (57, '0aa03d69-2163-4af0-8286-b27043080801', 'Omeprazole 50 mg - Lo demo 004', 'VD-DEMO-EXP-2026-004', 'Omeprazole', '50 mg', 'Bột uống', 'Hop 30 goi bot', 1, 'DA_DUYET'),
    (58, '230d8c79-cf59-4f32-a823-47024aef4f75', 'Metformin 100 mg - Lo demo 005', 'VD-DEMO-EXP-2026-005', 'Metformin hydrochloride', '100 mg', 'Vien nang', 'Hop 2 vi x 7 vien nang', 1, 'DA_DUYET'),
    (59, 'bbaef6d5-c6ac-436b-a139-e53fa93bdecc', 'Amlodipine 250 mg - Lo demo 006', 'VD-DEMO-EXP-2026-006', 'Amlodipine besylate', '250 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, 'DA_DUYET'),
    (60, 'c3011813-4ae5-4052-9bf2-353527060c27', 'Losartan 500 mg - Lo demo 007', 'VD-DEMO-EXP-2026-007', 'Losartan potassium', '500 mg', 'Vien nen', 'Hop 5 vi x 10 vien', 1, 'DA_DUYET'),
    (61, '1a21857e-ac93-4ad2-84c7-c46e9af6c7c9', 'Atorvastatin 10 mg - Lo demo 008', 'VD-DEMO-EXP-2026-008', 'Atorvastatin calcium', '10 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, 'DA_DUYET'),
    (62, 'ba3bbefa-b82b-4e04-869a-3db39527e893', 'Captopril 20 mg - Lo demo 009', 'VD-DEMO-EXP-2026-009', 'Captopril', '20 mg', 'Bột uống', 'Hop 30 goi bot', 1, 'DA_DUYET'),
    (63, '6d1e9704-3bca-4ef8-8319-4d832f0575ba', 'Azithromycin 25 mg - Lo demo 010', 'VD-DEMO-EXP-2026-010', 'Azithromycin', '25 mg', 'Vien nang', 'Hop 2 vi x 7 vien nang', 1, 'DA_DUYET'),
    (64, '1c545c33-762e-470e-b681-e1f992f6a61d', 'Cefixime 50 mg - Lo demo 011', 'VD-DEMO-EXP-2026-011', 'Cefixime trihydrate', '50 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, 'DA_DUYET'),
    (65, 'd53ab341-3683-4248-95fd-b56697dfbf59', 'Ambroxol 100 mg - Lo demo 012', 'VD-DEMO-EXP-2026-012', 'Ambroxol hydrochloride', '100 mg', 'Vien nen', 'Hop 5 vi x 10 vien', 1, 'DA_DUYET'),
    (66, '62f99c2a-6cf2-40e7-bc2d-287f92ee0e8b', 'Montelukast 250 mg - Lo demo 013', 'VD-DEMO-EXP-2026-013', 'Montelukast sodium', '250 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, 'DA_DUYET'),
    (67, '8f75b45a-2eaf-4707-8ef2-385e9321e583', 'Domperidone 500 mg - Lo demo 014', 'VD-DEMO-EXP-2026-014', 'Domperidone', '500 mg', 'Bột uống', 'Hop 30 goi bot', 1, 'DA_DUYET'),
    (68, '4ae21696-d8a7-4828-981a-42ebcfa53f9d', 'Folic acid 10 mg - Lo demo 015', 'VD-DEMO-EXP-2026-015', 'Folic acid', '10 mg', 'Vien nang', 'Hop 2 vi x 7 vien nang', 1, 'DA_DUYET'),
    (69, 'c789a505-50a3-4c96-b9e8-f547a9049045', 'Acetylcysteine 20 mg - Lo demo 016', 'VD-DEMO-EXP-2026-016', 'Acetylcysteine', '20 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, 'DA_DUYET'),
    (70, 'a8124fee-a737-4f91-b2ae-b96f667a126f', 'Vitamin C 25 mg - Lo demo 017', 'VD-DEMO-EXP-2026-017', 'Ascorbic acid', '25 mg', 'Vien nen', 'Hop 5 vi x 10 vien', 1, 'DA_DUYET'),
    (71, '0ea3aeaa-c6c3-4f78-9248-7cc7457a4019', 'Doxycycline 50 mg - Lo demo 018', 'VD-DEMO-EXP-2026-018', 'Doxycycline hyclate', '50 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, 'DA_DUYET'),
    (72, '46aab083-fc37-47be-8cd6-b463941b9e63', 'Diclofenac 100 mg - Lo demo 019', 'VD-DEMO-EXP-2026-019', 'Diclofenac sodium', '100 mg', 'Bột uống', 'Hop 30 goi bot', 1, 'DA_DUYET'),
    (73, '32593baa-1892-4159-b405-8bf67a07d8f6', 'Ferrous fumarate 250 mg - Lo demo 020', 'VD-DEMO-EXP-2026-020', 'Ferrous fumarate', '250 mg', 'Vien nang', 'Hop 2 vi x 7 vien nang', 1, 'DA_DUYET'),
    (74, '7f3f670b-eb94-408a-924b-664de6867f66', 'Paracetamol 500 mg - Lo demo 021', 'VD-DEMO-EXP-2026-021', 'Paracetamol', '500 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, 'DA_DUYET'),
    (75, 'b3a6a402-a2fa-4aa3-950f-7f0026e2ac55', 'Loratadine 10 mg - Lo demo 022', 'VD-DEMO-EXP-2026-022', 'Loratadine', '10 mg', 'Vien nen', 'Hop 5 vi x 10 vien', 1, 'DA_DUYET'),
    (76, '607d85fb-900d-4dde-8d6c-5940a10b6cc1', 'Cetirizine 20 mg - Lo demo 023', 'VD-DEMO-EXP-2026-023', 'Cetirizine dihydrochloride', '20 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, 'DA_DUYET'),
    (77, '79168f6d-011b-4af8-adb7-0755b76302a4', 'Omeprazole 25 mg - Lo demo 024', 'VD-DEMO-EXP-2026-024', 'Omeprazole', '25 mg', 'Bột uống', 'Hop 30 goi bot', 1, 'DA_DUYET'),
    (78, 'a67cb442-8d18-472b-aae9-c1c04f88d7f5', 'Metformin 50 mg - Lo demo 025', 'VD-DEMO-EXP-2026-025', 'Metformin hydrochloride', '50 mg', 'Vien nang', 'Hop 2 vi x 7 vien nang', 1, 'DA_DUYET'),
    (79, 'ae48ccb5-2cb4-4858-b33c-47bb33b81f04', 'Amlodipine 100 mg - Lo demo 026', 'VD-DEMO-EXP-2026-026', 'Amlodipine besylate', '100 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, 'DA_DUYET'),
    (80, 'dceb1bdf-d4d5-45a6-ac6e-6270a7044624', 'Losartan 250 mg - Lo demo 027', 'VD-DEMO-EXP-2026-027', 'Losartan potassium', '250 mg', 'Vien nen', 'Hop 5 vi x 10 vien', 1, 'DA_DUYET'),
    (81, '9400aaf0-d7c9-4b9b-a5ed-b8b173bd061d', 'Atorvastatin 500 mg - Lo demo 028', 'VD-DEMO-EXP-2026-028', 'Atorvastatin calcium', '500 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, 'DA_DUYET'),
    (82, '4cd97f6b-359d-4d80-b9bf-0a9d188d2e4a', 'Captopril 10 mg - Lo demo 029', 'VD-DEMO-EXP-2026-029', 'Captopril', '10 mg', 'Bột uống', 'Hop 30 goi bot', 1, 'DA_DUYET'),
    (83, '36481e88-f892-47bb-9247-fa36041096dd', 'Azithromycin 20 mg - Lo demo 030', 'VD-DEMO-EXP-2026-030', 'Azithromycin', '20 mg', 'Vien nang', 'Hop 2 vi x 7 vien nang', 1, 'DA_DUYET'),
    (84, '40c9dc5f-159e-4d5b-83f8-ddd819994502', 'Cefixime 25 mg - Lo demo 031', 'VD-DEMO-EXP-2026-031', 'Cefixime trihydrate', '25 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, 'DA_DUYET'),
    (85, '989f3b64-092b-4d0a-ab74-698ef52f6fe8', 'Ambroxol 50 mg - Lo demo 032', 'VD-DEMO-EXP-2026-032', 'Ambroxol hydrochloride', '50 mg', 'Vien nen', 'Hop 5 vi x 10 vien', 1, 'DA_DUYET'),
    (86, 'a63a1a99-8c2a-47b3-bd48-d3fcbf593ce1', 'Montelukast 100 mg - Lo demo 033', 'VD-DEMO-EXP-2026-033', 'Montelukast sodium', '100 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, 'DA_DUYET'),
    (87, '90d43098-e687-4d33-baf1-6c12f9652476', 'Domperidone 250 mg - Lo demo 034', 'VD-DEMO-EXP-2026-034', 'Domperidone', '250 mg', 'Bột uống', 'Hop 30 goi bot', 1, 'DA_DUYET'),
    (88, 'cde86786-8c38-4f30-a682-140ce53ae6b1', 'Folic acid 500 mg - Lo demo 035', 'VD-DEMO-EXP-2026-035', 'Folic acid', '500 mg', 'Vien nang', 'Hop 2 vi x 7 vien nang', 1, 'DA_DUYET'),
    (89, 'dc8f2acc-185c-409f-afc4-fe3e7f11be87', 'Acetylcysteine 10 mg - Lo demo 036', 'VD-DEMO-EXP-2026-036', 'Acetylcysteine', '10 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 6, 'DA_DUYET'),
    (90, '9dad8268-135e-4810-a359-71be0144a94f', 'Vitamin C 20 mg - Lo demo 037', 'VD-DEMO-EXP-2026-037', 'Ascorbic acid', '20 mg', 'Vien nen', 'Hop 5 vi x 10 vien', 6, 'DA_DUYET'),
    (91, 'd693487c-4f19-4a1f-a35b-0e3a0201049e', 'Doxycycline 25 mg - Lo demo 038', 'VD-DEMO-EXP-2026-038', 'Doxycycline hyclate', '25 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 6, 'DA_DUYET'),
    (92, '9bb8f291-a803-4281-814f-d144b49c8e44', 'Diclofenac 50 mg - Lo demo 039', 'VD-DEMO-EXP-2026-039', 'Diclofenac sodium', '50 mg', 'Bột uống', 'Hop 30 goi bot', 6, 'DA_DUYET'),
    (93, '4986dc30-ac3f-4fb3-ab04-dc7afbfaa405', 'Ferrous fumarate 100 mg - Lo demo 040', 'VD-DEMO-EXP-2026-040', 'Ferrous fumarate', '100 mg', 'Vien nang', 'Hop 2 vi x 7 vien nang', 6, 'DA_DUYET'),
    (94, '129fd8a6-682d-4cb9-b20a-0d3d81dda373', 'Paracetamol 250 mg - Lo demo 041', 'VD-DEMO-EXP-2026-041', 'Paracetamol', '250 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 6, 'DA_DUYET'),
    (95, '0a530876-5bf7-4595-b7d4-01e3a9491850', 'Loratadine 500 mg - Lo demo 042', 'VD-DEMO-EXP-2026-042', 'Loratadine', '500 mg', 'Vien nen', 'Hop 5 vi x 10 vien', 6, 'DA_DUYET'),
    (96, '0e380ce9-fecf-4fea-9f81-a7f1ca673ee1', 'Cetirizine 10 mg - Lo demo 043', 'VD-DEMO-EXP-2026-043', 'Cetirizine dihydrochloride', '10 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 6, 'DA_DUYET'),
    (97, '2f5020ce-1b99-4cca-ac20-654a9b0776e1', 'Omeprazole 20 mg - Lo demo 044', 'VD-DEMO-EXP-2026-044', 'Omeprazole', '20 mg', 'Bột uống', 'Hop 30 goi bot', 6, 'DA_DUYET'),
    (98, 'bcaaa9ec-096c-4878-899e-1307fb01b9ad', 'Metformin 25 mg - Lo demo 045', 'VD-DEMO-EXP-2026-045', 'Metformin hydrochloride', '25 mg', 'Vien nang', 'Hop 2 vi x 7 vien nang', 6, 'DA_DUYET'),
    (99, '6b2c4751-0632-48ac-b350-a4e611e842af', 'Amlodipine 50 mg - Lo demo 046', 'VD-DEMO-EXP-2026-046', 'Amlodipine besylate', '50 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 7, 'CHO_DUYET'),
    (100, '9093efae-b0f9-4948-838b-e523becbf5e9', 'Losartan 100 mg - Lo demo 047', 'VD-DEMO-EXP-2026-047', 'Losartan potassium', '100 mg', 'Vien nen', 'Hop 5 vi x 10 vien', 7, 'CHO_DUYET'),
    (101, '295c9db9-92b6-41e7-8b27-5ccf84d49b41', 'Atorvastatin 250 mg - Lo demo 048', 'VD-DEMO-EXP-2026-048', 'Atorvastatin calcium', '250 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 7, 'CHO_DUYET'),
    (102, '0331e108-34c7-49ae-b13c-38b9a6468352', 'Captopril 500 mg - Lo demo 049', 'VD-DEMO-EXP-2026-049', 'Captopril', '500 mg', 'Bột uống', 'Hop 30 goi bot', 7, 'CHO_DUYET'),
    (103, '3de8bb16-2445-4329-a178-d004e68f1f1e', 'Azithromycin 10 mg - Lo demo 050', 'VD-DEMO-EXP-2026-050', 'Azithromycin', '10 mg', 'Vien nang', 'Hop 2 vi x 7 vien nang', 7, 'CHO_DUYET'),
    (104, '95e110cf-f395-498a-8d8d-4383e0cf942a', 'Cefixime 20 mg - Lo demo 051', 'VD-DEMO-EXP-2026-051', 'Cefixime trihydrate', '20 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 7, 'TU_CHOI'),
    (105, '460d8aac-b353-4a27-8a34-7c358ec61d08', 'Ambroxol 25 mg - Lo demo 052', 'VD-DEMO-EXP-2026-052', 'Ambroxol hydrochloride', '25 mg', 'Vien nen', 'Hop 5 vi x 10 vien', 7, 'TU_CHOI'),
    (106, 'be65aaa0-9045-4b41-acff-923d4aabc63e', 'Montelukast 50 mg - Lo demo 053', 'VD-DEMO-EXP-2026-053', 'Montelukast sodium', '50 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 7, 'TU_CHOI');

-- 7. Bảng yeuCauDangKySanPham
INSERT INTO yeuCauDangKySanPham
    (id, sanPhamId, tenSanPham, thanhPhan, hamLuong, dangBaoChe, quyCachDongGoi, versionHoSo, ngayGui, trangThai, nguoiXuLyId, ngayXuLy, lyDoTuChoi)
VALUES
    (1, 1, 'Paracetamol An Khang 500 mg (DEMO)', 'Paracetamol', '500 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, '2026-09-08 00:00:00', 'DA_DUYET', 2, '2026-09-15 00:00:00', NULL),
    (2, 2, 'Amoxicillin An Khang 500 mg (DEMO)', 'Amoxicillin', '500 mg', 'Vien nang', 'Hop 10 vi x 10 vien', 1, '2026-09-09 00:00:00', 'DA_DUYET', 2, '2026-09-16 00:00:00', NULL),
    (3, 3, 'Vitamin C Hoa Binh 500 mg (DEMO)', 'Acid ascorbic', '500 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, '2026-10-05 00:00:00', 'CHO_DUYET', NULL, NULL, NULL),
    (4, 4, 'Loratadine An Khang 10 mg (DEMO)', 'Loratadine', '10 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, '2026-09-17 00:00:00', 'DA_DUYET', 2, '2026-09-18 00:00:00', NULL),
    (5, 5, 'Cetirizine An Khang 10 mg (DEMO)', 'Cetirizine dihydrochloride', '10 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, '2026-09-17 00:00:00', 'DA_DUYET', 2, '2026-09-18 00:00:00', NULL),
    (6, 6, 'Omeprazole An Khang 20 mg (DEMO)', 'Omeprazole', '20 mg', 'Vien nang', 'Hop 3 vi x 10 vien nang', 1, '2026-09-18 00:00:00', 'DA_DUYET', 2, '2026-09-19 00:00:00', NULL),
    (7, 7, 'Esomeprazole An Khang 20 mg (DEMO)', 'Esomeprazole', '20 mg', 'Vien nang', 'Hop 3 vi x 10 vien nang', 1, '2026-09-18 00:00:00', 'DA_DUYET', 2, '2026-09-19 00:00:00', NULL),
    (8, 8, 'Metformin An Khang 500 mg (DEMO)', 'Metformin hydrochloride', '500 mg', 'Vien nen', 'Hop 6 vi x 10 vien', 1, '2026-09-19 00:00:00', 'DA_DUYET', 2, '2026-09-20 00:00:00', NULL),
    (9, 9, 'Amlodipine An Khang 5 mg (DEMO)', 'Amlodipine', '5 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, '2026-09-19 00:00:00', 'DA_DUYET', 2, '2026-09-20 00:00:00', NULL),
    (10, 10, 'Losartan An Khang 50 mg (DEMO)', 'Losartan potassium', '50 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, '2026-09-20 00:00:00', 'DA_DUYET', 2, '2026-09-21 00:00:00', NULL),
    (11, 11, 'Atorvastatin An Khang 10 mg (DEMO)', 'Atorvastatin calcium', '10 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, '2026-09-20 00:00:00', 'DA_DUYET', 2, '2026-09-21 00:00:00', NULL),
    (12, 12, 'Simvastatin An Khang 10 mg (DEMO)', 'Simvastatin', '10 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, '2026-09-21 00:00:00', 'DA_DUYET', 2, '2026-09-22 00:00:00', NULL),
    (13, 13, 'Captopril An Khang 25 mg (DEMO)', 'Captopril', '25 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, '2026-09-21 00:00:00', 'DA_DUYET', 2, '2026-09-22 00:00:00', NULL),
    (14, 14, 'Enalapril An Khang 5 mg (DEMO)', 'Enalapril maleate', '5 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, '2026-09-22 00:00:00', 'DA_DUYET', 2, '2026-09-23 00:00:00', NULL),
    (15, 15, 'Bisoprolol An Khang 2.5 mg (DEMO)', 'Bisoprolol fumarate', '2.5 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, '2026-09-22 00:00:00', 'DA_DUYET', 2, '2026-09-23 00:00:00', NULL),
    (16, 16, 'Hydrochlorothiazide An Khang 25 mg (DEMO)', 'Hydrochlorothiazide', '25 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, '2026-09-23 00:00:00', 'DA_DUYET', 2, '2026-09-24 00:00:00', NULL),
    (17, 17, 'Gliclazide An Khang 30 mg (DEMO)', 'Gliclazide', '30 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, '2026-09-23 00:00:00', 'DA_DUYET', 2, '2026-09-24 00:00:00', NULL),
    (18, 18, 'Glimepiride An Khang 2 mg (DEMO)', 'Glimepiride', '2 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, '2026-09-24 00:00:00', 'DA_DUYET', 2, '2026-09-25 00:00:00', NULL),
    (19, 19, 'Dapagliflozin An Khang 10 mg (DEMO)', 'Dapagliflozin', '10 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, '2026-09-24 00:00:00', 'DA_DUYET', 2, '2026-09-25 00:00:00', NULL),
    (20, 20, 'Aspirin An Khang 81 mg (DEMO)', 'Acetylsalicylic acid', '81 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, '2026-09-25 00:00:00', 'DA_DUYET', 2, '2026-09-26 00:00:00', NULL),
    (21, 21, 'Clopidogrel An Khang 75 mg (DEMO)', 'Clopidogrel', '75 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, '2026-09-25 00:00:00', 'DA_DUYET', 2, '2026-09-26 00:00:00', NULL),
    (22, 22, 'Diclofenac An Khang 50 mg (DEMO)', 'Diclofenac sodium', '50 mg', 'Vien nen', 'Hop 5 vi x 10 vien', 1, '2026-09-26 00:00:00', 'DA_DUYET', 2, '2026-09-27 00:00:00', NULL),
    (23, 23, 'Meloxicam An Khang 7.5 mg (DEMO)', 'Meloxicam', '7.5 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, '2026-09-26 00:00:00', 'DA_DUYET', 2, '2026-09-27 00:00:00', NULL),
    (24, 24, 'Celecoxib An Khang 100 mg (DEMO)', 'Celecoxib', '100 mg', 'Vien nang', 'Hop 3 vi x 10 vien nang', 1, '2026-09-27 00:00:00', 'DA_DUYET', 2, '2026-09-28 00:00:00', NULL),
    (25, 25, 'Dexamethasone An Khang 0.5 mg (DEMO)', 'Dexamethasone', '0.5 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, '2026-09-27 00:00:00', 'DA_DUYET', 2, '2026-09-28 00:00:00', NULL),
    (26, 26, 'Prednisolone An Khang 5 mg (DEMO)', 'Prednisolone', '5 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, '2026-09-28 00:00:00', 'DA_DUYET', 2, '2026-09-29 00:00:00', NULL),
    (27, 27, 'Azithromycin An Khang 250 mg (DEMO)', 'Azithromycin', '250 mg', 'Vien nen', 'Hop 1 vi x 6 vien', 1, '2026-09-28 00:00:00', 'DA_DUYET', 2, '2026-09-29 00:00:00', NULL),
    (28, 28, 'Cefixime An Khang 200 mg (DEMO)', 'Cefixime', '200 mg', 'Vien nen', 'Hop 2 vi x 10 vien', 1, '2026-09-29 00:00:00', 'DA_DUYET', 2, '2026-09-30 00:00:00', NULL),
    (29, 29, 'Cefuroxime An Khang 250 mg (DEMO)', 'Cefuroxime axetil', '250 mg', 'Vien nen', 'Hop 2 vi x 10 vien', 1, '2026-09-29 00:00:00', 'DA_DUYET', 2, '2026-09-30 00:00:00', NULL),
    (30, 30, 'Clarithromycin An Khang 500 mg (DEMO)', 'Clarithromycin', '500 mg', 'Vien nen', 'Hop 2 vi x 7 vien', 1, '2026-09-30 00:00:00', 'DA_DUYET', 2, '2026-10-01 00:00:00', NULL),
    (31, 31, 'Doxycycline An Khang 100 mg (DEMO)', 'Doxycycline hyclate', '100 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, '2026-09-30 00:00:00', 'DA_DUYET', 2, '2026-10-01 00:00:00', NULL),
    (32, 32, 'Metronidazole An Khang 250 mg (DEMO)', 'Metronidazole', '250 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, '2026-10-01 00:00:00', 'DA_DUYET', 2, '2026-10-02 00:00:00', NULL),
    (33, 33, 'Fluconazole An Khang 150 mg (DEMO)', 'Fluconazole', '150 mg', 'Vien nang', 'Hop 1 vi x 1 vien nang', 1, '2026-10-01 00:00:00', 'DA_DUYET', 2, '2026-10-02 00:00:00', NULL),
    (34, 34, 'Acyclovir An Khang 200 mg (DEMO)', 'Acyclovir', '200 mg', 'Vien nen', 'Hop 5 vi x 5 vien', 1, '2026-10-02 00:00:00', 'DA_DUYET', 2, '2026-10-03 00:00:00', NULL),
    (35, 35, 'Ambroxol An Khang 30 mg (DEMO)', 'Ambroxol hydrochloride', '30 mg', 'Vien nen', 'Hop 2 vi x 10 vien', 1, '2026-10-02 00:00:00', 'DA_DUYET', 2, '2026-10-03 00:00:00', NULL),
    (36, 36, 'Acetylcysteine An Khang 200 mg (DEMO)', 'Acetylcysteine', '200 mg', 'Bột uống', 'Hop 30 goi bot', 1, '2026-10-03 00:00:00', 'DA_DUYET', 2, '2026-10-04 00:00:00', NULL),
    (37, 37, 'Montelukast An Khang 10 mg (DEMO)', 'Montelukast', '10 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, '2026-10-03 00:00:00', 'DA_DUYET', 2, '2026-10-04 00:00:00', NULL),
    (38, 38, 'Salbutamol An Khang 2 mg (DEMO)', 'Salbutamol sulfate', '2 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, '2026-10-04 00:00:00', 'DA_DUYET', 2, '2026-10-05 00:00:00', NULL),
    (39, 39, 'Domperidone An Khang 10 mg (DEMO)', 'Domperidone', '10 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, '2026-10-04 00:00:00', 'DA_DUYET', 2, '2026-10-05 00:00:00', NULL),
    (40, 40, 'Ondansetron An Khang 4 mg (DEMO)', 'Ondansetron', '4 mg', 'Vien nen', 'Hop 2 vi x 10 vien', 1, '2026-10-05 00:00:00', 'DA_DUYET', 2, '2026-10-06 00:00:00', NULL),
    (41, 41, 'Loperamide An Khang 2 mg (DEMO)', 'Loperamide hydrochloride', '2 mg', 'Vien nen', 'Hop 2 vi x 10 vien', 1, '2026-10-05 00:00:00', 'DA_DUYET', 2, '2026-10-06 00:00:00', NULL),
    (42, 42, 'Racecadotril An Khang 100 mg (DEMO)', 'Racecadotril', '100 mg', 'Vien nang', 'Hop 2 vi x 10 vien nang', 1, '2026-10-06 00:00:00', 'DA_DUYET', 2, '2026-10-06 00:00:00', NULL),
    (43, 43, 'Ferrous fumarate An Khang 200 mg (DEMO)', 'Ferrous fumarate', '200 mg', 'Vien nen', 'Hop 5 vi x 10 vien', 1, '2026-10-06 00:00:00', 'DA_DUYET', 2, '2026-10-06 00:00:00', NULL),
    (44, 44, 'Folic acid An Khang 5 mg (DEMO)', 'Folic acid', '5 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, '2026-10-06 00:00:00', 'DA_DUYET', 2, '2026-10-06 00:00:00', NULL),
    (45, 45, 'Calcium carbonate An Khang 500 mg (DEMO)', 'Calcium carbonate', '500 mg', 'Vien nen', 'Hop 5 vi x 10 vien', 1, '2026-10-06 00:00:00', 'DA_DUYET', 2, '2026-10-06 00:00:00', NULL),
    (46, 46, 'Zinc gluconate An Khang 10 mg (DEMO)', 'Zinc gluconate', '10 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, '2026-10-06 00:00:00', 'DA_DUYET', 2, '2026-10-06 00:00:00', NULL),
    (47, 47, 'Thiamine An Khang 100 mg (DEMO)', 'Thiamine hydrochloride', '100 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, '2026-10-06 00:00:00', 'DA_DUYET', 2, '2026-10-06 00:00:00', NULL),
    (48, 48, 'Pyridoxine An Khang 50 mg (DEMO)', 'Pyridoxine hydrochloride', '50 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, '2026-10-06 00:00:00', 'DA_DUYET', 2, '2026-10-06 00:00:00', NULL),
    (49, 49, 'Cholecalciferol An Khang 1000 IU (DEMO)', 'Cholecalciferol', '1000 IU', 'Vien nang mềm', 'Hop 3 vi x 10 vien nang mem', 1, '2026-10-06 00:00:00', 'DA_DUYET', 2, '2026-10-06 00:00:00', NULL),
    (50, 50, 'Oral rehydration salts An Khang (DEMO)', 'Natri clorid, kali clorid, natri citrat, glucose', 'Standard', 'Bột uống', 'Hop 20 goi bot pha dung dich', 1, '2026-10-06 00:00:00', 'DA_DUYET', 2, '2026-10-06 00:00:00', NULL),
    (51, 51, 'Cyanocobalamin An Khang 500 mcg (DEMO)', 'Cyanocobalamin', '500 mcg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, '2026-10-06 00:00:00', 'DA_DUYET', 2, '2026-10-06 00:00:00', NULL),
    (52, 52, 'Levofloxacin An Khang 500 mg (DEMO)', 'Levofloxacin', '500 mg', 'Vien nen', 'Hop 1 vi x 10 vien', 1, '2026-10-06 00:00:00', 'DA_DUYET', 2, '2026-10-06 00:00:00', NULL),
    (53, 53, 'Cefuroxime An Khang 500 mg (DEMO)', 'Cefuroxime axetil', '500 mg', 'Vien nen', 'Hop 2 vi x 10 vien', 1, '2026-10-06 00:00:00', 'DA_DUYET', 2, '2026-10-06 00:00:00', NULL),
    (54, 54, 'Paracetamol 10 mg - Lo demo 001', 'Paracetamol', '10 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, '2026-10-01 00:00:00', 'DA_DUYET', 2, '2026-10-03 00:00:00', NULL),
    (55, 55, 'Loratadine 20 mg - Lo demo 002', 'Loratadine', '20 mg', 'Vien nen', 'Hop 5 vi x 10 vien', 1, '2026-10-02 00:00:00', 'DA_DUYET', 2, '2026-10-04 00:00:00', NULL),
    (56, 56, 'Cetirizine 25 mg - Lo demo 003', 'Cetirizine dihydrochloride', '25 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, '2026-10-03 00:00:00', 'DA_DUYET', 2, '2026-10-05 00:00:00', NULL),
    (57, 57, 'Omeprazole 50 mg - Lo demo 004', 'Omeprazole', '50 mg', 'Bột uống', 'Hop 30 goi bot', 1, '2026-10-04 00:00:00', 'DA_DUYET', 2, '2026-10-06 00:00:00', NULL),
    (58, 58, 'Metformin 100 mg - Lo demo 005', 'Metformin hydrochloride', '100 mg', 'Vien nang', 'Hop 2 vi x 7 vien nang', 1, '2026-10-05 00:00:00', 'DA_DUYET', 2, '2026-10-07 00:00:00', NULL),
    (59, 59, 'Amlodipine 250 mg - Lo demo 006', 'Amlodipine besylate', '250 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, '2026-10-06 00:00:00', 'DA_DUYET', 2, '2026-10-08 00:00:00', NULL),
    (60, 60, 'Losartan 500 mg - Lo demo 007', 'Losartan potassium', '500 mg', 'Vien nen', 'Hop 5 vi x 10 vien', 1, '2026-10-07 00:00:00', 'DA_DUYET', 2, '2026-10-09 00:00:00', NULL),
    (61, 61, 'Atorvastatin 10 mg - Lo demo 008', 'Atorvastatin calcium', '10 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, '2026-10-08 00:00:00', 'DA_DUYET', 2, '2026-10-01 00:00:00', NULL),
    (62, 62, 'Captopril 20 mg - Lo demo 009', 'Captopril', '20 mg', 'Bột uống', 'Hop 30 goi bot', 1, '2026-10-09 00:00:00', 'DA_DUYET', 2, '2026-10-02 00:00:00', NULL),
    (63, 63, 'Azithromycin 25 mg - Lo demo 010', 'Azithromycin', '25 mg', 'Vien nang', 'Hop 2 vi x 7 vien nang', 1, '2026-10-01 00:00:00', 'DA_DUYET', 2, '2026-10-03 00:00:00', NULL),
    (64, 64, 'Cefixime 50 mg - Lo demo 011', 'Cefixime trihydrate', '50 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, '2026-10-02 00:00:00', 'DA_DUYET', 2, '2026-10-04 00:00:00', NULL),
    (65, 65, 'Ambroxol 100 mg - Lo demo 012', 'Ambroxol hydrochloride', '100 mg', 'Vien nen', 'Hop 5 vi x 10 vien', 1, '2026-10-03 00:00:00', 'DA_DUYET', 2, '2026-10-05 00:00:00', NULL),
    (66, 66, 'Montelukast 250 mg - Lo demo 013', 'Montelukast sodium', '250 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, '2026-10-04 00:00:00', 'DA_DUYET', 2, '2026-10-06 00:00:00', NULL),
    (67, 67, 'Domperidone 500 mg - Lo demo 014', 'Domperidone', '500 mg', 'Bột uống', 'Hop 30 goi bot', 1, '2026-10-05 00:00:00', 'DA_DUYET', 2, '2026-10-07 00:00:00', NULL),
    (68, 68, 'Folic acid 10 mg - Lo demo 015', 'Folic acid', '10 mg', 'Vien nang', 'Hop 2 vi x 7 vien nang', 1, '2026-10-06 00:00:00', 'DA_DUYET', 2, '2026-10-08 00:00:00', NULL),
    (69, 69, 'Acetylcysteine 20 mg - Lo demo 016', 'Acetylcysteine', '20 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, '2026-10-07 00:00:00', 'DA_DUYET', 2, '2026-10-09 00:00:00', NULL),
    (70, 70, 'Vitamin C 25 mg - Lo demo 017', 'Ascorbic acid', '25 mg', 'Vien nen', 'Hop 5 vi x 10 vien', 1, '2026-10-08 00:00:00', 'DA_DUYET', 2, '2026-10-01 00:00:00', NULL),
    (71, 71, 'Doxycycline 50 mg - Lo demo 018', 'Doxycycline hyclate', '50 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, '2026-10-09 00:00:00', 'DA_DUYET', 2, '2026-10-02 00:00:00', NULL),
    (72, 72, 'Diclofenac 100 mg - Lo demo 019', 'Diclofenac sodium', '100 mg', 'Bột uống', 'Hop 30 goi bot', 1, '2026-10-01 00:00:00', 'DA_DUYET', 2, '2026-10-03 00:00:00', NULL),
    (73, 73, 'Ferrous fumarate 250 mg - Lo demo 020', 'Ferrous fumarate', '250 mg', 'Vien nang', 'Hop 2 vi x 7 vien nang', 1, '2026-10-02 00:00:00', 'DA_DUYET', 2, '2026-10-04 00:00:00', NULL),
    (74, 74, 'Paracetamol 500 mg - Lo demo 021', 'Paracetamol', '500 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, '2026-10-03 00:00:00', 'DA_DUYET', 2, '2026-10-05 00:00:00', NULL),
    (75, 75, 'Loratadine 10 mg - Lo demo 022', 'Loratadine', '10 mg', 'Vien nen', 'Hop 5 vi x 10 vien', 1, '2026-10-04 00:00:00', 'DA_DUYET', 2, '2026-10-06 00:00:00', NULL),
    (76, 76, 'Cetirizine 20 mg - Lo demo 023', 'Cetirizine dihydrochloride', '20 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, '2026-10-05 00:00:00', 'DA_DUYET', 2, '2026-10-07 00:00:00', NULL),
    (77, 77, 'Omeprazole 25 mg - Lo demo 024', 'Omeprazole', '25 mg', 'Bột uống', 'Hop 30 goi bot', 1, '2026-10-06 00:00:00', 'DA_DUYET', 2, '2026-10-08 00:00:00', NULL),
    (78, 78, 'Metformin 50 mg - Lo demo 025', 'Metformin hydrochloride', '50 mg', 'Vien nang', 'Hop 2 vi x 7 vien nang', 1, '2026-10-07 00:00:00', 'DA_DUYET', 2, '2026-10-09 00:00:00', NULL),
    (79, 79, 'Amlodipine 100 mg - Lo demo 026', 'Amlodipine besylate', '100 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, '2026-10-08 00:00:00', 'DA_DUYET', 2, '2026-10-01 00:00:00', NULL),
    (80, 80, 'Losartan 250 mg - Lo demo 027', 'Losartan potassium', '250 mg', 'Vien nen', 'Hop 5 vi x 10 vien', 1, '2026-10-09 00:00:00', 'DA_DUYET', 2, '2026-10-02 00:00:00', NULL),
    (81, 81, 'Atorvastatin 500 mg - Lo demo 028', 'Atorvastatin calcium', '500 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, '2026-10-01 00:00:00', 'DA_DUYET', 2, '2026-10-03 00:00:00', NULL),
    (82, 82, 'Captopril 10 mg - Lo demo 029', 'Captopril', '10 mg', 'Bột uống', 'Hop 30 goi bot', 1, '2026-10-02 00:00:00', 'DA_DUYET', 2, '2026-10-04 00:00:00', NULL),
    (83, 83, 'Azithromycin 20 mg - Lo demo 030', 'Azithromycin', '20 mg', 'Vien nang', 'Hop 2 vi x 7 vien nang', 1, '2026-10-03 00:00:00', 'DA_DUYET', 2, '2026-10-05 00:00:00', NULL),
    (84, 84, 'Cefixime 25 mg - Lo demo 031', 'Cefixime trihydrate', '25 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, '2026-10-04 00:00:00', 'DA_DUYET', 2, '2026-10-06 00:00:00', NULL),
    (85, 85, 'Ambroxol 50 mg - Lo demo 032', 'Ambroxol hydrochloride', '50 mg', 'Vien nen', 'Hop 5 vi x 10 vien', 1, '2026-10-05 00:00:00', 'DA_DUYET', 2, '2026-10-07 00:00:00', NULL),
    (86, 86, 'Montelukast 100 mg - Lo demo 033', 'Montelukast sodium', '100 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, '2026-10-06 00:00:00', 'DA_DUYET', 2, '2026-10-08 00:00:00', NULL),
    (87, 87, 'Domperidone 250 mg - Lo demo 034', 'Domperidone', '250 mg', 'Bột uống', 'Hop 30 goi bot', 1, '2026-10-07 00:00:00', 'DA_DUYET', 2, '2026-10-09 00:00:00', NULL),
    (88, 88, 'Folic acid 500 mg - Lo demo 035', 'Folic acid', '500 mg', 'Vien nang', 'Hop 2 vi x 7 vien nang', 1, '2026-10-08 00:00:00', 'DA_DUYET', 2, '2026-10-01 00:00:00', NULL),
    (89, 89, 'Acetylcysteine 10 mg - Lo demo 036', 'Acetylcysteine', '10 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, '2026-10-09 00:00:00', 'DA_DUYET', 2, '2026-10-02 00:00:00', NULL),
    (90, 90, 'Vitamin C 20 mg - Lo demo 037', 'Ascorbic acid', '20 mg', 'Vien nen', 'Hop 5 vi x 10 vien', 1, '2026-10-01 00:00:00', 'DA_DUYET', 2, '2026-10-03 00:00:00', NULL),
    (91, 91, 'Doxycycline 25 mg - Lo demo 038', 'Doxycycline hyclate', '25 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, '2026-10-02 00:00:00', 'DA_DUYET', 2, '2026-10-04 00:00:00', NULL),
    (92, 92, 'Diclofenac 50 mg - Lo demo 039', 'Diclofenac sodium', '50 mg', 'Bột uống', 'Hop 30 goi bot', 1, '2026-10-03 00:00:00', 'DA_DUYET', 2, '2026-10-05 00:00:00', NULL),
    (93, 93, 'Ferrous fumarate 100 mg - Lo demo 040', 'Ferrous fumarate', '100 mg', 'Vien nang', 'Hop 2 vi x 7 vien nang', 1, '2026-10-04 00:00:00', 'DA_DUYET', 2, '2026-10-06 00:00:00', NULL),
    (94, 94, 'Paracetamol 250 mg - Lo demo 041', 'Paracetamol', '250 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, '2026-10-05 00:00:00', 'DA_DUYET', 2, '2026-10-07 00:00:00', NULL),
    (95, 95, 'Loratadine 500 mg - Lo demo 042', 'Loratadine', '500 mg', 'Vien nen', 'Hop 5 vi x 10 vien', 1, '2026-10-06 00:00:00', 'DA_DUYET', 2, '2026-10-08 00:00:00', NULL),
    (96, 96, 'Cetirizine 10 mg - Lo demo 043', 'Cetirizine dihydrochloride', '10 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, '2026-10-07 00:00:00', 'DA_DUYET', 2, '2026-10-09 00:00:00', NULL),
    (97, 97, 'Omeprazole 20 mg - Lo demo 044', 'Omeprazole', '20 mg', 'Bột uống', 'Hop 30 goi bot', 1, '2026-10-08 00:00:00', 'DA_DUYET', 2, '2026-10-01 00:00:00', NULL),
    (98, 98, 'Metformin 25 mg - Lo demo 045', 'Metformin hydrochloride', '25 mg', 'Vien nang', 'Hop 2 vi x 7 vien nang', 1, '2026-10-09 00:00:00', 'DA_DUYET', 2, '2026-10-02 00:00:00', NULL),
    (99, 99, 'Amlodipine 50 mg - Lo demo 046', 'Amlodipine besylate', '50 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, '2026-10-01 00:00:00', 'CHO_DUYET', NULL, NULL, NULL),
    (100, 100, 'Losartan 100 mg - Lo demo 047', 'Losartan potassium', '100 mg', 'Vien nen', 'Hop 5 vi x 10 vien', 1, '2026-10-02 00:00:00', 'CHO_DUYET', NULL, NULL, NULL),
    (101, 101, 'Atorvastatin 250 mg - Lo demo 048', 'Atorvastatin calcium', '250 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, '2026-10-03 00:00:00', 'CHO_DUYET', NULL, NULL, NULL),
    (102, 102, 'Captopril 500 mg - Lo demo 049', 'Captopril', '500 mg', 'Bột uống', 'Hop 30 goi bot', 1, '2026-10-04 00:00:00', 'CHO_DUYET', NULL, NULL, NULL),
    (103, 103, 'Azithromycin 10 mg - Lo demo 050', 'Azithromycin', '10 mg', 'Vien nang', 'Hop 2 vi x 7 vien nang', 1, '2026-10-05 00:00:00', 'CHO_DUYET', NULL, NULL, NULL),
    (104, 104, 'Cefixime 20 mg - Lo demo 051', 'Cefixime trihydrate', '20 mg', 'Vien nen', 'Hop 3 vi x 10 vien', 1, '2026-10-06 00:00:00', 'TU_CHOI', 2, '2026-10-08 00:00:00', 'Ho so demo can bo sung thong tin.'),
    (105, 105, 'Ambroxol 25 mg - Lo demo 052', 'Ambroxol hydrochloride', '25 mg', 'Vien nen', 'Hop 5 vi x 10 vien', 1, '2026-10-07 00:00:00', 'TU_CHOI', 2, '2026-10-09 00:00:00', 'Ho so demo can bo sung thong tin.'),
    (106, 106, 'Montelukast 50 mg - Lo demo 053', 'Montelukast sodium', '50 mg', 'Vien nen', 'Hop 10 vi x 10 vien', 1, '2026-10-08 00:00:00', 'TU_CHOI', 2, '2026-10-01 00:00:00', 'Ho so demo can bo sung thong tin.');
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
    (id, maDonVi, loThuocId, banLeId, trangThai, ngayTao, ngayBan)
VALUES
    (1, 'AK-PARA-2026-001-000001', 1, 1, 'DISPENSED', '2026-10-02 08:30:00', '2026-10-05 16:30:00'),
    (2, 'AK-PARA-2026-001-000002', 1, 1, 'DISPENSED', '2026-10-02 08:30:00', '2026-10-05 16:30:00'),
    (3, 'AK-PARA-2026-001-000003', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL),
    (4, 'AK-PARA-2026-001-000004', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL),
    (5, 'AK-PARA-2026-001-000005', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL),
    (6, 'AK-PARA-2026-001-000006', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL),
    (7, 'AK-PARA-2026-001-000007', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL),
    (8, 'AK-PARA-2026-001-000008', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL),
    (9, 'AK-PARA-2026-001-000009', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL),
    (10, 'AK-PARA-2026-001-000010', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL),
    (11, 'AK-PARA-2026-001-000011', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL),
    (12, 'AK-PARA-2026-001-000012', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL),
    (13, 'AK-PARA-2026-001-000013', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL),
    (14, 'AK-PARA-2026-001-000014', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL),
    (15, 'AK-PARA-2026-001-000015', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL),
    (16, 'AK-PARA-2026-001-000016', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL),
    (17, 'AK-PARA-2026-001-000017', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL),
    (18, 'AK-PARA-2026-001-000018', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL),
    (19, 'AK-PARA-2026-001-000019', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL),
    (20, 'AK-PARA-2026-001-000020', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL),
    (21, 'AK-AMOX-2026-002-000001', 2, NULL, 'AVAILABLE', '2026-10-02 09:00:00', NULL),
    (22, 'AK-AMOX-2026-002-000002', 2, NULL, 'AVAILABLE', '2026-10-02 09:00:00', NULL),
    (23, 'AK-AMOX-2026-002-000003', 2, NULL, 'AVAILABLE', '2026-10-02 09:00:00', NULL),
    (24, 'AK-AMOX-2026-002-000004', 2, NULL, 'AVAILABLE', '2026-10-02 09:00:00', NULL),
    (25, 'AK-AMOX-2026-002-000005', 2, NULL, 'AVAILABLE', '2026-10-02 09:00:00', NULL),
    (26, 'AK-AMOX-2026-002-000006', 2, NULL, 'AVAILABLE', '2026-10-02 09:00:00', NULL),
    (27, 'DEMO-EXP-2026-B001-0001', 3, 2, 'DISPENSED', '2026-09-01 08:00:00', '2026-10-07 14:00:00'),
    (28, 'DEMO-EXP-2026-B001-0002', 3, 2, 'DISPENSED', '2026-09-01 08:00:00', '2026-10-07 14:00:00'),
    (29, 'DEMO-EXP-2026-B001-0003', 3, NULL, 'AVAILABLE', '2026-09-01 08:00:00', NULL),
    (30, 'DEMO-EXP-2026-B001-0004', 3, NULL, 'AVAILABLE', '2026-09-01 08:00:00', NULL),
    (31, 'DEMO-EXP-2026-B001-0005', 3, NULL, 'AVAILABLE', '2026-09-01 08:00:00', NULL),
    (32, 'DEMO-EXP-2026-B001-0006', 3, NULL, 'AVAILABLE', '2026-09-01 08:00:00', NULL),
    (33, 'DEMO-EXP-2026-B001-0007', 3, NULL, 'AVAILABLE', '2026-09-01 08:00:00', NULL),
    (34, 'DEMO-EXP-2026-B001-0008', 3, NULL, 'AVAILABLE', '2026-09-01 08:00:00', NULL),
    (35, 'DEMO-EXP-2026-B001-0009', 3, NULL, 'AVAILABLE', '2026-09-01 08:00:00', NULL),
    (36, 'DEMO-EXP-2026-B001-0010', 3, NULL, 'AVAILABLE', '2026-09-01 08:00:00', NULL),
    (37, 'DEMO-EXP-2026-B001-0011', 3, NULL, 'AVAILABLE', '2026-09-01 08:00:00', NULL),
    (38, 'DEMO-EXP-2026-B001-0012', 3, NULL, 'AVAILABLE', '2026-09-01 08:00:00', NULL),
    (39, 'DEMO-EXP-2026-B001-0013', 3, NULL, 'AVAILABLE', '2026-09-01 08:00:00', NULL),
    (40, 'DEMO-EXP-2026-B001-0014', 3, NULL, 'AVAILABLE', '2026-09-01 08:00:00', NULL),
    (41, 'DEMO-EXP-2026-B001-0015', 3, NULL, 'AVAILABLE', '2026-09-01 08:00:00', NULL),
    (42, 'DEMO-EXP-2026-B001-0016', 3, NULL, 'AVAILABLE', '2026-09-01 08:00:00', NULL),
    (43, 'DEMO-EXP-2026-B002-0001', 4, 3, 'DISPENSED', '2026-09-02 08:00:00', '2026-10-08 14:00:00'),
    (44, 'DEMO-EXP-2026-B002-0002', 4, NULL, 'AVAILABLE', '2026-09-02 08:00:00', NULL),
    (45, 'DEMO-EXP-2026-B002-0003', 4, NULL, 'AVAILABLE', '2026-09-02 08:00:00', NULL),
    (46, 'DEMO-EXP-2026-B002-0004', 4, NULL, 'AVAILABLE', '2026-09-02 08:00:00', NULL),
    (47, 'DEMO-EXP-2026-B002-0005', 4, NULL, 'AVAILABLE', '2026-09-02 08:00:00', NULL),
    (48, 'DEMO-EXP-2026-B002-0006', 4, NULL, 'AVAILABLE', '2026-09-02 08:00:00', NULL),
    (49, 'DEMO-EXP-2026-B002-0007', 4, NULL, 'AVAILABLE', '2026-09-02 08:00:00', NULL),
    (50, 'DEMO-EXP-2026-B002-0008', 4, NULL, 'AVAILABLE', '2026-09-02 08:00:00', NULL),
    (51, 'DEMO-EXP-2026-B002-0009', 4, NULL, 'AVAILABLE', '2026-09-02 08:00:00', NULL),
    (52, 'DEMO-EXP-2026-B002-0010', 4, NULL, 'AVAILABLE', '2026-09-02 08:00:00', NULL);

-- 11. Bảng qrCode
INSERT INTO qrCode
    (id, giaTriQR, loaiQR, loThuocId, donViSanPhamId, ngayTao, trangThai)
VALUES
    (1, 'QR-BATCH-AK-PARA-2026-001', 'BATCH', 1, NULL, '2026-10-02 08:31:00', 'ACTIVE'),
    (2, 'QR-UNIT-AK26PARA00000001', 'UNIT', NULL, 1, '2026-10-02 08:31:00', 'ACTIVE'),
    (3, 'QR-UNIT-AK26PARA00000002', 'UNIT', NULL, 2, '2026-10-02 08:31:00', 'ACTIVE'),
    (4, 'QR-UNIT-AK26PARA00000003', 'UNIT', NULL, 3, '2026-10-02 08:31:00', 'ACTIVE'),
    (5, 'QR-UNIT-AK26PARA00000004', 'UNIT', NULL, 4, '2026-10-02 08:31:00', 'ACTIVE'),
    (6, 'QR-UNIT-AK26PARA00000005', 'UNIT', NULL, 5, '2026-10-02 08:31:00', 'ACTIVE'),
    (7, 'QR-UNIT-AK26PARA00000006', 'UNIT', NULL, 6, '2026-10-02 08:31:00', 'ACTIVE'),
    (8, 'QR-UNIT-AK26PARA00000007', 'UNIT', NULL, 7, '2026-10-02 08:31:00', 'ACTIVE'),
    (9, 'QR-UNIT-AK26PARA00000008', 'UNIT', NULL, 8, '2026-10-02 08:31:00', 'ACTIVE'),
    (10, 'QR-UNIT-AK26PARA00000009', 'UNIT', NULL, 9, '2026-10-02 08:31:00', 'ACTIVE'),
    (11, 'QR-UNIT-AK26PARA00000010', 'UNIT', NULL, 10, '2026-10-02 08:31:00', 'ACTIVE'),
    (12, 'QR-UNIT-AK26PARA00000011', 'UNIT', NULL, 11, '2026-10-02 08:31:00', 'ACTIVE'),
    (13, 'QR-UNIT-AK26PARA00000012', 'UNIT', NULL, 12, '2026-10-02 08:31:00', 'ACTIVE'),
    (14, 'QR-UNIT-AK26PARA00000013', 'UNIT', NULL, 13, '2026-10-02 08:31:00', 'ACTIVE'),
    (15, 'QR-UNIT-AK26PARA00000014', 'UNIT', NULL, 14, '2026-10-02 08:31:00', 'ACTIVE'),
    (16, 'QR-UNIT-AK26PARA00000015', 'UNIT', NULL, 15, '2026-10-02 08:31:00', 'ACTIVE'),
    (17, 'QR-UNIT-AK26PARA00000016', 'UNIT', NULL, 16, '2026-10-02 08:31:00', 'ACTIVE'),
    (18, 'QR-UNIT-AK26PARA00000017', 'UNIT', NULL, 17, '2026-10-02 08:31:00', 'ACTIVE'),
    (19, 'QR-UNIT-AK26PARA00000018', 'UNIT', NULL, 18, '2026-10-02 08:31:00', 'ACTIVE'),
    (20, 'QR-UNIT-AK26PARA00000019', 'UNIT', NULL, 19, '2026-10-02 08:31:00', 'ACTIVE'),
    (21, 'QR-UNIT-AK26PARA00000020', 'UNIT', NULL, 20, '2026-10-02 08:31:00', 'ACTIVE'),
    (22, 'QR-BATCH-AK-AMOX-2026-002', 'BATCH', 2, NULL, '2026-10-02 09:01:00', 'ACTIVE'),
    (23, 'QR-UNIT-AK26AMOX00000001', 'UNIT', NULL, 21, '2026-10-02 09:01:00', 'ACTIVE'),
    (24, 'QR-UNIT-AK26AMOX00000002', 'UNIT', NULL, 22, '2026-10-02 09:01:00', 'ACTIVE'),
    (25, 'QR-UNIT-AK26AMOX00000003', 'UNIT', NULL, 23, '2026-10-02 09:01:00', 'ACTIVE'),
    (26, 'QR-UNIT-AK26AMOX00000004', 'UNIT', NULL, 24, '2026-10-02 09:01:00', 'ACTIVE'),
    (27, 'QR-UNIT-AK26AMOX00000005', 'UNIT', NULL, 25, '2026-10-02 09:01:00', 'ACTIVE'),
    (28, 'QR-UNIT-AK26AMOX00000006', 'UNIT', NULL, 26, '2026-10-02 09:01:00', 'ACTIVE'),
    (29, 'QR-BATCH-DEMO-EXP-2026-B001', 'BATCH', 3, NULL, '2026-09-01 08:30:00', 'ACTIVE'),
    (30, 'QR-BATCH-DEMO-EXP-2026-B002', 'BATCH', 4, NULL, '2026-09-01 08:30:00', 'ACTIVE'),
    (31, 'QR-BATCH-DEMO-EXP-2026-B003', 'BATCH', 5, NULL, '2026-09-01 08:30:00', 'ACTIVE'),
    (32, 'QR-UNIT-DEMO-EXP-2026-B001-0001', 'UNIT', NULL, 27, '2026-09-01 08:30:00', 'ACTIVE'),
    (33, 'QR-UNIT-DEMO-EXP-2026-B001-0002', 'UNIT', NULL, 28, '2026-09-01 08:30:00', 'ACTIVE'),
    (34, 'QR-UNIT-DEMO-EXP-2026-B001-0003', 'UNIT', NULL, 29, '2026-09-01 08:30:00', 'ACTIVE'),
    (35, 'QR-UNIT-DEMO-EXP-2026-B001-0004', 'UNIT', NULL, 30, '2026-09-01 08:30:00', 'ACTIVE'),
    (36, 'QR-UNIT-DEMO-EXP-2026-B001-0005', 'UNIT', NULL, 31, '2026-09-01 08:30:00', 'ACTIVE'),
    (37, 'QR-UNIT-DEMO-EXP-2026-B001-0006', 'UNIT', NULL, 32, '2026-09-01 08:30:00', 'ACTIVE'),
    (38, 'QR-UNIT-DEMO-EXP-2026-B001-0007', 'UNIT', NULL, 33, '2026-09-01 08:30:00', 'ACTIVE'),
    (39, 'QR-UNIT-DEMO-EXP-2026-B001-0008', 'UNIT', NULL, 34, '2026-09-01 08:30:00', 'ACTIVE'),
    (40, 'QR-UNIT-DEMO-EXP-2026-B001-0009', 'UNIT', NULL, 35, '2026-09-01 08:30:00', 'ACTIVE'),
    (41, 'QR-UNIT-DEMO-EXP-2026-B001-0010', 'UNIT', NULL, 36, '2026-09-01 08:30:00', 'ACTIVE'),
    (42, 'QR-UNIT-DEMO-EXP-2026-B001-0011', 'UNIT', NULL, 37, '2026-09-01 08:30:00', 'ACTIVE'),
    (43, 'QR-UNIT-DEMO-EXP-2026-B001-0012', 'UNIT', NULL, 38, '2026-09-01 08:30:00', 'ACTIVE'),
    (44, 'QR-UNIT-DEMO-EXP-2026-B001-0013', 'UNIT', NULL, 39, '2026-09-01 08:30:00', 'ACTIVE'),
    (45, 'QR-UNIT-DEMO-EXP-2026-B001-0014', 'UNIT', NULL, 40, '2026-09-01 08:30:00', 'ACTIVE'),
    (46, 'QR-UNIT-DEMO-EXP-2026-B001-0015', 'UNIT', NULL, 41, '2026-09-01 08:30:00', 'ACTIVE'),
    (47, 'QR-UNIT-DEMO-EXP-2026-B001-0016', 'UNIT', NULL, 42, '2026-09-01 08:30:00', 'ACTIVE'),
    (48, 'QR-UNIT-DEMO-EXP-2026-B002-0001', 'UNIT', NULL, 43, '2026-09-01 08:30:00', 'ACTIVE'),
    (49, 'QR-UNIT-DEMO-EXP-2026-B002-0002', 'UNIT', NULL, 44, '2026-09-01 08:30:00', 'ACTIVE'),
    (50, 'QR-UNIT-DEMO-EXP-2026-B002-0003', 'UNIT', NULL, 45, '2026-09-01 08:30:00', 'ACTIVE'),
    (51, 'QR-UNIT-DEMO-EXP-2026-B002-0004', 'UNIT', NULL, 46, '2026-09-01 08:30:00', 'ACTIVE'),
    (52, 'QR-UNIT-DEMO-EXP-2026-B002-0005', 'UNIT', NULL, 47, '2026-09-01 08:30:00', 'ACTIVE'),
    (53, 'QR-UNIT-DEMO-EXP-2026-B002-0006', 'UNIT', NULL, 48, '2026-09-01 08:30:00', 'ACTIVE'),
    (54, 'QR-UNIT-DEMO-EXP-2026-B002-0007', 'UNIT', NULL, 49, '2026-09-01 08:30:00', 'ACTIVE'),
    (55, 'QR-UNIT-DEMO-EXP-2026-B002-0008', 'UNIT', NULL, 50, '2026-09-01 08:30:00', 'ACTIVE'),
    (56, 'QR-UNIT-DEMO-EXP-2026-B002-0009', 'UNIT', NULL, 51, '2026-09-01 08:30:00', 'ACTIVE'),
    (57, 'QR-UNIT-DEMO-EXP-2026-B002-0010', 'UNIT', NULL, 52, '2026-09-01 08:30:00', 'ACTIVE');

-- 12. Bảng tonKhoLo
INSERT INTO tonKhoLo (id, loThuocId, toChucId, soLuong, ngayCapNhat)
VALUES
    (1, 1, 1, 10, '2026-10-05 10:00:00'),
    (2, 1, 2, 5, '2026-10-04 11:00:00'),
    (3, 1, 3, 3, '2026-10-05 16:30:00'),
    (4, 2, 1, 6, '2026-10-05 17:00:00'),
    (5, 3, 6, 6, '2026-10-08 10:00:00'),
    (6, 3, 8, 8, '2026-10-08 10:00:00'),
    (7, 3, 9, 1, '2026-10-08 10:00:00'),
    (8, 4, 6, 4, '2026-10-08 10:00:00'),
    (9, 4, 8, 4, '2026-10-08 10:00:00'),
    (10, 4, 9, 1, '2026-10-08 10:00:00'),
    (11, 5, 7, 8, '2026-10-08 10:00:00');

-- 13. Bảng chuyenGiao (Chuyển đổi trangThai IN_TRANSIT, REJECTED về chuẩn v4 PENDING/RECEIVED)
INSERT INTO chuyenGiao
    (id, maChuyenGiao, loThuocId, benGuiId, benNhanId, soLuong, thoiGianKhoiTao, thoiGianXacNhan, trangThai)
VALUES
    (1, 'dddddddd-dddd-4ddd-8ddd-dddddddddddd', 1, 1, 2, 10, '2026-10-02 10:00:00', '2026-10-03 10:00:00', 'RECEIVED'),
    (2, 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', 1, 2, 3, 5, '2026-10-03 11:00:00', '2026-10-04 11:00:00', 'RECEIVED'),
    (3, 'ffffffff-ffff-4fff-8fff-ffffffffffff', 1, 1, 2, 2, '2026-10-05 17:00:00', NULL, 'PENDING'),
    (4, '42d4abbd-0314-4f7c-a0ba-7bfc1a2f9663', 3, 6, 8, 6, '2026-09-03 10:00:00', '2026-09-04 10:00:00', 'RECEIVED'),
    (5, 'c821c892-24d4-438e-b2d7-77db36ce187a', 3, 8, 9, 2, '2026-09-05 11:00:00', NULL, 'PENDING'),
    (6, 'b7c773bd-b2cd-4c46-9742-ea4c529a2d4a', 4, 6, 8, 4, '2026-09-06 12:00:00', NULL, 'PENDING'),
    (7, 'd980c7c3-bcbc-4313-b417-372c2e46fc56', 4, 8, 9, 1, '2026-09-07 13:00:00', '2026-09-07 15:00:00', 'RECEIVED');

-- 14. Bảng baoCaoNghiVan (Bổ sung maLoKhaiBao và map trạng thái)
INSERT INTO baoCaoNghiVan
    (id, maBaoCao, loThuocId, maLoKhaiBao, taiKhoanId, lyDo, moTa, ngayGui, trangThai)
VALUES
    (1, 'BC-DEMO-2026-0001', 2, 'AK-AMOX-2026-002', NULL, 'Nghi ngo chat luong lo thuoc', 'Bao cao an danh: phat hien vien thuoc co mau sac khong dong nhat.', '2026-10-05 00:00:00', 'DA_XU_LY'),
    (2, 'BC-DEMO-2026-0002', 1, 'AK-PARA-2026-001', 5, 'Niem phong thung hang co dau hieu bi rach', 'Bao cao cua nha thuoc, dang cho co quan quan ly xem xet.', '2026-10-06 00:00:00', 'DA_GUI'),
    (3, 'BC-DEMO-EXP-2026-0001', 5, 'DEMO-EXP-2026-B003', 10, 'Nghi ngo bao quan lo thuoc khong dung dieu kien.', 'Du lieu minh hoa, khong phai ket luan kiem dinh that.', '2026-09-08 00:00:00', 'DANG_XU_LY'),
    (4, 'BC-DEMO-EXP-2026-0002', 5, 'DEMO-EXP-2026-B003', 10, 'Phat hien sai lech trong ho so lo thuoc.', 'Du lieu minh hoa, khong phai ket luan kiem dinh that.', '2026-09-09 00:00:00', 'DA_XU_LY');

-- 15. Bảng minhChungBaoCao
INSERT INTO minhChungBaoCao (id, baoCaoId, tenFile, loaiFile, duongDan, ngayTaiLen)
VALUES
    (1, 1, 'anh-mau-vien-thuoc-demo.jpg', 'image/jpeg', 'demo/bao-cao/BC-DEMO-2026-0001/anh-mau.jpg', '2026-10-05 00:00:00'),
    (2, 2, 'anh-thung-hang-demo.jpg', 'image/jpeg', 'demo/bao-cao/BC-DEMO-2026-0002/anh-thung.jpg', '2026-10-06 00:00:00'),
    (3, 3, 'minh-chung-demo-1.jpg', 'image/jpeg', 'demo/mo-rong/bao-cao/BC-DEMO-EXP-2026-0001/minh-chung.jpg', '2026-09-08 00:00:00'),
    (4, 4, 'minh-chung-demo-2.jpg', 'image/jpeg', 'demo/mo-rong/bao-cao/BC-DEMO-EXP-2026-0002/minh-chung.jpg', '2026-09-09 00:00:00');

-- 16. Bảng ketQuaXuLyBaoCao (Thay nguoiXuLyId bằng coQuanXuLyId)
INSERT INTO ketQuaXuLyBaoCao
    (id, baoCaoId, coQuanXuLyId, quyetDinh, lyDoXuLy, ghiChu, ngayXuLy)
VALUES
    (1, 1, 2, 'THU_HOI', 'Ket qua kiem tra mau khong dat yeu cau.', 'Du lieu minh hoa; khong phai ket luan kiem dinh that.', '2026-10-05 00:00:00'),
    (2, 4, 11, 'THU_HOI', 'Can cach ly lo hang de kiem tra.', 'Du lieu demo.', '2026-09-12 00:00:00');

-- 17. Bảng thuHoiLoHang (Bổ sung coQuanQuyetDinhId)
INSERT INTO thuHoiLoHang
    (id, loThuocId, coQuanQuyetDinhId, ketQuaXuLyBaoCaoId, lyDo, ngayThuHoi)
VALUES
    (1, 2, 2, 1, 'Thu hoi demo theo ket qua xu ly BC-DEMO-2026-0001.', '2026-10-05 00:00:00'),
    (2, 5, 11, 2, 'Thu hoi minh hoa theo ket qua xu ly bao cao demo.', '2026-09-12 00:00:00');

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
    (9, 10, 'Polygon Testnet', '0xb840087a6457de1ef6a838b26ffdabcfef5c069f', 'DEMO-CERT-EXP-005', 'INACTIVE');

-- 19. Bảng blockchainTransaction
INSERT INTO blockchainTransaction
    (id, txHash, blockNumber, eventName, businessId, thoiGian, network, trangThai, toChucId, loThuocId, chuyenGiaoId, donViSanPhamId, banLeId)
VALUES
    (1, '0x1111111111111111111111111111111111111111111111111111111111111111', 100001, 'BatchRegistered', 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', '2026-10-02 08:30:00', 'Polygon Testnet', 'CONFIRMED', 1, 1, NULL, NULL, NULL),
    (2, '0x2222222222222222222222222222222222222222222222222222222222222222', 100120, 'TransferReceived', 'dddddddd-dddd-4ddd-8ddd-dddddddddddd', '2026-10-03 10:00:00', 'Polygon Testnet', 'CONFIRMED', 2, 1, 1, NULL, NULL),
    (3, '0x3333333333333333333333333333333333333333333333333333333333333333', 100180, 'TransferReceived', 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', '2026-10-04 11:00:00', 'Polygon Testnet', 'CONFIRMED', 3, 1, 2, NULL, NULL),
    (4, '0x4444444444444444444444444444444444444444444444444444444444444444', 100240, 'BatchRecalled', 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', '2026-10-05 17:00:00', 'Polygon Testnet', 'CONFIRMED', 4, 2, NULL, NULL, NULL),
    (5, '0x5555555555555555555555555555555555555555555555555555555555555555', 100230, 'RetailSale', 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', '2026-10-05 16:30:00', 'Polygon Testnet', 'CONFIRMED', 3, 1, NULL, 1, 1),
    (6, '0x6666666666666666666666666666666666666666666666666666666666666666', NULL, 'TransferCreated', 'ffffffff-ffff-4fff-8fff-ffffffffffff', '2026-10-05 17:00:00', 'Polygon Testnet', 'PENDING', 1, 1, 3, NULL, NULL),
    (7, '0x7777777777777777777777777777777777777777777777777777777777777777', 100002, 'BatchRegistered', 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', '2026-10-02 09:00:00', 'Polygon Testnet', 'CONFIRMED', 1, 2, NULL, NULL, NULL),
    (8, '0xfea5fa4b0e463c934a4406806b1ff4daf32ca0d16eca6772c9af65d910d5c4d5', 101000, 'BatchRegistered', 'DEMO-EXP-2026-B001', '2026-09-01 08:30:00', 'Polygon Testnet', 'CONFIRMED', 1, 3, NULL, NULL, NULL),
    (9, '0x171a31057f6de2f2aa1d9f31eae2b3b0be6209aa4f4a83fd2058604ab28a923b', 101010, 'BatchRegistered', 'DEMO-EXP-2026-B002', '2026-09-02 08:30:00', 'Polygon Testnet', 'CONFIRMED', 6, 4, NULL, NULL, NULL),
    (10, '0xf3e6c6ec64554a99d2d9f059cb884c56b8f14b11259f55def09e1b8897bee83c', 101020, 'BatchRegistered', 'DEMO-EXP-2026-B003', '2026-09-03 08:30:00', 'Polygon Testnet', 'CONFIRMED', 6, 5, NULL, NULL, NULL),
    (11, '0x5868d077f280a0333b520b3f74ac83cc95c0ca4ce8537efcf854dfd26853504b', 101100, 'TransferCreated', '42d4abbd-0314-4f7c-a0ba-7bfc1a2f9663', '2026-09-03 10:00:00', 'Polygon Testnet', 'CONFIRMED', 6, 3, 4, NULL, NULL),
    (12, '0x10070959ce4113c739013aa8aff26bb9ed167a9f26d9d2ec61a485d7a7b5e017', 101101, 'TransferCreated', 'c821c892-24d4-438e-b2d7-77db36ce187a', '2026-09-05 11:00:00', 'Polygon Testnet', 'PENDING', 8, 3, 5, NULL, NULL),
    (13, '0xdc8eba212ec6bba1595516555328c4662fcaf0f9fb1bf2a6a035cecbc61f4b2f', 101102, 'TransferCreated', 'b7c773bd-b2cd-4c46-9742-ea4c529a2d4a', '2026-09-06 12:00:00', 'Polygon Testnet', 'PENDING', 6, 4, 6, NULL, NULL),
    (14, '0x311b72917b5bc0a11b0fdd821511f986c452375056d68c228a6a52c66a8a5c79', 101200, 'RetailSale', 'e12144ac-ec46-4496-a688-6e8c4ddc8d21', '2026-10-07 14:00:00', 'Polygon Testnet', 'CONFIRMED', 9, 3, NULL, 27, 2),
    (15, '0x2b4894999a5371ed92e0ad7b9b63cc317dc897c9df3959fb230c309edf5f0323', 101201, 'RetailSale', '457df10c-396d-4195-b5bc-86c2af9a7448', '2026-10-08 14:00:00', 'Polygon Testnet', 'CONFIRMED', 9, 4, NULL, 43, 3),
    (16, '0xf2b4d7ad4883fe0395cdd284015c1455bd040aa3546cd8e285dec34806db34b0', 101300, 'BatchRecalled', 'DEMO-EXP-2026-B003', '2026-09-12 15:00:00', 'Polygon Testnet', 'CONFIRMED', 10, 5, NULL, NULL, NULL);

COMMIT;