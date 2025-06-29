<?php
$config = require __DIR__ . '/config.php';

header('Content-Type: application/json');

$email = $_POST['email'] ?? null;
$password = $_POST['password'] ?? null;

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'emailとpasswordを指定してください']);
    exit;
}

try {
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8";
    $pdo = new PDO($dsn, $config['user'], $config['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // すでに登録済みか確認
    $stmt = $pdo->prepare("SELECT apikey FROM certs WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'このメールアドレスは既に登録されています']);
        exit;
    }

    // ランダムなapikeyを生成（例：32文字の英数字）
    $apikey = bin2hex(random_bytes(16));

    // 仮の登録データ（本来はname/ip/basenodeは別途入力される想定）
    $stmt = $pdo->prepare("INSERT INTO certs (apikey, name, ip, basenode, email, password) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $apikey,
        '新規ユーザー',
        $_SERVER['REMOTE_ADDR'],  // IP
        'node-' . rand(100, 999), // basenode名の仮決定
        $email,
        $password                 // 本番環境では password_hash() 推奨！
    ]);

    $exportScript = __DIR__ . '/db/export_cert.php';
    if (file_exists($exportScript)) {
        // CLI経由でPHPスクリプトを実行（サーバによっては `php` のフルパス指定が必要）
        exec("/usr/local/bin/php " . escapeshellarg($exportScript), $output, $resultCode);

        if ($resultCode !== 0) {
            // cert.json 更新失敗しても登録自体は成功なので、警告として返す
            echo json_encode([
                'apikey' => $apikey,
                'warning' => '登録成功。ただし cert.json の更新に失敗しました。',
                'export_output' => $output
            ]);
            exit;
        }
    }

    echo json_encode(['apikey' => $apikey]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DBエラー: ' . $e->getMessage()]);
}
