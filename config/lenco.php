<?php
// config/lenco.php

/**
 * Lenco by Broadpay Configuration
 * 
 * Replace these placeholder keys with your actual production or 
 * test keys from the Broadpay Dashboard.
 */
return [
    // Your Broadpay Public Key
    'public_key' => 'pub-50404897ea30f039040b8f08dcea28c7bc2f20f0eca67271',
    
    // Your Broadpay Secret Key
    'secret_key' => '98b26d14fcf3503fedbd7216cc9df1043393beb79a8f318b6514343db65a9b60',
    
    // Default currency (usually ZMW or USD depending on your account)
    'currency'   => 'ZMW', 
    
    // Base URL of the Broadpay API gateway
    'base_url'   => 'https://api.lenco.co/access/v2',
    
    // SSL Verification: True for production, false for local development
    'verify_ssl' => false,
];
