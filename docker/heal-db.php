<?php

$host = getenv('DB_HOST') ?: 'mysql';
$port = getenv('DB_PORT') ?: '3306';
$database = getenv('DB_DATABASE') ?: 'mserp';
$username = getenv('DB_USERNAME') ?: 'mserp_user';
$password = getenv('DB_PASSWORD') ?: 'mserp_password';
$rootPassword = getenv('DB_ROOT_PASSWORD') ?: 'rootpassword';

echo "=================================================\n";
echo "   🔧 MS ERP - Database Self-Healing Check      \n";
echo "=================================================\n";
echo "Host: {$host}:{$port}\n";
echo "Database: {$database}\n";
echo "User: {$username}\n";

// 1. Wait for MySQL TCP port to respond
$maxWait = 30;
$waited = 0;
while ($waited < $maxWait) {
    $connection = @fsockopen($host, (int) $port, $errno, $errstr, 2);
    if (is_resource($connection)) {
        fclose($connection);
        break;
    }
    sleep(1);
    $waited++;
    echo "⏳ Waiting for MySQL service at {$host}:{$port} ({$waited}/{$maxWait})...\n";
}

// 2. Test user connection
try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$database}", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 4,
    ]);
    echo "✔ Connection to database '{$database}' as '{$username}' verified!\n";
    exit(0);
} catch (Exception $e) {
    echo "⚠ Cannot connect as '{$username}': " . $e->getMessage() . "\n";
    echo "⚙ Attempting root credentials to synchronize user permissions...\n";
}

// 3. Connect as root
$rootCandidates = array_values(array_filter(array_unique([
    $rootPassword,
    'rootpassword',
    'root',
    '',
    $password
]), function($v) { return $v !== null; }));

$rootPdo = null;
foreach ($rootCandidates as $candidate) {
    try {
        $rootPdo = new PDO("mysql:host={$host};port={$port}", 'root', $candidate, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 4,
        ]);
        echo "✔ Connected to MySQL server as 'root'!\n";
        break;
    } catch (Exception $ex) {
        // try next candidate
    }
}

if (!$rootPdo) {
    echo "❌ Could not authenticate as root using supplied candidates.\n";
    exit(1);
}

// 4. Create database if missing
try {
    $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    echo "✔ Database `{$database}` verified/created.\n";
} catch (Exception $e) {
    echo "⚠ Error creating database: " . $e->getMessage() . "\n";
}

// 5. Ensure user exists with the configured password and full permissions
try {
    // Try creating user if doesn't exist
    $quotedPass = $rootPdo->quote($password);
    $rootPdo->exec("CREATE USER IF NOT EXISTS '{$username}'@'%' IDENTIFIED BY {$quotedPass};");
    $rootPdo->exec("ALTER USER '{$username}'@'%' IDENTIFIED BY {$quotedPass};");
    $rootPdo->exec("GRANT ALL PRIVILEGES ON `{$database}`.* TO '{$username}'@'%';");
    $rootPdo->exec("FLUSH PRIVILEGES;");
    echo "✔ Successfully updated user '{$username}' with permissions on '{$database}'!\n";
    exit(0);
} catch (Exception $e) {
    echo "❌ Error setting up user permissions: " . $e->getMessage() . "\n";
    exit(1);
}
