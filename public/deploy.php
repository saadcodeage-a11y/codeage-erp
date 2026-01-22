<?php
/**
 * Smart Deployment Script
 * Automatically handles Git Initialization and Pull
 */

// 1. Setup Environment
header('Content-Type: text/html; charset=utf-8');
header('X-Accel-Buffering: no');
ini_set('output_buffering', 'off');
ini_set('zlib.output_compression', false);
if (function_exists('apache_setenv')) apache_setenv('no-gzip', 1);
ob_implicit_flush(true);

// Set directory to Project Root
$rootDir = realpath(__DIR__ . '/../');
chdir($rootDir);

// Fix for Composer & Git
putenv('HOME=' . $rootDir);
putenv('COMPOSER_HOME=' . $rootDir . '/.composer');
// Bypass SSH Host Key checking for first-time connection
putenv('GIT_SSH_COMMAND=ssh -o StrictHostKeyChecking=no'); 

// 2. Security Check
$envFile = '.env';
$deployToken = '';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), 'DEPLOY_TOKEN=') === 0) {
            $deployToken = trim(trim(explode('=', $line, 2)[1]), '"\'');
            break;
        }
    }
} else {
    die_error('.env file not found', 'Expected path: ' . $rootDir . '/.env');
}

if (empty($deployToken)) die_error('DEPLOY_TOKEN missing', 'Add DEPLOY_TOKEN="codeage_deploy_secret_2026" to your .env file');
if (($_GET['token'] ?? '') !== $deployToken) die_error('403 Forbidden', 'Invalid deployment token.');

// 3. Configuration
$repoUrl = 'git@github.com:saadcodeage-a11y/codeage-erp.git'; // SSH URL is required for Deploy Keys

// 4. Helper Functions
function die_error($title, $msg) {
    header('HTTP/1.1 500 Internal Server Error');
    die("<h1>❌ $title</h1><p>$msg</p>");
}

function run_command($label, $command) {
    echo "<div class='step'>";
    echo "<div class='step-header'><span>$label</span> <span style='opacity: 0.7; font-size: 0.9em;'>$ $command</span></div>";
    echo "<div class='step-output'>";
    $handle = popen("$command 2>&1", 'r');
    while (!feof($handle)) {
        echo htmlspecialchars(fgets($handle));
        echo "<script>window.scrollTo(0, document.body.scrollHeight);</script>";
        flush();
    }
    $status = pclose($handle);
    echo "</div></div>";
    return $status === 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Smart Deployment</title>
    <style>
        body { font-family: monospace; background: #0f172a; color: #cbd5e1; padding: 20px; max-width: 900px; margin: 0 auto; }
        h1 { color: #38bdf8; border-bottom: 1px solid #334155; padding-bottom: 10px; }
        .step { margin-bottom: 15px; border: 1px solid #334155; border-radius: 6px; background: #1e293b; overflow: hidden; }
        .step-header { background: #334155; padding: 8px 12px; font-weight: bold; color: #fff; display: flex; justify-content: space-between; }
        .step-output { padding: 12px; white-space: pre-wrap; font-size: 13px; }
    </style>
</head>
<body>
    <h1>🚀 Deployment Started</h1>
    
    <?php
    // --- LOGIC ---
    
    // Check if .git exists
    if (!is_dir('.git')) {
        echo "<h3 style='color: #fbbf24'>⚠️ Initializing Git Repository...</h3>";
        
        run_command('Init Git', 'git init');
        run_command('Add Remote', "git remote add origin $repoUrl");
        
        echo "<h3 style='color: #fbbf24'>⚠️ Performing First Pull (This may take a moment)...</h3>";
        // Force pull main
        if (!run_command('Fetch & Pull', 'git pull origin main')) {
            die("<h2 style='color: #f87171'>❌ Initial Pull Failed</h2><p>Please check that your <b>SSH Deploy Key</b> is added to GitHub Settings.</p>");
        }
        
        // Reset tracking information just in case
        run_command('Track Branch', 'git branch --set-upstream-to=origin/main main');
    } else {
        // Normal Pull
        run_command('Git Pull', 'git pull origin main');
    }

    // Standard Build Steps
    run_command('Composer Install', 'composer install --no-dev --optimize-autoloader');
    run_command('Run Migrations', 'php artisan migrate --force');
    run_command('Clear Cache', 'php artisan optimize:clear');
    run_command('Cache All', 'php artisan config:cache && php artisan route:cache && php artisan view:cache');
    ?>

    <div style="margin-top: 40px; padding: 20px; background: #064e3b; border: 1px solid #059669; border-radius: 8px; text-align: center;">
        <h2 style="margin: 0; color: #4ade80;">✅ Deployment Completed Successfully!</h2>
    </div>
</body>
</html>
