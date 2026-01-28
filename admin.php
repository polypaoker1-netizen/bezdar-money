<?php
require_once 'config.php';

session_start();
if(!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    die('Access denied');
}

// Одобрить депозит
if(isset($_GET['approve_deposit'])) {
    $id = (int)$_GET['approve_deposit'];
    $stmt = $pdo->prepare("SELECT * FROM deposits WHERE id = ? AND status = 'pending'");
    $stmt->execute([$id]);
    $deposit = $stmt->fetch();
    
    if($deposit) {
        // Начисляем баланс
        updateUserBalance($deposit['user_id'], $deposit['amount']);
        
        // Обновляем статус
        $stmt = $pdo->prepare("UPDATE deposits SET status = 'approved', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
        
        // Логируем
        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, details) VALUES (?, 'deposit', ?, ?)");
        $stmt->execute([$deposit['user_id'], $deposit['amount'], json_encode(['deposit_id' => $id])]);
        
        echo "Deposit #$id approved";
    }
}

// Выполнить вывод (здесь интеграция с Fragment API)
if(isset($_GET['process_withdrawal'])) {
    $id = (int)$_GET['process_withdrawal'];
    $stmt = $pdo->prepare("SELECT * FROM withdrawals WHERE id = ? AND status = 'pending'");
    $stmt->execute([$id]);
    $withdrawal = $stmt->fetch();
    
    if($withdrawal) {
        // Здесь реальный код для Fragment API
        // $response = sendFragmentRequest($withdrawal['amount'], $withdrawal['telegram_username']);
        
        // Если успешно
        $stmt = $pdo->prepare("UPDATE withdrawals SET status = 'completed', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
        
        echo "Withdrawal #$id processed";
    }
}

// Показать все ожидающие
$stmt = $pdo->query("SELECT * FROM deposits WHERE status = 'pending' ORDER BY created_at DESC");
$deposits = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM withdrawals WHERE status = 'pending' ORDER BY created_at DESC");
$withdrawals = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .section { margin: 30px 0; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>BezdarMoney Admin Panel</h1>
    
    <div class="section">
        <h2>Pending Deposits</h2>
        <table>
            <tr>
                <th>ID</th><th>User ID</th><th>Amount</th><th>TON</th><th>Time</th><th>Action</th>
            </tr>
            <?php foreach($deposits as $d): ?>
            <tr>
                <td><?= $d['id'] ?></td>
                <td><?= htmlspecialchars($d['user_id']) ?></td>
                <td><?= $d['amount'] ?> ⭐</td>
                <td><?= $d['ton_amount'] ?> TON</td>
                <td><?= $d['created_at'] ?></td>
                <td><a href="?approve_deposit=<?= $d['id'] ?>">✅ Approve</a></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <div class="section">
        <h2>Pending Withdrawals</h2>
        <table>
            <tr>
                <th>ID</th><th>User ID</th><th>Amount</th><th>Username</th><th>Time</th><th>Action</th>
            </tr>
            <?php foreach($withdrawals as $w): ?>
            <tr>
                <td><?= $w['id'] ?></td>
                <td><?= htmlspecialchars($w['user_id']) ?></td>
                <td><?= $w['amount'] ?> ⭐</td>
                <td><?= htmlspecialchars($w['telegram_username']) ?></td>
                <td><?= $w['created_at'] ?></td>
                <td><a href="?process_withdrawal=<?= $w['id'] ?>">✅ Process</a></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
