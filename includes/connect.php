<?php
    // Use absolute path for vendor/autoload.php to avoid inclusion issues
    $autoloadPath = dirname(__DIR__) . '/vendor/autoload.php';
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
    }

    // Load .env file if it exists (primarily for local development)
    if (class_exists('Dotenv\Dotenv')) {
        $dotenv = Dotenv\Dotenv::createUnsafeImmutable(dirname(__DIR__));
        // safeLoad() won't throw an exception if .env is missing
        $dotenv->safeLoad();
    }

    /**
     * Helper to get environment variables with fallback
     */
    function get_db_env($key, $default) {
        $val = getenv($key);
        if ($val !== false && $val !== '') return $val;
        
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') return $_ENV[$key];
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') return $_SERVER[$key];
        
        return $default;
    }

    // Database configuration - prioritizing local development defaults
    $host     = get_db_env('DB_HOST', 'localhost');
    $username = get_db_env('DB_USERNAME', 'root');
    $password = get_db_env('DB_PASSWORD', '');
    $database = get_db_env('DB_DATABASE', 'yogamart_db');
    $port     = get_db_env('DB_PORT', '3306');

    // Attempt to establish a connection
    // We use @ to suppress the default warning as we handle the error manually
    $con = @mysqli_connect($host, $username, $password, $database, $port);

    if (!$con) {
        // Detailed error for logging (it will be caught by init.php handler)
        $error = mysqli_connect_error();
        $errno = mysqli_connect_errno();
        
        // Log the failure with details for the admin
        error_log("Database connection failed ($errno): $error | Host: $host, User: $username, Port: $port");
        
        // Throw exception to be handled by global handler in init.php
        throw new RuntimeException("Database connection failed. Please ensure the database service is running and accessible.");
    }

    // Set charset to utf8mb4 for full emoji support
    mysqli_set_charset($con, "utf8mb4");
?>