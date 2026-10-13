// SPDX-License-Identifier: MIT
// Khai bao contract dung Solidity 0.8.x.
pragma solidity ^0.8.24;

contract BatchTraceability {
    // Dia chi quan tri contract; tai khoan deploy ban dau la owner.
    address public owner;

    // Danh sach dia chi duoc phep ghi event. Owner khong tu dong la writer.
    mapping(address => bool) public writers;

    // Danh dau event key da ghi de chan ghi trung.
    mapping(bytes32 => bool) public recordedEventKeys;

    // Trang thai cong khai toi thieu de ung dung va nguoi tieu dung co the tu kiem tra.
    mapping(bytes32 => uint8) public productStatuses;
    mapping(bytes32 => uint8) public organizationStatuses;
    mapping(bytes32 => bool) public registeredBatches;
    mapping(bytes32 => bytes32) public batchProducts;
    mapping(bytes32 => uint256) public batchExpiryDates;
    mapping(bytes32 => bool) public recalledBatches;
    mapping(bytes32 => bool) public dispensedUnits;
    mapping(bytes32 => bytes32) public unitBatchIds;
    mapping(bytes32 => uint8) public transferStatuses;

    struct TransferRecord {
        bytes32 batchId;
        bytes32 fromOrganization;
        bytes32 toOrganization;
        uint256 quantity;
    }

    mapping(bytes32 => TransferRecord) private transferRecords;

    // Phat khi quyen owner duoc chuyen.
    event OwnershipTransferred(address indexed previousOwner, address indexed newOwner);

    // Phat khi owner cap hoac thu hoi quyen writer.
    event WriterPermissionChanged(address indexed writer, bool enabled);

    // Su kien cong khai dau vet mot nghiep vu gan voi lo thuoc.
    event BatchEventRecorded(
        // Hash dinh danh lo thuoc, khong phai ma lo dang chuoi ro.
        bytes32 indexed batchId,
        // Hash dinh danh duy nhat cho giao dich nghiep vu nay.
        bytes32 indexed businessId,
        // Dia chi vi da gui giao dich len contract.
        address indexed actor,
        // Hash ten loai su kien, vi du keccak256("BatchRegistered").
        bytes32 eventType,
        // Hash du lieu nghiep vu da chuan hoa; khong luu du lieu goc o day.
        bytes32 payloadHash,
        // Thoi diem block ghi nhan event, tinh theo Unix timestamp.
        uint256 timestamp
    );

    event ProductStatusChanged(
        bytes32 indexed productId,
        uint8 status,
        bytes32 payloadHash,
        uint256 timestamp
    );

    event OrganizationStatusChanged(
        bytes32 indexed organizationId,
        uint8 status,
        bytes32 payloadHash,
        uint256 timestamp
    );

    event BatchRegistered(
        bytes32 indexed batchId,
        bytes32 indexed productId,
        address indexed actor,
        bytes32 payloadHash,
        uint256 timestamp
    );

    event TransferStatusChanged(
        bytes32 indexed batchId,
        bytes32 indexed transferId,
        bytes32 fromOrganization,
        bytes32 toOrganization,
        uint256 quantity,
        uint8 status,
        bytes32 payloadHash,
        uint256 timestamp
    );

    event UnitDispensed(
        bytes32 indexed unitId,
        bytes32 indexed batchId,
        address indexed actor,
        bytes32 payloadHash,
        uint256 timestamp
    );

    event BatchRecalled(
        bytes32 indexed batchId,
        address indexed actor,
        bytes32 payloadHash,
        uint256 timestamp
    );

    // Loi tuy chinh khi nguoi goi khong co quyen.
    error Unauthorized();
    // Loi khi nhap dia chi rong.
    error InvalidAddress();
    // Loi khi mot trong cac truong bytes32 la gia tri 0.
    error InvalidEventData();
    // Loi khi event key nay da duoc ghi truoc do.
    error DuplicateEvent();
    error InvalidStatus();
    error ProductNotApproved();
    error BatchNotFound();
    error BatchAlreadyRecalled();
    error InvalidTransferState();
    error BatchExpired();

    // Chay mot lan luc deploy, gan nguoi deploy lam owner.
    constructor() {
        owner = msg.sender;
        emit OwnershipTransferred(address(0), msg.sender);
    }

    // Chan ham ben duoi neu nguoi goi khong phai owner.
    modifier onlyOwner() {
        if (msg.sender != owner) revert Unauthorized();
        _;
    }

    // Chan ham ben duoi neu nguoi goi chua duoc cap quyen writer.
    modifier onlyWriter() {
        if (!writers[msg.sender]) revert Unauthorized();
        _;
    }

    // Owner bat/tat quyen ghi cho mot dia chi vi.
    function setWriter(address writer, bool enabled) external onlyOwner {
        if (writer == address(0)) revert InvalidAddress();
        writers[writer] = enabled;
        emit WriterPermissionChanged(writer, enabled);
    }

    // Owner chuyen quyen quan tri sang dia chi moi.
    function transferOwnership(address newOwner) external onlyOwner {
        if (newOwner == address(0)) revert InvalidAddress();
        address previousOwner = owner;
        owner = newOwner;
        emit OwnershipTransferred(previousOwner, newOwner);
    }

    function setProductStatus(bytes32 productId, uint8 status, bytes32 payloadHash) external onlyWriter {
        if (productId == bytes32(0) || payloadHash == bytes32(0)) revert InvalidEventData();
        if (status != 1 && status != 2) revert InvalidStatus();

        productStatuses[productId] = status;
        emit ProductStatusChanged(productId, status, payloadHash, block.timestamp);
    }

    function setOrganizationStatus(bytes32 organizationId, uint8 status, bytes32 payloadHash) external onlyWriter {
        if (organizationId == bytes32(0) || payloadHash == bytes32(0)) revert InvalidEventData();
        if (status != 1 && status != 2) revert InvalidStatus();

        organizationStatuses[organizationId] = status;
        emit OrganizationStatusChanged(organizationId, status, payloadHash, block.timestamp);
    }

    function recordAuditEvent(bytes32 businessId, bytes32 eventType, bytes32 payloadHash) external onlyWriter {
        if (businessId == bytes32(0) || eventType == bytes32(0) || payloadHash == bytes32(0)) {
            revert InvalidEventData();
        }
        bytes32 eventKey = keccak256(abi.encode(businessId, eventType));
        if (recordedEventKeys[eventKey]) revert DuplicateEvent();
        recordedEventKeys[eventKey] = true;
        emit BatchEventRecorded(businessId, eventKey, msg.sender, eventType, payloadHash, block.timestamp);
    }

    function registerBatch(
        bytes32 batchId,
        bytes32 productId,
        uint256 expiresAt,
        bytes32 payloadHash
    ) external onlyWriter {
        if (batchId == bytes32(0) || productId == bytes32(0) || payloadHash == bytes32(0)) {
            revert InvalidEventData();
        }
        if (productStatuses[productId] != 1) revert ProductNotApproved();
        if (registeredBatches[batchId]) revert DuplicateEvent();
        if (expiresAt <= block.timestamp) revert BatchExpired();

        registeredBatches[batchId] = true;
        batchProducts[batchId] = productId;
        batchExpiryDates[batchId] = expiresAt;
        emit BatchRegistered(batchId, productId, msg.sender, payloadHash, block.timestamp);
    }

    function recordTransfer(
        bytes32 batchId,
        bytes32 transferId,
        bytes32 fromOrganization,
        bytes32 toOrganization,
        uint256 quantity,
        uint8 status,
        bytes32 payloadHash
    ) external onlyWriter {
        if (
            batchId == bytes32(0)
            || transferId == bytes32(0)
            || fromOrganization == bytes32(0)
            || toOrganization == bytes32(0)
            || payloadHash == bytes32(0)
            || quantity == 0
        ) revert InvalidEventData();
        if (!registeredBatches[batchId]) revert BatchNotFound();
        if (recalledBatches[batchId]) revert BatchAlreadyRecalled();

        if (status == 1) {
            if (transferStatuses[transferId] != 0) revert DuplicateEvent();
            transferRecords[transferId] = TransferRecord(batchId, fromOrganization, toOrganization, quantity);
        } else if (status == 2) {
            TransferRecord memory transferRecord = transferRecords[transferId];
            if (
                transferStatuses[transferId] != 1
                || transferRecord.batchId != batchId
                || transferRecord.fromOrganization != fromOrganization
                || transferRecord.toOrganization != toOrganization
                || transferRecord.quantity != quantity
            ) revert InvalidTransferState();
        } else {
            revert InvalidStatus();
        }

        transferStatuses[transferId] = status;
        emit TransferStatusChanged(
            batchId,
            transferId,
            fromOrganization,
            toOrganization,
            quantity,
            status,
            payloadHash,
            block.timestamp
        );
    }

    function dispenseUnit(bytes32 unitId, bytes32 batchId, bytes32 payloadHash) external onlyWriter {
        if (unitId == bytes32(0) || batchId == bytes32(0) || payloadHash == bytes32(0)) {
            revert InvalidEventData();
        }
        if (!registeredBatches[batchId]) revert BatchNotFound();
        if (recalledBatches[batchId]) revert BatchAlreadyRecalled();
        if (batchExpiryDates[batchId] < block.timestamp) revert BatchExpired();
        if (dispensedUnits[unitId]) revert DuplicateEvent();

        dispensedUnits[unitId] = true;
        unitBatchIds[unitId] = batchId;
        emit UnitDispensed(unitId, batchId, msg.sender, payloadHash, block.timestamp);
    }

    function recallBatch(bytes32 batchId, bytes32 payloadHash) external onlyWriter {
        if (batchId == bytes32(0) || payloadHash == bytes32(0)) revert InvalidEventData();
        if (!registeredBatches[batchId]) revert BatchNotFound();
        if (recalledBatches[batchId]) revert BatchAlreadyRecalled();

        recalledBatches[batchId] = true;
        emit BatchRecalled(batchId, msg.sender, payloadHash, block.timestamp);
    }

    // Ghi mot event cho lo thuoc. Ca bon tham so bytes32 phai la 32 byte
    // (64 ky tu hex sau 0x) va khong duoc bang 0.
    function recordBatchEvent(
        // Hash cua ma lo nghiep vu, vi du keccak256("LOT-2026-001").
        bytes32 batchId,
        // Hash ma giao dich nghiep vu; can khac nhau giua cac su kien.
        bytes32 businessId,
        // Hash ten su kien, vi du keccak256("BatchRegistered").
        bytes32 eventType,
        // Hash payload du lieu nghiep vu da chuan hoa.
        bytes32 payloadHash
    ) external onlyWriter {
        // Chan gia tri rong; Solidity bytes32(0) la 32 byte deu bang 0.
        if (batchId == bytes32(0) || businessId == bytes32(0) || eventType == bytes32(0) || payloadHash == bytes32(0)) {
            revert InvalidEventData();
        }

        // Tao khoa duy nhat tu lo + giao dich + loai su kien.
        bytes32 eventKey = keccak256(abi.encode(batchId, businessId, eventType));
        // Neu khoa da ton tai thi dung, tranh ghi lap cung mot su kien.
        if (recordedEventKeys[eventKey]) revert DuplicateEvent();

        // Danh dau da ghi truoc khi phat event.
        recordedEventKeys[eventKey] = true;
        // Event duoc ghi vao log cua blockchain; payload goc khong duoc luu.
        emit BatchEventRecorded(batchId, businessId, msg.sender, eventType, payloadHash, block.timestamp);
    }
}
