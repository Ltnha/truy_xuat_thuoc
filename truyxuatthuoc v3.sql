CREATE DATABASE IF NOT EXISTS truyxuatthuoc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE truyxuatthuoc;

-- 1. toChuc
CREATE TABLE toChuc (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tenToChuc VARCHAR(255) NOT NULL,
    loaiToChuc ENUM('NHA_SAN_XUAT','NHA_PHAN_PHOI','NHA_THUOC','CO_QUAN_QUAN_LY') NOT NULL,
    diaChi VARCHAR(255),
    soDienThoai VARCHAR(20),
    maSoThue VARCHAR(20) UNIQUE,
    soGiayPhepDuoc VARCHAR(50) NULL,          -- cơ quan quản lý có thể không có giấy phép dược
    ngayCapGiayPhep DATE NULL,
    ngayHetHanGiayPhep DATE NULL,
    coQuanCap VARCHAR(255) NULL,
    trangThaiDuyet ENUM('CHO_DUYET','DA_DUYET','TU_CHOI','BI_THU_HOI') NOT NULL DEFAULT 'CHO_DUYET'
);

-- 2. taiKhoan
CREATE TABLE taiKhoan (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tenDangNhap VARCHAR(50) NOT NULL UNIQUE,
    matKhau VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE,
    soDienThoai VARCHAR(20),
    vaiTro ENUM('NHA_SAN_XUAT','NHA_PHAN_PHOI','NHA_THUOC','CO_QUAN_QUAN_LY','QUAN_TRI_VIEN') NOT NULL,
    trangThai ENUM('PENDING','ACTIVE','LOCKED','REVOKED') NOT NULL DEFAULT 'PENDING',
    toChucId BIGINT NULL,                     -- NULL với tài khoản quản trị viên hệ thống
    ngayTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (toChucId) REFERENCES toChuc(id)
);

-- 3. quyenHan (mới)
CREATE TABLE quyenHan (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tenQuyen VARCHAR(100) NOT NULL UNIQUE,
    moTa VARCHAR(255)
);

-- 4. taiKhoan_quyenHan 
CREATE TABLE taiKhoan_quyenHan (
    taiKhoanId BIGINT NOT NULL,
    quyenHanId BIGINT NOT NULL,
    PRIMARY KEY (taiKhoanId, quyenHanId),
    FOREIGN KEY (taiKhoanId) REFERENCES taiKhoan(id),
    FOREIGN KEY (quyenHanId) REFERENCES quyenHan(id)
);

-- 5. nhatKyHeThong
CREATE TABLE nhatKyHeThong (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    taiKhoanId BIGINT NOT NULL,
    hanhDong VARCHAR(100) NOT NULL,
    thoiGian DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    noiDung TEXT,
    diaChiIP VARCHAR(45),
    FOREIGN KEY (taiKhoanId) REFERENCES taiKhoan(id)
);

-- 6. yeuCauDangKy (yêu cầu đăng ký tổ chức)
CREATE TABLE yeuCauDangKy (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    toChucId BIGINT NOT NULL,
    ngayGui DATE NOT NULL DEFAULT (CURRENT_DATE),
    versionHoSo INT NOT NULL DEFAULT 1,
    trangThai ENUM('CHO_DUYET','DA_DUYET','TU_CHOI','BI_THU_HOI') NOT NULL DEFAULT 'CHO_DUYET',
    nguoiDuyetId BIGINT NULL,                 -- class: nguoiDuyet, quan hệ TaiKhoan 0..1 "xử lý"
    ngayXuLy DATE NULL,
    lyDoTuChoi VARCHAR(500) NULL,
    FOREIGN KEY (toChucId) REFERENCES toChuc(id),
    FOREIGN KEY (nguoiDuyetId) REFERENCES taiKhoan(id)
);

-- 7. taiLieuDangKy
CREATE TABLE taiLieuDangKy (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    yeuCauDangKyId BIGINT NOT NULL,
    tenTaiLieu VARCHAR(255) NOT NULL,
    loaiTaiLieu VARCHAR(100),
    duongDan VARCHAR(500) NOT NULL,
    ngayTaiLen DATE NOT NULL DEFAULT (CURRENT_DATE),
    FOREIGN KEY (yeuCauDangKyId) REFERENCES yeuCauDangKy(id)
);

-- 8. sanPham
CREATE TABLE sanPham (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    maSanPham CHAR(36) NOT NULL UNIQUE,       -- mã định danh duy nhất (ánh xạ blockchain)
    tenSanPham VARCHAR(255) NOT NULL,
    hoatChat VARCHAR(255),
    quyCachDongGoi VARCHAR(255),
    soDangKy VARCHAR(50) NOT NULL UNIQUE,
    nhaSanXuatId BIGINT NOT NULL,             -- quan hệ ToChuc "đăng ký" SanPham
    trangThaiDuyet ENUM('CHO_DUYET','DA_DUYET','TU_CHOI') NOT NULL DEFAULT 'CHO_DUYET',
    FOREIGN KEY (nhaSanXuatId) REFERENCES toChuc(id)
);

-- 9. yeuCauDangKySanPham
CREATE TABLE yeuCauDangKySanPham (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    sanPhamId BIGINT NOT NULL,
    versionHoSo INT NOT NULL DEFAULT 1,
    ngayGui DATE NOT NULL DEFAULT (CURRENT_DATE),
    trangThai ENUM('CHO_DUYET','DA_DUYET','TU_CHOI') NOT NULL DEFAULT 'CHO_DUYET',
    nguoiXuLyId BIGINT NULL,
    ngayXuLy DATE NULL,
    lyDoTuChoi VARCHAR(500) NULL,
    FOREIGN KEY (sanPhamId) REFERENCES sanPham(id),
    FOREIGN KEY (nguoiXuLyId) REFERENCES taiKhoan(id)
);

-- 10. loThuoc
CREATE TABLE loThuoc (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    maLo CHAR(36) NOT NULL UNIQUE,            -- mã định danh duy nhất (ánh xạ blockchain)
    maLoNghiepVu VARCHAR(50) NOT NULL UNIQUE, -- số lô nghiệp vụ (trước đây là cột maLo)
    sanPhamId BIGINT NOT NULL,
    toChucId BIGINT NOT NULL,                 -- tổ chức đăng ký lô (nhà sản xuất)
    soLuong INT NOT NULL,
    ngaySanXuat DATE NOT NULL,
    hanSuDung DATE NOT NULL,
    trangThai ENUM('CREATED','IN_TRANSIT','RECALLED') NOT NULL DEFAULT 'CREATED',
    FOREIGN KEY (sanPhamId) REFERENCES sanPham(id),
    FOREIGN KEY (toChucId) REFERENCES toChuc(id),
    CONSTRAINT chk_lo_soluong CHECK (soLuong > 0),
    CONSTRAINT chk_lo_han CHECK (hanSuDung > ngaySanXuat)
);

-- 11. banLe (mới)
CREATE TABLE banLe (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    maBanLe CHAR(36) NOT NULL UNIQUE,
    toChucId BIGINT NOT NULL,                 -- nhà thuốc thực hiện
    ngayBan DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    soLuong INT NOT NULL,
    trangThai VARCHAR(30) NOT NULL DEFAULT 'PENDING',
    txHash VARCHAR(66) NULL,
    FOREIGN KEY (toChucId) REFERENCES toChuc(id),
    CONSTRAINT chk_banle_soluong CHECK (soLuong > 0)
);

-- 12. donViSanPham
CREATE TABLE donViSanPham (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    maDonVi VARCHAR(50) NOT NULL UNIQUE,
    serialNumber VARCHAR(100) NOT NULL UNIQUE,
    loThuocId BIGINT NOT NULL,
    banLeId BIGINT NULL,                      -- BanLe 1 - 1..* DonViSanPham
    trangThai ENUM('AVAILABLE','DISPENSED') NOT NULL DEFAULT 'AVAILABLE',
    ngayTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ngayBan DATETIME NULL,
    txHash VARCHAR(66) NULL,
    FOREIGN KEY (loThuocId) REFERENCES loThuoc(id),
    FOREIGN KEY (banLeId) REFERENCES banLe(id)
);

-- 13. qrCode 
CREATE TABLE qrCode (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    giaTriQR VARCHAR(255) NOT NULL UNIQUE,
    loaiQR ENUM('BATCH','UNIT') NOT NULL,
    loThuocId BIGINT NULL,                    -- dùng khi loaiQR = 'BATCH' (1 lô - nhiều QR)
    donViSanPhamId BIGINT NULL UNIQUE,        -- dùng khi loaiQR = 'UNIT' (1 đơn vị - 0..1 QR)
    ngayTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trangThai VARCHAR(30) NOT NULL DEFAULT 'ACTIVE',
    FOREIGN KEY (loThuocId) REFERENCES loThuoc(id),
    FOREIGN KEY (donViSanPhamId) REFERENCES donViSanPham(id),
    CONSTRAINT chk_qr_loai CHECK (
        (loaiQR = 'BATCH' AND loThuocId IS NOT NULL AND donViSanPhamId IS NULL) OR
        (loaiQR = 'UNIT'  AND donViSanPhamId IS NOT NULL AND loThuocId IS NULL)
    )
);

-- 14. tonKhoLo
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

-- 15. chuyenGiao
CREATE TABLE chuyenGiao (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    maChuyenGiao CHAR(36) NOT NULL UNIQUE,    -- mã định danh duy nhất (ánh xạ blockchain)
    loThuocId BIGINT NOT NULL,               
    benGuiId BIGINT NOT NULL,                
    benNhanId BIGINT NOT NULL,                
    soLuong INT NOT NULL,
    thoiGianKhoiTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    thoiGianXacNhan DATETIME NULL,
    trangThai ENUM('PENDING','IN_TRANSIT','RECEIVED','REJECTED') NOT NULL DEFAULT 'PENDING',
    txHash VARCHAR(66) NULL,                 
    FOREIGN KEY (loThuocId) REFERENCES loThuoc(id),
    FOREIGN KEY (benGuiId) REFERENCES toChuc(id),
    FOREIGN KEY (benNhanId) REFERENCES toChuc(id),
    CONSTRAINT chk_soluong CHECK (soLuong > 0),
    CONSTRAINT chk_khac_to_chuc CHECK (benGuiId <> benNhanId),
    CONSTRAINT chk_xac_nhan CHECK (trangThai <> 'RECEIVED' OR thoiGianXacNhan IS NOT NULL)
);

-- 16. traCuu 
CREATE TABLE traCuu (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    loThuocId BIGINT NOT NULL,
    maQRHoacSerial VARCHAR(255) NOT NULL,
    thoiGianTraCuu DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    diaChiIP VARCHAR(45),
    FOREIGN KEY (loThuocId) REFERENCES loThuoc(id)
);

-- 17. lichSuLoThuoc 
CREATE TABLE lichSuLoThuoc (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    loThuocId BIGINT NOT NULL,
    thoiGian DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    suKien VARCHAR(100) NOT NULL,
    moTa TEXT,
    txHash VARCHAR(66) NULL,
    FOREIGN KEY (loThuocId) REFERENCES loThuoc(id)
);

-- 18. baoCaoNghiVan
CREATE TABLE baoCaoNghiVan (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    maBaoCao VARCHAR(50) NOT NULL UNIQUE,
    loThuocId BIGINT NOT NULL,
    taiKhoanId BIGINT NULL,                   -- NULL khi là người tiêu dùng
    lyDo TEXT NOT NULL,
    moTa TEXT NULL,
    ngayGui DATE NOT NULL DEFAULT (CURRENT_DATE),
    trangThai ENUM('PENDING','PROCESSING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
    FOREIGN KEY (loThuocId) REFERENCES loThuoc(id),
    FOREIGN KEY (taiKhoanId) REFERENCES taiKhoan(id)
);

-- 19. minhChungBaoCao
CREATE TABLE minhChungBaoCao (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    baoCaoId BIGINT NOT NULL,
    tenFile VARCHAR(255) NOT NULL,
    loaiFile VARCHAR(50),
    duongDan VARCHAR(500) NOT NULL,
    ngayTaiLen DATE NOT NULL DEFAULT (CURRENT_DATE),
    FOREIGN KEY (baoCaoId) REFERENCES baoCaoNghiVan(id)
);

-- 20. ketQuaXuLyBaoCao
CREATE TABLE ketQuaXuLyBaoCao (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    baoCaoId BIGINT NOT NULL UNIQUE,          -- BaoCaoNghiVan 1 - 0..1 KetQuaXuLy
    nguoiXuLyId BIGINT NOT NULL,              
    quyetDinh ENUM('THU_HOI','TU_CHOI') NOT NULL,
    ngayXuLy DATE NOT NULL DEFAULT (CURRENT_DATE),
    lyDoXuLy VARCHAR(500) NULL,               
    ghiChu VARCHAR(500) NULL,
    FOREIGN KEY (baoCaoId) REFERENCES baoCaoNghiVan(id),
    FOREIGN KEY (nguoiXuLyId) REFERENCES taiKhoan(id)
);

-- 21. thuHoiLoHang
CREATE TABLE thuHoiLoHang (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    loThuocId BIGINT NOT NULL,
    ketQuaXuLyBaoCaoId BIGINT NULL UNIQUE,    -- KetQuaXuLy 1 - 0..1 ThuHoi
    lyDo TEXT NOT NULL,
    ngayThuHoi DATE NOT NULL DEFAULT (CURRENT_DATE),
    trangThai VARCHAR(30) NOT NULL DEFAULT 'PENDING',
    txHash VARCHAR(66) NULL,                  
    FOREIGN KEY (loThuocId) REFERENCES loThuoc(id),
    FOREIGN KEY (ketQuaXuLyBaoCaoId) REFERENCES ketQuaXuLyBaoCao(id)
);

-- 22. blockchainIdentity 
CREATE TABLE blockchainIdentity (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    toChucId BIGINT NOT NULL UNIQUE,          -- ToChuc 1 - 0..1 BlockchainIdentity
    network VARCHAR(50) NOT NULL DEFAULT 'Polygon Testnet',
    address VARCHAR(42) NOT NULL UNIQUE,      
    certificateId VARCHAR(100) NULL,
    trangThai ENUM('ACTIVE','INACTIVE','REVOKED') NOT NULL DEFAULT 'ACTIVE',
    FOREIGN KEY (toChucId) REFERENCES toChuc(id)
);

-- 23. blockchainTransaction 
CREATE TABLE blockchainTransaction (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    txHash VARCHAR(66) NOT NULL UNIQUE,
    blockNumber BIGINT,
    eventName VARCHAR(100) NOT NULL,
    businessId VARCHAR(36) NOT NULL,
    thoiGian DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    network VARCHAR(50) NOT NULL DEFAULT 'Polygon Testnet',
    trangThai VARCHAR(30) NOT NULL DEFAULT 'PENDING',
    toChucId BIGINT NOT NULL,                 -- ToChuc "tạo"
    loThuocId BIGINT NOT NULL,                -- LoThuoc "ghi nhận"
    chuyenGiaoId BIGINT NULL,                 -- đối chiếu (0..1)
    donViSanPhamId BIGINT NULL,
    banLeId BIGINT NULL,
    FOREIGN KEY (toChucId) REFERENCES toChuc(id),
    FOREIGN KEY (loThuocId) REFERENCES loThuoc(id),
    FOREIGN KEY (chuyenGiaoId) REFERENCES chuyenGiao(id),
    FOREIGN KEY (donViSanPhamId) REFERENCES donViSanPham(id),
    FOREIGN KEY (banLeId) REFERENCES banLe(id)
);

-- 24. Index tăng tốc truy vấn
CREATE INDEX idx_taikhoan_tochuc ON taiKhoan(toChucId);
CREATE INDEX idx_nhatky_taikhoan ON nhatKyHeThong(taiKhoanId);
CREATE INDEX idx_nhatky_thoigian ON nhatKyHeThong(thoiGian);
CREATE INDEX idx_yeucaudk_tochuc ON yeuCauDangKy(toChucId);
CREATE INDEX idx_yeucaudk_trangthai ON yeuCauDangKy(trangThai);
CREATE INDEX idx_sanpham_nhasanxuat ON sanPham(nhaSanXuatId);
CREATE INDEX idx_yeucausp_sanpham ON yeuCauDangKySanPham(sanPhamId);
CREATE INDEX idx_lothuoc_sanpham ON loThuoc(sanPhamId);
CREATE INDEX idx_lothuoc_tochuc ON loThuoc(toChucId);
CREATE INDEX idx_lothuoc_trangthai ON loThuoc(trangThai);
CREATE INDEX idx_donvi_lothuoc ON donViSanPham(loThuocId);
CREATE INDEX idx_donvi_banle ON donViSanPham(banLeId);
CREATE INDEX idx_donvi_trangthai ON donViSanPham(trangThai);
CREATE INDEX idx_qr_lothuoc ON qrCode(loThuocId);
CREATE INDEX idx_tonkho_tochuc ON tonKhoLo(toChucId);
CREATE INDEX idx_chuyengiao_lothuoc ON chuyenGiao(loThuocId);
CREATE INDEX idx_chuyengiao_bengui ON chuyenGiao(benGuiId);
CREATE INDEX idx_chuyengiao_bennhan ON chuyenGiao(benNhanId);
CREATE INDEX idx_chuyengiao_trangthai ON chuyenGiao(trangThai);
CREATE INDEX idx_banle_tochuc ON banLe(toChucId);
CREATE INDEX idx_tracuu_lothuoc ON traCuu(loThuocId);
CREATE INDEX idx_tracuu_thoigian ON traCuu(thoiGianTraCuu);
CREATE INDEX idx_lichsu_lothuoc ON lichSuLoThuoc(loThuocId);
CREATE INDEX idx_baocao_lothuoc ON baoCaoNghiVan(loThuocId);
CREATE INDEX idx_baocao_trangthai ON baoCaoNghiVan(trangThai);
CREATE INDEX idx_thuhoi_lothuoc ON thuHoiLoHang(loThuocId);
CREATE INDEX idx_bctx_lothuoc ON blockchainTransaction(loThuocId);
CREATE INDEX idx_bctx_tochuc ON blockchainTransaction(toChucId);
CREATE INDEX idx_bctx_businessid ON blockchainTransaction(businessId);

-- 25. Du lieu mau de kiem thu (demo only)
-- Chi import phan nay mot lan tren database moi sau khi tao schema.
-- Mat khau demo chung: Demo@12345; khong dung tai khoan nay tren production.
-- Dia chi vi, txHash va blockNumber ben duoi deu la du lieu gia, khong phai giao dich that.
START TRANSACTION;

INSERT INTO toChuc
    (id, tenToChuc, loaiToChuc, diaChi, soDienThoai, maSoThue, soGiayPhepDuoc, ngayCapGiayPhep, ngayHetHanGiayPhep, coQuanCap, trangThaiDuyet)
VALUES
    (1, 'Cong ty Co phan Duoc pham An Khang (DEMO)', 'NHA_SAN_XUAT', 'Khu cong nghiep Hoa Khanh, Da Nang', '02363888001', '0402190001', 'GP-N1-2025-001', '2025-01-15', '2030-01-14', 'Bo Y te', 'DA_DUYET'),
    (2, 'Cong ty TNHH Phan phoi Minh Chau (DEMO)', 'NHA_PHAN_PHOI', 'Quan Binh Thanh, TP Ho Chi Minh', '02838880002', '0318290002', 'GP-PP-2025-002', '2025-02-10', '2030-02-09', 'So Y te TP Ho Chi Minh', 'DA_DUYET'),
    (3, 'Nha thuoc Tan Tam (DEMO)', 'NHA_THUOC', 'Quan Hai Chau, Da Nang', '02363888003', '0402190003', 'GP-NT-2025-003', '2025-03-01', '2030-02-28', 'So Y te Da Nang', 'DA_DUYET'),
    (4, 'Co quan Quan ly Duoc Da Nang (DEMO)', 'CO_QUAN_QUAN_LY', 'Quan Hai Chau, Da Nang', '02363888004', '0402190004', NULL, NULL, NULL, 'Bo Y te', 'DA_DUYET'),
    (5, 'Cong ty Duoc Hoa Binh (DEMO - cho duyet)', 'NHA_SAN_XUAT', 'Thanh pho Hue', '02343888005', '3302190005', 'GP-N1-2026-005', '2026-05-10', '2031-05-09', 'So Y te Thua Thien Hue', 'CHO_DUYET');

INSERT INTO taiKhoan
    (id, tenDangNhap, matKhau, email, soDienThoai, vaiTro, trangThai, toChucId, ngayTao)
VALUES
    (1, 'admin.demo', '$2y$10$Zi0DJPfM42Uslc39G53WWuVLqd1L2sOOzGTsUWAsC/tpvKFiwje6q', 'admin.demo@example.test', '0900000001', 'QUAN_TRI_VIEN', 'ACTIVE', NULL, '2026-10-01 08:00:00'),
    (2, 'coquan.demo', '$2y$10$Zi0DJPfM42Uslc39G53WWuVLqd1L2sOOzGTsUWAsC/tpvKFiwje6q', 'coquan.demo@example.test', '0900000002', 'CO_QUAN_QUAN_LY', 'ACTIVE', 4, '2026-10-01 08:05:00'),
    (3, 'nsx.demo', '$2y$10$Zi0DJPfM42Uslc39G53WWuVLqd1L2sOOzGTsUWAsC/tpvKFiwje6q', 'nsx.demo@example.test', '0900000003', 'NHA_SAN_XUAT', 'ACTIVE', 1, '2026-10-01 08:10:00'),
    (4, 'npp.demo', '$2y$10$Zi0DJPfM42Uslc39G53WWuVLqd1L2sOOzGTsUWAsC/tpvKFiwje6q', 'npp.demo@example.test', '0900000004', 'NHA_PHAN_PHOI', 'ACTIVE', 2, '2026-10-01 08:15:00'),
    (5, 'nhathuoc.demo', '$2y$10$Zi0DJPfM42Uslc39G53WWuVLqd1L2sOOzGTsUWAsC/tpvKFiwje6q', 'nhathuoc.demo@example.test', '0900000005', 'NHA_THUOC', 'ACTIVE', 3, '2026-10-01 08:20:00'),
    (6, 'nsx.choduyet.demo', '$2y$10$Zi0DJPfM42Uslc39G53WWuVLqd1L2sOOzGTsUWAsC/tpvKFiwje6q', 'nsx.choduyet.demo@example.test', '0900000006', 'NHA_SAN_XUAT', 'PENDING', 5, '2026-10-05 09:00:00');

INSERT INTO quyenHan (id, tenQuyen, moTa)
VALUES
    (1, 'toChucXem', 'Xem thong tin to chuc'),
    (2, 'toChucDuyet', 'Phe duyet ho so to chuc'),
    (3, 'sanPhamQuanLy', 'Quan ly ho so san pham'),
    (4, 'loThuocQuanLy', 'Quan ly lo thuoc'),
    (5, 'chuyenGiaoQuanLy', 'Tao va xu ly chuyen giao'),
    (6, 'baoCaoXuLy', 'Tiep nhan va xu ly bao cao nghi van');

INSERT INTO taiKhoan_quyenHan (taiKhoanId, quyenHanId)
VALUES
    (1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6),
    (2, 1), (2, 2), (2, 3), (2, 6),
    (3, 1), (3, 3), (3, 4), (3, 5),
    (4, 1), (4, 5),
    (5, 1), (5, 5);

INSERT INTO nhatKyHeThong (id, taiKhoanId, hanhDong, thoiGian, noiDung, diaChiIP)
VALUES
    (1, 1, 'TAO_DU_LIEU_DEMO', '2026-10-01 08:30:00', 'Khoi tao bo du lieu demo cho kiem thu local.', '192.0.2.10'),
    (2, 2, 'PHE_DUYET_TO_CHUC', '2026-10-01 09:00:00', 'Phe duyet ho so cac to chuc demo.', '192.0.2.11'),
    (3, 3, 'DANG_KY_LO', '2026-10-02 08:30:00', 'Dang ky lo demo AK-PARA-2026-001.', '192.0.2.12'),
    (4, 4, 'XAC_NHAN_CHUYEN_GIAO', '2026-10-03 10:00:00', 'Nhan mot phan lo AK-PARA-2026-001.', '192.0.2.13'),
    (5, 5, 'GHI_NHAN_BAN_LE', '2026-10-05 16:30:00', 'Ban 2 don vi thuoc demo.', '192.0.2.14');

INSERT INTO yeuCauDangKy
    (id, toChucId, ngayGui, versionHoSo, trangThai, nguoiDuyetId, ngayXuLy, lyDoTuChoi)
VALUES
    (1, 1, '2026-09-01', 1, 'DA_DUYET', 2, '2026-09-05', NULL),
    (2, 2, '2026-09-02', 1, 'DA_DUYET', 2, '2026-09-06', NULL),
    (3, 3, '2026-09-03', 1, 'DA_DUYET', 2, '2026-09-07', NULL),
    (4, 4, '2026-09-01', 1, 'DA_DUYET', 1, '2026-09-04', NULL),
    (5, 5, '2026-10-05', 1, 'CHO_DUYET', NULL, NULL, NULL);

INSERT INTO taiLieuDangKy (id, yeuCauDangKyId, tenTaiLieu, loaiTaiLieu, duongDan, ngayTaiLen)
VALUES
    (1, 1, 'Giay chung nhan du dieu kien kinh doanh duoc', 'GIAY_PHEP', 'demo/ho-so/an-khang/giay-phep.pdf', '2026-09-01'),
    (2, 1, 'Giay chung nhan GMP', 'GMP', 'demo/ho-so/an-khang/gmp.pdf', '2026-09-01'),
    (3, 2, 'Giay chung nhan du dieu kien phan phoi', 'GIAY_PHEP', 'demo/ho-so/minh-chau/giay-phep.pdf', '2026-09-02'),
    (4, 3, 'Giay chung nhan du dieu kien nha thuoc', 'GIAY_PHEP', 'demo/ho-so/tan-tam/giay-phep.pdf', '2026-09-03'),
    (5, 5, 'Ho so dang ky doanh nghiep', 'HO_SO_DOANH_NGHIEP', 'demo/ho-so/hoa-binh/dang-ky.pdf', '2026-10-05');

INSERT INTO sanPham
    (id, maSanPham, tenSanPham, hoatChat, quyCachDongGoi, soDangKy, nhaSanXuatId, trangThaiDuyet)
VALUES
    (1, '11111111-1111-4111-8111-111111111111', 'Paracetamol An Khang 500 mg (DEMO)', 'Paracetamol 500 mg', 'Hop 10 vi x 10 vien', 'VD-DEMO-2026-001', 1, 'DA_DUYET'),
    (2, '22222222-2222-4222-8222-222222222222', 'Amoxicillin An Khang 500 mg (DEMO)', 'Amoxicillin 500 mg', 'Hop 10 vi x 10 vien', 'VD-DEMO-2026-002', 1, 'DA_DUYET'),
    (3, '33333333-3333-4333-8333-333333333333', 'Vitamin C Hoa Binh 500 mg (DEMO)', 'Acid ascorbic 500 mg', 'Hop 10 vi x 10 vien', 'VD-DEMO-2026-003', 5, 'CHO_DUYET');

-- 50 san pham demo bo sung cua nha san xuat An Khang.
-- Ten/so dang ky deu danh dau DEMO; khong phai thong tin cap phep that.
INSERT INTO sanPham
    (id, maSanPham, tenSanPham, hoatChat, quyCachDongGoi, soDangKy, nhaSanXuatId, trangThaiDuyet)
VALUES
    (4, '40000000-0000-4000-8000-000000000004', 'Loratadine An Khang 10 mg (DEMO)', 'Loratadine 10 mg', 'Hop 3 vi x 10 vien', 'VD-DEMO-2026-004', 1, 'DA_DUYET'),
    (5, '40000000-0000-4000-8000-000000000005', 'Cetirizine An Khang 10 mg (DEMO)', 'Cetirizine dihydrochloride 10 mg', 'Hop 3 vi x 10 vien', 'VD-DEMO-2026-005', 1, 'DA_DUYET'),
    (6, '40000000-0000-4000-8000-000000000006', 'Omeprazole An Khang 20 mg (DEMO)', 'Omeprazole 20 mg', 'Hop 3 vi x 10 vien nang', 'VD-DEMO-2026-006', 1, 'DA_DUYET'),
    (7, '40000000-0000-4000-8000-000000000007', 'Esomeprazole An Khang 20 mg (DEMO)', 'Esomeprazole 20 mg', 'Hop 3 vi x 10 vien nang', 'VD-DEMO-2026-007', 1, 'DA_DUYET'),
    (8, '40000000-0000-4000-8000-000000000008', 'Metformin An Khang 500 mg (DEMO)', 'Metformin hydrochloride 500 mg', 'Hop 6 vi x 10 vien', 'VD-DEMO-2026-008', 1, 'DA_DUYET'),
    (9, '40000000-0000-4000-8000-000000000009', 'Amlodipine An Khang 5 mg (DEMO)', 'Amlodipine 5 mg', 'Hop 3 vi x 10 vien', 'VD-DEMO-2026-009', 1, 'DA_DUYET'),
    (10, '40000000-0000-4000-8000-000000000010', 'Losartan An Khang 50 mg (DEMO)', 'Losartan potassium 50 mg', 'Hop 3 vi x 10 vien', 'VD-DEMO-2026-010', 1, 'DA_DUYET'),
    (11, '40000000-0000-4000-8000-000000000011', 'Atorvastatin An Khang 10 mg (DEMO)', 'Atorvastatin calcium 10 mg', 'Hop 3 vi x 10 vien', 'VD-DEMO-2026-011', 1, 'DA_DUYET'),
    (12, '40000000-0000-4000-8000-000000000012', 'Simvastatin An Khang 10 mg (DEMO)', 'Simvastatin 10 mg', 'Hop 3 vi x 10 vien', 'VD-DEMO-2026-012', 1, 'DA_DUYET'),
    (13, '40000000-0000-4000-8000-000000000013', 'Captopril An Khang 25 mg (DEMO)', 'Captopril 25 mg', 'Hop 10 vi x 10 vien', 'VD-DEMO-2026-013', 1, 'DA_DUYET'),
    (14, '40000000-0000-4000-8000-000000000014', 'Enalapril An Khang 5 mg (DEMO)', 'Enalapril maleate 5 mg', 'Hop 3 vi x 10 vien', 'VD-DEMO-2026-014', 1, 'DA_DUYET'),
    (15, '40000000-0000-4000-8000-000000000015', 'Bisoprolol An Khang 2.5 mg (DEMO)', 'Bisoprolol fumarate 2.5 mg', 'Hop 3 vi x 10 vien', 'VD-DEMO-2026-015', 1, 'DA_DUYET'),
    (16, '40000000-0000-4000-8000-000000000016', 'Hydrochlorothiazide An Khang 25 mg (DEMO)', 'Hydrochlorothiazide 25 mg', 'Hop 3 vi x 10 vien', 'VD-DEMO-2026-016', 1, 'DA_DUYET'),
    (17, '40000000-0000-4000-8000-000000000017', 'Gliclazide An Khang 30 mg (DEMO)', 'Gliclazide 30 mg', 'Hop 3 vi x 10 vien', 'VD-DEMO-2026-017', 1, 'DA_DUYET'),
    (18, '40000000-0000-4000-8000-000000000018', 'Glimepiride An Khang 2 mg (DEMO)', 'Glimepiride 2 mg', 'Hop 3 vi x 10 vien', 'VD-DEMO-2026-018', 1, 'DA_DUYET'),
    (19, '40000000-0000-4000-8000-000000000019', 'Dapagliflozin An Khang 10 mg (DEMO)', 'Dapagliflozin 10 mg', 'Hop 3 vi x 10 vien', 'VD-DEMO-2026-019', 1, 'DA_DUYET'),
    (20, '40000000-0000-4000-8000-000000000020', 'Aspirin An Khang 81 mg (DEMO)', 'Acetylsalicylic acid 81 mg', 'Hop 3 vi x 10 vien', 'VD-DEMO-2026-020', 1, 'DA_DUYET'),
    (21, '40000000-0000-4000-8000-000000000021', 'Clopidogrel An Khang 75 mg (DEMO)', 'Clopidogrel 75 mg', 'Hop 3 vi x 10 vien', 'VD-DEMO-2026-021', 1, 'DA_DUYET'),
    (22, '40000000-0000-4000-8000-000000000022', 'Diclofenac An Khang 50 mg (DEMO)', 'Diclofenac sodium 50 mg', 'Hop 5 vi x 10 vien', 'VD-DEMO-2026-022', 1, 'DA_DUYET'),
    (23, '40000000-0000-4000-8000-000000000023', 'Meloxicam An Khang 7.5 mg (DEMO)', 'Meloxicam 7.5 mg', 'Hop 3 vi x 10 vien', 'VD-DEMO-2026-023', 1, 'DA_DUYET'),
    (24, '40000000-0000-4000-8000-000000000024', 'Celecoxib An Khang 100 mg (DEMO)', 'Celecoxib 100 mg', 'Hop 3 vi x 10 vien nang', 'VD-DEMO-2026-024', 1, 'DA_DUYET'),
    (25, '40000000-0000-4000-8000-000000000025', 'Dexamethasone An Khang 0.5 mg (DEMO)', 'Dexamethasone 0.5 mg', 'Hop 10 vi x 10 vien', 'VD-DEMO-2026-025', 1, 'DA_DUYET'),
    (26, '40000000-0000-4000-8000-000000000026', 'Prednisolone An Khang 5 mg (DEMO)', 'Prednisolone 5 mg', 'Hop 10 vi x 10 vien', 'VD-DEMO-2026-026', 1, 'DA_DUYET'),
    (27, '40000000-0000-4000-8000-000000000027', 'Azithromycin An Khang 250 mg (DEMO)', 'Azithromycin 250 mg', 'Hop 1 vi x 6 vien', 'VD-DEMO-2026-027', 1, 'DA_DUYET'),
    (28, '40000000-0000-4000-8000-000000000028', 'Cefixime An Khang 200 mg (DEMO)', 'Cefixime 200 mg', 'Hop 2 vi x 10 vien', 'VD-DEMO-2026-028', 1, 'DA_DUYET'),
    (29, '40000000-0000-4000-8000-000000000029', 'Cefuroxime An Khang 250 mg (DEMO)', 'Cefuroxime axetil 250 mg', 'Hop 2 vi x 10 vien', 'VD-DEMO-2026-029', 1, 'DA_DUYET'),
    (30, '40000000-0000-4000-8000-000000000030', 'Clarithromycin An Khang 500 mg (DEMO)', 'Clarithromycin 500 mg', 'Hop 2 vi x 7 vien', 'VD-DEMO-2026-030', 1, 'DA_DUYET'),
    (31, '40000000-0000-4000-8000-000000000031', 'Doxycycline An Khang 100 mg (DEMO)', 'Doxycycline hyclate 100 mg', 'Hop 10 vi x 10 vien', 'VD-DEMO-2026-031', 1, 'DA_DUYET'),
    (32, '40000000-0000-4000-8000-000000000032', 'Metronidazole An Khang 250 mg (DEMO)', 'Metronidazole 250 mg', 'Hop 10 vi x 10 vien', 'VD-DEMO-2026-032', 1, 'DA_DUYET'),
    (33, '40000000-0000-4000-8000-000000000033', 'Fluconazole An Khang 150 mg (DEMO)', 'Fluconazole 150 mg', 'Hop 1 vi x 1 vien nang', 'VD-DEMO-2026-033', 1, 'DA_DUYET'),
    (34, '40000000-0000-4000-8000-000000000034', 'Acyclovir An Khang 200 mg (DEMO)', 'Acyclovir 200 mg', 'Hop 5 vi x 5 vien', 'VD-DEMO-2026-034', 1, 'DA_DUYET'),
    (35, '40000000-0000-4000-8000-000000000035', 'Ambroxol An Khang 30 mg (DEMO)', 'Ambroxol hydrochloride 30 mg', 'Hop 2 vi x 10 vien', 'VD-DEMO-2026-035', 1, 'DA_DUYET'),
    (36, '40000000-0000-4000-8000-000000000036', 'Acetylcysteine An Khang 200 mg (DEMO)', 'Acetylcysteine 200 mg', 'Hop 30 goi bot', 'VD-DEMO-2026-036', 1, 'DA_DUYET'),
    (37, '40000000-0000-4000-8000-000000000037', 'Montelukast An Khang 10 mg (DEMO)', 'Montelukast 10 mg', 'Hop 3 vi x 10 vien', 'VD-DEMO-2026-037', 1, 'DA_DUYET'),
    (38, '40000000-0000-4000-8000-000000000038', 'Salbutamol An Khang 2 mg (DEMO)', 'Salbutamol sulfate 2 mg', 'Hop 10 vi x 10 vien', 'VD-DEMO-2026-038', 1, 'DA_DUYET'),
    (39, '40000000-0000-4000-8000-000000000039', 'Domperidone An Khang 10 mg (DEMO)', 'Domperidone 10 mg', 'Hop 3 vi x 10 vien', 'VD-DEMO-2026-039', 1, 'DA_DUYET'),
    (40, '40000000-0000-4000-8000-000000000040', 'Ondansetron An Khang 4 mg (DEMO)', 'Ondansetron 4 mg', 'Hop 2 vi x 10 vien', 'VD-DEMO-2026-040', 1, 'DA_DUYET'),
    (41, '40000000-0000-4000-8000-000000000041', 'Loperamide An Khang 2 mg (DEMO)', 'Loperamide hydrochloride 2 mg', 'Hop 2 vi x 10 vien', 'VD-DEMO-2026-041', 1, 'DA_DUYET'),
    (42, '40000000-0000-4000-8000-000000000042', 'Racecadotril An Khang 100 mg (DEMO)', 'Racecadotril 100 mg', 'Hop 2 vi x 10 vien nang', 'VD-DEMO-2026-042', 1, 'DA_DUYET'),
    (43, '40000000-0000-4000-8000-000000000043', 'Ferrous fumarate An Khang 200 mg (DEMO)', 'Ferrous fumarate 200 mg', 'Hop 5 vi x 10 vien', 'VD-DEMO-2026-043', 1, 'DA_DUYET'),
    (44, '40000000-0000-4000-8000-000000000044', 'Folic acid An Khang 5 mg (DEMO)', 'Folic acid 5 mg', 'Hop 10 vi x 10 vien', 'VD-DEMO-2026-044', 1, 'DA_DUYET'),
    (45, '40000000-0000-4000-8000-000000000045', 'Calcium carbonate An Khang 500 mg (DEMO)', 'Calcium carbonate 500 mg', 'Hop 5 vi x 10 vien', 'VD-DEMO-2026-045', 1, 'DA_DUYET'),
    (46, '40000000-0000-4000-8000-000000000046', 'Zinc gluconate An Khang 10 mg (DEMO)', 'Zinc gluconate tuong duong 10 mg kem', 'Hop 3 vi x 10 vien', 'VD-DEMO-2026-046', 1, 'DA_DUYET'),
    (47, '40000000-0000-4000-8000-000000000047', 'Thiamine An Khang 100 mg (DEMO)', 'Thiamine hydrochloride 100 mg', 'Hop 10 vi x 10 vien', 'VD-DEMO-2026-047', 1, 'DA_DUYET'),
    (48, '40000000-0000-4000-8000-000000000048', 'Pyridoxine An Khang 50 mg (DEMO)', 'Pyridoxine hydrochloride 50 mg', 'Hop 10 vi x 10 vien', 'VD-DEMO-2026-048', 1, 'DA_DUYET'),
    (49, '40000000-0000-4000-8000-000000000049', 'Cholecalciferol An Khang 1000 IU (DEMO)', 'Cholecalciferol 1000 IU', 'Hop 3 vi x 10 vien nang mem', 'VD-DEMO-2026-049', 1, 'DA_DUYET'),
    (50, '40000000-0000-4000-8000-000000000050', 'Oral rehydration salts An Khang (DEMO)', 'Natri clorid, kali clorid, natri citrat, glucose', 'Hop 20 goi bot pha dung dich', 'VD-DEMO-2026-050', 1, 'DA_DUYET'),
    (51, '40000000-0000-4000-8000-000000000051', 'Cyanocobalamin An Khang 500 mcg (DEMO)', 'Cyanocobalamin 500 mcg', 'Hop 3 vi x 10 vien', 'VD-DEMO-2026-051', 1, 'DA_DUYET'),
    (52, '40000000-0000-4000-8000-000000000052', 'Levofloxacin An Khang 500 mg (DEMO)', 'Levofloxacin 500 mg', 'Hop 1 vi x 10 vien', 'VD-DEMO-2026-052', 1, 'DA_DUYET'),
    (53, '40000000-0000-4000-8000-000000000053', 'Cefuroxime An Khang 500 mg (DEMO)', 'Cefuroxime axetil 500 mg', 'Hop 2 vi x 10 vien', 'VD-DEMO-2026-053', 1, 'DA_DUYET');

INSERT INTO yeuCauDangKySanPham
    (id, sanPhamId, versionHoSo, ngayGui, trangThai, nguoiXuLyId, ngayXuLy, lyDoTuChoi)
VALUES
    (1, 1, 1, '2026-09-08', 'DA_DUYET', 2, '2026-09-15', NULL),
    (2, 2, 1, '2026-09-09', 'DA_DUYET', 2, '2026-09-16', NULL),
    (3, 3, 1, '2026-10-05', 'CHO_DUYET', NULL, NULL, NULL);

INSERT INTO yeuCauDangKySanPham
    (id, sanPhamId, versionHoSo, ngayGui, trangThai, nguoiXuLyId, ngayXuLy, lyDoTuChoi)
VALUES
    (4, 4, 1, '2026-09-17', 'DA_DUYET', 2, '2026-09-18', NULL),
    (5, 5, 1, '2026-09-17', 'DA_DUYET', 2, '2026-09-18', NULL),
    (6, 6, 1, '2026-09-18', 'DA_DUYET', 2, '2026-09-19', NULL),
    (7, 7, 1, '2026-09-18', 'DA_DUYET', 2, '2026-09-19', NULL),
    (8, 8, 1, '2026-09-19', 'DA_DUYET', 2, '2026-09-20', NULL),
    (9, 9, 1, '2026-09-19', 'DA_DUYET', 2, '2026-09-20', NULL),
    (10, 10, 1, '2026-09-20', 'DA_DUYET', 2, '2026-09-21', NULL),
    (11, 11, 1, '2026-09-20', 'DA_DUYET', 2, '2026-09-21', NULL),
    (12, 12, 1, '2026-09-21', 'DA_DUYET', 2, '2026-09-22', NULL),
    (13, 13, 1, '2026-09-21', 'DA_DUYET', 2, '2026-09-22', NULL),
    (14, 14, 1, '2026-09-22', 'DA_DUYET', 2, '2026-09-23', NULL),
    (15, 15, 1, '2026-09-22', 'DA_DUYET', 2, '2026-09-23', NULL),
    (16, 16, 1, '2026-09-23', 'DA_DUYET', 2, '2026-09-24', NULL),
    (17, 17, 1, '2026-09-23', 'DA_DUYET', 2, '2026-09-24', NULL),
    (18, 18, 1, '2026-09-24', 'DA_DUYET', 2, '2026-09-25', NULL),
    (19, 19, 1, '2026-09-24', 'DA_DUYET', 2, '2026-09-25', NULL),
    (20, 20, 1, '2026-09-25', 'DA_DUYET', 2, '2026-09-26', NULL),
    (21, 21, 1, '2026-09-25', 'DA_DUYET', 2, '2026-09-26', NULL),
    (22, 22, 1, '2026-09-26', 'DA_DUYET', 2, '2026-09-27', NULL),
    (23, 23, 1, '2026-09-26', 'DA_DUYET', 2, '2026-09-27', NULL),
    (24, 24, 1, '2026-09-27', 'DA_DUYET', 2, '2026-09-28', NULL),
    (25, 25, 1, '2026-09-27', 'DA_DUYET', 2, '2026-09-28', NULL),
    (26, 26, 1, '2026-09-28', 'DA_DUYET', 2, '2026-09-29', NULL),
    (27, 27, 1, '2026-09-28', 'DA_DUYET', 2, '2026-09-29', NULL),
    (28, 28, 1, '2026-09-29', 'DA_DUYET', 2, '2026-09-30', NULL),
    (29, 29, 1, '2026-09-29', 'DA_DUYET', 2, '2026-09-30', NULL),
    (30, 30, 1, '2026-09-30', 'DA_DUYET', 2, '2026-10-01', NULL),
    (31, 31, 1, '2026-09-30', 'DA_DUYET', 2, '2026-10-01', NULL),
    (32, 32, 1, '2026-10-01', 'DA_DUYET', 2, '2026-10-02', NULL),
    (33, 33, 1, '2026-10-01', 'DA_DUYET', 2, '2026-10-02', NULL),
    (34, 34, 1, '2026-10-02', 'DA_DUYET', 2, '2026-10-03', NULL),
    (35, 35, 1, '2026-10-02', 'DA_DUYET', 2, '2026-10-03', NULL),
    (36, 36, 1, '2026-10-03', 'DA_DUYET', 2, '2026-10-04', NULL),
    (37, 37, 1, '2026-10-03', 'DA_DUYET', 2, '2026-10-04', NULL),
    (38, 38, 1, '2026-10-04', 'DA_DUYET', 2, '2026-10-05', NULL),
    (39, 39, 1, '2026-10-04', 'DA_DUYET', 2, '2026-10-05', NULL),
    (40, 40, 1, '2026-10-05', 'DA_DUYET', 2, '2026-10-06', NULL),
    (41, 41, 1, '2026-10-05', 'DA_DUYET', 2, '2026-10-06', NULL),
    (42, 42, 1, '2026-10-06', 'DA_DUYET', 2, '2026-10-06', NULL),
    (43, 43, 1, '2026-10-06', 'DA_DUYET', 2, '2026-10-06', NULL),
    (44, 44, 1, '2026-10-06', 'DA_DUYET', 2, '2026-10-06', NULL),
    (45, 45, 1, '2026-10-06', 'DA_DUYET', 2, '2026-10-06', NULL),
    (46, 46, 1, '2026-10-06', 'DA_DUYET', 2, '2026-10-06', NULL),
    (47, 47, 1, '2026-10-06', 'DA_DUYET', 2, '2026-10-06', NULL),
    (48, 48, 1, '2026-10-06', 'DA_DUYET', 2, '2026-10-06', NULL),
    (49, 49, 1, '2026-10-06', 'DA_DUYET', 2, '2026-10-06', NULL),
    (50, 50, 1, '2026-10-06', 'DA_DUYET', 2, '2026-10-06', NULL),
    (51, 51, 1, '2026-10-06', 'DA_DUYET', 2, '2026-10-06', NULL),
    (52, 52, 1, '2026-10-06', 'DA_DUYET', 2, '2026-10-06', NULL),
    (53, 53, 1, '2026-10-06', 'DA_DUYET', 2, '2026-10-06', NULL);

INSERT INTO loThuoc
    (id, maLo, maLoNghiepVu, sanPhamId, toChucId, soLuong, ngaySanXuat, hanSuDung, trangThai)
VALUES
    (1, 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'AK-PARA-2026-001', 1, 1, 20, '2026-08-01', '2028-07-31', 'CREATED'),
    (2, 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'AK-AMOX-2026-002', 2, 1, 6, '2026-09-01', '2028-08-31', 'RECALLED');

INSERT INTO banLe
    (id, maBanLe, toChucId, ngayBan, soLuong, trangThai, txHash)
VALUES
    (1, 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', 3, '2026-10-05 16:30:00', 2, 'CONFIRMED', '0x5555555555555555555555555555555555555555555555555555555555555555');

INSERT INTO donViSanPham
    (id, maDonVi, serialNumber, loThuocId, banLeId, trangThai, ngayTao, ngayBan, txHash)
VALUES
    (1, 'AK-PARA-2026-001-000001', 'AK26PARA00000001', 1, 1, 'DISPENSED', '2026-10-02 08:30:00', '2026-10-05 16:30:00', '0x5555555555555555555555555555555555555555555555555555555555555555'),
    (2, 'AK-PARA-2026-001-000002', 'AK26PARA00000002', 1, 1, 'DISPENSED', '2026-10-02 08:30:00', '2026-10-05 16:30:00', '0x5555555555555555555555555555555555555555555555555555555555555555'),
    (3, 'AK-PARA-2026-001-000003', 'AK26PARA00000003', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL, NULL),
    (4, 'AK-PARA-2026-001-000004', 'AK26PARA00000004', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL, NULL),
    (5, 'AK-PARA-2026-001-000005', 'AK26PARA00000005', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL, NULL),
    (6, 'AK-PARA-2026-001-000006', 'AK26PARA00000006', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL, NULL),
    (7, 'AK-PARA-2026-001-000007', 'AK26PARA00000007', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL, NULL),
    (8, 'AK-PARA-2026-001-000008', 'AK26PARA00000008', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL, NULL),
    (9, 'AK-PARA-2026-001-000009', 'AK26PARA00000009', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL, NULL),
    (10, 'AK-PARA-2026-001-000010', 'AK26PARA00000010', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL, NULL),
    (11, 'AK-PARA-2026-001-000011', 'AK26PARA00000011', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL, NULL),
    (12, 'AK-PARA-2026-001-000012', 'AK26PARA00000012', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL, NULL),
    (13, 'AK-PARA-2026-001-000013', 'AK26PARA00000013', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL, NULL),
    (14, 'AK-PARA-2026-001-000014', 'AK26PARA00000014', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL, NULL),
    (15, 'AK-PARA-2026-001-000015', 'AK26PARA00000015', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL, NULL),
    (16, 'AK-PARA-2026-001-000016', 'AK26PARA00000016', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL, NULL),
    (17, 'AK-PARA-2026-001-000017', 'AK26PARA00000017', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL, NULL),
    (18, 'AK-PARA-2026-001-000018', 'AK26PARA00000018', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL, NULL),
    (19, 'AK-PARA-2026-001-000019', 'AK26PARA00000019', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL, NULL),
    (20, 'AK-PARA-2026-001-000020', 'AK26PARA00000020', 1, NULL, 'AVAILABLE', '2026-10-02 08:30:00', NULL, NULL),
    (21, 'AK-AMOX-2026-002-000001', 'AK26AMOX00000001', 2, NULL, 'AVAILABLE', '2026-10-02 09:00:00', NULL, NULL),
    (22, 'AK-AMOX-2026-002-000002', 'AK26AMOX00000002', 2, NULL, 'AVAILABLE', '2026-10-02 09:00:00', NULL, NULL),
    (23, 'AK-AMOX-2026-002-000003', 'AK26AMOX00000003', 2, NULL, 'AVAILABLE', '2026-10-02 09:00:00', NULL, NULL),
    (24, 'AK-AMOX-2026-002-000004', 'AK26AMOX00000004', 2, NULL, 'AVAILABLE', '2026-10-02 09:00:00', NULL, NULL),
    (25, 'AK-AMOX-2026-002-000005', 'AK26AMOX00000005', 2, NULL, 'AVAILABLE', '2026-10-02 09:00:00', NULL, NULL),
    (26, 'AK-AMOX-2026-002-000006', 'AK26AMOX00000006', 2, NULL, 'AVAILABLE', '2026-10-02 09:00:00', NULL, NULL);

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
    (28, 'QR-UNIT-AK26AMOX00000006', 'UNIT', NULL, 26, '2026-10-02 09:01:00', 'ACTIVE');

INSERT INTO tonKhoLo (id, loThuocId, toChucId, soLuong, ngayCapNhat)
VALUES
    (1, 1, 1, 10, '2026-10-05 10:00:00'),
    (2, 1, 2, 5, '2026-10-04 11:00:00'),
    (3, 1, 3, 3, '2026-10-05 16:30:00'),
    (4, 2, 1, 6, '2026-10-05 17:00:00');

INSERT INTO chuyenGiao
    (id, maChuyenGiao, loThuocId, benGuiId, benNhanId, soLuong, thoiGianKhoiTao, thoiGianXacNhan, trangThai, txHash)
VALUES
    (1, 'dddddddd-dddd-4ddd-8ddd-dddddddddddd', 1, 1, 2, 10, '2026-10-02 10:00:00', '2026-10-03 10:00:00', 'RECEIVED', '0x2222222222222222222222222222222222222222222222222222222222222222'),
    (2, 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', 1, 2, 3, 5, '2026-10-03 11:00:00', '2026-10-04 11:00:00', 'RECEIVED', '0x3333333333333333333333333333333333333333333333333333333333333333'),
    (3, 'ffffffff-ffff-4fff-8fff-ffffffffffff', 1, 1, 2, 2, '2026-10-05 17:00:00', NULL, 'PENDING', '0x6666666666666666666666666666666666666666666666666666666666666666');

INSERT INTO traCuu (id, loThuocId, maQRHoacSerial, thoiGianTraCuu, diaChiIP)
VALUES
    (1, 1, 'QR-UNIT-AK26PARA00000003', '2026-10-05 12:00:00', '192.0.2.20'),
    (2, 2, 'QR-BATCH-AK-AMOX-2026-002', '2026-10-05 17:30:00', '192.0.2.21');

INSERT INTO lichSuLoThuoc (id, loThuocId, thoiGian, suKien, moTa, txHash)
VALUES
    (1, 1, '2026-10-02 08:30:00', 'DANG_KY_LO', 'Nha san xuat tao lo AK-PARA-2026-001 gom 20 don vi.', '0x1111111111111111111111111111111111111111111111111111111111111111'),
    (2, 1, '2026-10-03 10:00:00', 'XAC_NHAN_CHUYEN_GIAO', 'Nha phan phoi Minh Chau xac nhan nhan 10 don vi.', '0x2222222222222222222222222222222222222222222222222222222222222222'),
    (3, 1, '2026-10-04 11:00:00', 'XAC_NHAN_CHUYEN_GIAO', 'Nha thuoc Tan Tam xac nhan nhan 5 don vi.', '0x3333333333333333333333333333333333333333333333333333333333333333'),
    (4, 1, '2026-10-05 16:30:00', 'BAN_LE', 'Nha thuoc ban 2 don vi cho khach hang.', '0x5555555555555555555555555555555555555555555555555555555555555555'),
    (5, 2, '2026-10-05 17:00:00', 'THU_HOI_LO', 'Co quan quan ly thu hoi lo do phat hien van de chat luong.', '0x4444444444444444444444444444444444444444444444444444444444444444'),
    (6, 2, '2026-10-02 09:00:00', 'DANG_KY_LO', 'Nha san xuat tao lo AK-AMOX-2026-002 gom 6 don vi.', '0x7777777777777777777777777777777777777777777777777777777777777777');

INSERT INTO baoCaoNghiVan
    (id, maBaoCao, loThuocId, taiKhoanId, lyDo, moTa, ngayGui, trangThai)
VALUES
    (1, 'BC-DEMO-2026-0001', 2, NULL, 'Nghi ngo chat luong lo thuoc', 'Bao cao an danh: phat hien vien thuoc co mau sac khong dong nhat.', '2026-10-05', 'APPROVED'),
    (2, 'BC-DEMO-2026-0002', 1, 5, 'Niem phong thung hang co dau hieu bi rach', 'Bao cao cua nha thuoc, dang cho co quan quan ly xem xet.', '2026-10-06', 'PENDING');

INSERT INTO minhChungBaoCao (id, baoCaoId, tenFile, loaiFile, duongDan, ngayTaiLen)
VALUES
    (1, 1, 'anh-mau-vien-thuoc-demo.jpg', 'image/jpeg', 'demo/bao-cao/BC-DEMO-2026-0001/anh-mau.jpg', '2026-10-05'),
    (2, 2, 'anh-thung-hang-demo.jpg', 'image/jpeg', 'demo/bao-cao/BC-DEMO-2026-0002/anh-thung.jpg', '2026-10-06');

INSERT INTO ketQuaXuLyBaoCao
    (id, baoCaoId, nguoiXuLyId, quyetDinh, ngayXuLy, lyDoXuLy, ghiChu)
VALUES
    (1, 1, 2, 'THU_HOI', '2026-10-05', 'Ket qua kiem tra mau khong dat yeu cau.', 'Du lieu minh hoa; khong phai ket luan kiem dinh that.');

INSERT INTO thuHoiLoHang
    (id, loThuocId, ketQuaXuLyBaoCaoId, lyDo, ngayThuHoi, trangThai, txHash)
VALUES
    (1, 2, 1, 'Thu hoi demo theo ket qua xu ly BC-DEMO-2026-0001.', '2026-10-05', 'COMPLETED', '0x4444444444444444444444444444444444444444444444444444444444444444');

INSERT INTO blockchainIdentity
    (id, toChucId, network, address, certificateId, trangThai)
VALUES
    (1, 1, 'Polygon Testnet', '0x1111111111111111111111111111111111111111', 'DEMO-CERT-NSX-001', 'ACTIVE'),
    (2, 2, 'Polygon Testnet', '0x2222222222222222222222222222222222222222', 'DEMO-CERT-NPP-001', 'ACTIVE'),
    (3, 3, 'Polygon Testnet', '0x3333333333333333333333333333333333333333', 'DEMO-CERT-NT-001', 'ACTIVE'),
    (4, 4, 'Polygon Testnet', '0x4444444444444444444444444444444444444444', 'DEMO-CERT-CQ-001', 'ACTIVE');

INSERT INTO blockchainTransaction
    (id, txHash, blockNumber, eventName, businessId, thoiGian, network, trangThai, toChucId, loThuocId, chuyenGiaoId, donViSanPhamId, banLeId)
VALUES
    (1, '0x1111111111111111111111111111111111111111111111111111111111111111', 100001, 'BatchRegistered', 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', '2026-10-02 08:30:00', 'Polygon Testnet', 'CONFIRMED', 1, 1, NULL, NULL, NULL),
    (2, '0x2222222222222222222222222222222222222222222222222222222222222222', 100120, 'TransferReceived', 'dddddddd-dddd-4ddd-8ddd-dddddddddddd', '2026-10-03 10:00:00', 'Polygon Testnet', 'CONFIRMED', 2, 1, 1, NULL, NULL),
    (3, '0x3333333333333333333333333333333333333333333333333333333333333333', 100180, 'TransferReceived', 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', '2026-10-04 11:00:00', 'Polygon Testnet', 'CONFIRMED', 3, 1, 2, NULL, NULL),
    (4, '0x4444444444444444444444444444444444444444444444444444444444444444', 100240, 'BatchRecalled', 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', '2026-10-05 17:00:00', 'Polygon Testnet', 'CONFIRMED', 4, 2, NULL, NULL, NULL),
    (5, '0x5555555555555555555555555555555555555555555555555555555555555555', 100230, 'RetailSale', 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', '2026-10-05 16:30:00', 'Polygon Testnet', 'CONFIRMED', 3, 1, NULL, 1, 1),
    (6, '0x6666666666666666666666666666666666666666666666666666666666666666', NULL, 'TransferCreated', 'ffffffff-ffff-4fff-8fff-ffffffffffff', '2026-10-05 17:00:00', 'Polygon Testnet', 'PENDING', 1, 1, 3, NULL, NULL),
    (7, '0x7777777777777777777777777777777777777777777777777777777777777777', 100002, 'BatchRegistered', 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', '2026-10-02 09:00:00', 'Polygon Testnet', 'CONFIRMED', 1, 2, NULL, NULL, NULL);

COMMIT;