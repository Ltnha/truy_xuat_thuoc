// SPDX-License-Identifier: MIT
pragma solidity ^0.8.24;

/// @title ProductApproval
/// @notice Separate on-chain approval ledger for pharmaceutical product registrations.
/// @dev Kept separate from BatchTraceability so deploying this contract does not reset
///      existing batches, transfers, or organization roles in the traceability contract.
contract ProductApproval {
    address public owner;

    bytes32 public constant ROLE_CO_QUAN = keccak256("CO_QUAN_QUAN_LY");

    mapping(address => mapping(bytes32 => bool)) private roles;
    mapping(bytes32 => bool) public approvedProducts;

    error Unauthorized();
    error InvalidAddress();
    error InvalidData();
    error RoleAlreadyGranted();
    error RoleNotGranted();
    error ProductAlreadyApproved();

    event OwnershipTransferred(address indexed previousOwner, address indexed newOwner);
    event RoleGranted(address indexed account, bytes32 indexed role, address indexed sender);
    event RoleRevoked(address indexed account, bytes32 indexed role, address indexed sender);
    event ProductApproved(
        bytes32 indexed productId,
        bytes32 indexed manufacturerId,
        address indexed approver,
        bytes32 payloadHash,
        uint256 timestamp
    );

    modifier onlyOwner() {
        if (msg.sender != owner) revert Unauthorized();
        _;
    }

    constructor() {
        owner = msg.sender;
        roles[msg.sender][ROLE_CO_QUAN] = true;
        emit RoleGranted(msg.sender, ROLE_CO_QUAN, msg.sender);
        emit OwnershipTransferred(address(0), msg.sender);
    }

    function hasRole(address account, bytes32 role) external view returns (bool) {
        return roles[account][role];
    }

    function grantRole(address account, bytes32 role) external onlyOwner {
        if (account == address(0)) revert InvalidAddress();
        if (roles[account][role]) revert RoleAlreadyGranted();
        roles[account][role] = true;
        emit RoleGranted(account, role, msg.sender);
    }

    function revokeRole(address account, bytes32 role) external onlyOwner {
        if (!roles[account][role]) revert RoleNotGranted();
        roles[account][role] = false;
        emit RoleRevoked(account, role, msg.sender);
    }

    function approveProduct(
        bytes32 productId,
        bytes32 manufacturerId,
        bytes32 payloadHash
    ) external {
        if (!roles[msg.sender][ROLE_CO_QUAN]) revert Unauthorized();
        if (
            productId == bytes32(0) ||
            manufacturerId == bytes32(0) ||
            payloadHash == bytes32(0)
        ) revert InvalidData();
        if (approvedProducts[productId]) revert ProductAlreadyApproved();

        approvedProducts[productId] = true;
        emit ProductApproved(
            productId,
            manufacturerId,
            msg.sender,
            payloadHash,
            block.timestamp
        );
    }
}
