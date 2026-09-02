<?php
ob_start();

// --- Centralized Error and Exception Handling ---

// Allow PHP to display errors for easier local development.
ini_set('display_errors', 1);
// Log errors to a file.
ini_set('log_errors', 1);
// Specify the path to the error log file.
ini_set('error_log', __DIR__ . '/error.log');

/**
 * Global Uncaught Exception Handler.
 *
 * This function is the last line of defense. It catches any exception that isn't
 * caught elsewhere, cleans up, logs the error, and sends a clean JSON
 * response to the client.
 */
set_exception_handler(function($exception) {
    // Ensure no other output interferes. Clean all output buffers.
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    // Set appropriate headers for a JSON error response.
    http_response_code(500); // Internal Server Error
    header('Content-Type: application/json');

    // Log the full exception details for debugging.
    // The message will be automatically timestamped and sent to the file specified in 'error_log'.
    error_log("Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());

    // Echo a generic, safe, and valid JSON error message to the client.
    echo json_encode([
        'success' => false,
        'error' => 'An unexpected server error occurred. Please try again.'
    ]);

    // Terminate script execution.
    exit();
});

/**
 * Custom Error Handler.
 *
 * This function converts all triggerable PHP errors (like warnings and notices)
 * into ErrorException objects. This allows them to be caught by the
 * exception handler set above, standardizing all error handling.
 */
set_error_handler(function($severity, $message, $file, $line) {
    // Check if this error level is currently being reported.
    if (!(error_reporting() & $severity)) {
        return false; // Don't execute the internal PHP error handler.
    }
    // Throw an exception for the error, which will be caught by our exception handler.
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// --- End of Error Handling ---

// Define project root directory for reliable path checking
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', realpath(__DIR__ . '/..'));
}

require_once __DIR__ . '/session_check.php';
?>