-- HỆ THỐNG TRUY XUẤT NGUỒN GỐC DƯỢC PHẨM (VERSION 3.2)
-- Database Schema chuẩn hóa kết hợp giữa v3 và v3.1

CREATE DATABASE IF NOT EXISTS truyxuatthuoc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE truyxuatthuoc;

-- 1. Bảng toChuc
CREATE TABLE toChuc (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tenToChuc VARCHAR(255) NOT NULL,
    loaiToChuc ENUM('NHA_SAN_XUAT','NHA_PHAN_PHOI','NHA_THUOC','CO_QUAN_QUAN_LY') NOT NULL,
    diaChi VARCHAR(255) NULL, --v6
    soDienThoai VARCHAR(20) NULL, --v6
    maSoThue VARCHAR(20) UNIQUE,
    soGiayPhepDuoc VARCHAR(50) NULL,
    ngayCapGiayPhep DATE NULL,
    ngayHetHanGiayPhep DATE NULL,
    coQuanCap VARCHAR(255) NULL,
    trangThaiDuyet ENUM('CHO_DUYET','DA_DUYET','TU_CHOI','BI_THU_HOI') NOT NULL DEFAULT 'CHO_DUYET'
)

-- 2. Bảng taiKhoan
CREATE TABLE taiKhoan (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tenDangNhap VARCHAR(50) NOT NULL UNIQUE,
    matKhau VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE,
    vaiTro ENUM('NHA_SAN_XUAT','NHA_PHAN_PHOI','NHA_THUOC','CO_QUAN_QUAN_LY','QUAN_TRI_VIEN') NOT NULL,
    trangThai ENUM('PENDING','ACTIVE','LOCKED') NOT NULL DEFAULT 'PENDING',
    lyDoKhoa VARCHAR(500) NULL,
    nguoiKhoaId BIGINT NULL,
    thoiGianKhoa DATETIME NULL,
    toChucId BIGINT NULL,
    ngayTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (toChucId) REFERENCES toChuc(id),
    FOREIGN KEY (nguoiKhoaId) REFERENCES taiKhoan(id)
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
    trangThaiDongBo ENUM('PENDING', 'SUCCESS', 'FAILED') NOT NULL DEFAULT 'PENDING', --v6
    txHash VARCHAR(66) NULL, --v6
    ngayThuHoi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (toChucId) REFERENCES toChuc(id),
    FOREIGN KEY (nguoiThuHoiId) REFERENCES taiKhoan(id)
)
-- 6. Bảng nhatKyHeThong
CREATE TABLE nhatKyHeThong (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    taiKhoanId BIGINT NULL,
    doiTuongId BIGINT NULL,
    hanhDong VARCHAR(100) NOT NULL,
    ketQua ENUM('THANH_CONG','BI_TU_CHOI','LOI') NOT NULL DEFAULT 'THANH_CONG',
    thoiGian DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    chiTiet TEXT,
    FOREIGN KEY (taiKhoanId) REFERENCES taiKhoan(id)   -- v7 
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
    trangThai ENUM('PENDING','RECEIVED','CANCELLED','REJECTED') NOT NULL DEFAULT 'PENDING', --  v7
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
    signerKeyId VARCHAR(100) NULL, --v6 mã định danh tham chiếu ví bên Signer Service
    diaChiVi VARCHAR(42) NOT NULL UNIQUE, --v6
    network VARCHAR(50) NOT NULL DEFAULT 'Ethereum Sepolia Testnet',
    trangThai ENUM('KHOI_TAO', 'DA_NAP_GAS', 'ACTIVE', 'INACTIVE', 'REVOKED') NOT NULL DEFAULT 'KHOI_TAO', --v6
    ngayTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, --v6
    FOREIGN KEY (toChucId) REFERENCES toChuc(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. Bảng blockchainTransaction (Nguồn tham chiếu duy nhất cho mọi TxHash)
CREATE TABLE blockchainTransaction (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    txHash VARCHAR(66) NOT NULL UNIQUE,
    blockNumber BIGINT NULL,
    eventName VARCHAR(100) NOT NULL,
    doiTuongToChucId BIGINT NULL,
    businessId VARCHAR(36) NOT NULL,
    thoiGian DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    network VARCHAR(50) NOT NULL DEFAULT 'Ethereum Sepolia Testnet', --v6
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