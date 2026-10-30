// SPDX-License-Identifier: MIT
pragma solidity ^0.8.24;

contract BatchTraceability {
    // Địa chỉ quản trị cao nhất (Owner/Admin)
    address public owner;

    // Danh sách địa chỉ được cấp quyền writer trực tiếp (Tính năng cũ)
    mapping(address => bool) public writers;

    // Các hằng số định danh Vai trò doanh nghiệp (Role Management mới)
    bytes32 public constant ROLE_CO_QUAN   = keccak256("CO_QUAN_QUAN_LY");
    bytes32 public constant ROLE_NSX       = keccak256("NHA_SAN_XUAT");
    bytes32 public constant ROLE_NPP       = keccak256("NHA_PHAN_PHOI");
    bytes32 public constant ROLE_NHA_THUOC = keccak256("NHA_THUOC");

    // Bảng lưu trữ vai trò: địa chỉ ví => (mã vai trò => true/false)
    mapping(address => mapping(bytes32 => bool)) private _roles;

    // Danh dấu event key đã ghi để tránh ghi trùng lặp
    mapping(bytes32 => bool) public recordedEventKeys;

    // --- SỰ KIỆN (EVENTS) ---
    event OwnershipTransferred(address indexed previousOwner, address indexed newOwner);
    event WriterPermissionChanged(address indexed writer, bool enabled);
    event RoleGranted(address indexed account, bytes32 indexed role, address indexed sender);
    event RoleRevoked(address indexed account, bytes32 indexed role, address indexed sender);

    event BatchEventRecorded(
        bytes32 indexed batchId,
        bytes32 indexed businessId,
        address indexed actor,
        bytes32 eventType,
        bytes32 payloadHash,
        uint256 timestamp
    );

    // --- CẤU TRÚC & SỰ KIỆN CHUYỂN GIAO (TRANSFER MANAGEMENT) ---
    enum TransferStatus { PENDING, RECEIVED, REJECTED }

    struct TransferRecord {
        bytes32 maChuyenGiao;
        address benGui;
        address benNhan;
        TransferStatus status;
        uint256 thoiGian;
        string lyDo;
    }

    mapping(bytes32 => TransferRecord) public transfers;

    event TransferSubmitted(
        bytes32 indexed maChuyenGiao,
        address indexed benGui,
        address indexed benNhan,
        uint256 thoiGian
    );

    event TransferReceived(
        bytes32 indexed maChuyenGiao,
        address indexed benNhan,
        uint256 thoiGian
    );

    event TransferRejected(
        bytes32 indexed maChuyenGiao,
        address indexed benNhan,
        uint256 thoiGian,
        string lyDo
    );

    // --- LỖI TỰ ĐỊNH NGHĨA (CUSTOM ERRORS) ---
    error Unauthorized();
    error InvalidAddress();
    error InvalidEventData();
    error DuplicateEvent();
    error RoleAlreadyGranted();
    error RoleNotGranted();

    // --- MODIFIERS ---
    modifier onlyOwner() {
        if (msg.sender != owner) revert Unauthorized();
        _;
    }

    // Cho phép gọi nếu là Writer trực tiếp HOẶC có Vai trò doanh nghiệp hợp lệ
    modifier onlyWriter() {
        if (!writers[msg.sender] && !isAuthorizedRole(msg.sender)) revert Unauthorized();
        _;
    }

    constructor() {
        owner = msg.sender;
        writers[msg.sender] = true; // Thiết lập người deploy là writer (Tính năng cũ)

        emit OwnershipTransferred(address(0), msg.sender);
        emit WriterPermissionChanged(msg.sender, true);

        // Tự động cấp vai trò Cơ quan quản lý cho tài khoản Owner (Tính năng mới)
        _grantRole(msg.sender, ROLE_CO_QUAN);
    }

    // --- QUẢN LÝ WRITER TRỰC TIẾP (TÍNH NĂNG CŨ) ---
    function setWriter(address writer, bool enabled) external onlyOwner {
        if (writer == address(0)) revert InvalidAddress();
        writers[writer] = enabled;
        emit WriterPermissionChanged(writer, enabled);
    }

    // --- QUẢN LÝ VAI TRÒ ON-CHAIN (TÍNH NĂNG MỚI) ---
    function hasRole(address account, bytes32 role) public view returns (bool) {
        return _roles[account][role];
    }

    function isAuthorizedRole(address account) public view returns (bool) {
        return _roles[account][ROLE_NSX] || 
               _roles[account][ROLE_NPP] || 
               _roles[account][ROLE_NHA_THUOC] || 
               _roles[account][ROLE_CO_QUAN];
    }

    function grantRole(address account, bytes32 role) external onlyOwner {
        if (account == address(0)) revert InvalidAddress();
        if (_roles[account][role]) revert RoleAlreadyGranted();

        _grantRole(account, role);
    }

    function revokeRole(address account, bytes32 role) external onlyOwner {
        if (account == address(0)) revert InvalidAddress();
        if (!_roles[account][role]) revert RoleNotGranted();

        _roles[account][role] = false;
        emit RoleRevoked(account, role, msg.sender);
    }

    function _grantRole(address account, bytes32 role) internal {
        _roles[account][role] = true;
        emit RoleGranted(account, role, msg.sender);
    }

    // --- CHUYỂN QUYỀN OWNER ---
    function transferOwnership(address newOwner) external onlyOwner {
        if (newOwner == address(0)) revert InvalidAddress();
        address previousOwner = owner;
        owner = newOwner;
        emit OwnershipTransferred(previousOwner, newOwner);
    }

    // --- GHI NHẬN SỰ KIỆN LÔ THUỐC (HỢP NHẤT) ---
    function recordBatchEvent(
        bytes32 batchId,
        bytes32 businessId,
        bytes32 eventType,
        bytes32 payloadHash
    ) external onlyWriter {
        if (batchId == bytes32(0) || businessId == bytes32(0) || eventType == bytes32(0) || payloadHash == bytes32(0)) {
            revert InvalidEventData();
        }

        // Tạo khóa sự kiện bằng abi.encodePacked theo chuẩn file cũ
        bytes32 eventKey = keccak256(abi.encodePacked(batchId, businessId, eventType));
        if (recordedEventKeys[eventKey]) revert DuplicateEvent();

        recordedEventKeys[eventKey] = true;

        emit BatchEventRecorded(
            batchId,
            businessId,
            msg.sender,
            eventType,
            payloadHash,
            block.timestamp
        );
    }

    // --- QUẢN LÝ CHUYỂN GIAO LÔ HÀNG ON-CHAIN ---
    function submitTransfer(bytes32 maChuyenGiao, address benNhan) external onlyWriter {
        if (maChuyenGiao == bytes32(0) || benNhan == address(0)) revert InvalidEventData();

        TransferRecord storage record = transfers[maChuyenGiao];
        record.maChuyenGiao = maChuyenGiao;
        record.benGui = msg.sender;
        record.benNhan = benNhan;
        record.status = TransferStatus.PENDING;
        record.thoiGian = block.timestamp;

        emit TransferSubmitted(maChuyenGiao, msg.sender, benNhan, block.timestamp);
    }

    function receiveTransfer(bytes32 maChuyenGiao) external onlyWriter {
        if (maChuyenGiao == bytes32(0)) revert InvalidEventData();

        TransferRecord storage record = transfers[maChuyenGiao];
        record.maChuyenGiao = maChuyenGiao;
        record.benNhan = msg.sender;
        record.status = TransferStatus.RECEIVED;
        record.thoiGian = block.timestamp;

        emit TransferReceived(maChuyenGiao, msg.sender, block.timestamp);
    }

    /**
     * @notice Từ chối tiếp nhận chuyển giao lô hàng
     * @param maChuyenGiao Mã chuyển giao dạng bytes32
     * @param lyDo Lý do từ chối tiếp nhận
     */
    function rejectTransfer(bytes32 maChuyenGiao, string calldata lyDo) public onlyWriter {
        if (maChuyenGiao == bytes32(0)) revert InvalidEventData();

        TransferRecord storage record = transfers[maChuyenGiao];
        record.maChuyenGiao = maChuyenGiao;
        record.benNhan = msg.sender;
        record.status = TransferStatus.REJECTED;
        record.thoiGian = block.timestamp;
        record.lyDo = lyDo;

        emit TransferRejected(maChuyenGiao, msg.sender, block.timestamp, lyDo);
    }

    /**
     * @notice Hủy chuyển giao / Từ chối tiếp nhận (Alias)
     */
    function cancelTransfer(bytes32 maChuyenGiao, string calldata lyDo) external onlyWriter {
        rejectTransfer(maChuyenGiao, lyDo);
    }
}