
require('dotenv').config();
const { ethers } = require('ethers');
const abi = require('../API.json');

async function main() {
    const provider = new ethers.JsonRpcProvider(
        process.env.BLOCKCHAIN_RPC_URL
    );

    const network = await provider.getNetwork();
    const contractAddress = process.env.BLOCKCHAIN_CONTRACT_ADDRESS;

    const code = await provider.getCode(contractAddress);
    if (code === '0x') {
        throw new Error('Không tìm thấy bytecode tại contract address.');
    }

    const adminWallet = new ethers.Wallet(
        process.env.ADMIN_PRIVATE_KEY,
        provider
    );

    const contract = new ethers.Contract(
        contractAddress,
        abi,
        provider
    );

    const owner = await contract.owner();

    const roles = {
        CO_QUAN_QUAN_LY:
            '0x1d311b942596aef61ec59d06e9f164585203bbded6508df661898fbdb9984af5',
        NHA_THUOC:
            '0xb1802fd42310626117e379b25258b1b91a10c166206dd9986fbfba9c5790c55b',
        NHA_PHAN_PHOI:
            '0xc89bc684fc08226b0536c7149feab571fab99542ae80739c2eaa9c072463d66b',
        NHA_SAN_XUAT:
            '0x15b45b7c513f33a44d38557efbe88fa1f48c940795233c714b16de859fd7cf60'
    };

    console.log('Chain ID:', network.chainId.toString());
    console.log('Contract:', contractAddress);
    console.log('Code exists:', code !== '0x');
    console.log('Contract owner:', owner);
    console.log('Admin wallet:', adminWallet.address);
    console.log(
        'Admin is owner:',
        owner.toLowerCase() === adminWallet.address.toLowerCase()
    );

    for (const [name, role] of Object.entries(roles)) {
        const granted = await contract.hasRole(adminWallet.address, role);
        console.log(`Admin has ${name}:`, granted);
    }
}

main().catch(error => {
    console.error('CHECK FAILED:', error.shortMessage || error.message);
    process.exit(1);
});
