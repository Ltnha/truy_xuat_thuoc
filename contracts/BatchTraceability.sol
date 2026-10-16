// SPDX-License-Identifier: MIT
pragma solidity ^0.8.24;

contract BatchTraceability {
    address public owner;
    mapping(address => bool) public writers;
    mapping(bytes32 => bool) public recordedEvents;

    event BatchEventRecorded(
        bytes32 indexed batchId,
        bytes32 indexed businessId,
        address indexed actor,
        bytes32 eventType,
        bytes32 payloadHash,
        uint256 timestamp
    );

    error Unauthorized();
    error DuplicateEvent();

    modifier onlyOwner() {
        if (msg.sender != owner) revert Unauthorized();
        _;
    }

    modifier onlyWriter() {
        if (!writers[msg.sender]) revert Unauthorized();
        _;
    }

    constructor() {
        owner = msg.sender;
        writers[msg.sender] = true;
    }

    function setWriter(address writer, bool enabled) external onlyOwner {
        writers[writer] = enabled;
    }

    function recordBatchEvent(
        bytes32 batchId,
        bytes32 businessId,
        bytes32 eventType,
        bytes32 payloadHash
    ) external onlyWriter {
        bytes32 eventKey = keccak256(abi.encodePacked(batchId, businessId, eventType));
        if (recordedEvents[eventKey]) revert DuplicateEvent();

        recordedEvents[eventKey] = true;

        emit BatchEventRecorded(
            batchId,
            businessId,
            msg.sender,
            eventType,
            payloadHash,
            block.timestamp
        );
    }
}