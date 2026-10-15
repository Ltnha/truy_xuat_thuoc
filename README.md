```
truy_xuat_thuoc
├─ .editorconfig
├─ app
│  ├─ Enums
│  │  ├─ AccountStatus.php
│  │  ├─ BatchStatus.php
│  │  ├─ BlockchainIdentityStatus.php
│  │  ├─ DecisionType.php
│  │  ├─ LookupResult.php
│  │  ├─ OrganizationApprovalStatus.php
│  │  ├─ OrganizationType.php
│  │  ├─ ProductApprovalStatus.php
│  │  ├─ QRType.php
│  │  ├─ ReportStatus.php
│  │  ├─ Role.php
│  │  ├─ TransferStatus.php
│  │  └─ UnitStatus.php
│  ├─ Exceptions
│  │  ├─ BlockchainRejectedException.php
│  │  └─ NghiepVuException.php
│  ├─ Http
│  │  ├─ Controllers
│  │  │  ├─ Api
│  │  │  │  └─ DashboardDataController.php
│  │  │  ├─ BaoCao
│  │  │  │  └─ BaoCaoNghiVanController.php
│  │  │  ├─ Blockchain
│  │  │  │  └─ SepoliaTransactionController.php
│  │  │  ├─ ChuoiCungUng
│  │  │  │  ├─ BanLeController.php
│  │  │  │  └─ ChuyenGiaoController.php
│  │  │  ├─ Controller.php
│  │  │  ├─ CoQuan
│  │  │  │  └─ DuyetHoSoController.php
│  │  │  ├─ LoThuoc
│  │  │  │  └─ LoThuocController.php
│  │  │  ├─ NhaSanXuat
│  │  │  │  └─ SanPhamController.php
│  │  │  ├─ QuanTri
│  │  │  │  ├─ NhatKyController.php
│  │  │  │  └─ TaiKhoanController.php
│  │  │  ├─ ToChuc
│  │  │  │  └─ DuyetToChucController.php
│  │  │  ├─ TraCuu
│  │  │  │  └─ TraCuuController.php
│  │  │  └─ XacThuc
│  │  │     ├─ DangKyToChucController.php
│  │  │     ├─ DangNhapController.php
│  │  │     └─ HoSoController.php
│  │  ├─ Middleware
│  │  │  ├─ KiemTraVaiTro.php
│  │  │  ├─ TaiKhoanHoatDong.php
│  │  │  └─ ToChucDaDuyet.php
│  │  └─ Requests
│  │     ├─ BaseRequest.php
│  │     ├─ LoThuoc
│  │     │  └─ LoThuocRequest.php
│  │     ├─ ToChuc
│  │     │  └─ DuyetToChucRequest.php
│  │     └─ XacThuc
│  │        ├─ DangKyToChucRequest.php
│  │        └─ DangNhapRequest.php
│  ├─ Models
│  │  ├─ BanLe.php
│  │  ├─ BaoCaoNghiVan.php
│  │  ├─ BlockchainIdentity.php
│  │  ├─ BlockchainTransaction.php
│  │  ├─ ChuyenGiao.php
│  │  ├─ DonViSanPham.php
│  │  ├─ KetQuaXuLyBaoCao.php
│  │  ├─ LichSuLoThuoc.php
│  │  ├─ LoThuoc.php
│  │  ├─ MinhChungBaoCao.php
│  │  ├─ NhatKyHeThong.php
│  │  ├─ QRCode.php
│  │  ├─ QuyenHan.php
│  │  ├─ SanPham.php
│  │  ├─ TaiKhoan.php
│  │  ├─ TaiLieuDangKy.php
│  │  ├─ ThuHoiLoHang.php
│  │  ├─ ToChuc.php
│  │  ├─ TonKhoLo.php
│  │  ├─ TraCuu.php
│  │  ├─ YeuCauDangKy.php
│  │  └─ YeuCauDangKySanPham.php
│  ├─ Policies
│  │  ├─ LoThuocPolicy.php
│  │  ├─ Policy.php
│  │  └─ ToChucPolicy.php
│  ├─ Providers
│  │  ├─ AppServiceProvider.php
│  │  └─ AuthServiceProvider.php
│  └─ Services
│     ├─ Blockchain
│     │  ├─ BlockchainGateway.php
│     │  ├─ ChainId.php
│     │  ├─ FakeBlockchainGateway.php
│     │  ├─ GhiNhanBlockchain.php
│     │  ├─ SepoliaBatchEvent.php
│     │  ├─ SepoliaBlockchainGateway.php
│     │  └─ SepoliaTransactionSynchronizer.php
│     ├─ ChuoiCungUng
│     │  ├─ BanLeService.php
│     │  ├─ ChuyenGiaoService.php
│     │  └─ TonKhoService.php
│     ├─ Concerns
│     │  └─ ChayNghiepVu.php
│     ├─ LoThuoc
│     │  ├─ DonViSanPhamService.php
│     │  ├─ LoThuocService.php
│     │  └─ QrService.php
│     ├─ NhatKyService.php
│     ├─ QuanTri
│     │  └─ NhatKyService.php
│     ├─ Service.php
│     ├─ ToChuc
│     │  └─ ToChucService.php
│     └─ XacThuc
│        └─ XacThucService.php
├─ artisan
├─ autoload_probe.txt
├─ bootstrap
│  ├─ app.php
│  ├─ cache
│  │  ├─ packages.php
│  │  └─ services.php
│  └─ providers.php
├─ composer.json
├─ composer.lock
├─ config
│  ├─ app.php
│  ├─ auth.php
│  ├─ cache.php
│  ├─ database.php
│  ├─ filesystems.php
│  ├─ logging.php
│  ├─ mail.php
│  ├─ queue.php
│  ├─ services.php
│  ├─ session.php
│  └─ truyXuat.php
├─ contracts
│  └─ BatchTraceability.sol
├─ database
│  └─ seeders
│     ├─ DatabaseSeeder.php
│     ├─ DemoDataExpansionSeeder.php
│     ├─ QuanTriSeeder.php
│     └─ QuyenHanSeeder.php
├─ docs
│  └─ huong-dan-blockchain-testnet.md
├─ monolog_check.txt
├─ package-lock.json
├─ package.json
├─ phpunit.mariadb.xml
├─ phpunit.xml
├─ public
│  ├─ .htaccess
│  ├─ assets
│  │  ├─ css
│  │  │  ├─ coQuanQuanLy
│  │  │  │  ├─ pheDuyetTaiKhoanToChuc.css
│  │  │  │  ├─ quanLyQuyenHan.css
│  │  │  │  ├─ quanLyThuHoi.css
│  │  │  │  ├─ thongKeBaoCao.css
│  │  │  │  ├─ xetDuyetSanPham.css
│  │  │  │  └─ xuLyNghiVan.css
│  │  │  ├─ layoutNguoiTieuDung
│  │  │  │  ├─ footerNguoiTieuDung.css
│  │  │  │  ├─ headerNguoiTieuDung.css
│  │  │  │  └─ navbarNguoiTieuDung.css
│  │  │  ├─ nguoiTieuDung
│  │  │  │  ├─ baoCaoNghiVan.css
│  │  │  │  ├─ dangKyTocChuc.css
│  │  │  │  ├─ dangNhap.css
│  │  │  │  ├─ lichSuTraCuu.css
│  │  │  │  ├─ traCuuNguonGoc.css
│  │  │  │  └─ trangChu.css
│  │  │  ├─ nhaPhanPhoi
│  │  │  │  └─ quanLyLoHang.css
│  │  │  ├─ nhaSanXuat
│  │  │  │  ├─ quanLyLoThuoc.css
│  │  │  │  └─ quanLySanPham.css
│  │  │  ├─ nhaThuoc
│  │  │  │  ├─ quanLyBanHang.css
│  │  │  │  └─ tiepNhanLoHang.css
│  │  │  ├─ quanTriVien
│  │  │  │  ├─ nhatKyHeThong.css
│  │  │  │  └─ quanLyTaiKhoan.css
│  │  │  └─ trangDungChung
│  │  │     ├─ quanLyBaoCaoNghiVan.css
│  │  │     └─ thongTinCaNhan.css
│  │  └─ js
│  │     ├─ coQuanQuanLy
│  │     │  ├─ pheDuyetTaiKhoanToChuc.js
│  │     │  ├─ quanLyQuyenHan.js
│  │     │  ├─ quanLyThuHoi.js
│  │     │  ├─ thongKeBaoCao.js
│  │     │  ├─ xetDuyetSanPham.js
│  │     │  └─ xuLyNghiVan.js
│  │     ├─ layoutNguoiTieuDung
│  │     │  ├─ footerNguoiTieuDung.js
│  │     │  ├─ headerNguoiTieuDung.js
│  │     │  └─ navbarNguoiTieuDung.js
│  │     ├─ nguoiTieuDung
│  │     │  ├─ baoCaoNghiVan.js
│  │     │  ├─ dangKyToChuc.js
│  │     │  ├─ dangNhap.js
│  │     │  ├─ lichSuTraCuu.js
│  │     │  ├─ traCuuNguonGoc.js
│  │     │  └─ trangChu.js
│  │     ├─ nhaPhanPhoi
│  │     │  └─ quanLyLoHang.js
│  │     ├─ nhaSanXuat
│  │     │  ├─ quanLyLoThuoc.js
│  │     │  └─ quanLySanPham.js
│  │     ├─ nhaThuoc
│  │     │  ├─ quanLyBanHang.js
│  │     │  └─ tiepNhanLoHang.js
│  │     ├─ quanTriVien
│  │     │  ├─ nhatKyHeThong.js
│  │     │  └─ quanLyTaiKhoan.js
│  │     └─ trangDungChung
│  │        ├─ quanLyBaoCaoNghiVan.js
│  │        └─ thongTinCaNhan.js
│  ├─ favicon.ico
│  ├─ index.php
│  └─ robots.txt
├─ README.md
├─ resources
│  ├─ css
│  │  └─ app.css
│  ├─ js
│  │  ├─ app.js
│  │  └─ bootstrap.js
│  └─ views
│     ├─ coQuanQuanLy
│     │  ├─ pheDuyetTaiKhoanToChuc.blade.php
│     │  ├─ quanLyQuyenHan.blade.php
│     │  ├─ quanLyThuHoi.blade.php
│     │  ├─ thongKeBaoCao.blade.php
│     │  ├─ xetDuyetSanPham.blade.php
│     │  └─ xuLyNghiVan.blade.php
│     ├─ layoutDungChung
│     │  ├─ master.blade.php
│     │  ├─ sidebarDungChung.blade.php
│     │  └─ topbarDungChung.blade.php
│     ├─ layoutNguoiTieuDung
│     │  ├─ footerNguoiTieuDung.blade.php
│     │  ├─ headerNguoiTieuDung.blade.php
│     │  ├─ main.blade.php
│     │  └─ navbarNguoiTieuDung.blade.php
│     ├─ nguoiTieuDung
│     │  ├─ baoCaoNghiVan.blade.php
│     │  ├─ dangKyToChuc.blade.php
│     │  ├─ dangNhap.blade.php
│     │  ├─ lichSuTraCuu.blade.php
│     │  ├─ traCuuNguonGoc.blade.php
│     │  └─ trangChu.blade.php
│     ├─ nhaPhanPhoi
│     │  └─ quanLyLoHang.blade.php
│     ├─ nhaSanXuat
│     │  ├─ quanLyLoThuoc.blade.php
│     │  └─ quanLySanPham.blade.php
│     ├─ nhaThuoc
│     │  ├─ quanLyBanHang.blade.php
│     │  └─ tiepNhanLoHang.blade.php
│     ├─ quanTriVien
│     │  ├─ nhatKyHeThong.blade.php
│     │  └─ quanLyTaiKhoan.blade.php
│     └─ trangDungChung
│        ├─ quanLyBaoCaoNghiVan.blade.php
│        └─ thongTinCaNhan.blade.php
├─ routes
│  ├─ console.php
│  └─ web.php
├─ storage
│  ├─ app
│  │  ├─ private
│  │  │  └─ tai-lieu-dang-ky
│  │  │     └─ OFuGdXAhMEy1jmo67l9OT9yPhji3u1YU0WyD0ThU.jpg
│  │  └─ public
│  │     └─ tai-lieu-dang-ky
│  │        ├─ 7AsYltpBUIDiLZNztdw7ywwNCCvHPIbCXZdTdvq1.pdf
│  │        ├─ 9tBtHGgIYmtkbNqk8vhAg98EjxwI5PEXMGl7m9ts.pdf
│  │        ├─ bEQhmyQYuL1feRKnBNVse8NGwZJgroB5ASMqvNfx.jpg
│  │        ├─ bxX4mCJvSxv2Xsn5TIfhU4KP6iGo5lbInGDxXGrD.jpg
│  │        ├─ fZt7hd4bBjnspck1JNO6ocwGNUiBjxxE1TJ2r6Kn.pdf
│  │        ├─ hjGDt5OZCMCr3enOd3CfMT5zom4I9t28h4GiN9GC.pdf
│  │        ├─ LaCzQt8cqih0MXhQZ8SK4wFBfEZzdnxta8SiNgCj.png
│  │        ├─ lsakk7uDwI6rNjxtKj57hwAeWoBgwokswf5L39Cv.jpg
│  │        ├─ nXMDEF2EX4KZEfguijtXDu0Z8c1kxdfT66MTgznr.jpg
│  │        └─ T6ag19jfEKZqL5ymvC2gQ6ogSVsO1vC5SNOycgIo.jpg
│  ├─ framework
│  │  ├─ cache
│  │  │  └─ data
│  │  ├─ sessions
│  │  │  ├─ AvOlHE0XDEnRYtx6bdWodhwJeSI3BBZMvhUoqm7v
│  │  │  ├─ MuVYpJAJaKxZ2EPE3JQtkEpmjjkSt6eqczF3MEjj
│  │  │  ├─ oKCf9maIbY9q1QxU2Z0tdT14g4fCxmfMEPjgSGJ8
│  │  │  ├─ PwkLy2HCQA5aP4vpxfyDqqC60ximwMzcnnqRZtw5
│  │  │  ├─ rd6S6ODgqwiH1aU93Skt90yycSKgbyqiaXmCSWCi
│  │  │  ├─ SOfmjNldhOT0MCKUuE5N2GRIqElvI07cHdk8HpV6
│  │  │  ├─ UmD9ut53eNPZ8nkOHMuOa99NEdspnCWMXg7Vf8dM
│  │  │  └─ wDPvsqh70RnXiO9xDBYLrfgyIdLXTjYm4Hxezl2W
│  │  ├─ testing
│  │  └─ views
│  │     ├─ 003e2cb6b8073d6c65a8ba29dd3de1a3.php
│  │     ├─ 1878de6f35923c583b84d5595cb57dde.php
│  │     ├─ 2305f106441e1f9a23c54e332fd4f3da.php
│  │     ├─ 3a21b499124aa2e727ce7f19eba89c83.php
│  │     ├─ 4b6b03a8365874d03bcd495e4b77ff8d.php
│  │     ├─ 7e8c5fb405b097e2cc35e76bcb580f1f.php
│  │     ├─ 95245472938aae797b87da43d15b8f9a.php
│  │     ├─ ab26b96cc32f28f88682afd59e65469d.php
│  │     ├─ ab8753b4461257f4fb85f889052e5f11.php
│  │     ├─ bc0d81ad3032929d6ccdbef00c943fae.php
│  │     ├─ c22a38954b4ecbc916871f419f6dee35.php
│  │     ├─ d2cc06a7e3b34c2db5f7d30b1964491a.php
│  │     ├─ e718ffa9359ce16c3ea7996405287ba2.php
│  │     └─ ee1870d7ed41d671d963a57255e294bc.php
│  └─ logs
├─ tests
│  ├─ Feature
│  │  ├─ BanLeTest.php
│  │  ├─ BaoCaoXuLyTest.php
│  │  ├─ DangKyToChucTest.php
│  │  ├─ DangNhapTest.php
│  │  ├─ ExampleTest.php
│  │  ├─ KhungDuAnTest.php
│  │  ├─ SepoliaTransactionSyncTest.php
│  │  └─ UiLayoutTest.php
│  ├─ Integration
│  │  └─ MariaDbSchemaTest.php
│  ├─ TestCase.php
│  └─ Unit
│     ├─ ChainIdTest.php
│     ├─ EnumTest.php
│     ├─ ExampleTest.php
│     └─ RoleMiddlewareTest.php
├─ truyxuatthuoc v5 cấu trúc bảng.sql
├─ truyxuatthuoc v5.sql
└─ vite.config.js

```
