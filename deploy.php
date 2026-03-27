<?php
session_start();
require_once('/home/noorgeec/kwa.env.php');

// 1. Security & Session Management
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: deploy.php");
    exit;
}

if (isset($_POST['password'])) {
    if ($_POST['password'] === DEPLOY_KEY) {
        $_SESSION['kwa_auth'] = true;
    } else {
        $error = "Access Denied. Incorrect Password.";
    }
}

if (!isset($_SESSION['kwa_auth']) || $_SESSION['kwa_auth'] !== true) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8"><title>KWA Secure Login</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-[#0d1117] flex items-center justify-center h-screen">
        <form method="POST" class="bg-[#161b22] p-8 rounded-xl border border-gray-700 w-96 shadow-2xl">
            <div class="text-center mb-6">
                <h2 class="text-emerald-500 text-2xl font-bold tracking-tight">KWA CONSOLE</h2>
                <p class="text-gray-500 text-xs mt-1">Infrastructure Management</p>
            </div>
            <?php if(isset($error)): ?>
                <p class="text-red-400 text-center text-xs mb-4 bg-red-900/20 py-2 rounded"><?php echo $error; ?></p>
            <?php endif; ?>
            <input type="password" name="password" placeholder="Enter Admin Password" autofocus
                   class="w-full bg-gray-900 border border-gray-700 rounded-lg p-3 text-white mb-4 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition-all">
            <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3 rounded-lg shadow-lg transition-all active:scale-95">
                Unlock System
            </button>
        </form>
    </body>
    </html>
    <?php
    exit;
}

// 2. DevOps Engine
date_default_timezone_set('Asia/Karachi');

function execute_command($cmd, $title) {
    $descriptor = [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"]];
    $process = proc_open($cmd, $descriptor, $pipes);
    $output = "";
    if (is_resource($process)) {
        $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[0]); fclose($pipes[1]); fclose($pipes[2]);
        $return_value = proc_close($process);
    }
    return [
        'title' => $title, 'cmd' => $cmd, 
        'output' => htmlspecialchars($output), 
        'status' => ($return_value === 0) ? 'success' : 'error'
    ];
}

$results = [];

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'deploy') {
        $results[] = execute_command("cd " . REPO_PATH . " && git fetch origin " . BRANCH . " 2>&1", "Fetching Latest Updates");
        $results[] = execute_command("cd " . REPO_PATH . " && git reset --hard origin/" . BRANCH . " 2>&1", "Resetting Local Repository");
        $results[] = execute_command("git --git-dir=" . REPO_PATH . "/.git --work-tree=" . LIVE_PATH . " checkout -f " . BRANCH . " 2>&1", "Deploying to Production");
    } 
    elseif ($_POST['action'] === 'revert' && !empty($_POST['commit_id'])) {
        $hash = escapeshellarg($_POST['commit_id']);
        $results[] = execute_command("cd " . REPO_PATH . " && git reset --hard $hash 2>&1", "Reverting Repo to $hash");
        $results[] = execute_command("git --git-dir=" . REPO_PATH . "/.git --work-tree=" . LIVE_PATH . " checkout -f $hash 2>&1", "Syncing Reverted State to Live");
    }
}

// Get Data for UI
$log_data = execute_command("cd " . REPO_PATH . " && git log -10 --format='%h|%s|%ar'", "Get History");
$history_lines = array_filter(explode("\n", trim($log_data['output'])));
$current_status = explode('|', $history_lines[0] ?? '---|---|---');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>KWA DevOps Console</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #0d1117; color: #c9d1d9; }
        .terminal-font { font-family: 'ui-monospace', 'SFMono-Regular', 'Menlo', 'Monaco', 'Consolas', monospace; }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #0d1117; }
        ::-webkit-scrollbar-thumb { background: #30363d; border-radius: 10px; }
    </style>
</head>
<body class="p-4 md:p-8">
    <div class="max-w-6xl mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 border-b border-gray-800 pb-6">
            <div>
                <div class="flex items-center gap-3">
                    <div class="w-3 h-3 bg-emerald-500 rounded-full animate-pulse"></div>
                    <h1 class="text-2xl font-bold text-white tracking-tight">KWA <span class="text-emerald-500">Infrastructure</span></h1>
                </div>
                <p class="text-xs text-gray-500 font-mono mt-1">Server: yottasrc | Path: <?php echo LIVE_PATH; ?></p>
            </div>
            <div class="flex gap-3 mt-4 md:mt-0">
                <a href="index.html" target="_blank" class="px-4 py-2 border border-gray-700 rounded-lg text-sm hover:bg-gray-800 transition">Open Site</a>
                <a href="?logout=1" class="px-4 py-2 bg-red-900/20 text-red-400 border border-red-900/40 rounded-lg text-sm hover:bg-red-900/40 transition">Logout</a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <div class="lg:col-span-2 bg-[#161b22] border border-gray-800 rounded-xl p-6 shadow-sm">
                <h3 class="text-white font-bold mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path></svg>
                    Primary Deployment
                </h3>
                <p class="text-sm text-gray-400 mb-6">Fetch the latest code from GitHub `main` branch and update the live website immediately.</p>
                
                <div class="flex flex-wrap gap-4 items-center">
                    <form method="POST" onsubmit="return confirm('Update live site now?')">
                        <input type="hidden" name="action" value="deploy">
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-8 py-3 rounded-lg shadow-lg shadow-emerald-900/20 transition-all active:scale-95 flex items-center gap-2 text-sm">
                            🚀 Run Pull & Deploy
                        </button>
                    </form>
                    <button onclick="location.reload()" class="px-6 py-3 border border-gray-700 rounded-lg text-sm hover:bg-gray-800 transition">Refresh Status</button>
                </div>
            </div>

            <div class="bg-[#161b22] border border-gray-800 rounded-xl p-6 shadow-sm">
                <h3 class="text-white font-bold mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Rollback System
                </h3>
                <form method="POST" onsubmit="return confirm('Are you sure you want to revert to this version?')">
                    <input type="hidden" name="action" value="revert">
                    <label class="text-[10px] text-gray-500 uppercase block mb-1">Select Previous Version</label>
                    <select name="commit_id" class="w-full bg-gray-900 border border-gray-700 rounded-lg p-2 text-xs text-gray-300 mb-4 outline-none focus:border-orange-500">
                        <?php foreach($history_lines as $line): 
                            list($h, $m, $t) = explode('|', $line); ?>
                            <option value="<?php echo $h; ?>"><?php echo "$h - $m ($t)"; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="w-full border border-orange-500/30 text-orange-500 hover:bg-orange-500 hover:text-white py-2 rounded-lg text-xs font-bold transition-all italic">
                        ⚠️ Restore Selected Version
                    </button>
                </form>
            </div>
        </div>

        <div class="bg-[#0d1117] border border-gray-800 rounded-xl overflow-hidden shadow-2xl">
            <div class="bg-[#161b22] px-4 py-2 border-b border-gray-800 flex justify-between items-center">
                <span class="text-[10px] font-bold text-gray-500 tracking-widest uppercase">System Execution Log</span>
                <span class="text-[10px] text-emerald-500/50 font-mono">Status: Connected</span>
            </div>
            <div class="p-6 h-[400px] overflow-y-auto terminal-font text-xs leading-relaxed">
                <?php if (empty($results)): ?>
                    <div class="flex flex-col items-center justify-center h-full text-gray-600">
                        <svg class="w-12 h-12 mb-2 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <p>No active tasks. System is on standby.</p>
                        <p class="text-[10px] mt-1">Current HEAD: <?php echo $current_status[0]; ?> - <?php echo $current_status[1]; ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($results as $res): ?>
                        <div class="mb-6 group">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="<?php echo $res['status'] === 'success' ? 'text-emerald-500' : 'text-red-500'; ?> font-bold text-[10px]">
                                    [<?php echo strtoupper($res['status']); ?>]
                                </span>
                                <span class="text-gray-300 font-bold"><?php echo $res['title']; ?></span>
                            </div>
                            <div class="bg-black/30 p-3 rounded-lg border border-gray-800/50 group-hover:border-gray-700 transition">
                                <p class="text-gray-500 mb-2 italic">$ <?php echo $res['cmd']; ?></p>
                                <pre class="whitespace-pre-wrap <?php echo $res['status'] === 'success' ? 'text-emerald-400/80' : 'text-red-400'; ?>"><?php echo $res['output'] ?: "Command executed with no output."; ?></pre>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <footer class="mt-8 text-center">
            <p class="text-gray-600 text-[10px] uppercase tracking-widest italic">KWA Digital Infrastructure Management &bull; Karachi, PK</p>
        </footer>
    </div>
</body>
</html>
