<?php
/**
 * Copy this file to config/config.php and fill in real values.
 * Do NOT commit config.php to source control.
 */
return [
    'app' => [
        'name'      => 'Golders',
        'url'       => 'https://example.com', // public base URL (used for BlockBee callbacks)
        'env'       => 'production',          // 'production' | 'development'
        'timezone'  => 'UTC',
    ],

    'db' => [
        'host'      => '127.0.0.1',
        'port'      => 3306,
        'name'      => 'golders',
        'user'      => 'golders_app',
        'password'  => 'jQ"RN07.9)@T',
        'charset'   => 'utf8mb4',
    ],

    // Random 64-byte key used to sign BlockBee callback tokens and other HMACs.
    // Generate with: php -r "echo bin2hex(random_bytes(32));"
    'app_key' => '4c7ed105bf2ddb255131bf394d68fb34989004fa7ec4aefeeb5bb51a6fcb3b87',

    // BlockBee / CryptAPI: non-custodial, no-KYC payment gateway.
    // Set wallet addresses you control - BlockBee forwards funds straight
    // to these addresses. No signup or API key required.
    //   BTC: a bc1... (bech32) or 1.../3... address you control
    //   ETH: a 0x... address you control (hardware wallet, MetaMask, etc.)
    // Tip: use a fresh BTC address per merchant deployment - BlockBee already
    // generates a unique deposit address per order, but the *forwarding*
    // address you put here will receive all paid orders linked together.
    'blockbee' => [
        'wallets' => [
            'BTC' => 'bc1qh3xzzm84swq39l3haupahjgk8zq9tz6mty2x2e',
            'ETH' => '0x32e51C4B8053921077C8c34865E2c67E229bE941',
        ],
        'accepted_coins'      => ['BTC', 'ETH'],
        'min_confirmations'   => [
            'BTC' => 2,   // ~20 min on average
            'ETH' => 12,  // ~3 min on average
        ],
    ],

    'session' => [
        'name'     => 'golders_sid',
        'lifetime' => 60 * 60 * 2, // 2 hours
    ],
];
