<?php
// 環境変数テスト用エンドポイント
header('Content-Type: application/json');

echo json_encode([
    'APP_KEY_SET' => !empty($_ENV['APP_KEY']) || !empty(getenv('APP_KEY')),
    'DB_PASSWORD_SET' => !empty($_ENV['DB_PASSWORD']) || !empty(getenv('DB_PASSWORD')),
    'APP_ENV' => $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?? 'not set',
    'APP_DEBUG' => $_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG') ?? 'not set',
    'DB_HOST' => $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'not set',
    'REDIS_HOST' => $_ENV['REDIS_HOST'] ?? getenv('REDIS_HOST') ?? 'not set',
], JSON_PRETTY_PRINT);
