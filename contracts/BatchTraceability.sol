// SPDX-License-Identifier: MIT
pragma solidity ^0.8.24;

contract BatchTraceability {

    // =====================================================
    // 1. OWNER
    // =====================================================

    address public owner;

    modifier onlyOwner() {
        if (msg.sender != owner) revert Unauthorized();
        _;
    }

    constructor() {
        owner = msg.sender;

        _grantRole(msg.sender, ROLE_CO_QUAN);

        emit OwnershipTransferred(address(0), msg.sender);
    }

    /// @notice Chuyển quyền owner. Role CO_QUAN được chuyển theo owner mới.
    function transferOwnership(address newOwner) external onlyOwner {
        if (newOwner == address(0)) revert InvalidAddress();

        address previousOwner = owner;
        owner = newOwner;

        if (!roles[newOwner][ROLE_CO_QUAN]) {
            _grantRole(newOwner, ROLE_CO_QUAN);
        }
        if (roles[previousOwner][ROLE_CO_QUAN]) {
            roles[previousOwner][ROLE_CO_QUAN] = false;
            emit RoleRevoked(previousOwner, ROLE_CO_QUAN, msg.sender);
        }

        emit OwnershipTransferred(previousOwner, newOwner);
    }

    // =====================================================
    // 2. ROLES
    // =====================================================

    bytes32 public constant ROLE_CO_QUAN   = keccak256("CO_QUAN_QUAN_LY");
    bytes32 public constant ROLE_NSX       = keccak256("NHA_SAN_XUAT");
    bytes32 public constant ROLE_NPP       = keccak256("NHA_PHAN_PHOI");
    bytes32 public constant ROLE_NHA_THUOC = keccak256("NHA_THUOC");

    mapping(address => mapping(bytes32 => bool)) private roles;

    // =====================================================
    // 3. BATCH & EVENTS STORAGE
    // =====================================================

    struct BatchInfo {
        bool exists;
        bool recalled;
        address manufacturer;   // NSX đã đăng ký lô (không đổi)
        address currentOwner;
        uint256 createdAt;
    }

    mapping(bytes32 => BatchInfo) public batches;
    mapping(bytes32 => bytes32[]) public batchTransfers;
    mapping(bytes32 => bool) public recordedEventKeys;

    // batchId => transferId đang PENDING (bytes32(0) nếu không có)
    mapping(bytes32 => bytes32) public pendingTransferOf;

    // productId => hash hồ sơ sản phẩm đã được Cơ quan quản lý duyệt (0 = chưa duyệt)
    mapping(bytes32 => bytes32) public approvedProducts;

    bytes32 public constant EVENT_PRODUCTION   = keccak256("PRODUCTION");
    bytes32 public constant EVENT_RECALL_BATCH = keccak256("RECALL_BATCH");

    // =====================================================
    // 4. TRANSFERS
    // =====================================================

    enum TransferStatus {
        NONE,
        PENDING,
        RECEIVED,
        REJECTED,
        CANCELLED
    }

    struct TransferRecord {
        bytes32 transferId;
        bytes32 batchId;
        address sender;
        address receiver;
        TransferStatus status;
        uint256 createdAt;
        uint256 completedAt;
        bytes32 reasonHash;
    }

    mapping(bytes32 => TransferRecord) public transfers;

    // =====================================================
    // 5. ERRORS
    // =====================================================

    error Unauthorized();
    error InvalidAddress();
    error InvalidData();
    error BatchAlreadyExists();
    error BatchNotFound();
    error BatchAlreadyRecalled();
    error DuplicateEvent();
    error TransferAlreadyExists();
    error TransferNotFound();
    error InvalidTransferStatus();
    error NotCurrentOwner();
    error NotTransferSender();
    error NotTransferReceiver();
    error RoleAlreadyGranted();
    error RoleNotGranted();
    error PendingTransferExists();
    error ProductAlreadyApproved();

    // =====================================================
    // 6. EVENTS
    // =====================================================

    event OwnershipTransferred(address indexed previousOwner, address indexed newOwner);
    event RoleGranted(address indexed account, bytes32 indexed role, address indexed sender);
    event RoleRevoked(address indexed account, bytes32 indexed role, address indexed sender);
    event ProductApproved(bytes32 indexed productId, address indexed approver, bytes32 payloadHash, uint256 timestamp);
    event BatchRegistered(bytes32 indexed batchId, address indexed manufacturer, uint256 timestamp);
    event BatchOwnerChanged(bytes32 indexed batchId, address indexed oldOwner, address indexed newOwner, uint256 timestamp);
    event BatchRecalled(bytes32 indexed batchId, address indexed actor, bytes32 payloadHash, uint256 timestamp);
    event BatchEventRecorded(bytes32 indexed batchId, bytes32 indexed businessId, address indexed actor, bytes32 eventType, bytes32 payloadHash, uint256 timestamp);
    event TransferSubmitted(bytes32 indexed transferId, bytes32 indexed batchId, address indexed sender, address receiver, uint256 timestamp);
    event TransferReceived(bytes32 indexed transferId, bytes32 indexed batchId, address indexed receiver, uint256 timestamp);
    event TransferRejected(bytes32 indexed transferId, bytes32 indexed batchId, address indexed receiver, bytes32 reasonHash, uint256 timestamp);
    event TransferCancelled(bytes32 indexed transferId, bytes32 indexed batchId, address indexed sender, uint256 timestamp);

    // =====================================================
    // 7. MODIFIERS
    // =====================================================

    modifier onlyManufacturer() {
        if (!hasRole(msg.sender, ROLE_NSX)) revert Unauthorized();
        _;
    }

    // =====================================================
    // 8. ROLE MANAGEMENT
    // =====================================================

    function hasRole(address account, bytes32 role) public view returns (bool) {
        return roles[account][role];
    }

    function grantRole(address account, bytes32 role) external onlyOwner {
        if (account == address(0)) revert InvalidAddress();
        if (roles[account][role]) revert RoleAlreadyGranted();
        _grantRole(account, role);
    }

    function revokeRole(address account, bytes32 role) external onlyOwner {
        if (!roles[account][role]) revert RoleNotGranted();
        roles[account][role] = false;
        emit RoleRevoked(account, role, msg.sender);
    }

    function _grantRole(address account, bytes32 role) internal {
        roles[account][role] = true;
        emit RoleGranted(account, role, msg.sender);
    }

    // =====================================================
    // 9A. PRODUCT APPROVAL
    // =====================================================

    /// @notice Cơ quan quản lý ký duyệt sản phẩm và ghi dấu vết lên chain.
    /// @dev Chỉ ghi nhận (lưu hash + phát event), KHÔNG ép buộc trong registerBatch.
    ///      Việc chỉ cho tạo lô từ sản phẩm đã duyệt do backend kiểm soát.
    /// @param productId   định danh sản phẩm (hash tính ở off-chain)
    /// @param payloadHash hash của hồ sơ sản phẩm lúc được duyệt
    function approveProduct(bytes32 productId, bytes32 payloadHash) external {
        if (!hasRole(msg.sender, ROLE_CO_QUAN)) revert Unauthorized();
        if (productId == bytes32(0) || payloadHash == bytes32(0)) revert InvalidData();
        if (approvedProducts[productId] != bytes32(0)) revert ProductAlreadyApproved();

        approvedProducts[productId] = payloadHash;

        emit ProductApproved(productId, msg.sender, payloadHash, block.timestamp);
    }

    function isProductApproved(bytes32 productId) external view returns (bool) {
        return approvedProducts[productId] != bytes32(0);
    }

    // =====================================================
    // 9. BATCH REGISTRATION & EVENTS
    // =====================================================

    /// @dev Việc chỉ cho phép đăng ký lô từ sản phẩm đã được duyệt
    ///      được kiểm soát ở off-chain (backend).
    function registerBatch(bytes32 batchId) external onlyManufacturer {
        if (batchId == bytes32(0)) revert InvalidData();
        if (batches[batchId].exists) revert BatchAlreadyExists();

        batches[batchId] = BatchInfo({
            exists: true,
            recalled: false,
            manufacturer: msg.sender,
            currentOwner: msg.sender,
            createdAt: block.timestamp
        });

        emit BatchRegistered(batchId, msg.sender, block.timestamp);
    }

    /// @notice Ghi sự kiện của lô.
    ///  - NSX tạo ra lô được ghi sự kiện cho lô của mình.
    ///  - NPP / Nhà thuốc được ghi khi đang là chủ hiện tại của lô.
    ///  - Không ghi được khi lô đã bị thu hồi.
    ///  - Sự kiện thu hồi phải đi qua recallBatch.
    function recordBatchEvent(
        bytes32 batchId,
        bytes32 businessId,
        bytes32 eventType,
        bytes32 payloadHash
    ) external {
        BatchInfo storage b = batches[batchId];
        if (!b.exists) revert BatchNotFound();
        if (b.recalled) revert BatchAlreadyRecalled();
        if (eventType == bytes32(0) || eventType == EVENT_RECALL_BATCH) revert InvalidData();

        bool isManufacturerOfBatch =
            msg.sender == b.manufacturer && hasRole(msg.sender, ROLE_NSX);
        bool isHolder =
            msg.sender == b.currentOwner &&
            (hasRole(msg.sender, ROLE_NPP) || hasRole(msg.sender, ROLE_NHA_THUOC));

        if (!isManufacturerOfBatch && !isHolder) revert Unauthorized();

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

    /// @notice Thu hồi lô: chỉ NSX tạo lô hoặc Cơ quan quản lý.
    function recallBatch(bytes32 batchId, bytes32 payloadHash) external {
        BatchInfo storage b = batches[batchId];
        if (!b.exists) revert BatchNotFound();
        if (b.recalled) revert BatchAlreadyRecalled();

        bool isManufacturerOfBatch =
            msg.sender == b.manufacturer && hasRole(msg.sender, ROLE_NSX);
        bool isAuthority = hasRole(msg.sender, ROLE_CO_QUAN);

        if (!isManufacturerOfBatch && !isAuthority) revert Unauthorized();

        b.recalled = true;

        emit BatchRecalled(batchId, msg.sender, payloadHash, block.timestamp);
    }

    // =====================================================
    // 10. TRANSFER MANAGEMENT
    // =====================================================

    /// @dev Tuyến hợp lệ: NSX -> NPP, NPP -> Nhà thuốc.
    function _checkRoute(address sender, address receiver) internal view {
        if (hasRole(sender, ROLE_NSX)) {
            if (!hasRole(receiver, ROLE_NPP)) revert Unauthorized();
        } else if (hasRole(sender, ROLE_NPP)) {
            if (!hasRole(receiver, ROLE_NHA_THUOC)) revert Unauthorized();
        } else {
            revert Unauthorized();
        }
    }

    function submitTransfer(bytes32 transferId, bytes32 batchId, address receiver) external {
        if (transferId == bytes32(0)) revert InvalidData();
        if (receiver == address(0)) revert InvalidAddress();

        BatchInfo storage b = batches[batchId];
        if (!b.exists) revert BatchNotFound();
        if (b.recalled) revert BatchAlreadyRecalled();
        if (b.currentOwner != msg.sender) revert NotCurrentOwner();
        if (transfers[transferId].transferId != bytes32(0)) revert TransferAlreadyExists();
        if (pendingTransferOf[batchId] != bytes32(0)) revert PendingTransferExists();

        _checkRoute(msg.sender, receiver);

        transfers[transferId] = TransferRecord({
            transferId: transferId,
            batchId: batchId,
            sender: msg.sender,
            receiver: receiver,
            status: TransferStatus.PENDING,
            createdAt: block.timestamp,
            completedAt: 0,
            reasonHash: bytes32(0)
        });

        pendingTransferOf[batchId] = transferId;
        batchTransfers[batchId].push(transferId);

        emit TransferSubmitted(transferId, batchId, msg.sender, receiver, block.timestamp);
    }

    function receiveTransfer(bytes32 transferId) external {
        TransferRecord storage tr = transfers[transferId];
        if (tr.transferId == bytes32(0)) revert TransferNotFound();
        if (tr.receiver != msg.sender) revert NotTransferReceiver();
        if (tr.status != TransferStatus.PENDING) revert InvalidTransferStatus();

        BatchInfo storage b = batches[tr.batchId];
        if (b.recalled) revert BatchAlreadyRecalled();
        if (b.currentOwner != tr.sender) revert NotCurrentOwner();

        // Người nhận phải còn role phù hợp tại thời điểm nhận
        _checkRoute(tr.sender, msg.sender);

        address oldOwner = b.currentOwner;
        b.currentOwner = msg.sender;
        tr.status = TransferStatus.RECEIVED;
        tr.completedAt = block.timestamp;
        delete pendingTransferOf[tr.batchId];

        emit BatchOwnerChanged(tr.batchId, oldOwner, msg.sender, block.timestamp);
        emit TransferReceived(transferId, tr.batchId, msg.sender, block.timestamp);
    }

    function rejectTransfer(bytes32 transferId, bytes32 reasonHash) external {
        TransferRecord storage tr = transfers[transferId];
        if (tr.transferId == bytes32(0)) revert TransferNotFound();
        if (tr.receiver != msg.sender) revert NotTransferReceiver();
        if (tr.status != TransferStatus.PENDING) revert InvalidTransferStatus();

        tr.status = TransferStatus.REJECTED;
        tr.reasonHash = reasonHash;
        tr.completedAt = block.timestamp;
        delete pendingTransferOf[tr.batchId];

        emit TransferRejected(transferId, tr.batchId, msg.sender, reasonHash, block.timestamp);
    }

    function cancelTransfer(bytes32 transferId) external {
        TransferRecord storage tr = transfers[transferId];
        if (tr.transferId == bytes32(0)) revert TransferNotFound();
        if (tr.sender != msg.sender) revert NotTransferSender();
        if (tr.status != TransferStatus.PENDING) revert InvalidTransferStatus();

        tr.status = TransferStatus.CANCELLED;
        tr.completedAt = block.timestamp;
        delete pendingTransferOf[tr.batchId];

        emit TransferCancelled(transferId, tr.batchId, msg.sender, block.timestamp);
    }

    // =====================================================
    // 11. VIEW FUNCTIONS
    // =====================================================

    function getBatchOwner(bytes32 batchId) external view returns (address) {
        return batches[batchId].currentOwner;
    }

    function getBatchTransferCount(bytes32 batchId) external view returns (uint256) {
        return batchTransfers[batchId].length;
    }

    /// @notice Trả về toàn bộ transferId của lô theo thứ tự thời gian.
    function getBatchTransfers(bytes32 batchId) external view returns (bytes32[] memory) {
        return batchTransfers[batchId];
    }

    function isBatchRecalled(bytes32 batchId) external view returns (bool) {
        return batches[batchId].recalled;
    }

    function isBatchEventRecorded(
        bytes32 batchId,
        bytes32 businessId,
        bytes32 eventType
    ) external view returns (bool) {
        bytes32 eventKey = keccak256(abi.encodePacked(batchId, businessId, eventType));
        return recordedEventKeys[eventKey];
    }
}
