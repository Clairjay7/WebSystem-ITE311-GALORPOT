<?php
// Simple session checker
session_start();

echo "<h2>Session Debug</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n\n";
echo "Session Data:\n";
print_r($_SESSION);
echo "</pre>";

echo "<hr>";
echo "<h3>CodeIgniter Session Check</h3>";

// Load CodeIgniter
require_once 'system/bootstrap.php';

$session = \Config\Services::session();
echo "<pre>";
echo "isLoggedIn: " . ($session->get('isLoggedIn') ? 'YES' : 'NO') . "\n";
echo "role: " . ($session->get('role') ?? 'NONE') . "\n";
echo "name: " . ($session->get('name') ?? 'NONE') . "\n";
echo "email: " . ($session->get('email') ?? 'NONE') . "\n";
echo "id: " . ($session->get('id') ?? 'NONE') . "\n";
echo "</pre>";
?>

