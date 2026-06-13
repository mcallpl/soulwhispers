<?php
// Admin Authentication Helper

session_start();

// Check if user is authenticated
function require_admin_login() {
    if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
        header('Location: login.php');
        exit;
    }
}

// Logout function
function logout_admin() {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Get current admin username
function get_admin_username() {
    return isset($_SESSION['admin_username']) ? htmlspecialchars($_SESSION['admin_username']) : 'Admin';
}

// Get current admin role
function get_admin_role() {
    require_once '../config.php';

    if (!isset($_SESSION['admin_user_id'])) {
        return null;
    }

    $query = "SELECT role FROM admin_users WHERE id = ?";
    $stmt = $GLOBALS['conn']->prepare($query);
    $stmt->bind_param('i', $_SESSION['admin_user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user['role'];
    }

    $stmt->close();
    return null;
}

// Check if user is super admin
function is_super_admin() {
    return get_admin_role() === 'super_admin';
}

// Check if user is authenticated (without redirect)
function is_admin_authenticated() {
    return isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true;
}
?>
