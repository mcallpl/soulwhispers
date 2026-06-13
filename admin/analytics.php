<?php
require_once '../config.php';
require_once 'auth.php';

// Require admin login
require_admin_login();

// Check if user is super_admin - redirect if not
if (!is_super_admin()) {
    header('Location: index.php');
    exit;
}

$message = '';
$messageType = '';

// Fetch all admin users with their login info
$query = "SELECT id, username, full_name, role, last_login_at, login_count, created_at
          FROM admin_users
          ORDER BY login_count DESC, last_login_at DESC";
$result = $conn->query($query);
$admins = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
}

// Calculate statistics
$totalAdmins = count($admins);
$totalLogins = 0;
$activeToday = 0;
$activeTodayTime = strtotime('today midnight');

foreach ($admins as $admin) {
    $totalLogins += $admin['login_count'];
    if ($admin['last_login_at'] && strtotime($admin['last_login_at']) >= $activeTodayTime) {
        $activeToday++;
    }
}

$avgLogins = $totalAdmins > 0 ? round($totalLogins / $totalAdmins, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Analytics - Soul Whispers</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=EB+Garamond:wght@400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #1a2847;
            border: 1px solid #2a3a5a;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
        }

        .stat-card .label {
            font-size: 0.85rem;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .stat-card .value {
            font-size: 2rem;
            font-weight: 600;
            color: #d4af37;
            font-family: 'Playfair Display', serif;
        }

        .admins-table-wrapper {
            overflow-x: auto;
            background: #1a2847;
            border: 1px solid #2a3a5a;
            border-radius: 8px;
            padding: 1.5rem;
        }

        .admins-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .admins-table thead {
            border-bottom: 2px solid #2a3a5a;
        }

        .admins-table th {
            padding: 1rem;
            text-align: left;
            color: #d4af37;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }

        .admins-table td {
            padding: 1rem;
            border-bottom: 1px solid #2a3a5a;
            color: #e0e0e0;
        }

        .admins-table tbody tr:hover {
            background: rgba(212, 175, 55, 0.05);
        }

        .role-badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .role-badge.super_admin {
            background: rgba(212, 175, 55, 0.2);
            color: #d4af37;
            border: 1px solid #d4af37;
        }

        .role-badge.admin {
            background: rgba(100, 150, 200, 0.2);
            color: #7da8d8;
            border: 1px solid #7da8d8;
        }

        .login-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #4caf50;
        }

        .status-indicator.inactive {
            background: #999;
        }

        .cell-username {
            font-weight: 500;
        }

        .cell-date {
            color: #aaa;
            font-size: 0.85rem;
        }

        .cell-number {
            text-align: center;
            font-weight: 600;
            color: #d4af37;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #888;
        }

        .empty-state p {
            margin: 0;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .admins-table {
                font-size: 0.8rem;
            }

            .admins-table th,
            .admins-table td {
                padding: 0.75rem 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <div class="admin-title-section">
                <h1>Admin Analytics</h1>
                <p>Login tracking and admin activity</p>
            </div>
            <div class="admin-controls">
                <span class="logged-in-user">Logged in as: <strong><?php echo get_admin_username(); ?></strong></span>
                <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
                <a href="?logout=1" class="btn btn-logout">Logout</a>
            </div>
        </header>

        <main class="admin-main">
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="label">Total Admins</div>
                    <div class="value"><?php echo $totalAdmins; ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">Total Logins</div>
                    <div class="value"><?php echo $totalLogins; ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">Active Today</div>
                    <div class="value"><?php echo $activeToday; ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">Avg Logins</div>
                    <div class="value"><?php echo $avgLogins; ?></div>
                </div>
            </div>

            <!-- Admin Activity Table -->
            <?php if (empty($admins)): ?>
            <div class="empty-state">
                <p>No admin users found</p>
            </div>
            <?php else: ?>
            <div class="admins-table-wrapper">
                <table class="admins-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Role</th>
                            <th>Last Login</th>
                            <th>Login Count</th>
                            <th>Account Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td class="cell-username"><?php echo htmlspecialchars($admin['username']); ?></td>
                            <td><?php echo htmlspecialchars($admin['full_name'] ?? '—'); ?></td>
                            <td>
                                <span class="role-badge <?php echo htmlspecialchars($admin['role']); ?>">
                                    <?php echo htmlspecialchars($admin['role']); ?>
                                </span>
                            </td>
                            <td class="cell-date">
                                <?php
                                if ($admin['last_login_at']) {
                                    $loginTime = strtotime($admin['last_login_at']);
                                    $isToday = date('Y-m-d', $loginTime) === date('Y-m-d');
                                    echo $isToday
                                        ? date('H:i:s', $loginTime)
                                        : date('M d, Y H:i', $loginTime);
                                } else {
                                    echo '—';
                                }
                                ?>
                            </td>
                            <td class="cell-number"><?php echo $admin['login_count']; ?></td>
                            <td class="cell-date"><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Handle logout
        const logoutLink = document.querySelector('[href*="logout"]');
        if (logoutLink) {
            logoutLink.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to logout?')) {
                    e.preventDefault();
                }
            });
        }
    </script>
</body>
</html>
