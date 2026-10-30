# Project Context & Architecture Summary for AI Assistants

## 1. Project Overview
- **Project Name:** `truy_xuat_thuoc` (Pharmaceutical Traceability System)
- **Architecture Model:** Hybrid (Off-chain MySQL + On-chain Event Anchoring)
- **Core Tech Stack:**
  - **Backend:** Laravel (PHP, MVC + Service Layer)
  - **Database:** MySQL
  - **Blockchain:** Ethereum Sepolia Testnet
  - **Smart Contract:** `contracts/BatchTraceability.sol` (Solidity ^0.8.24)
  - **Signer Microservice:** Node.js + ethers.js (`signer-service/`)
  - **Frontend:** Blade + Vanilla JS

## 2. Key Architecture & Data Flow
- **Off-chain (MySQL):** Handles all daily operations (batch creation, transfer, retail, organization approval, QR code generation, etc.).
- **On-chain (Sepolia):** Anchors event hashes on-chain using a single entry-point function:
  ```solidity
  recordBatchEvent(bytes32 batchId, bytes32 businessId, bytes32 eventType, bytes32 payloadHash)
  ```
- **Signer Service (`signer-service/index.js`):** Manages custodial wallets, signs and sends transactions via `ethers.js`, keeping private keys secure.
- **Current State:** Default configuration uses `BLOCKCHAIN_DRIVER=fake`. Logic for sending real transactions to Sepolia is fully implemented (`SepoliaBlockchainGateway` + `BlockchainService`).
- **Sync Mechanism:** Includes asynchronous background synchronization of transaction statuses from Sepolia back to the `blockchain_transactions` table.

## 3. Main Entities & Roles
- **System Roles (Both Off-chain and On-chain):**
  - Regulatory Authority (`ROLE_CO_QUAN`)
  - Manufacturer (`ROLE_NSX`)
  - Distributor (`ROLE_NPP`)
  - Pharmacy / Retailer (`ROLE_NHA_THUOC`)
- **Core Modules:** Organization & Approval Management, Batch Lifecycle Management (Create → Transfer → Retail → Recall), Public QR Verification, Suspicion Reporting, On-chain Wallet & Role Management.

## 4. Important Notes for Next Development
- **Privacy & Storage:** Only event hashes are stored on the blockchain; sensitive/personal data remains strictly off-chain in MySQL.
- **Contract Constraints:** The Smart Contract enforces Role-Based Access Control (RBAC) and prevents duplicate event records using `recordedEventKeys`.
- **Integrity & Idempotency:** Prioritize idempotency and verify on-chain roles prior to dispatching events.
- **Production Readiness:** Transitioning to production requires replacing Sepolia with Ethereum Mainnet / Layer 2 networks (e.g., Arbitrum, Optimism, Base) and securing private keys using KMS / HSM services.