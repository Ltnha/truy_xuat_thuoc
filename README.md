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
│  │  ├─ routes-v7.php
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
│  │        ├─ 6sMeZfSs4cJQNKAlQgxNEVbcxYYcZUxkWoJQMHE9.jpg
│  │        ├─ 7AsYltpBUIDiLZNztdw7ywwNCCvHPIbCXZdTdvq1.pdf
│  │        ├─ 9tBtHGgIYmtkbNqk8vhAg98EjxwI5PEXMGl7m9ts.pdf
│  │        ├─ bEQhmyQYuL1feRKnBNVse8NGwZJgroB5ASMqvNfx.jpg
│  │        ├─ bxX4mCJvSxv2Xsn5TIfhU4KP6iGo5lbInGDxXGrD.jpg
│  │        ├─ fZt7hd4bBjnspck1JNO6ocwGNUiBjxxE1TJ2r6Kn.pdf
│  │        ├─ GeucZpXFGAJCoVJHF7HMvdJfcPVuBo8sRvX8yRQ1.jpg
│  │        ├─ hjGDt5OZCMCr3enOd3CfMT5zom4I9t28h4GiN9GC.pdf
│  │        ├─ LaCzQt8cqih0MXhQZ8SK4wFBfEZzdnxta8SiNgCj.png
│  │        ├─ lsakk7uDwI6rNjxtKj57hwAeWoBgwokswf5L39Cv.jpg
│  │        ├─ nXMDEF2EX4KZEfguijtXDu0Z8c1kxdfT66MTgznr.jpg
│  │        └─ T6ag19jfEKZqL5ymvC2gQ6ogSVsO1vC5SNOycgIo.jpg
│  ├─ framework
│  │  ├─ cache
│  │  │  └─ data
│  │  ├─ sessions
│  │  │  └─ l1nzqLbM8DQ20N2ET1YeFCPpBKTtSt8SgPhTf2Lo
│  │  ├─ testing
│  │  └─ views
│  │     ├─ 035731246571d2843ed8806b895b1df3.php
│  │     ├─ 0421cdad5b572f8bb719588f1d726fa9.php
│  │     ├─ 0d04c670e3e803c183a03541de2acaa3.php
│  │     ├─ 0d8b13144357f330d7c8c861a4c9ab2c.php
│  │     ├─ 1773df56c706204342805adcb42a7115.php
│  │     ├─ 1b8e9dac5559a14c209fd36df5a15225.php
│  │     ├─ 22eb8eae42c599ba8c9dd4876fc849a7.php
│  │     ├─ 28219c8155a2f356df928011d80046b0.php
│  │     ├─ 396143b79356ad0b0f1053b49e224ec7.php
│  │     ├─ 4a3814166a7e64ed0e5a84c7112895db.php
│  │     ├─ 4be7b176892a5509639bee1b760f8b80.php
│  │     ├─ 50c3ad9c21b4ebd5e5aa809c983c90f6.php
│  │     ├─ 50f7d82012c2b3e18db56975b1882785.php
│  │     ├─ 54d74bdf29555370c479a3af59307076.php
│  │     ├─ 5855bba434fe8dc0965bf9e7541af038.php
│  │     ├─ 5942872b258bb3280fbbb51877cd5e8a.php
│  │     ├─ 61969e8957d6d1d8ed2b2f33d228bccd.php
│  │     ├─ 68268c164e30a32f372d9ef32daec3bd.php
│  │     ├─ 69f732ec30bf92e8cd6e4b11d123593e.php
│  │     ├─ 6ead2b88bff4f2585ae1d45acb9f9b26.php
│  │     ├─ 789fe0400d66a3ce25e508b35006a3db.php
│  │     ├─ 7d3d11463513c682f5e829a82c2b2df4.php
│  │     ├─ 8273b6b455b6a1d9d2a065dde11b4ada.php
│  │     ├─ 8621b4c20348f86febdc9411ac4bc8e0.php
│  │     ├─ 887d9f1000bd04da7ecddac96d054b17.php
│  │     ├─ 8c0cf3c13e4b27bf441bb098299e6494.php
│  │     ├─ 8ca1b0eea16fb29146e1d683f8c7a85a.php
│  │     ├─ 90e942402fed7819e694b59d917bc2e0.php
│  │     ├─ 95245472938aae797b87da43d15b8f9a.php
│  │     ├─ 97ab56c3b983bbe1b81c63e51377ff9c.php
│  │     ├─ a60c944afcd7edc87fbb06a01759eafb.php
│  │     ├─ ab8753b4461257f4fb85f889052e5f11.php
│  │     ├─ bc0d81ad3032929d6ccdbef00c943fae.php
│  │     ├─ bd505a601cfcb0aa18d9f5ea11becc5c.php
│  │     ├─ be4c7eec3eb4b90497fa3aee52cec1bc.php
│  │     ├─ c02c224546c00467333c876706465c6a.php
│  │     ├─ d0428481c2156e6982ac40bc84442f15.php
│  │     ├─ d2cc06a7e3b34c2db5f7d30b1964491a.php
│  │     ├─ daf4e8367eee70874eb348faa52286b3.php
│  │     ├─ df99fd61a7bfa684895dfdef8df20304.php
│  │     ├─ e0702df386324677ea105eed8692b265.php
│  │     ├─ ee6f5b342e2a2da600f0da9ac26c373c.php
│  │     ├─ f3d283dc88ac91e1769c92151406293d.php
│  │     ├─ f6416e93d5de273fb2236af3973588ab.php
│  │     ├─ f95e45b15ea80ddfea0df4b22e7ac57b.php
│  │     └─ ff4e1f4a1ae284b553dbaee3d587464f.php
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
