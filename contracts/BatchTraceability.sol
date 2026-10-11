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

    // Loi tuy chinh khi nguoi goi khong co quyen.
    error Unauthorized();
    // Loi khi nhap dia chi rong.
    error InvalidAddress();
    // Loi khi mot trong cac truong bytes32 la gia tri 0.
    error InvalidEventData();
    // Loi khi event key nay da duoc ghi truoc do.
    error DuplicateEvent();

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
