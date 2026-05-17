<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Step 1: PHP works<br>";

require_once __DIR__ . '/config/db.php';
echo "Step 2: DB connected<br>";

require_once __DIR__ . '/includes/auth_functions.php';
echo "Step 3: Auth functions loaded<br>";

echo "All good!";
