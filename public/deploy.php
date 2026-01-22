<?php
/**
 * Simple Deployment Script
 * 
 * Usage: https://your-domain.com/deploy.php?token=YOUR_SECRET_TOKEN
 */

// Move context to project root
chdir(__DIR__ . '/../');

// 1. Security: Read DEPLOY_TOKEN from .env manually
$envFile = '.env';
$deployToken = '';

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, '#') === 0) continue;
        
        if (strpos($line, 'DEPLOY_TOKEN=') === 0) {
            $parts = explode('=', $line, 2);
            $deployToken = trim($parts[1] ?? '');
            $deployToken = trim($deployToken, '"\'');
            break;
        }
    }
}

// Verify Token
$requestToken = $_GET['token'] ?? '';
if (empty($deployToken) || $requestToken !== $deployToken) {
    header('HTTP/1.1 403 Forbidden');
    die('<h1>403 Forbidden</h1><p>Access denied. Invalid or missing deployment token.</p>');
}

// 2. UI Setup for Streaming Output
header('Content-Type: text/html; charset=utf-8');
header('X-Accel-Buffering: no');
ini_set('output_buffering', 'off');
ini_set('zlib.output_compression', false);
if (function_exists('apache_setenv')) {
    apache_setenv('no-gzip', 1);
}
ob_implicit_flush(true);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deployment Output</title>
    <style>
        body { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; background: #0f172a; color: #f8fafc; padding: 20px; line-height: 1.5; }
        .container { max-width: 900px; margin: 0 auto; }
        h1 { color: #38bdf8; border-bottom: 1px solid #334155; padding-bottom: 10px; }
        .step { margin-bottom: 20px; border: 1px solid #334155; border-radius: 6px; overflow: hidden; background: #1e293b; }
        .step-header { background: #334155; padding: 8px 12px; font-weight: bold; color: #e2e8f0; display: flex; justify-content: space-between; }
        .step-output { padding: 12px; overflow-x: auto; white-space: pre-wrap; font-size: 13px; color: #cbd5e1; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Deployment Started</h1>
        <p style="color: #94a3b8; font-size: 12px;">Time: <?php echo date('Y-m-d H:i:s'); ?></p>

        <?php
        function run_command($label, $command) {
            echo "<div class='step'>";
            echo "<div class='step-header'><span>$label</span> <span style='opacity: 0.7; font-weight: normal; font-size: 0.9em;'>$ $command</span></div>";
            echo "<div class='step-output'>";
            
            // Append 2>&1 to capture error output
            $handle = popen("$command 2>&1", 'r');
            while (!feof($handle)) {
                $buffer = fgets($handle);
                echo htmlspecialchars($buffer);
                echo "<script>window.scrollTo(0, document.body.scrollHeight);</script>";
                flush();
            }
            pclose($handle);
            echo "</div></div>";
            flush();
        }

        // --- COMMANDS ---
        run_command('Git Pull', 'git pull origin main');
        run_command('Composer Install', 'composer install --no-dev --optimize-autoloader');
        run_command('Run Migrations', 'php artisan migrate --force');
        run_command('Clear Cache', 'php artisan optimize:clear');
        run_command('Cache Config', 'php artisan config:cache');
        run_command('Cache Routes', 'php artisan route:cache');
        run_command('Cache Views', 'php artisan view:cache');
        ?>

        <div style="margin-top: 40px; padding: 20px; background: #064e3b; border: 1px solid #059669; border-radius: 8px; text-align: center;">
            <h2 style="margin: 0; color: #4ade80;">✅ Deployment Completed Successfully!</h2>
            <p style="margin-top: 10px;"><a href="/" style="color: white; text-decoration: underline;">Return to Homepage</a></p>
        </div>
    </div>
</body>
</html>
