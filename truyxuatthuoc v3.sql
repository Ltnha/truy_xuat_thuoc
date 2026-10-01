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