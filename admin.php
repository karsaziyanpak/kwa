<?php
/**
 * KWA Admin Panel - Message Management Dashboard
 * Secure admin interface for viewing, filtering, and managing contact form messages
 */

session_start();

// Load configuration
if (!file_exists('/home/noorgeec/kwa.env.php')) {
    die("Error: Configuration file not found");
}
require_once '/home/noorgeec/kwa.env.php';

// Authentication check
$auth_pass = $_GET['key'] ?? $_POST['password'] ?? '';
if ($auth_pass !== DEPLOY_KEY) {
    if (!isset($_SESSION['kwa_admin_auth']) || $_SESSION['kwa_admin_auth'] !== true) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>KWA Admin - Login</title>
            <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body class="bg-[#0d1117] flex items-center justify-center h-screen">
            <form method="POST" class="bg-[#161b22] p-8 rounded-xl border border-gray-700 w-96 shadow-2xl">
                <div class="text-center mb-6">
                    <h2 class="text-emerald-500 text-2xl font-bold tracking-tight">KWA ADMIN</h2>
                    <p class="text-gray-500 text-xs mt-1">Message Management</p>
                </div>
                <input type="password" name="password" placeholder="Enter Admin Password" autofocus
                       class="w-full bg-gray-900 border border-gray-700 rounded-lg p-3 text-white mb-4 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition-all">
                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3 rounded-lg shadow-lg transition-all active:scale-95">
                    Login
                </button>
            </form>
        </body>
        </html>
        <?php
        exit;
    }
} else {
    $_SESSION['kwa_admin_auth'] = true;
}

// Data directory
$dataDir = __DIR__ . '/data';
$jsonFile = $dataDir . '/messages.json';

// Get all messages
$messages = [];
if (file_exists($jsonFile)) {
    $data = file_get_contents($jsonFile);
    if ($data) {
        $messages = json_decode($data, true);
        if (!is_array($messages)) {
            $messages = [];
        }
    }
}

// Reverse to show newest first
$messages = array_reverse($messages);

// Get filter parameter
$filter = $_GET['filter'] ?? 'info';
$filtered_messages = $messages;

if ($filter !== 'all') {
    $filtered_messages = array_filter($messages, function($msg) use ($filter) {
        return $msg['category'] === $filter;
    });
    $filtered_messages = array_values($filtered_messages);
}

// Handle actions
$action_result = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'delete' && isset($_POST['msg_id'])) {
            $msg_id = $_POST['msg_id'];
            $messages = array_filter($messages, function($msg) use ($msg_id) {
                return $msg['id'] !== $msg_id;
            });
            $messages = array_values($messages);
            file_put_contents($jsonFile, json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $action_result = '<div class="bg-green-900/20 border border-green-500/30 text-green-400 p-3 rounded mb-4">Message deleted successfully.</div>';
            
            // Refresh filtered messages
            if ($filter !== 'all') {
                $filtered_messages = array_filter($messages, function($msg) use ($filter) {
                    return $msg['category'] === $filter;
                });
                $filtered_messages = array_values($filtered_messages);
            } else {
                $filtered_messages = $messages;
            }
        }
        elseif ($_POST['action'] === 'mark_status' && isset($_POST['msg_id']) && isset($_POST['status'])) {
            $msg_id = $_POST['msg_id'];
            $new_status = $_POST['status'];
            foreach ($messages as &$msg) {
                if ($msg['id'] === $msg_id) {
                    $msg['status'] = $new_status;
                    break;
                }
            }
            file_put_contents($jsonFile, json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $action_result = '<div class="bg-green-900/20 border border-green-500/30 text-green-400 p-3 rounded mb-4">Message status updated.</div>';
            
            // Refresh filtered messages
            if ($filter !== 'all') {
                $filtered_messages = array_filter($messages, function($msg) use ($filter) {
                    return $msg['category'] === $filter;
                });
                $filtered_messages = array_values($filtered_messages);
            } else {
                $filtered_messages = $messages;
            }
        }
    }
}

// Format date
function formatDate($timestamp) {
    $dt = new DateTime($timestamp);
    return $dt->format('M d, Y - h:i A');
}

// Get category label
function getCategoryLabel($category) {
    $labels = [
        'info' => 'Information Request',
        'donation' => 'Donation Inquiry',
        'volunteer' => 'Volunteer Request',
        'general' => 'General Inquiry',
        'staff' => 'Staff Related'
    ];
    return $labels[$category] ?? ucfirst($category);
}

// Get status badge
function getStatusBadge($status) {
    $badges = [
        'new' => '<span class="px-2 py-1 bg-blue-900/30 text-blue-400 text-xs rounded border border-blue-500/30">New</span>',
        'read' => '<span class="px-2 py-1 bg-yellow-900/30 text-yellow-400 text-xs rounded border border-yellow-500/30">Read</span>',
        'replied' => '<span class="px-2 py-1 bg-green-900/30 text-green-400 text-xs rounded border border-green-500/30">Replied</span>',
        'archived' => '<span class="px-2 py-1 bg-gray-900/30 text-gray-400 text-xs rounded border border-gray-500/30">Archived</span>'
    ];
    return $badges[$status] ?? $badges['new'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KWA Admin Panel - Message Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #0d1117; color: #c9d1d9; }
        .terminal-font { font-family: 'ui-monospace', 'SFMono-Regular', 'Menlo', 'Monaco', 'Consolas', monospace; }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #0d1117; }
        ::-webkit-scrollbar-thumb { background: #30363d; border-radius: 10px; }
        table { border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #30363d; }
        th { background-color: #161b22; font-weight: 600; color: #58a6ff; }
        tr:hover { background-color: #0d1117; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); }
        .modal.active { display: flex; align-items: center; justify-content: center; }
        .modal-content { background-color: #161b22; padding: 20px; border-radius: 8px; max-width: 600px; max-height: 80vh; overflow-y: auto; border: 1px solid #30363d; }
    </style>
</head>
<body class="p-4 md:p-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 border-b border-gray-800 pb-6">
            <div>
                <div class="flex items-center gap-3">
                    <div class="w-3 h-3 bg-emerald-500 rounded-full animate-pulse"></div>
                    <h1 class="text-2xl font-bold text-white tracking-tight">KWA <span class="text-emerald-500">Admin Panel</span></h1>
                </div>
                <p class="text-xs text-gray-500 font-mono mt-1">Message Management Dashboard</p>
            </div>
            <div class="flex gap-3 mt-4 md:mt-0 flex-wrap">
                <a href="index.html" class="px-4 py-2 border border-gray-700 rounded-lg text-sm hover:bg-gray-800 transition">Home</a>
                <a href="deploy.php" class="px-4 py-2 border border-gray-700 rounded-lg text-sm hover:bg-gray-800 transition">Deploy</a>
                <button onclick="location.reload()" class="px-4 py-2 border border-gray-700 rounded-lg text-sm hover:bg-gray-800 transition">Refresh</button>
                <a href="?logout=1" class="px-4 py-2 bg-red-900/20 text-red-400 border border-red-900/40 rounded-lg text-sm hover:bg-red-900/40 transition">Logout</a>
            </div>
        </div>

        <?php echo $action_result; ?>

        <!-- Filter Section -->
        <div class="bg-[#161b22] border border-gray-800 rounded-xl p-6 mb-6">
            <h3 class="text-white font-bold mb-4">Filter Messages</h3>
            <form method="GET" class="flex gap-4 items-center flex-wrap">
                <label class="text-sm text-gray-400">Category:</label>
                <select name="filter" onchange="this.form.submit()" class="bg-gray-900 border border-gray-700 rounded-lg p-2 text-sm text-gray-300 outline-none focus:border-emerald-500">
                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Messages</option>
                    <option value="info" <?php echo $filter === 'info' ? 'selected' : ''; ?>>Information Request</option>
                    <option value="donation" <?php echo $filter === 'donation' ? 'selected' : ''; ?>>Donation Inquiry</option>
                    <option value="volunteer" <?php echo $filter === 'volunteer' ? 'selected' : ''; ?>>Volunteer Request</option>
                    <option value="general" <?php echo $filter === 'general' ? 'selected' : ''; ?>>General Inquiry</option>
                    <option value="staff" <?php echo $filter === 'staff' ? 'selected' : ''; ?>>Staff Related</option>
                </select>
                <span class="text-sm text-gray-500">Total: <strong class="text-emerald-400"><?php echo count($filtered_messages); ?></strong></span>
            </form>
        </div>

        <!-- Messages Table -->
        <div class="bg-[#0d1117] border border-gray-800 rounded-xl overflow-hidden shadow-2xl">
            <div class="bg-[#161b22] px-6 py-3 border-b border-gray-800">
                <span class="text-sm font-bold text-gray-500 tracking-widest uppercase">Messages List</span>
            </div>
            
            <?php if (count($filtered_messages) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr>
                                <th style="width: 80px;">Status</th>
                                <th style="width: 100px;">Date & Time</th>
                                <th style="width: 120px;">Category</th>
                                <th style="width: 150px;">Name</th>
                                <th style="width: 180px;">Email</th>
                                <th style="width: 100px;">Phone</th>
                                <th style="width: 150px;">Subject</th>
                                <th style="width: 200px;">Message</th>
                                <th style="width: 150px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filtered_messages as $msg): ?>
                                <tr class="hover:bg-[#161b22] transition">
                                    <td><?php echo getStatusBadge($msg['status'] ?? 'new'); ?></td>
                                    <td class="text-gray-400 font-mono text-xs"><?php echo formatDate($msg['timestamp']); ?></td>
                                    <td><span class="px-2 py-1 bg-emerald-900/30 text-emerald-400 text-xs rounded border border-emerald-500/30"><?php echo getCategoryLabel($msg['category']); ?></span></td>
                                    <td class="font-medium"><?php echo htmlspecialchars($msg['name']); ?></td>
                                    <td><a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>" class="text-blue-400 hover:underline"><?php echo htmlspecialchars($msg['email']); ?></a></td>
                                    <td><?php echo $msg['phone'] ? '<a href="tel:' . htmlspecialchars($msg['phone']) . '" class="text-blue-400 hover:underline">' . htmlspecialchars($msg['phone']) . '</a>' : '<span class="text-gray-600">-</span>'; ?></td>
                                    <td class="text-gray-300"><?php echo $msg['subject'] ? htmlspecialchars(substr($msg['subject'], 0, 30)) . (strlen($msg['subject']) > 30 ? '...' : '') : '<span class="text-gray-600">-</span>'; ?></td>
                                    <td class="text-gray-300"><?php echo htmlspecialchars(substr($msg['message'], 0, 40)) . (strlen($msg['message']) > 40 ? '...' : ''); ?></td>
                                    <td>
                                        <div class="flex gap-2">
                                            <button onclick="viewMessage('<?php echo htmlspecialchars(json_encode($msg), ENT_QUOTES); ?>')" class="px-2 py-1 bg-blue-900/30 text-blue-400 text-xs rounded hover:bg-blue-900/50 transition">View</button>
                                            <button onclick="replyMessage('<?php echo htmlspecialchars($msg['email']); ?>')" class="px-2 py-1 bg-green-900/30 text-green-400 text-xs rounded hover:bg-green-900/50 transition">Reply</button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this message?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="msg_id" value="<?php echo htmlspecialchars($msg['id']); ?>">
                                                <button type="submit" class="px-2 py-1 bg-red-900/30 text-red-400 text-xs rounded hover:bg-red-900/50 transition">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-8 text-center text-gray-600">
                    <p>No messages found in this category.</p>
                </div>
            <?php endif; ?>
        </div>

        <footer class="mt-8 text-center">
            <p class="text-gray-600 text-xs uppercase tracking-widest italic">KWA Admin Panel &bull; Karachi, PK</p>
        </footer>
    </div>

    <!-- View Message Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-4 pb-4 border-b border-gray-700">
                <h2 class="text-xl font-bold text-white">Message Details</h2>
                <button onclick="closeModal('viewModal')" class="text-gray-400 hover:text-white text-2xl">&times;</button>
            </div>
            <div id="modalContent" class="text-gray-300 space-y-3"></div>
        </div>
    </div>

    <script>
        function viewMessage(msgJson) {
            try {
                const msg = JSON.parse(msgJson);
                const modal = document.getElementById('viewModal');
                const content = document.getElementById('modalContent');
                
                const statusMap = {
                    'new': 'New',
                    'read': 'Read',
                    'replied': 'Replied',
                    'archived': 'Archived'
                };

                content.innerHTML = `
                    <div>
                        <strong class="text-emerald-400">ID:</strong> ${msg.id}
                    </div>
                    <div>
                        <strong class="text-emerald-400">Status:</strong> ${statusMap[msg.status] || 'New'}
                    </div>
                    <div>
                        <strong class="text-emerald-400">Name:</strong> ${msg.name}
                    </div>
                    <div>
                        <strong class="text-emerald-400">Email:</strong> <a href="mailto:${msg.email}" class="text-blue-400 hover:underline">${msg.email}</a>
                    </div>
                    <div>
                        <strong class="text-emerald-400">Phone:</strong> ${msg.phone || 'Not provided'}
                    </div>
                    <div>
                        <strong class="text-emerald-400">Category:</strong> ${msg.category}
                    </div>
                    <div>
                        <strong class="text-emerald-400">Subject:</strong> ${msg.subject || 'Not provided'}
                    </div>
                    <div>
                        <strong class="text-emerald-400">Date & Time:</strong> ${new Date(msg.timestamp).toLocaleString()}
                    </div>
                    <div>
                        <strong class="text-emerald-400">IP Address:</strong> ${msg.ip_address}
                    </div>
                    <div class="pt-4 border-t border-gray-700">
                        <strong class="text-emerald-400">Message:</strong>
                        <p class="mt-2 p-3 bg-gray-900 rounded text-gray-300 whitespace-pre-wrap">${msg.message}</p>
                    </div>
                    <div class="flex gap-2 mt-4 pt-4 border-t border-gray-700">
                        <form method="POST" style="flex: 1;">
                            <input type="hidden" name="action" value="mark_status">
                            <input type="hidden" name="msg_id" value="${msg.id}">
                            <input type="hidden" name="status" value="replied">
                            <button type="submit" class="w-full px-4 py-2 bg-green-900/30 text-green-400 rounded hover:bg-green-900/50 transition">Mark as Replied</button>
                        </form>
                        <a href="mailto:${msg.email}" class="flex-1 px-4 py-2 bg-blue-900/30 text-blue-400 rounded hover:bg-blue-900/50 transition text-center">Send Reply</a>
                    </div>
                `;
                
                modal.classList.add('active');
            } catch (e) {
                alert('Error parsing message data');
            }
        }

        function replyMessage(email) {
            window.location.href = 'mailto:' + email;
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('viewModal');
            if (event.target === modal) {
                modal.classList.remove('active');
            }
        }
    </script>
</body>
</html>
