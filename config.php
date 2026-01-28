<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$config = [
    'db_host' => 'localhost',
    'db_name' => 'bezdarmoney',
    'db_user' => 'root',
    'db_pass' => '',
    'fragment_api_key' => 'f3de8ad1-f911-431b-9266-82bf65276ec8',
    'ton_wallet' => 'UQAXba2HyRqtUiZLIk5KnHznrD47jhy0FxcIZAu2rSZzmVEJ'
];

try {
    $pdo = new PDO(
        "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
        $config['db_user'],
        $config['db_pass']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die(json_encode(['error' => 'Database connection failed']));
}

function getUserBalance($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT balance FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$user) {
        $stmt = $pdo->prepare("INSERT INTO users (user_id, balance) VALUES (?, 1000)");
        $stmt->execute([$user_id]);
        return 1000;
    }
    return $user['balance'];
}

function updateUserBalance($user_id, $amount) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE user_id = ?");
    return $stmt->execute([$amount, $user_id]);
}
