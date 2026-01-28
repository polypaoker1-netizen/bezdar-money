<?php
require_once 'config.php';

$action = $_POST['action'] ?? '';

switch($action) {
    case 'get_balance':
        $user_id = $_POST['user_id'] ?? '';
        echo json_encode(['balance' => getUserBalance($user_id)]);
        break;
        
    case 'create_deposit':
        $user_id = $_POST['user_id'] ?? '';
        $amount = (int)($_POST['amount'] ?? 0);
        
        if($amount < 50) {
            echo json_encode(['error' => 'Minimum 50 stars']);
            break;
        }
        
        $ton_amount = $amount * 0.015; // 1 star = 0.015 USD
        
        $stmt = $pdo->prepare("INSERT INTO deposits (user_id, amount, ton_amount) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $amount, $ton_amount]);
        $deposit_id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'deposit_id' => $deposit_id,
            'ton_address' => $config['ton_wallet'],
            'ton_amount' => $ton_amount
        ]);
        break;
        
    case 'create_withdrawal':
        $user_id = $_POST['user_id'] ?? '';
        $amount = (int)($_POST['amount'] ?? 0);
        $username = $_POST['username'] ?? '';
        
        if($amount < 350) {
            echo json_encode(['error' => 'Minimum 350 stars']);
            break;
        }
        
        if(!$username || !preg_match('/^@[a-zA-Z0-9_]{5,32}$/', $username)) {
            echo json_encode(['error' => 'Invalid Telegram username']);
            break;
        }
        
        $balance = getUserBalance($user_id);
        if($balance < $amount) {
            echo json_encode(['error' => 'Insufficient balance']);
            break;
        }
        
        // Списываем баланс
        updateUserBalance($user_id, -$amount);
        
        $stmt = $pdo->prepare("INSERT INTO withdrawals (user_id, amount, telegram_username) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $amount, $username]);
        $withdraw_id = $pdo->lastInsertId();
        
        // Логируем транзакцию
        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, details) VALUES (?, 'withdraw', ?, ?)");
        $stmt->execute([$user_id, $amount, json_encode(['username' => $username])]);
        
        echo json_encode([
            'success' => true,
            'withdraw_id' => $withdraw_id,
            'message' => 'Withdrawal request created'
        ]);
        break;
        
    case 'play_game':
        $user_id = $_POST['user_id'] ?? '';
        $game = $_POST['game'] ?? '';
        $bet = (int)($_POST['bet'] ?? 0);
        $data = $_POST['data'] ?? '';
        
        if($bet < 20) {
            echo json_encode(['error' => 'Minimum bet 20 stars']);
            break;
        }
        
        $balance = getUserBalance($user_id);
        if($balance < $bet) {
            echo json_encode(['error' => 'Insufficient balance']);
            break;
        }
        
        // Списываем ставку
        updateUserBalance($user_id, -$bet);
        
        $result = playGameLogic($game, $bet, $data);
        
        if($result['win']) {
            updateUserBalance($user_id, $result['win_amount']);
            $type = 'game_win';
        } else {
            $type = 'game_lose';
        }
        
        // Логируем
        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, game_type, details) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $user_id,
            $type,
            $result['win'] ? $result['win_amount'] : $bet,
            $game,
            json_encode($result)
        ]);
        
        echo json_encode(array_merge($result, [
            'new_balance' => getUserBalance($user_id)
        ]));
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}

function playGameLogic($game, $bet, $data) {
    switch($game) {
        case 'dice':
            $chance = (int)($data['chance'] ?? 50);
            $realChance = $chance * 0.125; // 1/8 шанс
            $win = mt_rand(1, 100) <= $realChance;
            
            if($win) {
                $multiplier = 100 / $chance;
                $winAmount = floor($bet * $multiplier);
                return ['win' => true, 'win_amount' => $winAmount, 'multiplier' => $multiplier];
            }
            return ['win' => false];
            
        case 'crash':
            // Генерируем краш точку от 1.0 до 100.0
            $r = mt_rand(0, 10000) / 10000;
            $crashPoint = 1.0 + (100.0 - 1.0) * $r;
            
            // Игрок может забрать только от 1.6x
            $playerCashout = min($crashPoint, 100.0);
            if($playerCashout < 1.6) {
                return ['win' => false, 'crash_point' => $crashPoint];
            }
            
            $winAmount = floor($bet * $playerCashout);
            return ['win' => true, 'win_amount' => $winAmount, 'multiplier' => $playerCashout];
            
        case 'roulette':
            $betType = $data['type'] ?? 'red';
            $number = mt_rand(0, 36);
            
            $win = false;
            $multiplier = 1;
            
            if($number === 0) {
                if($betType === 'green') $win = true;
                $multiplier = 14;
            } elseif($number % 2 === 1 && $betType === 'red') {
                $win = true;
                $multiplier = 2;
            } elseif($number % 2 === 0 && $number !== 0 && $betType === 'black') {
                $win = true;
                $multiplier = 2;
            }
            
            if($win) {
                $winAmount = floor($bet * $multiplier);
                return ['win' => true, 'win_amount' => $winAmount, 'multiplier' => $multiplier];
            }
            return ['win' => false];
            
        default:
            return ['win' => false];
    }
}
