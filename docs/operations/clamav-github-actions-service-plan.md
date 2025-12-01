# ClamAV GitHub Actions Service Container 導入計画

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-01 | GitHub Copilot | 初版作成: ClamAV Serviceコンテナ導入計画 |
| 2025-12-01 | GitHub Copilot | INSTREAMプロトコル使用の単一方針に統一 |

## 概要

GitHub Actions CI/CD環境でClamAVウイルススキャンテストを実行するため、以下の構成を実装します:

1. **ClamAV Serviceコンテナ**: `clamav/clamav:1.4` 公式イメージを使用し、ウイルス定義ファイルが事前ロード済みのclamdデーモンを提供
2. **INSTREAMプロトコル**: ファイル内容をTCP経由でストリーム送信し、VMとコンテナ間のファイルシステム分離問題を解決

この構成により、**現在のUbuntu VMベースのワークフローを維持したまま**、ローカル環境と同等の高速スキャン(0.02秒/ファイル)を実現します。

## 現状の課題

### ローカル環境
- ✅ ClamAVデーモン（clamd）が起動済み
- ✅ ウイルス定義ファイルがメモリにロード済み
- ✅ テスト実行時間: **0.19秒**（6テスト）

### CI/CD環境（GitHub Actions）
- ❌ ClamAVデーモンが起動していない
- ❌ `clamdscan`コマンドが接続失敗
- ❌ 現在の状態: 4テスト失敗（Permission denied）
- ⚠️ 代替案（clamscan使用）の問題: ウイルス定義ファイル読み込みで14秒/スキャン

## 解決策: INSTREAMプロトコルによるストリームスキャン

### 実装方針

**ClamAV Serviceコンテナ** + **INSTREAMプロトコル**の組み合わせで実装します。

### メリット

1. **高速化**: ウイルス定義ファイルが事前ロード済み（0.02秒/スキャン）
2. **既存ワークフロー維持**: Ubuntu VMベースの構成を変更不要
3. **ファイルシステム問題の完全解決**: ファイル内容を直接TCP送信
4. **環境統一**: ローカル(Unixソケット)とCI/CD(TCP)を自動切替
5. **公式プロトコル使用**: ClamAV公式のINSTREAMコマンドで安定動作
6. **並列実行対応**: 複数テストジョブで同じサービスを共有可能

### 実装コスト

1. **初回起動時間**: Serviceコンテナ起動に10-20秒（テスト全体で1回のみ）
2. **コード追加**: 約100行のINSTREAMプロトコル実装（一度実装すれば保守容易）
3. **イメージサイズ**: 500MB-1GB程度（GitHub Actionsで自動キャッシュ）

## 使用するDockerイメージ

### clamav/clamav:1.4 (公式イメージ)

**イメージ**: `clamav/clamav:1.4`

**選定理由**:
- ✅ ClamAV公式チームがメンテナンス
- ✅ ウイルス定義ファイルが含まれる
- ✅ clamdデーモンが自動起動
- ✅ 定期的な更新とセキュリティパッチ
- ✅ 複数アーキテクチャ対応（amd64, arm64）
- ✅ TCPソケット(3310ポート)がデフォルトで有効

**GitHub Actions設定例**:
```yaml
services:
  clamav:
    image: clamav/clamav:1.4
    ports:
      - 3310:3310
    options: >-
      --health-cmd "clamdscan --ping 1"
      --health-interval 10s
      --health-timeout 5s
      --health-retries 10
```

**参考**: https://hub.docker.com/r/clamav/clamav

---

## INSTREAMプロトコルの技術詳細

### 現状のdocker-compose.yml構成

MyTeacherアプリケーションは現在、以下のサービス構成で動作しています：

```yaml
services:
  app:
    networks:
      - mtdev-network
    depends_on:
      - db
      - s3
      - redis
    environment:
      - DB_HOST=db              # サービス名で接続
      - AWS_ENDPOINT=http://s3:9100
      - REDIS_HOST=redis

  db:
    networks:
      - mtdev-network
    ports:
      - "5432:5432"

  s3:
    networks:
      - mtdev-network
    ports:
      - "9100:9100"

  redis:
    networks:
      - mtdev-network
    ports:
      - "6379:6379"

networks:
  mtdev-network:
    driver: bridge
```

**特徴**:
- カスタムネットワーク `mtdev-network` で全サービスが通信
- サービス名（`db`, `s3`, `redis`）がホスト名として機能
- 内部通信はサービス名、外部通信は `localhost:ポート` を使用

---

### GitHub Actions Service Containerのネットワーク構造

GitHub Actionsのサービスコンテナは**異なるネットワーク構造**を持ちます：

#### パターン1: ジョブがコンテナで実行される場合（`container:`あり）

```yaml
jobs:
  test:
    runs-on: ubuntu-latest
    container: php:8.3-cli  # ジョブ自体がコンテナ
    
    services:
      clamav:
        image: clamav/clamav:1.4
        ports:
          - 3310:3310
```

**ネットワーク構造**:
```
Docker Network: github_network_xxxxx (自動作成)
├── ジョブコンテナ（php:8.3-cli）
│   └── 接続先: clamav:3310 （サービス名で直接接続可能）
└── clamavサービス
    └── ポート: 3310
```

**接続方法**:
- ✅ `clamav:3310` で直接接続可能
- ✅ ファイル共有: Dockerボリュームマウント経由
- ✅ 高速: コンテナ間通信、オーバーヘッド最小

**問題点**:
- ❌ 現在のワークフローは `runs-on: ubuntu-latest`（VMベース）
- ❌ `container:` 指定がないため、この方式は使えない

---

#### パターン2: ジョブがVMで実行される場合（現在の構成）

```yaml
jobs:
  test:
    runs-on: ubuntu-latest  # VM上で実行
    
    services:
      clamav:
        image: clamav/clamav:1.4
        ports:
          - 3310:3310  # ホスト側にポートマッピング
```

**ネットワーク構造**:
```
GitHub Actions VM (ubuntu-latest)
├── ジョブプロセス（直接VM上で実行）
│   └── 接続先: localhost:3310 または 127.0.0.1:3310
│                     ↓ ポートマッピング
└── Docker Bridge Network
    └── clamavサービス（コンテナ）
        └── 内部ポート: 3310
```

**接続方法**:
### ファイルシステム分離問題の詳細

**現在の構成**:
```
GitHub Actions VM (ubuntu-latest)
├── テストプロセス（VM上で直接実行）
│   └── アップロードファイル: /tmp/phpXXXXXX （VM内のパス）
│                     ↓ TCP接続のみ可能
└── Docker Bridge Network
    └── clamavサービス（コンテナ）
        └── ファイルシステム: コンテナ内部（独立）
```

**課題**: 従来の`clamdscan --file`方式では、VMのファイルパスをコンテナから参照できない

```php
// ❌ 従来の方式（CI/CDで失敗）
$process = new Process([
    'clamdscan',
    '--no-summary',
    '--infected',
    '/tmp/phpXXXXXX'  // ← VM内のパス、コンテナから見えない
]);
```

**エラー例**:
```
clamdscan: Can't access file /tmp/phpABCDEF
ERROR
```

---

### INSTREAMプロトコルによる解決

ClamAVの公式プロトコルである**INSTREAM**を使用することで、ファイルパスではなく**ファイル内容そのもの**をTCP経由で送信します。

#### INSTREAMプロトコルの仕組み

1. TCP接続確立（localhost:3310）
2. `zINSTREAM\0` コマンド送信
3. ファイル内容を8KBチャンクで送信（各チャンク前に4バイトの長さ情報）
4. 終了マーカー送信（長さ0のチャンク）
5. スキャン結果受信（"stream: OK" または "stream: Virus.Name FOUND"）

#### 実装例（完全版）

```php
// app/Services/Security/ClamAVScanService.php

private function scanWithRemoteDaemon(string $filePath): bool
{
    $host = config('security.clamav.daemon_host', 'localhost');
    $port = config('security.clamav.daemon_port', 3310);
    
    // TCP接続確立
    $socket = @fsockopen($host, $port, $errno, $errstr, 5);
    
    if (!$socket) {
        Log::error('ClamAV接続失敗', [
            'host' => $host,
            'port' => $port,
            'error' => "$errno: $errstr"
        ]);
        return false;
    }
    
    try {
        // INSTREAMコマンド送信
        if (!fwrite($socket, "zINSTREAM\0")) {
            throw new \RuntimeException('INSTREAMコマンド送信失敗');
        }
        
        // ファイル内容を8KBチャンクで送信
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            throw new \RuntimeException("ファイル読み込み失敗: $filePath");
        }
        
        while (!feof($handle)) {
            $chunk = fread($handle, 8192);
            if ($chunk === false) break;
            
            // チャンク長をビッグエンディアン32bitで送信
            $size = pack('N', strlen($chunk));
            if (!fwrite($socket, $size . $chunk)) {
                throw new \RuntimeException('チャンク送信失敗');
            }
        }
        fclose($handle);
        
        // 終了マーカー（長さ0）
        if (!fwrite($socket, pack('N', 0))) {
            throw new \RuntimeException('終了マーカー送信失敗');
        }
        
        // スキャン結果受信
        $response = trim(fgets($socket));
        Log::info('ClamAVスキャン結果', [
            'file' => basename($filePath),
            'response' => $response
        ]);
        
        // "stream: OK" または "stream: Virus.Name FOUND"
        return strpos($response, ' OK') !== false;
        
    } catch (\Exception $e) {
        Log::error('ClamAVスキャンエラー', [
            'error' => $e->getMessage(),
            'file' => $filePath
        ]);
        return false;
    } finally {
        fclose($socket);
    }
}
```

#### 環境自動検出ロジック

```php
// config/security.php

return [
    'clamav' => [
        'enabled' => env('CLAMAV_ENABLED', true),
        
        // ローカル開発: Unixソケット（高速）
        // CI/CD: TCPソケット（Service Container）
        'daemon_host' => env('CLAMAV_HOST', 
            env('CI') ? 'localhost' : null
        ),
        'daemon_port' => env('CLAMAV_PORT', 3310),
        
        // Unixソケットパス（ローカル開発用）
        'socket_path' => env('CLAMAV_SOCKET', '/var/run/clamav/clamd.ctl'),
    ],
];
```

```php
// サービス内での分岐
public function scan(UploadedFile|string $file): bool
{
    $filePath = $file instanceof UploadedFile ? $file->getRealPath() : $file;
    
    // 環境に応じた接続方法選択
    if (config('security.clamav.daemon_host')) {
        // CI/CD環境: TCP + INSTREAMプロトコル
        return $this->scanWithRemoteDaemon($filePath);
    } else {
        // ローカル開発: Unixソケット + clamdscanコマンド
        return $this->scanWithLocalDaemon($filePath);
    }
}
```

**メリット**:
- ✅ **ファイルシステム問題の完全解決**: ファイル内容を直接送信
- ✅ **環境統一**: ローカル/CI-CDで同じスキャンロジック
- ✅ **既存ワークフロー維持**: Ubuntu VMベースのまま実装可能
- ✅ **公式プロトコル**: ClamAV公式サポート、安定性高
- ✅ **柔軟な接続**: Unixソケット/TCPを環境変数で切り替え

**実装コスト**:
- ⚠️ INSTREAMプロトコル実装（約100行）
- ⚠️ 大容量ファイルでメモリ消費増加（チャンク送信で軽減）
- ⚠️ ネットワーク帯域消費（通常は問題なし）

---

**メリット**:
- ✅ ローカル環境と完全一致
- ✅ 既存のネットワーク構造を再現
- ✅ ファイル共有が自然
- ✅ デバッグが容易

**デメリット**:
- ❌ 起動時間が長い（複数コンテナ起動）
- ❌ CI/CD実行時間が増加
- ❌ リソース消費が多い
- ❌ ワークフローが複雑化

---

### 推奨アプローチ（現実的な選択）

**Phase 1: INSTREAMプロトコル実装（最優先）**

- 理由: 最小限の変更で動作、ポートマッピングのみで解決
- 実装: `ClamAVScanService` にリモートスキャンメソッド追加
- 互換性: ローカル環境（Unixソケット）とCI/CD環境（TCP）を自動切替
- 所要時間: 30分

**Phase 2（将来的な改善）: ジョブコンテナ化検討**

- タイミング: 他のサービス（PostgreSQL、Redis）もCI/CD統合時
- メリット: 完全な環境再現、ファイル共有の簡素化
- 条件: ワークフロー全体のリファクタリングとして実施

---

### 実装の複雑性の具体例

#### 現在の `ClamAVScanService`（ローカル専用）

```php
// ✅ シンプルだがローカルのみ対応
$process = new Process([
    $this->clamdScanPath,
    '--no-summary',
    '--infected',
    $filePath  // ← ファイルパスを直接渡す
]);
```

#### INSTREAMプロトコル対応版（ローカル + リモート対応）

```php
// ⚠️ 複雑だが両環境対応
public function scan(UploadedFile|string $file): bool
{
    $filePath = $file instanceof UploadedFile ? $file->getRealPath() : $file;
    
    // リモートデーモン（GitHub Actions Service Container）の場合
    if ($this->daemonHost && $this->daemonPort) {
        return $this->scanWithRemoteDaemon($filePath);
    }
    
    // ローカルデーモン（Unixソケット）の場合
    if ($this->useDaemon && $this->isDaemonAvailable()) {
        return $this->scanWithLocalDaemon($filePath);
    }
    
    // フォールバック: clamscan使用
    return $this->scanWithClamscan($filePath);
}

private function scanWithRemoteDaemon(string $filePath): bool
{
    // 約50行のソケット通信コード
    // INSTREAMプロトコル実装
}
```

**コード行数比較**:
- 現在: 約100行
- INSTREAM対応後: 約200行（2倍）
- ただし、一度実装すれば保守は容易

---

### ネットワーク設定のチェックリスト

GitHub Actions Service Containerを使用する場合の確認事項：

#### ✅ 必須設定

- [ ] `services:` ブロックにclamav定義
- [ ] `ports:` でポートマッピング（例: `3310:3310`）
- [ ] 環境変数 `CLAMAV_DAEMON_HOST=localhost`
- [ ] 環境変数 `CLAMAV_DAEMON_PORT=3310`
- [ ] healthcheckで起動待機
- [ ] netcat（`nc`）による接続確認

#### ⚠️ 注意点

- [ ] `localhost` と `127.0.0.1` の違い（IPv4/IPv6）
- [ ] ファイアウォール設定（通常は不要だが環境依存）
- [ ] タイムアウト値の調整（接続・読み込み）
- [ ] エラーハンドリング（接続失敗時のフォールバック）

#### 🔍 デバッグ用コマンド

```bash
# ポート接続確認
nc -zv localhost 3310

# clamavサービスのログ確認
docker logs <clamav-container-id>

# INSTREAMプロトコルのテスト
echo -e "zPING\0" | nc localhost 3310
# 期待される応答: "PONG"
```

---

### まとめ: ネットワーク設定の課題と対策

| 課題 | 原因 | 対策 | 実装難易度 |
|------|------|------|-----------|
| ファイル共有不可 | VMとコンテナの分離 | INSTREAMプロトコル使用 | ⭐⭐⭐（中） |
| ポート衝突の可能性 | 複数ジョブ並列実行 | ランダムポート or job間分離 | ⭐（低） |
| 接続タイムアウト | サービス起動遅延 | healthcheck + 接続確認ループ | ⭐⭐（低～中） |
| デバッグ困難 | ネットワークログ不足 | 詳細ログ + 接続診断コマンド | ⭐⭐（低～中） |

**INSTREAMプロトコル実装の全体像**:
1. GitHub Actions ワークフロー設定（10分）
2. ClamAVScanService.php 修正（30分）
3. テスト実行・検証（10分）
4. 総所要時間: **約50分**

---

## 実装計画

### Phase 1: GitHub Actions ワークフロー修正

**ファイル**: `.github/workflows/deploy-myteacher-app.yml`

**変更内容**:

```yaml
name: Deploy MyTeacher App

on:
  push:
    branches: [main]
    paths:
      - 'app/**'
      - 'config/**'
      - 'database/**'
      - 'routes/**'
      - 'public/**'
      - 'resources/**'
      - 'composer.json'
      - 'composer.lock'
      - '.github/workflows/deploy-myteacher-app.yml'
  workflow_dispatch:
    inputs:
      skip_tests:
        description: 'Skip tests'
        required: false
        default: 'false'

jobs:
  build-and-deploy:
    runs-on: ubuntu-latest
    
    # ✅ ClamAV Serviceコンテナを追加
    services:
      clamav:
        image: clamav/clamav:1.4
        ports:
          - 3310:3310
        options: >-
          --health-cmd "clamdscan --ping 1"
          --health-interval 10s
          --health-timeout 5s
          --health-retries 10
    
    steps:
      # ... 既存のステップ ...
      
      - name: Run Tests
        if: ${{ !inputs.skip_tests }}
        continue-on-error: true
        env:
          # ✅ ClamAV設定を追加
          CLAMAV_USE_DAEMON: true
          CLAMAV_DAEMON_HOST: localhost
          CLAMAV_DAEMON_PORT: 3310
        run: |
          echo "🧪 Running tests with SQLite in-memory database..."
          
          # ... 既存のテスト設定 ...
          
          # ✅ ClamAVサービス接続確認
          echo "🔍 Checking ClamAV service availability..."
          timeout 30 bash -c 'until nc -z localhost 3310; do sleep 1; done' || {
            echo "⚠️ ClamAV service not ready, tests may fail"
          }
          
          php artisan test --parallel || {
            echo "⚠️ Tests failed but continuing deployment (continue-on-error: true)"
            echo "⚠️ Please check test results: https://github.com/${{ github.repository }}/actions/runs/${{ github.run_id }}"
            echo "⚠️ Failed tests should be fixed in subsequent deployment"
          }
          
          echo "✅ Test execution completed"
```

---

### Phase 2: Laravel設定修正

#### 2-1. ClamAVScanService の修正

**ファイル**: `app/Services/Security/ClamAVScanService.php`

**変更内容**: デーモンホスト/ポート設定のサポート追加

```php
<?php

namespace App\Services\Security;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

class ClamAVScanService implements VirusScanServiceInterface
{
    private array $scanResult = [];
    private string $clamScanPath;
    private string $clamdScanPath;
    private bool $useDaemon;
    private int $timeout;
    
    // ✅ デーモン接続情報を追加
    private ?string $daemonHost;
    private ?int $daemonPort;

    public function __construct()
    {
        $this->clamScanPath = config('security.clamav.path', '/usr/bin/clamscan');
        $this->clamdScanPath = config('security.clamav.daemon_path', '/usr/bin/clamdscan');
        $this->useDaemon = config('security.clamav.use_daemon', false) || app()->environment('testing');
        $this->timeout = config('security.clamav.timeout', 60);
        
        // ✅ GitHub Actions Service Container用の設定
        $this->daemonHost = config('security.clamav.daemon_host');
        $this->daemonPort = config('security.clamav.daemon_port');
    }

    public function scan(UploadedFile|string $file): bool
    {
        $filePath = $file instanceof UploadedFile ? $file->getRealPath() : $file;

        if (!file_exists($filePath)) {
            Log::error('Virus scan failed: File not found', ['path' => $filePath]);
            $this->scanResult = [
                'status' => 'error',
                'message' => 'File not found',
                'file' => $filePath,
            ];
            return false;
        }

        try {
            // デーモンモード or 通常モードでスキャン実行
            if ($this->useDaemon && $this->isDaemonAvailable()) {
                $command = [$this->clamdScanPath, '--no-summary', '--infected'];
                
                // ✅ リモートデーモン接続オプション追加
                if ($this->daemonHost && $this->daemonPort) {
                    $command[] = '--stream';
                    $command[] = '--fdpass'; // Unix socket経由でファイルを渡す（GitHub Actions不要）
                }
                
                $command[] = $filePath;
                
                $process = new Process($command);
                $process->setTimeout(5);
            } else {
                $process = new Process([
                    $this->clamScanPath,
                    '--no-summary',
                    '--infected',
                    $filePath
                ]);
                $process->setTimeout($this->timeout);
            }

            $process->run();
            $output = $process->getOutput();
            $exitCode = $process->getExitCode();

            // 終了コード: 0=ウイルスなし, 1=ウイルス検出, 2=エラー
            if ($exitCode === 0) {
                $this->scanResult = [
                    'status' => 'clean',
                    'message' => 'No virus detected',
                    'file' => $filePath,
                    'output' => $output,
                ];
                Log::info('Virus scan: Clean', ['file' => $filePath]);
                return true;
            } elseif ($exitCode === 1) {
                $this->scanResult = [
                    'status' => 'infected',
                    'message' => 'Virus detected',
                    'file' => $filePath,
                    'output' => $output,
                    'details' => $this->parseInfectedOutput($output),
                ];
                Log::warning('Virus scan: Infected', ['file' => $filePath, 'details' => $this->scanResult['details']]);
                return false;
            } else {
                $this->scanResult = [
                    'status' => 'error',
                    'message' => 'Scan error',
                    'file' => $filePath,
                    'exit_code' => $exitCode,
                    'output' => $output,
                ];
                Log::error('Virus scan: Error', ['file' => $filePath, 'exit_code' => $exitCode, 'error' => $output]);
                return false;
            }
        } catch (ProcessTimedOutException $e) {
            Log::error('Virus scan timeout', ['file' => $filePath, 'error' => $e->getMessage()]);
            $this->scanResult = [
                'status' => 'error',
                'message' => 'Scan timeout',
                'file' => $filePath,
            ];
            return false;
        } catch (\Exception $e) {
            Log::error('Virus scan exception', ['file' => $filePath, 'error' => $e->getMessage()]);
            $this->scanResult = [
                'status' => 'error',
                'message' => $e->getMessage(),
                'file' => $filePath,
            ];
            return false;
        }
    }

    protected function isDaemonAvailable(): bool
    {
        static $available = null;
        
        if ($available !== null) {
            return $available;
        }
        
        try {
            // ✅ リモートデーモンの場合はポート接続チェック
            if ($this->daemonHost && $this->daemonPort) {
                $socket = @fsockopen($this->daemonHost, $this->daemonPort, $errno, $errstr, 2);
                if ($socket) {
                    fclose($socket);
                    $available = true;
                    return true;
                }
                Log::warning('ClamAV daemon not reachable', [
                    'host' => $this->daemonHost,
                    'port' => $this->daemonPort,
                    'error' => "$errno: $errstr"
                ]);
                $available = false;
                return false;
            }
            
            // ローカルデーモンの場合はclamdscan --versionで確認
            $process = new Process([$this->clamdScanPath, '--version']);
            $process->setTimeout(1);
            $process->run();
            
            $available = $process->isSuccessful();
            return $available;
        } catch (\Exception $e) {
            $available = false;
            return false;
        }
    }

    // ... 他のメソッドは既存のまま ...
}
```

---

#### 2-2. config/security.php の修正

**追加設定**:

```php
<?php

return [
    'clamav' => [
        // ClamAVコマンドのパス
        'path' => env('CLAMAV_PATH', '/usr/bin/clamscan'),

        // ClamAVデーモンスキャンコマンドのパス（高速）
        'daemon_path' => env('CLAMAV_DAEMON_PATH', '/usr/bin/clamdscan'),

        // デーモンモードを使用（テスト環境で自動有効化）
        'use_daemon' => env('CLAMAV_USE_DAEMON', false),

        // ✅ デーモン接続情報（GitHub Actions Service Container用）
        'daemon_host' => env('CLAMAV_DAEMON_HOST', null),
        'daemon_port' => env('CLAMAV_DAEMON_PORT', null),

        // スキャンタイムアウト（秒）
        'timeout' => env('CLAMAV_TIMEOUT', 60),

        // スキャン対象ファイルサイズ上限（バイト、0=無制限）
        'max_file_size' => env('CLAMAV_MAX_FILE_SIZE', 0),
    ],

    'upload' => [
        // ウイルススキャンを有効化
        'virus_scan_enabled' => env('SECURITY_VIRUS_SCAN_ENABLED', true),

        // スキャン失敗時の動作（strict: 拒否, lenient: 警告のみ）
        'scan_failure_mode' => env('SECURITY_SCAN_FAILURE_MODE', 'strict'),
    ],
];
```

---

### Phase 3: 動作確認とテスト

#### 3-1. ローカルでの動作確認（Docker Compose）

**ファイル**: `docker-compose.test.yml` (新規作成)

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: docker/Dockerfile
    volumes:
      - .:/var/www/html
    environment:
      - CLAMAV_USE_DAEMON=true
      - CLAMAV_DAEMON_HOST=clamav
      - CLAMAV_DAEMON_PORT=3310
    depends_on:
      clamav:
        condition: service_healthy
    command: php artisan test tests/Feature/Security/VirusScanServiceTest.php

  clamav:
    image: clamav/clamav:1.4
    ports:
      - "3310:3310"
    healthcheck:
      test: ["CMD", "clamdscan", "--ping", "1"]
      interval: 10s
      timeout: 5s
      retries: 10
```

**実行**:
```bash
docker-compose -f docker-compose.test.yml up --abort-on-container-exit
```

---

#### 3-2. GitHub Actions でのテスト

**手順**:
1. ブランチ作成: `git checkout -b feature/clamav-service-container`
2. ワークフロー修正（Phase 1の内容）
3. コミット＆プッシュ
4. Pull Request作成してCI実行確認
5. テスト結果を確認（4つのClamAVテストが成功するか）

---

## タイムライン

| Phase | 作業内容 | 所要時間 | 担当 |
|-------|---------|---------|------|
| Phase 1 | GitHub Actionsワークフロー修正 | 10分 | 開発者 |
| Phase 2 | Laravel設定・コード修正 | 20分 | 開発者 |
| Phase 3 | ローカル動作確認 | 10分 | 開発者 |
| Phase 4 | GitHub Actions動作確認 | 10分 | CI/CD |
| **合計** | | **50分** | |

---

## 成功基準

### 必須要件
- ✅ GitHub ActionsでClamAVサービスコンテナが正常起動
- ✅ VirusScanServiceTestの4テストがすべて成功
- ✅ テスト実行時間が5秒以内（サービス起動時間除く）
- ✅ ローカル環境との動作一致性

### 理想要件
- ✅ CI/CD全体の実行時間が10秒以内に増加（許容範囲）
- ✅ 並列テスト実行時もClamAVサービスが安定動作
- ✅ エラーログが明確で問題切り分けが容易

---

## リスクと対策

### リスク1: サービスコンテナ起動遅延
**影響**: テスト開始時にClamAVが準備できず失敗

**対策**:
- healthcheckで確実に起動待機（retries: 10で最大100秒）
- テストステップでnc（netcat）による接続確認を追加
- タイムアウト値を適切に設定（30秒程度）

---

### リスク2: ネットワーク接続の問題
**影響**: localhost:3310に接続できない

**対策**:
- ホスト名の明示的設定（`CLAMAV_DAEMON_HOST=localhost`）
- ポートマッピングの確認（services配列で正しく設定）
- デバッグログで接続状態を確認

---

### リスク3: ウイルス定義ファイルの古さ
**影響**: 最新のウイルスを検出できない

**対策**:
- イメージのタグを定期的に更新（`:latest`ではなく`:1.4`などバージョン指定）
- freshclamによる自動更新が有効なイメージを使用
- 月次でのイメージ更新をスケジュール化

---

## 参考リンク

- [GitHub Actions - Service Containers](https://docs.github.com/en/actions/using-containerized-services/about-service-containers)
- [ClamAV Official Docker Image](https://hub.docker.com/r/clamav/clamav)
- [ClamAV Documentation](https://docs.clamav.net/)
- [ClamAV INSTREAM Protocol](https://linux.die.net/man/8/clamd)
- [Laravel Testing - External Services](https://laravel.com/docs/11.x/testing#interacting-with-external-services)

---

## 次のアクション

1. ✅ **このドキュメントのレビュー**: チーム内で合意形成
2. 🔧 **Phase 1実装**: ワークフロー修正（10分）
3. 🔧 **Phase 2実装**: ClamAVScanService.php修正（30分）
4. 🧪 **Phase 3検証**: テスト実行・動作確認（10分）
5. 📊 **結果レポート**: 成功/失敗の記録と次回改善点

---
