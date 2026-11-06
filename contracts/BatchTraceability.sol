// SPDX-License-Identifier: MIT
pragma solidity ^0.8.24;

contract BatchTraceability {

    // --- 1. QUẢN TỰ VÀ PHÂN QUYỀN TRỰC TIẾP (LEGACY WRITERS) ---
    address public owner;
    mapping(address => bool) public writers;

    // --- 2. VAI TRÒ DOANH NGHIỆP ON-CHAIN (ROLE MANAGEMENT) ---
    bytes32 public constant ROLE_CO_QUAN   = keccak256("CO_QUAN_QUAN_LY");
    bytes32 public constant ROLE_NSX       = keccak256("NHA_SAN_XUAT");
    bytes32 public constant ROLE_NPP       = keccak256("NHA_PHAN_PHOI");
    bytes32 public constant ROLE_NHA_THUOC = keccak256("NHA_THUOC");

    // Bảng lưu trữ vai trò: địa chỉ ví => (mã vai trò => true/false)
    mapping(address => mapping(bytes32 => bool)) private _roles;

    // --- 3. QUẢN LÝ SỰ KIỆN LÔ THUỐC ON-CHAIN ---
    // Đánh dấu event key đã ghi để tránh ghi trùng lặp (Idempotency)
    mapping(bytes32 => bool) public recordedEventKeys;

    // Mã băm định danh loại sự kiện Thu hồi lô
    bytes32 public constant EVENT_RECALL_BATCH = keccak256("RECALL_BATCH");

    // --- 4. CẤU TRÚC & SỰ KIỆN CHUYỂN GIAO LÔ HÀNG (TRANSFER MANAGEMENT) ---
    enum TransferStatus { NONE, PENDING, RECEIVED, REJECTED, CANCELLED }

    struct TransferRecord {
        bytes32 maChuyenGiao;
        address benGui;
        address benNhan;
        TransferStatus status;
        uint256 thoiGian;
        string lyDo;
    }

    mapping(bytes32 => TransferRecord) public transfers;

    // --- 5. SỰ KIỆN (EVENTS) ---
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

    event TransferCancelled(
        bytes32 indexed maChuyenGiao,
        address indexed benGui,
        uint256 thoiGian,
        string lyDo
    );

    // --- 6. LỖI TỰ ĐỊNH NGHĨA (CUSTOM ERRORS - TỐI ƯU GAS) ---
    error Unauthorized();
    error InvalidAddress();
    error InvalidEventData();
    error DuplicateEvent();
    error RoleAlreadyGranted();
    error RoleNotGranted();

    // --- 7. MODIFIERS ---
    modifier onlyOwner() {
        if (msg.sender != owner) revert Unauthorized();
        _;
    }

    // Cho phép gọi nếu là Writer trực tiếp HOẶC có Vai trò doanh nghiệp hợp lệ
    modifier onlyWriter() {
        if (!writers[msg.sender] && !isAuthorizedRole(msg.sender)) revert Unauthorized();
        _;
    }

    modifier onlyTransferSender() {
        if (!_roles[msg.sender][ROLE_NSX] && !_roles[msg.sender][ROLE_NPP]) revert Unauthorized();
        _;
    }

    modifier onlyTransferReceiver() {
        if (!_roles[msg.sender][ROLE_NPP] && !_roles[msg.sender][ROLE_NHA_THUOC]) revert Unauthorized();
        _;
    }

    // --- 8. CONSTRUCTOR ---
    constructor() {
        owner = msg.sender;
        writers[msg.sender] = true; // Thiết lập người deploy là writer

        emit OwnershipTransferred(address(0), msg.sender);
        emit WriterPermissionChanged(msg.sender, true);

        // Tự động cấp vai trò Cơ quan quản lý cho tài khoản Owner
        _grantRole(msg.sender, ROLE_CO_QUAN);
    }

    // --- 9. QUẢN LÝ WRITER TRỰC TIẾP & OWNER ---
    function setWriter(address writer, bool enabled) external onlyOwner {
        if (writer == address(0)) revert InvalidAddress();
        writers[writer] = enabled;
        emit WriterPermissionChanged(writer, enabled);
    }

    function transferOwnership(address newOwner) external onlyOwner {
        if (newOwner == address(0)) revert InvalidAddress();
        address previousOwner = owner;
        owner = newOwner;
        emit OwnershipTransferred(previousOwner, newOwner);
    }

    // --- 10. QUẢN LÝ VAI TRÒ ON-CHAIN (ROLE MANAGEMENT) ---
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

    // --- 11. GHI NHẬN SỰ KIỆN LÔ THUỐC (RECORD BATCH EVENT) ---
    function recordBatchEvent(
        bytes32 batchId,
        bytes32 businessId,
        bytes32 eventType,
        bytes32 payloadHash
    ) external onlyWriter {
        if (batchId == bytes32(0) || businessId == bytes32(0) || eventType == bytes32(0) || payloadHash == bytes32(0)) {
            revert InvalidEventData();
        }

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

    // --- 12. QUẢN LÝ CHUYỂN GIAO LÔ HÀNG ON-CHAIN ---
    function submitTransfer(bytes32 maChuyenGiao, address benNhan) external onlyTransferSender {
        if (maChuyenGiao == bytes32(0) || benNhan == address(0) || benNhan == msg.sender) revert InvalidEventData();
        if (!_roles[benNhan][ROLE_NPP] && !_roles[benNhan][ROLE_NHA_THUOC]) revert Unauthorized();

        TransferRecord storage record = transfers[maChuyenGiao];
        if (record.status != TransferStatus.NONE) revert DuplicateEvent();

        record.maChuyenGiao = maChuyenGiao;
        record.benGui = msg.sender;
        record.benNhan = benNhan;
        record.status = TransferStatus.PENDING;
        record.thoiGian = block.timestamp;

        emit TransferSubmitted(maChuyenGiao, msg.sender, benNhan, block.timestamp);
    }

    function receiveTransfer(bytes32 maChuyenGiao) external onlyTransferReceiver {
        TransferRecord storage record = transfers[maChuyenGiao];
        if (record.status != TransferStatus.PENDING || record.benNhan != msg.sender) revert Unauthorized();

        record.status = TransferStatus.RECEIVED;
        record.thoiGian = block.timestamp;
        emit TransferReceived(maChuyenGiao, msg.sender, block.timestamp);
    }

    function rejectTransfer(bytes32 maChuyenGiao, string calldata lyDo) external onlyTransferReceiver {
        TransferRecord storage record = transfers[maChuyenGiao];
        if (record.status != TransferStatus.PENDING || record.benNhan != msg.sender) revert Unauthorized();

        record.status = TransferStatus.REJECTED;
        record.thoiGian = block.timestamp;
        record.lyDo = lyDo;
        emit TransferRejected(maChuyenGiao, msg.sender, block.timestamp, lyDo);
    }

    function cancelTransfer(bytes32 maChuyenGiao, string calldata lyDo) external onlyTransferSender {
        TransferRecord storage record = transfers[maChuyenGiao];
        if (record.status != TransferStatus.PENDING || record.benGui != msg.sender) revert Unauthorized();

        record.status = TransferStatus.CANCELLED;
        record.thoiGian = block.timestamp;
        record.lyDo = lyDo;
        emit TransferCancelled(maChuyenGiao, msg.sender, block.timestamp, lyDo);
    }

    // --- 13. HÀM ĐỌC HỖ TRỢ ĐỒNG BỘ BACKEND & SIGNER SERVICE (VIEW FUNCTIONS) ---

    /**
     * @dev Kiểm tra xem một sự kiện cụ thể của Lô thuốc đã được ghi nhận On-chain chưa
     * Phục vụ API GET /events/check-batch từ Signer Service
     */
    function isBatchEventRecorded(
        bytes32 batchId,
        bytes32 businessId,
        bytes32 eventType
    ) external view returns (bool) {
        bytes32 eventKey = keccak256(abi.encodePacked(batchId, businessId, eventType));
        return recordedEventKeys[eventKey];
    }

    /**
     * @dev Kiểm tra nhanh xem Lô thuốc đã có quyết định thu hồi trên Blockchain chưa
     */
    function isBatchRecalled(bytes32 batchId, bytes32 businessId) external view returns (bool) {
        bytes32 eventKey = keccak256(abi.encodePacked(batchId, businessId, EVENT_RECALL_BATCH));
        return recordedEventKeys[eventKey];
    }
}