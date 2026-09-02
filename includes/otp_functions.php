<?php
    /**
     * Generates a 6-digit OTP, stores its HMAC hash in the session,
     * and also stores the plaintext OTP temporarily for sending via email.
     */
    function otp_Generator(){
        // Ensure session is started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $length = 6;
        $otp = str_pad(random_int(0, 10**$length - 1), $length, '0', STR_PAD_LEFT);
        
        // Safer way to access environment variables
        $secret = getenv('SECRET_KEY') ?: '8f2a1b3c4d5e6f7a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5f6a7b8c9d0e1f2';
        $hash = hash_hmac('sha256', $otp, $secret);

        // Store plaintext OTP temporarily for the email body.
        $_SESSION['plaintext_otp'] = $otp;
        
        // Store the secure hash and generation time.
        $_SESSION['otp_hash'] = $hash;
        $_SESSION['otp_time'] = time();

        return [$otp, $hash];
    }

    /**
     * Verifies the user-submitted OTP against the hash stored in the session.
     *
     * @param string $user_otp The OTP provided by the user.
     * @param string $session_key The session key to check against (default: 'otp_hash').
     * @param int $expiry_seconds Expiry time in seconds (default: 300 / 5 mins).
     * @return array An array containing the validation result and debug info.
     */
    function otp_checker($user_otp, $session_key = 'otp_hash', $expiry_seconds = 300){
        // Ensure session is started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION[$session_key])) {
            return [
                'isValid' => false,
                'error' => 'OTP session expired or not found.',
                'debug' => ['error' => "Session key '$session_key' not set."]
            ];
        }

        // Check expiry
        $otp_time = $_SESSION['otp_time'] ?? 0;
        if ((time() - $otp_time) > $expiry_seconds) {
            unset($_SESSION[$session_key], $_SESSION['otp_time'], $_SESSION['plaintext_otp']);
            return [
                'isValid' => false,
                'error' => 'OTP has expired. Please try again.',
                'debug' => ['error' => "OTP expired. Time diff: " . (time() - $otp_time)]
            ];
        }

        $secret = getenv('SECRET_KEY') ?: '8f2a1b3c4d5e6f7a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5f6a7b8c9d0e1f2';
        $user_hash = hash_hmac('sha256', $user_otp, $secret);
        
        $isValid = hash_equals($_SESSION[$session_key], $user_hash);

        if ($isValid) {
            // Clear OTP data after successful verification to prevent reuse
            unset($_SESSION[$session_key], $_SESSION['otp_time'], $_SESSION['plaintext_otp']);
        }

        return [
            'isValid' => $isValid,
            'error' => $isValid ? null : 'Invalid OTP code.',
            'debug' => [
                'secret_loaded' => getenv('SECRET_KEY') ? 'Yes' : 'No (using fallback)',
                'is_valid' => $isValid
            ]
        ];
    }
?>