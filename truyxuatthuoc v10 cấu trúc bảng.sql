-- HỆ THỐNG TRUY XUẤT NGUỒN GỐC DƯỢC PHẨM (VERSION v9)
-- v9: chuyen giao nguyen lo; ton kho moi lo chi co mot chu so huu; ban le theo so luong; QR theo lo; txHash that moi duoc luu.

CREATE DATABASE IF NOT EXISTS truyxuatthuoc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE truyxuatthuoc;

-- 1. Bảng toChuc
CREATE TABLE toChuc (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tenToChuc VARCHAR(255) NOT NULL,
    loaiToChuc ENUM('NHA_SAN_XUAT','NHA_PHAN_PHOI','NHA_THUOC','CO_QUAN_QUAN_LY') NOT NULL,
    diaChi VARCHAR(255) NULL,
    soDienThoai VARCHAR(20) NULL,
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
    trangThaiDongBo ENUM('PENDING', 'SUCCESS', 'FAILED') NOT NULL DEFAULT 'PENDING',
    txHash VARCHAR(66) NULL,
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
    FOREIGN KEY (taiKhoanId) REFERENCES taiKhoan(id)  -- v7
);

-- 7. Bảng sanPham
CREATE TABLE sanPham (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    maSanPham CHAR(36) NOT NULL UNIQUE,
    tenSanPham VARCHAR(255) NOT NULL,
    soDangKy VARCHAR(50) NOT NULL UNIQUE,
    thanhPhan VARCHAR(255),
    hamLuong VARCHAR(50),
    dangBaoChe VARCHAR(100),
    quyCachDongGoi VARCHAR(255),
    nhaSanXuatId BIGINT NOT NULL,
    trangThaiDuyet ENUM('CHO_DUYET','DA_DUYET','TU_CHOI') NOT NULL DEFAULT 'CHO_DUYET',
    hashHoSo CHAR(66) NULL COMMENT 'Keccak-256 cua snapshot ho so san pham da duyet; khong tu dong thay doi khi sua ho so',
    FOREIGN KEY (nhaSanXuatId) REFERENCES toChuc(id)
);

-- 8. Bảng yeuCauDangKySanPham
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
    maLo CHAR(36) NOT NULL UNIQUE,
    maLoNghiepVu VARCHAR(50) NOT NULL UNIQUE,
    sanPhamId BIGINT NOT NULL,
    toChucId BIGINT NOT NULL COMMENT 'To chuc san xuat tao lo; chu so huu hien tai duoc quan ly tai tonKhoLo',
    soLuong INT NOT NULL COMMENT 'Tong so luong ban dau cua lo',
    donViTinh VARCHAR(50) NOT NULL DEFAULT 'Hộp',
    ngaySanXuat DATE NOT NULL,
    hanSuDung DATE NOT NULL,
    trangThai ENUM('CREATED','IN_TRANSIT','RECALLED') NOT NULL DEFAULT 'CREATED',
    FOREIGN KEY (sanPhamId) REFERENCES sanPham(id),
    FOREIGN KEY (toChucId) REFERENCES toChuc(id),
    CONSTRAINT chk_lo_soluong CHECK (soLuong > 0),
    CONSTRAINT chk_lo_han CHECK (hanSuDung > ngaySanXuat)
);

-- 10. Bảng qrCode (Quản lý theo Lô hàng - 01 mã QR đại diện cho toàn bộ lô)
CREATE TABLE qrCode (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    giaTriQR VARCHAR(255) NOT NULL UNIQUE,
    loThuocId BIGINT NOT NULL UNIQUE,
    ngayTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trangThai ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    FOREIGN KEY (loThuocId) REFERENCES loThuoc(id)
);

-- 11. Bảng tonKhoLo
CREATE TABLE tonKhoLo (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    loThuocId BIGINT NOT NULL COMMENT 'Moi lo chi co mot dong ton kho; cap nhat toChucId khi chuyen giao thanh cong',
    toChucId BIGINT NOT NULL COMMENT 'To chuc dang so huu lo hien tai',
    soLuong INT NOT NULL DEFAULT 0 COMMENT 'So luong con lai dang nam trong kho cua chu so huu hien tai',
    ngayCapNhat DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (loThuocId) REFERENCES loThuoc(id),
    FOREIGN KEY (toChucId) REFERENCES toChuc(id),
    UNIQUE KEY uq_ton_kho_lo (loThuocId),
    CONSTRAINT chk_tonkho_soluong CHECK (soLuong >= 0)
);

-- 12. Bảng chuyenGiao
CREATE TABLE chuyenGiao (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    maChuyenGiao CHAR(36) NOT NULL UNIQUE,
    loThuocId BIGINT NOT NULL,
    benGuiId BIGINT NOT NULL,
    benNhanId BIGINT NOT NULL,
    soLuong INT NOT NULL COMMENT 'Bat buoc bang toan bo ton kho hien tai cua lo tai thoi diem gui',
    thoiGianKhoiTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    thoiGianXacNhan DATETIME NULL,
    trangThai ENUM('PENDING','RECEIVED','CANCELLED','REJECTED') NOT NULL DEFAULT 'PENDING', -- v7
    lyDoTuChoi VARCHAR(500) NULL, -- v8
    loThuocDangCho BIGINT GENERATED ALWAYS AS (CASE WHEN trangThai = 'PENDING' THEN loThuocId ELSE NULL END) STORED,
    FOREIGN KEY (loThuocId) REFERENCES loThuoc(id),
    FOREIGN KEY (benGuiId) REFERENCES toChuc(id),
    FOREIGN KEY (benNhanId) REFERENCES toChuc(id),
    CONSTRAINT chk_soluong CHECK (soLuong > 0),
    CONSTRAINT chk_khac_to_chuc CHECK (benGuiId <> benNhanId),
    CONSTRAINT chk_xac_nhan CHECK (trangThai <> 'RECEIVED' OR thoiGianXacNhan IS NOT NULL),
    UNIQUE KEY uq_chuyengiao_moi_lo (loThuocDangCho)
);

-- 13. Bảng baoCaoNghiVan
CREATE TABLE baoCaoNghiVan (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    maBaoCao VARCHAR(255) NOT NULL UNIQUE,
    loaiBaoCao ENUM('TRONG_VONG_DOI', 'NGOAI_HE_THONG') NOT NULL DEFAULT 'TRONG_VONG_DOI',
    loThuocId BIGINT NULL,
    loBiLamGiaId BIGINT NULL,
    maLoKhaiBao VARCHAR(50) NOT NULL,
    taiKhoanId BIGINT NULL,
    toChucId BIGINT NULL COMMENT 'To chuc gui bao cao neu la tai khoan doanh nghiep; NULL neu la nguoi tieu dung',
    lyDo TEXT NOT NULL,
    moTa TEXT NULL,
    ngayGui DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    trangThai ENUM('DA_GUI', 'DANG_XU_LY', 'DA_XU_LY') NOT NULL DEFAULT 'DA_GUI',
    FOREIGN KEY (loThuocId) REFERENCES loThuoc(id) ON DELETE SET NULL,
    FOREIGN KEY (loBiLamGiaId) REFERENCES loThuoc(id) ON DELETE SET NULL,
    FOREIGN KEY (taiKhoanId) REFERENCES taiKhoan(id) ON DELETE SET NULL,
    FOREIGN KEY (toChucId) REFERENCES toChuc(id) ON DELETE SET NULL
);

-- 14. Bảng minhChungBaoCao
CREATE TABLE minhChungBaoCao (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    baoCaoId BIGINT NOT NULL,
    tenFile VARCHAR(255) NOT NULL,
    loaiFile VARCHAR(50),
    duongDan VARCHAR(500) NOT NULL,
    ngayTaiLen DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (baoCaoId) REFERENCES baoCaoNghiVan(id)
);

-- 15. Bảng ketQuaXuLyBaoCao
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

-- 16. Bảng thuHoiLoHang
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

-- 17. Bảng banLe (Hóa đơn bán lẻ tại nhà thuốc; trạng thái bán tách biệt đồng bộ Blockchain)
CREATE TABLE banLe (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    maHoaDon CHAR(36) NOT NULL UNIQUE,
    toChucId BIGINT NOT NULL,
    taiKhoanId BIGINT NOT NULL,
    tongTien DECIMAL(15,2) NOT NULL DEFAULT 0,
    trangThai ENUM('DA_HOAN_TAT','DA_HUY') NOT NULL DEFAULT 'DA_HOAN_TAT',
    ngayBan DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ghiChu VARCHAR(500) NULL,
    FOREIGN KEY (toChucId) REFERENCES toChuc(id),
    FOREIGN KEY (taiKhoanId) REFERENCES taiKhoan(id),
    CONSTRAINT chk_banle_tongtien CHECK (tongTien >= 0)
);

-- 18. Bảng chiTietBanLe (Một dòng cho mỗi lô trong hóa đơn; không quản lý từng đơn vị sản phẩm)
CREATE TABLE chiTietBanLe (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    banLeId BIGINT NOT NULL,
    loThuocId BIGINT NOT NULL,
    soLuong INT NOT NULL,
    donGia DECIMAL(15,2) NOT NULL,
    thanhTien DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (banLeId) REFERENCES banLe(id),
    FOREIGN KEY (loThuocId) REFERENCES loThuoc(id),
    UNIQUE KEY uq_chitiet_banle_lo (banLeId, loThuocId),
    CONSTRAINT chk_chitiet_soluong CHECK (soLuong > 0),
    CONSTRAINT chk_chitiet_dongia CHECK (donGia >= 0),
    CONSTRAINT chk_chitiet_thanhtien CHECK (thanhTien >= 0)
);

-- 19. Bảng blockchainIdentity
CREATE TABLE blockchainIdentity (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    toChucId BIGINT NOT NULL,
    signerKeyId VARCHAR(100) NULL,
    diaChiVi VARCHAR(42) NOT NULL UNIQUE,
    network VARCHAR(50) NOT NULL DEFAULT 'Ethereum Sepolia Testnet',
    trangThai ENUM('KHOI_TAO', 'DA_NAP_GAS', 'ACTIVE', 'INACTIVE', 'REVOKED') NOT NULL DEFAULT 'KHOI_TAO',
    ngayTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (toChucId) REFERENCES toChuc(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. Bảng blockchainTransaction (chỉ lưu txHash thật do Signer trả về; NULL khi chưa submit thành công)
CREATE TABLE blockchainTransaction (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    txHash VARCHAR(255) NULL,
    blockNumber BIGINT NULL,
    eventName VARCHAR(100) NOT NULL,
    doiTuongToChucId BIGINT NULL,
    businessId CHAR(36) NOT NULL,
    payloadHash CHAR(66) NULL COMMENT 'Keccak-256 payload gui len contract',
    thoiGian DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    network VARCHAR(50) NOT NULL DEFAULT 'Ethereum Sepolia Testnet',
    trangThai ENUM('PENDING','CONFIRMED','FAILED') NOT NULL DEFAULT 'PENDING',
    soLanThu TINYINT UNSIGNED NOT NULL DEFAULT 0,
    lanThuCuoi DATETIME NULL,
    loiCuoi TEXT NULL,
    toChucId BIGINT NOT NULL,
    sanPhamId BIGINT NULL,
    loThuocId BIGINT NULL,
    chuyenGiaoId BIGINT NULL,
    banLeId BIGINT NULL,
    chiTietBanLeId BIGINT NULL,
    FOREIGN KEY (toChucId) REFERENCES toChuc(id),
    FOREIGN KEY (doiTuongToChucId) REFERENCES toChuc(id),
    FOREIGN KEY (sanPhamId) REFERENCES sanPham(id),
    FOREIGN KEY (loThuocId) REFERENCES loThuoc(id),
    FOREIGN KEY (chuyenGiaoId) REFERENCES chuyenGiao(id),
    FOREIGN KEY (banLeId) REFERENCES banLe(id),
    FOREIGN KEY (chiTietBanLeId) REFERENCES chiTietBanLe(id),
    CONSTRAINT chk_bctx_confirmed_hash CHECK (trangThai <> 'CONFIRMED' OR txHash IS NOT NULL),
    UNIQUE KEY uq_bctx_idempotency (loThuocId, businessId, eventName)
);
-- 21. Bảng lưu hàng đợi xử lý ngầm (Jobs)
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `queue` VARCHAR(255) NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `attempts` TINYINT UNSIGNED NOT NULL,
  `reserved_at` INT UNSIGNED NULL,
  `available_at` INT UNSIGNED NOT NULL,
  `created_at` INT UNSIGNED NOT NULL,
  INDEX `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 22. Bảng lưu các Job bị lỗi (Failed Jobs)
CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `uuid` VARCHAR(255) NOT NULL UNIQUE,
  `connection` TEXT NOT NULL,
  `queue` TEXT NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `exception` LONGTEXT NOT NULL,
  `failed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 23. Các Index tối ưu truy vấn
CREATE INDEX idx_taikhoan_tochuc ON taiKhoan(toChucId);
CREATE INDEX idx_nhatky_taikhoan ON nhatKyHeThong(taiKhoanId);
CREATE INDEX idx_nhatky_doituong ON nhatKyHeThong(doiTuongId);
CREATE INDEX idx_nhatky_thoigian ON nhatKyHeThong(thoiGian);
CREATE INDEX idx_yeucaudk_tochuc ON yeuCauDangKy(toChucId);
CREATE INDEX idx_sanpham_nhasanxuat ON sanPham(nhaSanXuatId);
CREATE INDEX idx_yeucausp_sanpham ON yeuCauDangKySanPham(sanPhamId);
CREATE INDEX idx_lothuoc_sanpham ON loThuoc(sanPhamId);
CREATE INDEX idx_lothuoc_tochuc ON loThuoc(toChucId);
CREATE INDEX idx_tonkho_tochuc ON tonKhoLo(toChucId);
CREATE INDEX idx_chuyengiao_lothuoc ON chuyenGiao(loThuocId);
CREATE INDEX idx_chuyengiao_bengui ON chuyenGiao(benGuiId);
CREATE INDEX idx_chuyengiao_bennhan ON chuyenGiao(benNhanId);
CREATE INDEX idx_baocao_lothuoc ON baoCaoNghiVan(loThuocId);
CREATE INDEX idx_thuhoi_lothuoc ON thuHoiLoHang(loThuocId);
CREATE INDEX idx_bctx_lothuoc ON blockchainTransaction(loThuocId);
CREATE INDEX idx_bctx_tochuc ON blockchainTransaction(toChucId);
CREATE INDEX idx_bctx_businessid ON blockchainTransaction(businessId);
CREATE INDEX idx_lothuoc_trangthai ON loThuoc(trangThai);
CREATE INDEX idx_chuyengiao_trangthai ON chuyenGiao(trangThai);
CREATE INDEX idx_baocao_trangthai ON baoCaoNghiVan(trangThai);
CREATE INDEX idx_yeucaudk_trangthai ON yeuCauDangKy(trangThai);
CREATE INDEX idx_yeucausp_trangthai ON yeuCauDangKySanPham(trangThai);
CREATE INDEX idx_nhatky_ketqua ON nhatKyHeThong(ketQua);
CREATE INDEX idx_bctx_eventname ON blockchainTransaction(eventName);
CREATE INDEX idx_bctx_chuyengiao ON blockchainTransaction(chuyenGiaoId);
CREATE INDEX idx_banle_tochuc_ngayban ON banLe(toChucId, ngayBan);
CREATE INDEX idx_chitietbanle_lothuoc ON chiTietBanLe(loThuocId);
CREATE INDEX idx_bctx_banle ON blockchainTransaction(banLeId);
CREATE INDEX idx_bctx_chitietbanle ON blockchainTransaction(chiTietBanLeId);
CREATE INDEX idx_bctx_trangthai ON blockchainTransaction(trangThai);