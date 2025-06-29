<?php
// --- APIキー認証チェック ---
$certPath = __DIR__ . '/cert.json';
$clientApiKey = $_SERVER['HTTP_APIKEY'] ?? null;

if (!file_exists($certPath)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'cert.json not found']);
    exit;
}

$certs = json_decode(file_get_contents($certPath), true);

// APIキーが存在しない、または不正な場合
if (!$clientApiKey || !array_key_exists($clientApiKey, $certs)) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized: invalid or missing API key']);
    exit;
}

// パラメータ取得
$controller = $_GET['controller'] ?? null;
$action     = $_GET['action'] ?? null;
$id         = $_GET['id'] ?? null;

$filePath = __DIR__ . '/files/sample.pdf';

// ルーティング処理
if ($controller === 'user') {
    header('Content-Type: application/json');
    if ($action === 'get' && $id) {
        echo json_encode(['user_id' => $id, 'name' => '山田太郎']);
    } else {
        echo json_encode(['message' => 'User API']);
    }

} elseif ($controller === 'product') {
    header('Content-Type: application/json');
    if ($action === 'list') {
        echo json_encode(['products' => ['りんご', 'バナナ', 'みかん']], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['message' => 'Product API']);
    }

} elseif ($controller === 'base64') {
    // PDFをBase64で返却
    header('Content-Type: application/json');

    if (file_exists($filePath)) {
        $pdfData = file_get_contents($filePath);
        $base64 = base64_encode($pdfData);

        echo json_encode([
            'filename' => basename($filePath),
            'content_base64' => $base64
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'PDF not found']);
    }

} elseif ($controller === 'pdf') {
    // PDFファイルをブラウザ表示
    if (file_exists($filePath)) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="sample.pdf"');
        readfile($filePath);
        exit;
    } else {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'PDF not found']);
    }

} elseif ($controller === 'rotate') {
    $inputPath = __DIR__ . '/files/sample.pdf';
    $rotatedPath = __DIR__ . '/files/sample_rotated.pdf';

    if (!file_exists($inputPath)) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'PDF not found']);
        exit;
    }

    // 🔧 qpdf のフルパスを指定（重要！）
    $qpdfPath = '/usr/local/bin/qpdf';
    $cmd = "$qpdfPath \"$inputPath\" --rotate=90 -- \"$rotatedPath\"";
    exec($cmd . ' 2>&1', $output, $resultCode);

    if ($resultCode !== 0 || !file_exists($rotatedPath)) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Failed to rotate PDF',
            'cmd' => $cmd,
            'output' => $output,
            'code' => $resultCode
        ]);
        exit;
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="rotated.pdf"');
    readfile($rotatedPath);
    exit;
} elseif ($controller === 'pdflink') {
    $filePath = __DIR__ . '/files/sample.pdf';

    if (!file_exists($filePath)) {
        http_response_code(404);
        header('Content-Type: text/html');
        echo "<p>PDF not found</p>";
        exit;
    }

    // ✅ Web上の公開パス（ApacheがアクセスできるURL）
    $publicUrl = '/testapi/files/sample.pdf'; // ←環境に合わせて変更

    header('Content-Type: text/html; charset=utf-8');
    echo <<<HTML
<p>PDFのダウンロードおよびプレビューはこちら：</p>
<a href="$publicUrl" download>📥 PDFをダウンロード</a><br><br>
<iframe src="$publicUrl" width="600" height="800" style="border:1px solid #ccc;"></iframe>
HTML;
    exit;

} else {
    // 未知のcontroller
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid controller']);
}
