<?php
// config/config.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('SITE_NAME', 'FunFair ERP');
define('BASE_URL', 'http://localhost/funfair_erp/');

// Set timezone to Pakistan Standard Time (or +05:00)
date_default_timezone_set('Asia/Karachi');

// Include DB connection
require_once __DIR__ . '/db.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "login.php");
        exit;
    }
    
    // Auto-assign daily shifts dynamically
    global $pdo;
    $today = date('Y-m-d');
    if (!isset($_SESSION['shifts_assigned_date']) || $_SESSION['shifts_assigned_date'] !== $today) {
        try {
            $today = date('Y-m-d');
            
            // Find employees who do not have a shift assigned today
            $query = "SELECT id, default_shift_start, default_shift_end FROM employees 
                      WHERE status = 'active' AND id NOT IN (
                          SELECT employee_id FROM employee_shifts WHERE shift_date = ?
                      )";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$today]);
            $unassigned_emps = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($unassigned_emps)) {
                $insert_stmt = $pdo->prepare("INSERT INTO employee_shifts (employee_id, shift_date, start_time, end_time, status) VALUES (?, ?, ?, ?, 'scheduled')");
                foreach ($unassigned_emps as $emp) {
                    $insert_stmt->execute([$emp['id'], $today, $emp['default_shift_start'], $emp['default_shift_end']]);
                }
            }
            // Mark as checked to prevent checking again today for this session
            $_SESSION['shifts_assigned_date'] = $today;
        } catch (PDOException $e) {
            // Silently ignore or log error
        }
    }
}

// Check permission
function hasPermission($slug) {
    global $pdo;
    if (!isLoggedIn()) return false;
    
    $role_id = $_SESSION['role_id'] ?? null;
    if (!$role_id) return false;

    if (hasRole(['superadmin'])) return true;
    
    // Cache permissions in session if needed, for now query DB
    $stmt = $pdo->prepare("SELECT p.slug FROM permissions p 
                           JOIN role_permissions rp ON p.id = rp.permission_id 
                           WHERE rp.role_id = ? AND p.slug = ?");
    $stmt->execute([$role_id, $slug]);
    return $stmt->fetch() ? true : false;
}

function hasAnyPermission($slugs) {
    foreach ($slugs as $slug) {
        if (hasPermission($slug)) return true;
    }
    return false;
}

function requireAnyPermission($slugs) {
    if (!hasAnyPermission($slugs)) {
        header("Location: " . BASE_URL . "index.php?error=unauthorized");
        exit;
    }
}

function requirePermission($slug) {
    if (!hasPermission($slug)) {
        header("Location: " . BASE_URL . "index.php?error=unauthorized");
        exit;
    }
}

// Check role directly by name array
function hasRole($roles) {
    global $pdo;
    if (!isLoggedIn()) return false;
    
    $role_id = $_SESSION['role_id'] ?? null;
    if (!$role_id) return false;
    
    $stmt = $pdo->prepare("SELECT name FROM roles WHERE id = ?");
    $stmt->execute([$role_id]);
    $user_role = $stmt->fetchColumn();
    
    $norm_role = strtolower(str_replace(' ', '', (string)$user_role));
    $norm_roles = array_map(function($r) { return strtolower(str_replace(' ', '', (string)$r)); }, $roles);
    
    if (in_array($norm_role, $norm_roles)) {
        return true;
    }
    return false;
}
?>
