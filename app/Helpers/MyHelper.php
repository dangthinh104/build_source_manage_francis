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
if (!function_exists('generateEnvFile')) {
    function generateEnvFile($pathFolderRoot, $custom)
    : \Illuminate\Http\JsonResponse {
        // Reading file .env.example
        $exampleEnvPath = $pathFolderRoot . '/.env.example';

        if (!File ::exists($exampleEnvPath)) {
            return response() -> json(['error' => '.env.example not found'], 404);
        }

        $envContent = File ::get($exampleEnvPath);

        // Lấy các biến từ MySQL
        $envVariables = EnvVariable ::all();

        foreach ($envVariables as $variable) {
            $realValue = decryptValue($variable -> variable_value);
            $placeholder = "###" . strtoupper($variable -> variable_name);
            $envContent = str_replace($placeholder, $realValue, $envContent);
        }
        // Replace PORT
        if (!empty($custom['PORT_PM2'])) {
            $envContent = str_replace('###PORT', $custom['PORT_PM2'], $envContent);
        }
        if (!empty($custom['VITE_WEB_NAME'])) {
            $envContent = str_replace('###VITE_WEB_NAME', $custom['VITE_WEB_NAME'], $envContent);
        }
        if (!empty($custom['VITE_API_URL'])) {
            $envContent = str_replace('###VITE_API_URL', $custom['VITE_API_URL'], $envContent);
        }
        // Ghi file .env mới
        $newEnvPath = $pathFolderRoot . '/.env';
        File ::put($newEnvPath, $envContent);

        return response() -> json(['success' => 'File .env create success!']);
    }
}
if (!function_exists('readNodeEnvArray')) {
    function readNodeEnvArray($pathProject)
    {
        $nodejsEnvPath = "$pathProject.env";
        
        // Kiểm tra xem file có tồn tại không
        if (!File::exists($nodejsEnvPath)) {
            return [];
        }
        
        // Đọc nội dung file
        $envContent = File::get($nodejsEnvPath);
        
        // Parse nội dung file .env thành mảng các biến môi trường
        $envVariables = [];
        $lines = explode("\n", $envContent);
        
        foreach ($lines as $line) {
            // Bỏ qua các dòng trống hoặc dòng comment
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            
            // Tách key và value
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Loại bỏ dấu ngoặc kép nếu có
                if (strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) {
                    $value = substr($value, 1, -1);
                }
                
                $envVariables[$key] = $value;
            }
        }
        
        return $envVariables;
    }
}

if (!function_exists('readNodeEnv')) {
    function readNodeEnv($pathProject)
    {
        $nodejsEnvPath = "$pathProject.env";
        
         // Kiểm tra xem file có tồn tại không
        if (!File::exists($nodejsEnvPath)) {
            return null;
        }
        
        // Đọc và trả về toàn bộ nội dung file
        return File::get($nodejsEnvPath);
    }
}