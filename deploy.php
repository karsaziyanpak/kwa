<?php
// 1. Security Check
require_once('/home/noorgeec/kwa.env.php');

if (!isset($_GET['key']) || $_GET['key'] !== DEPLOY_KEY) {
    header('HTTP/1.0 403 Forbidden');
    die("<h1>403 Forbidden</h1>Unauthorized Access. Please provide the correct secret key.");
}

date_default_timezone_set('Asia/Karachi');

// 2. Command Execution Engine
function execute_command($cmd, $title) {
    $descriptor = [
        0 => ["pipe", "r"],
        1 => ["pipe", "w"],
        2 => ["pipe", "w"]
    ];
    
    $process = proc_open($cmd, $descriptor, $pipes);
    $output = "";
    
    if (is_resource($process)) {
        $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[0]); fclose($pipes[1]); fclose($pipes[2]);
        $return_value = proc_close($process);
    }
    
    return [
        'title' => $title,
        'cmd' => $cmd,
        'output' => htmlspecialchars($output),
        'status' => ($return_value === 0) ? 'success' : 'error'
    ];
}

$results = [];

// 3. Actions Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'deploy') {
        // Step A: Fetch and Reset Local Repo
        $results[] = execute_command("cd " . REPO_PATH . " && git fetch origin " . BRANCH . " 2>&1", "Fetching Latest Code");
        $results[] = execute_command("cd " . REPO_PATH . " && git reset --hard origin/" . BRANCH . " 2>&1", "Resetting to Origin");
        
        // Step B: Deploy to Live Path
        $deploy_cmd = "git --git-dir=" . REPO_PATH . "/.git --work-tree=" . LIVE_PATH . " checkout -f " . BRANCH . " 2>&1";
        $results[] = execute_command($deploy_cmd, "Deploying to Live Site");
    }
}

// Get Current Status Info
$status_info = execute_command("cd " . REPO_PATH . " && git log -1 --format='%h|%an|%ar|%s|%ci' && git status -s", "Repo Status");
$details = explode('|', $status_info['output']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>KWA Deployment Console</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono&display=swap" rel="stylesheet">
    <style>
        body { background-color: #0d1117; color: #c9d1d9; font-family: 'Segoe UI', sans-serif; }
        .terminal { font-family: 'JetBrains Mono', monospace; background: #161b22; }
    </style>
</head>
<body class="p-4 md:p-10">

    <div class="max-w-5xl mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-center mb-8 border-b border-gray-700 pb-4">
            <div>
                <h1 class="text-2xl font-bold text-green-500">KWA Infrastructure Console</h1>
                <p class="text-sm text-gray-400">Target: kwa.com.pk | Branch: <?php echo BRANCH; ?></p>
            </div>
            <a href="index.html" class="text-blue-400 hover:underline mt-4 md:mt-0">&larr; Back to Website</a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="bg-gray-800 p-4 rounded-lg border border-gray-700">
                <span class="text-xs uppercase text-gray-500">Latest Commit</span>
                <p class="font-mono text-green-400"><?php echo $details[0] ?? '---'; ?></p>
            </div>
            <div class="bg-gray-800 p-4 rounded-lg border border-gray-700">
                <span class="text-xs uppercase text-gray-500">Date (Karachi)</span>
                <p class="text-sm"><?php echo $details[4] ?? '---'; ?></p>
            </div>
            <div class="bg-gray-800 p-4 rounded-lg border border-gray-700">
                <span class="text-xs uppercase text-gray-500">Commit Message</span>
                <p class="text-sm truncate"><?php echo $details[3] ?? '---'; ?></p>
            </div>
        </div>

        <div class="flex gap-4 mb-8">
            <form method="POST" onsubmit="return confirm('Push updates to live site?')">
                <input type="hidden" name="action" value="deploy">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded font-bold transition">
                    🚀 Pull & Deploy Now
                </button>
            </form>
            
            <button onclick="location.reload()" class="bg-gray-700 hover:bg-gray-600 text-white px-6 py-2 rounded transition">
                🔄 Check Status
            </button>
        </div>

        <div class="terminal rounded-lg overflow-hidden border border-gray-700">
            <div class="bg-gray-700 px-4 py-2 text-xs text-gray-300 flex justify-between">
                <span>System Terminal Output</span>
                <span>Active Session</span>
            </div>
            <div class="p-4 h-96 overflow-y-auto text-sm leading-relaxed">
                <?php if (empty($results)): ?>
                    <p class="text-blue-400 italic">Waiting for action...</p>
                <?php else: ?>
                    <?php foreach ($results as $res): ?>
                        <div class="mb-4">
                            <p class="text-blue-300 font-bold">[TASK] <?php echo $res['title']; ?></p>
                            <p class="text-gray-500 mb-1">$ <?php echo $res['cmd']; ?></p>
                            <pre class="<?php echo $res['status'] === 'success' ? 'text-green-400' : 'text-red-400'; ?>"><?php echo $res['output']; ?></pre>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <footer class="mt-8 text-center text-gray-600 text-xs">
            KWA DevOps Manager &copy; 2026 | Developed for Karsazian Welfare Association
        </footer>
    </div>

</body>
</html>
