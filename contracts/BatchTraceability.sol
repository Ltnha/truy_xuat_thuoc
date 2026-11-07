// SPDX-License-Identifier: MIT
pragma solidity ^0.8.24;

contract BatchTraceability {

    // =====================================================
    // OWNER
    // =====================================================

    address public owner;

    modifier onlyOwner() {
        if (msg.sender != owner) revert Unauthorized();
        _;
    }

    constructor() {
        owner = msg.sender;

        _grantRole(
            msg.sender,
            ROLE_CO_QUAN
        );

        emit OwnershipTransferred(
            address(0),
            msg.sender
        );
    }

    // =====================================================
    // ROLES
    // =====================================================

    bytes32 public constant ROLE_CO_QUAN =
        keccak256("CO_QUAN_QUAN_LY");

    bytes32 public constant ROLE_NSX =
        keccak256("NHA_SAN_XUAT");

    bytes32 public constant ROLE_NPP =
        keccak256("NHA_PHAN_PHOI");

    bytes32 public constant ROLE_NHA_THUOC =
        keccak256("NHA_THUOC");

    mapping(address => mapping(bytes32 => bool))
        private roles;

    // =====================================================
    // BATCH
    // =====================================================

    struct BatchInfo {
        bool exists;
        bool recalled;
        address currentOwner;
        uint256 createdAt;
    }

    mapping(bytes32 => BatchInfo)
        public batches;

    // batch => transfer ids
    mapping(bytes32 => bytes32[])
        public batchTransfers;

    // =====================================================
    // EVENTS STORAGE
    // =====================================================

    mapping(bytes32 => bool)
        public recordedEventKeys;

    bytes32 public constant EVENT_PRODUCTION =
        keccak256("PRODUCTION");

    bytes32 public constant EVENT_RECALL_BATCH =
        keccak256("RECALL_BATCH");

    // =====================================================
    // TRANSFERS
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

    mapping(bytes32 => TransferRecord)
        public transfers;

    // =====================================================
    // ERRORS
    // =====================================================

    error Unauthorized();
    error InvalidAddress();
    error InvalidData();

    error BatchAlreadyExists();
    error BatchNotFound();
    error BatchRecalled();

    error DuplicateEvent();

    error TransferAlreadyExists();
    error TransferNotFound();
    error InvalidTransferStatus();

    error NotCurrentOwner();
    error NotTransferSender();
    error NotTransferReceiver();

    error RoleAlreadyGranted();
    error RoleNotGranted();

    // =====================================================
    // EVENTS
    // =====================================================

    event OwnershipTransferred(
        address indexed previousOwner,
        address indexed newOwner
    );

    event RoleGranted(
        address indexed account,
        bytes32 indexed role,
        address indexed sender
    );

    event RoleRevoked(
        address indexed account,
        bytes32 indexed role,
        address indexed sender
    );

    event BatchRegistered(
        bytes32 indexed batchId,
        address indexed manufacturer,
        uint256 timestamp
    );

    event BatchOwnerChanged(
        bytes32 indexed batchId,
        address indexed oldOwner,
        address indexed newOwner,
        uint256 timestamp
    );

    event BatchRecalled(
        bytes32 indexed batchId,
        address indexed actor,
        bytes32 payloadHash,
        uint256 timestamp
    );

    event BatchEventRecorded(
        bytes32 indexed batchId,
        bytes32 indexed businessId,
        address indexed actor,
        bytes32 eventType,
        bytes32 payloadHash,
        uint256 timestamp
    );

    event TransferSubmitted(
        bytes32 indexed transferId,
        bytes32 indexed batchId,
        address indexed sender,
        address receiver,
        uint256 timestamp
    );

    event TransferReceived(
        bytes32 indexed transferId,
        bytes32 indexed batchId,
        address indexed receiver,
        uint256 timestamp
    );

    event TransferRejected(
        bytes32 indexed transferId,
        bytes32 indexed batchId,
        address indexed receiver,
        bytes32 reasonHash,
        uint256 timestamp
    );

    event TransferCancelled(
        bytes32 indexed transferId,
        bytes32 indexed batchId,
        address indexed sender,
        uint256 timestamp
    );

    // =====================================================
    // MODIFIERS
    // =====================================================

    modifier onlyManufacturer() {
        if (
            !hasRole(
                msg.sender,
                ROLE_NSX
            )
        ) revert Unauthorized();

        _;
    }

    // =====================================================
    // ROLE MANAGEMENT
    // =====================================================

    function hasRole(
        address account,
        bytes32 role
    )
        public
        view
        returns(bool)
    {
        return roles[account][role];
    }

    function grantRole(
        address account,
        bytes32 role
    )
        external
        onlyOwner
    {
        if (
            account == address(0)
        ) revert InvalidAddress();

        if (
            roles[account][role]
        ) revert RoleAlreadyGranted();

        _grantRole(
            account,
            role
        );
    }

    function revokeRole(
        address account,
        bytes32 role
    )
        external
        onlyOwner
    {
        if (
            !roles[account][role]
        ) revert RoleNotGranted();

        roles[account][role] = false;

        emit RoleRevoked(
            account,
            role,
            msg.sender
        );
    }

    function _grantRole(
        address account,
        bytes32 role
    )
        internal
    {
        roles[account][role] = true;

        emit RoleGranted(
            account,
            role,
            msg.sender
        );
    }

    // =====================================================
    // BATCH REGISTRATION
    // =====================================================

    function registerBatch(
        bytes32 batchId
    )
        external
        onlyManufacturer
    {
        if (
            batchId == bytes32(0)
        ) revert InvalidData();

        if (
            batches[batchId].exists
        ) revert BatchAlreadyExists();

        batches[batchId] =
            BatchInfo({
                exists: true,
                recalled: false,
                currentOwner: msg.sender,
                createdAt: block.timestamp
            });

        emit BatchRegistered(
            batchId,
            msg.sender,
            block.timestamp
        );
    }

    // =====================================================
    // RECORD EVENT
    // =====================================================

    function recordBatchEvent(
        bytes32 batchId,
        bytes32 businessId,
        bytes32 eventType,
        bytes32 payloadHash
    )
        external
        onlyManufacturer
    {
        if (
            !batches[batchId].exists
        ) revert BatchNotFound();

        bytes32 eventKey =
            keccak256(
                abi.encodePacked(
                    batchId,
                    businessId,
                    eventType
                )
            );

        if (
            recordedEventKeys[eventKey]
        ) revert DuplicateEvent();

        recordedEventKeys[eventKey] =
            true;

        emit BatchEventRecorded(
            batchId,
            businessId,
            msg.sender,
            eventType,
            payloadHash,
            block.timestamp
        );
    }

    // =====================================================
    // RECALL
    // =====================================================

    function recallBatch(
        bytes32 batchId,
        bytes32 payloadHash
    )
        external
        onlyManufacturer
    {
        if (
            !batches[batchId].exists
        ) revert BatchNotFound();

        batches[batchId].recalled = true;

        emit BatchRecalled(
            batchId,
            msg.sender,
            payloadHash,
            block.timestamp
        );
    }

    // =====================================================
    // TRANSFER
    // =====================================================

    function submitTransfer(
        bytes32 transferId,
        bytes32 batchId,
        address receiver
    )
        external
    {
        if (
            receiver == address(0)
        ) revert InvalidAddress();

        if (
            !batches[batchId].exists
        ) revert BatchNotFound();

        if (
            batches[batchId].recalled
        ) revert BatchRecalled();

        if (
            batches[batchId]
                .currentOwner
            != msg.sender
        ) {
            revert NotCurrentOwner();
        }

        if (
            transfers[transferId]
                .transferId
            != bytes32(0)
        ) {
            revert TransferAlreadyExists();
        }

        // NSX -> NPP
        if (
            hasRole(
                msg.sender,
                ROLE_NSX
            )
        ) {
            if (
                !hasRole(
                    receiver,
                    ROLE_NPP
                )
            ) revert Unauthorized();
        }

        // NPP -> NHA THUOC
        else if (
            hasRole(
                msg.sender,
                ROLE_NPP
            )
        ) {
            if (
                !hasRole(
                    receiver,
                    ROLE_NHA_THUOC
                )
            ) revert Unauthorized();
        }
        else {
            revert Unauthorized();
        }

        transfers[transferId] =
            TransferRecord({
                transferId: transferId,
                batchId: batchId,
                sender: msg.sender,
                receiver: receiver,
                status: TransferStatus.PENDING,
                createdAt: block.timestamp,
                completedAt: 0,
                reasonHash: bytes32(0)
            });

        batchTransfers[
            batchId
        ].push(
            transferId
        );

        emit TransferSubmitted(
            transferId,
            batchId,
            msg.sender,
            receiver,
            block.timestamp
        );
    }

    function receiveTransfer(
        bytes32 transferId
    )
        external
    {
        TransferRecord storage tr =
            transfers[transferId];

        if (
            tr.transferId ==
            bytes32(0)
        ) {
            revert TransferNotFound();
        }

        if (
            tr.receiver
            != msg.sender
        ) {
            revert NotTransferReceiver();
        }

        if (
            tr.status
            != TransferStatus.PENDING
        ) {
            revert InvalidTransferStatus();
        }

        address oldOwner =
            batches[
                tr.batchId
            ].currentOwner;

        batches[
            tr.batchId
        ].currentOwner =
            msg.sender;

        tr.status =
            TransferStatus.RECEIVED;

        tr.completedAt =
            block.timestamp;

        emit BatchOwnerChanged(
            tr.batchId,
            oldOwner,
            msg.sender,
            block.timestamp
        );

        emit TransferReceived(
            transferId,
            tr.batchId,
            msg.sender,
            block.timestamp
        );
    }

    function rejectTransfer(
        bytes32 transferId,
        bytes32 reasonHash
    )
        external
    {
        TransferRecord storage tr =
            transfers[transferId];

        if (
            tr.transferId ==
            bytes32(0)
        ) {
            revert TransferNotFound();
        }

        if (
            tr.receiver
            != msg.sender
        ) {
            revert NotTransferReceiver();
        }

        if (
            tr.status
            != TransferStatus.PENDING
        ) {
            revert InvalidTransferStatus();
        }

        tr.status =
            TransferStatus.REJECTED;

        tr.reasonHash =
            reasonHash;

        tr.completedAt =
            block.timestamp;

        emit TransferRejected(
            transferId,
            tr.batchId,
            msg.sender,
            reasonHash,
            block.timestamp
        );
    }

    function cancelTransfer(
        bytes32 transferId
    )
        external
    {
        TransferRecord storage tr =
            transfers[transferId];

        if (
            tr.transferId ==
            bytes32(0)
        ) {
            revert TransferNotFound();
        }

        if (
            tr.sender != msg.sender
        ) {
            revert NotTransferSender();
        }

        if (
            tr.status
            != TransferStatus.PENDING
        ) {
            revert InvalidTransferStatus();
        }

        tr.status =
            TransferStatus.CANCELLED;

        tr.completedAt =
            block.timestamp;

        emit TransferCancelled(
            transferId,
            tr.batchId,
            msg.sender,
            block.timestamp
        );
    }

    // =====================================================
    // VIEW
    // =====================================================

    function getBatchOwner(
        bytes32 batchId
    )
        external
        view
        returns(address)
    {
        return batches[
            batchId
        ].currentOwner;
    }

    function getBatchTransferCount(
        bytes32 batchId
    )
        external
        view
        returns(uint256)
    {
        return
            batchTransfers[
                batchId
            ].length;
    }

    function isBatchRecalled(
        bytes32 batchId
    )
        external
        view
        returns(bool)
    {
        return
            batches[
                batchId
            ].recalled;
    }
}