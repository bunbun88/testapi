<?php
$config = require __DIR__ . '/config.php';

$host = $config['host'];
$port = $config['port'];
$dbname = $config['dbname'];
$user = $config['user'];
$pass = $config['pass'];

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // テーブル作成SQL（email と password を追加）
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS certs (
            apikey VARCHAR(255) PRIMARY KEY,
            name VARCHAR(255),
            ip VARCHAR(255),
            basenode VARCHAR(255),
            email VARCHAR(255),
            password VARCHAR(255)
        )
    ";
    $pdo->exec($createTableSQL);

    // 初期データ挿入（新しい列も含む）
    $insertSQL = "
        INSERT INTO certs (apikey, name, ip, basenode, email, password) VALUES
        ('apikey1', 'Tanaka', '192.168.1.10', 'node01', 'tanaka@example.com', 'pass123'),
        ('apikey2', 'Sato',   '192.168.1.11', 'node02', 'sato@example.com',   'pass456')
        ON DUPLICATE KEY UPDATE 
            name = VALUES(name),
            ip = VALUES(ip),
            basenode = VALUES(basenode),
            email = VALUES(email),
            password = VALUES(password)
    ";
    $pdo->exec($insertSQL);

    echo "✅ certs テーブルを作成し、初期データを挿入しました。\n";
} catch (PDOException $e) {
    echo "❌ データベースエラー: " . $e->getMessage() . "\n";
}
