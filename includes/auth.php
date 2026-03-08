<?php
// includes/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if the user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if the user is an admin
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Get the redirect path after login based on role
 */
function getRedirectPath() {
    return isAdmin() ? '/admin/index.php' : '/dashboard.php';
}

/**
 * Redirect an already logged-in user to their appropriate dashboard
 */
function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        redirect(getRedirectPath());
    }
}

/**
 * Require login to access a page
 */
function requireLogin() {
    if (!isLoggedIn()) {
        setFlashMessage('error', 'Please log in to continue.');
        redirect('/login.php');
    }
}

/**
 * Require admin privileges to access a page
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        setFlashMessage('error', 'You do not have permission to access this page.');
        redirect('/index.php');
    }
}

/**
 * Get the current logged-in user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Log a user in
 */
function loginUser($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'];
}

/**
 * Log a user out
 */
function logoutUser() {
    session_unset();
    session_destroy();
}
