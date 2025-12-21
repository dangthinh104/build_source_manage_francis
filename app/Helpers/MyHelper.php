<?php

use App\Models\EnvVariable;
use Illuminate\Support\Facades\File;


if (!function_exists('encryptValue')) {
    /**
     * Encrypt a value using AES-256-CBC with a random IV.
     * The IV is prepended to the ciphertext for storage.
     *
     * @param string $value The value to encrypt
     * @return string Base64-encoded IV + ciphertext
     */
    function encryptValue($value): string {
        $secretKey = env('APP_ENCRYPT_KEY');

        // Generate a random IV for each encryption (16 bytes for AES-256-CBC)
        $iv = openssl_random_pseudo_bytes(16);

        $encryptedValue = openssl_encrypt($value, 'AES-256-CBC', $secretKey, OPENSSL_RAW_DATA, $iv);

        // Prepend IV to ciphertext so we can extract it during decryption
        return base64_encode($iv . $encryptedValue);
    }
}

if (!function_exists('decryptValue')) {
    /**
     * Decrypt a value encrypted with encryptValue().
     * Handles both new format (random IV) and legacy format (hardcoded IV) for backwards compatibility.
     *
     * @param string $encryptedValue Base64-encoded encrypted value
     * @return string|false Decrypted value or false on failure
     */
    function decryptValue($encryptedValue): string|false {
        $secretKey = env('APP_ENCRYPT_KEY');
        $data = base64_decode($encryptedValue);

        // New format: IV (16 bytes) + ciphertext
        if (strlen($data) > 16) {
            $iv = substr($data, 0, 16);
            $ciphertext = substr($data, 16);

            $decrypted = openssl_decrypt($ciphertext, 'AES-256-CBC', $secretKey, OPENSSL_RAW_DATA, $iv);

            // If new format works, return the result
            if ($decrypted !== false) {
                return $decrypted;
            }
        }

        // Legacy fallback: try with hardcoded IV for backwards compatibility
        $legacyIv = substr(hash('sha256', 'your-iv'), 0, 16);
        return openssl_decrypt($data, 'AES-256-CBC', $secretKey, 0, $legacyIv);
    }
}

