<?php

// TMS Rewritten - Early 2010, 2022
// By Joseph Robert Gillotti

defined('in_tms') or exit;	// Anti inclusion hack

// Symmetric encryption helpers using libsodium

function encrypt_payload($payload, $key) {
	$nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
	$ciphertext = sodium_crypto_secretbox($payload, $nonce, $key);
	return base64_encode($nonce.$ciphertext);
}

function decrypt_payload($encrypted, $key) {
	$bytes = base64_decode($encrypted);
	$nonce = substr($bytes, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
	$cipher = substr($bytes, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
	return sodium_crypto_secretbox_open($cipher, $nonce, $key);
}
