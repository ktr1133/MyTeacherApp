<?php

/**
 * ポータルサイト静的HTMLエクスポートスクリプト
 * 
 * 使用方法:
 *   docker-compose exec app php artisan-export-portal.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// 出力ディレクトリ
$outputDir = __DIR__.'/public/portal-static';

// ディレクトリ作成
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}
mkdir("$outputDir/guide", 0755, true);

// エクスポート対象のページ
$pages = [
    '/portal' => 'index.html',
    '/portal/faq' => 'faq.html',
    '/portal/maintenance' => 'maintenance.html',
    '/portal/contact' => 'contact.html',
    '/portal/updates' => 'updates.html',
    '/portal/guide' => 'guide/index.html',
];

echo "=== ポータルサイト静的HTMLエクスポート ===\n\n";

foreach ($pages as $uri => $filename) {
    echo "エクスポート中: $uri → $filename ... ";
    
    try {
        // リクエスト作成
        $request = Illuminate\Http\Request::create($uri, 'GET');
        
        // レスポンス取得
        $response = $kernel->handle($request);
        
        // HTML保存
        $outputPath = "$outputDir/$filename";
        file_put_contents($outputPath, $response->getContent());
        
        echo "✓\n";
    } catch (Exception $e) {
        echo "✗ ({$e->getMessage()})\n";
    }
    
    $kernel->terminate($request, $response);
}

// アセットコピー
echo "\nアセットコピー中...\n";

$assetDirs = ['build', 'images'];
foreach ($assetDirs as $dir) {
    $source = __DIR__."/public/$dir";
    $dest = "$outputDir/$dir";
    
    if (is_dir($source)) {
        echo "  $dir/ ... ";
        exec("cp -r $source $dest");
        echo "✓\n";
    }
}

// favicon等のコピー
$files = [
    'favicon.ico', 
    'favicon.svg', 
    'favicon-16x16.png', 
    'favicon-32x32.png', 
    'apple-touch-icon.png', 
    'robots.txt', 
    'site.webmanifest'
];
foreach ($files as $file) {
    $source = __DIR__."/public/$file";
    if (file_exists($source)) {
        copy($source, "$outputDir/$file");
    }
}

// HTMLファイル内のlocalhostパスを削除
echo "\nHTMLパス修正中...\n";
$htmlFiles = glob("$outputDir/*.html") + glob("$outputDir/*/*.html");
foreach ($htmlFiles as $file) {
    $content = file_get_contents($file);
    $content = str_replace('http://localhost', '', $content);
    file_put_contents($file, $content);
    echo "  " . basename($file) . " ... ✓\n";
}

echo "\n=== エクスポート完了 ===\n";
echo "出力先: $outputDir\n";
echo "\n次のステップ:\n";
echo "  aws s3 sync $outputDir/ s3://myteacher-portal-site/ --delete\n";
echo "  aws cloudfront create-invalidation --distribution-id EGM0NTO6T3TIL --paths '/*'\n";
