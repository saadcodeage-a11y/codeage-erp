<?php
/**
 * Smart Deployment Script
 * Automatically handles Git Initialization and Pull
 */

// 1. Setup Environment
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');
header('X-Accel-Buffering: no');
ini_set('output_buffering', 'off');
ini_set('zlib.output_compression', false);
if (function_exists('apache_setenv')) apache_setenv('no-gzip', 1);
ob_implicit_flush(true);

// Set directory to Project Root
$rootDir = realpath(__DIR__ . '/../');
chdir($rootDir);

if (PHP_VERSION_ID < 80200) {
    header('HTTP/1.1 500 Internal Server Error');
    die('<h1>PHP 8.2+ required</h1><p>This Laravel app requires PHP 8.2 or newer. Current PHP version: ' . htmlspecialchars(PHP_VERSION) . '</p><p>In Hostinger, set this domain/subdomain to PHP 8.2 or PHP 8.3, then reload this deploy URL.</p>');
}

function string_starts_with($value, $prefix) {
    return substr($value, 0, strlen($prefix)) === $prefix;
}

function string_ends_with($value, $suffix) {
    if ($suffix === '') return true;

    return substr($value, -strlen($suffix)) === $suffix;
}

// --- DEBUG UTILITY ---
if (isset($_GET['debug'])) {
    echo "<h2>🛠️ Debug Information</h2>";
    echo "<pre>";
    echo "Current User: " . shell_exec('whoami') . "\n";
    echo "Current Directory: " . getcwd() . "\n";
    echo "PHP Version: " . PHP_VERSION . "\n";
    echo "\nListing SSH Directory (~/.ssh):\n";
    echo shell_exec('ls -la ~/.ssh 2>&1');
    echo "\nListing Project Root:\n";
    echo shell_exec('ls -la . 2>&1');
    echo "</pre>";
    if (!isset($_GET['run'])) die("<hr><p>Debug finished. Add <b>&run=1</b> to the URL to proceed with deployment.</p>");
}

// Fix for Composer & Git
putenv('HOME=' . $rootDir);
putenv('COMPOSER_HOME=' . $rootDir . '/.composer');

// --- SSH KEY DETECTION ---
$sshKeyPath = '';
// Use shell to resolve the real home path (more reliable than PHP)
$homeDir = trim(shell_exec('echo $HOME'));
if (empty($homeDir) || $homeDir == '$HOME') {
    $homeDir = '/home/customer'; // Common SiteGround default
}

// Search for the key in multiple possible locations
$searchDirs = [
    $rootDir, // Search project root first for easier user upload
    $homeDir . '/.ssh', 
    '/home/' . trim(shell_exec('whoami')) . '/.ssh', 
    '/home/customer/.ssh'
];
foreach (array_unique($searchDirs) as $sshDir) {
    if (is_dir($sshDir)) {
        $files = @scandir($sshDir);
        if ($files === false) continue;
        foreach ($files as $file) {
            // Priority 1: User uploaded "deploy_key"
            // Priority 2: SiteGround style .priv files
            if ($file === 'deploy_key' || string_ends_with($file, '.priv') || $file === 'id_rsa' || $file === 'id_ed25519') {
                $sshKeyPath = $sshDir . '/' . $file;
                // Important: Key files in project root MUST have 600 permissions
                if ($sshDir === $rootDir) @chmod($sshKeyPath, 0600);
                break 2;
            }
        }
    }
}

// Allow manual override via URL for debugging
if (isset($_GET['ssh_key'])) {
    $sshKeyPath = $_GET['ssh_key'];
}

// Global SSH Command
$sshCommand = $sshKeyPath 
    ? "ssh -v -i $sshKeyPath -o StrictHostKeyChecking=no -o BatchMode=yes" 
    : "ssh -v -o StrictHostKeyChecking=no";

function read_env_value($file, $key) {
    if (!file_exists($file)) return '';

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || string_starts_with($line, '#')) continue;

        if (strpos($line, $key . '=') === 0) {
            return trim(trim(explode('=', $line, 2)[1]), '"\'');
        }
    }

    return '';
}

function apply_env_template($rootDir) {
    $template = $rootDir . '/always_replace/env';
    $target = $rootDir . '/.env';

    if (!file_exists($template)) return;

    if (!copy($template, $target)) {
        die_error('Unable to prepare .env', 'Could not copy always_replace/env to .env');
    }

    @chmod($target, 0600);
}

// 2. Security Check
$envFile = '.env';
$envTemplateFile = 'always_replace/env';
$deployToken = read_env_value($envFile, 'DEPLOY_TOKEN') ?: read_env_value($envTemplateFile, 'DEPLOY_TOKEN');

if (empty($deployToken)) die_error('DEPLOY_TOKEN missing', 'Add DEPLOY_TOKEN="codeage_deploy_secret_2026" to your .env file or always_replace/env');
if (($_GET['token'] ?? '') !== $deployToken) die_error('403 Forbidden', 'Invalid deployment token.');

apply_env_template($rootDir);

// 3. Configuration
$repoUrl = 'git@github.com:saadcodeage-a11y/codeage-erp.git'; // SSH URL is required for Deploy Keys

// 4. Helper Functions
function die_error($title, $msg) {
    header('HTTP/1.1 500 Internal Server Error');
    die("<h1>❌ $title</h1><p>$msg</p>");
}

function run_command($label, $command, $envPrefix = '', $stopOnFail = true) {
    if (is_bool($envPrefix)) {
        $stopOnFail = $envPrefix;
        $envPrefix = '';
    }
    
    $fullCommand = ($envPrefix ? $envPrefix . ' ' : '') . $command;
    
    echo "<div class='step'>";
    echo "<div class='step-header'><span>$label</span> <span style='opacity: 0.7; font-size: 0.8em;'>$ $fullCommand</span></div>";
    echo "<div class='step-output'>";
    
    $handle = popen("$fullCommand 2>&1", 'r');
    $output = '';
    while (!feof($handle)) {
        $line = fgets($handle);
        $output .= $line;
        echo htmlspecialchars($line);
        echo "<script>window.scrollTo(0, document.body.scrollHeight);</script>";
        flush();
    }
    $status = pclose($handle);
    echo "</div></div>";
    
    if ($status !== 0 && $stopOnFail) {
        echo "<h2 style='color: #f87171'>❌ Command Failed</h2>";
        echo "<p>Deployment halted. Please review the output above.</p>";
        die();
    }
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
        .step-output { padding: 12px; white-space: pre-wrap; font-size: 11px; color: #94a3b8; }
    </style>
</head>
<body>
    <div style="background: #1e293b; padding: 15px; border-radius: 6px; border: 1px solid #334155; margin-bottom: 20px; font-size: 13px;">
        <div style="color: #94a3b8; margin-bottom: 5px;">🔧 Automated Setup Status:</div>
        <div style="display: grid; grid-template-columns: 120px 1fr; gap: 5px;">
            <span>Detected Key:</span> <span style="color: <?php echo $sshKeyPath ? '#4ade80' : '#f87171'; ?>"><?php echo $sshKeyPath ?: 'None Found (Check ~/.ssh/)'; ?></span>
            <span>Home Dir:</span> <span style="color: #38bdf8"><?php echo $homeDir; ?></span>
        </div>
        
        <?php if ($sshKeyPath): ?>
        <div style="margin-top: 10px; padding: 10px; background: #334155; border-radius: 4px; color: #fbbf24; font-size: 12px;">
            ⚠️ <b>Important:</b> If your SSH key has a <b>passphrase</b>, this automation will fail. 
            Automated scripts require an SSH key with <b>no password</b>. 
            If you see 'Permission Denied', please create a new SSH key in SiteGround and leave the password field empty.
        </div>
        <?php endif; ?>
    </div>

    <?php
    // --- LOGIC ---
    $gitEnv = "GIT_SSH_COMMAND='$sshCommand'";
    
    // 1. Git Logic
    if (!is_dir('.git')) {
        echo "<h3 style='color: #fbbf24'>⚠️ Initializing Git Repository...</h3>";
        run_command('Init Git', 'git init');
        run_command('Add Remote', "git remote add origin $repoUrl");
    }

    echo "<h3 style='color: #fbbf24'>⚠️ Syncing files with GitHub...</h3>";
    run_command('Fetch from GitHub', 'git fetch origin main', $gitEnv);
    run_command('Force Sync', 'git reset --hard origin/main');
    apply_env_template($rootDir);

    // 2. Composer with override
    run_command('Composer Install', 'composer install --no-dev --optimize-autoloader --ignore-platform-reqs');

    // 3. Database & Optimization
    run_command('Run Migrations', 'php artisan migrate --force');
    run_command('Clear Cache', 'php artisan optimize:clear');
    run_command('Cache All', 'php artisan config:cache && php artisan route:cache && php artisan view:cache');
    ?>

    <div style="margin-top: 40px; padding: 20px; background: #064e3b; border: 1px solid #059669; border-radius: 8px; text-align: center;">
        <h2 style="margin: 0; color: #4ade80;">✅ Deployment Completed Successfully!</h2>
    </div>
</body>
</html>
