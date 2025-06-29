<?php
// config.php を読み込む
$config = require __DIR__ . '/config.php';

$host = $config['host'];
$port = $config['port'];
$dbname = $config['dbname'];
$user = $config['user'];
$pass = $config['pass'];

$jsonFilePath = __DIR__ . '/../cert.json';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // certs テーブルから全カラム取得
    $stmt = $pdo->query("SELECT apikey, name, ip, basenode, email, password FROM certs");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 構造を変形（apikeyごとにオブジェクト化）
    $output = [];
    foreach ($results as $row) {
        $output[$row['apikey']] = [
            'name' => $row['name'],
            'ip' => $row['ip'],
            'basenode' => $row['basenode'],
            'email' => $row['email'],
            'password' => $row['password'] // ⚠️ セキュリティ上、本当に出力していいか検討を！
        ];
    }

    // JSONに変換
    $json = json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    // ファイルに上書き保存
    file_put_contents($jsonFilePath, $json);

    echo "✅ cert.json に整形されたデータを書き込みました。\n";
} catch (PDOException $e) {
    echo "❌ DB接続エラー: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ ファイル書き込みエラー: " . $e->getMessage() . "\n";
}
