# ウイルススキャン実装レポート

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-11-30 | GitHub Copilot | 初版作成: ClamAVウイルススキャン実装完了 |

## 概要

Stripe管理者画面セキュリティ要件に対応し、**ClamAVによるウイルススキャン機能**を実装しました。この機能により、以下の目標を達成しました:

- ✅ **ファイルアップロード時の自動ウイルススキャン**: タスク証拠画像アップロード時にClamAVでスキャン
- ✅ **ウイルス検出時のアップロード拒否**: 感染ファイルをブロックし、ユーザーに通知
- ✅ **セキュリティログ記録**: 全スキャン結果をログに記録（監査証跡）
- ✅ **柔軟な設定**: 環境変数でスキャン有効/無効、タイムアウト、動作モードを制御
- ✅ **包括的なテスト**: EICAR標準テストファイルで検出機能を検証

## 計画との対応

**参照ドキュメント**: `docs/security/stripe-admin-security-implementation.md`

| 計画項目 | ステータス | 実施内容 | 差異・備考 |
|---------|-----------|---------|-----------|
| ClamAVインストール | ✅ 完了 | ClamAV 1.4.3インストール、署名DB更新（8.7M+） | なし |
| サービス実装 | ✅ 完了 | `ClamAVScanService`実装（Interface + Concrete） | なし |
| DI登録 | ✅ 完了 | `AppServiceProvider`にバインディング追加 | なし |
| ファイルアップロード統合 | ✅ 完了 | `RequestApprovalAction`と`UploadTaskImageApiAction`に統合 | なし |
| テスト作成 | ✅ 完了 | 6テスト実装、全テスト合格（EICAR検出確認） | なし |
| 設定ファイル | ✅ 完了 | `config/security.php`作成、環境変数追加 | なし |
| ドキュメント作成 | ✅ 完了 | 本レポート作成 | なし |

## 実施内容詳細

### 1. ClamAVインストール

```bash
# システムレベルでClamAVをインストール
sudo apt install -y clamav clamav-daemon

# ウイルス定義データベースを最新に更新
sudo systemctl stop clamav-freshclam
sudo freshclam
sudo systemctl start clamav-freshclam
```

**インストール結果**:
- ClamAV バージョン: 1.4.3+dfsg-0ubuntu0.24.04.1
- ウイルス署名数: 8,724,748種類
  - main.cvd v62: 6,647,427署名
  - daily.cld v27835: 2,077,241署名
  - bytecode.cvd v339: 80署名
- 自動更新サービス: `clamav-freshclam.service`有効化

### 2. サービス実装

**ファイル**: `/app/Services/Security/`

#### `VirusScanServiceInterface.php` (インターフェース)

```php
interface VirusScanServiceInterface
{
    public function scan(UploadedFile|string $file): bool;
    public function getScanResult(): array;
    public function isAvailable(): bool;
}
```

#### `ClamAVScanService.php` (実装)

**主要機能**:
- `scan()`: `clamscan`コマンド実行でウイルススキャン
- 終了コード判定: 0=クリーン、1=感染、2=エラー
- タイムアウト制御: デフォルト60秒（環境変数で変更可能）
- 詳細ログ記録: ユーザーID、ファイル名、スキャン結果を記録

**実装コード抜粋**:
```php
public function scan(UploadedFile|string $file): bool
{
    $filePath = $file instanceof UploadedFile ? $file->getRealPath() : $file;
    
    $process = new Process([
        $this->clamScanPath,
        '--no-summary',
        '--infected',
        $filePath
    ]);
    
    $process->setTimeout($this->timeout);
    $process->run();
    
    $exitCode = $process->getExitCode();
    
    if ($exitCode === 0) {
        // ウイルスなし
        $this->scanResult = ['status' => 'clean', ...];
        return true;
    } elseif ($exitCode === 1) {
        // ウイルス検出
        $this->scanResult = ['status' => 'infected', 'details' => $this->parseInfectedOutput($output)];
        return false;
    }
    // ...
}
```

### 3. 設定ファイル

**ファイル**: `config/security.php`

```php
return [
    'clamav' => [
        'path' => env('CLAMAV_PATH', '/usr/bin/clamscan'),
        'timeout' => env('CLAMAV_TIMEOUT', 60),
        'max_file_size' => env('CLAMAV_MAX_FILE_SIZE', 0),
    ],
    'upload' => [
        'virus_scan_enabled' => env('SECURITY_VIRUS_SCAN_ENABLED', true),
        'scan_failure_mode' => env('SECURITY_SCAN_FAILURE_MODE', 'strict'),
    ],
];
```

**環境変数** (`.env`に追加):
```dotenv
CLAMAV_PATH=/usr/bin/clamscan
CLAMAV_TIMEOUT=60
CLAMAV_MAX_FILE_SIZE=0
SECURITY_VIRUS_SCAN_ENABLED=true
SECURITY_SCAN_FAILURE_MODE=strict
```

### 4. ファイルアップロード統合

#### Web版: `RequestApprovalAction.php`

**修正内容**:
- コンストラクタに`VirusScanServiceInterface`注入
- `uploadImages()`メソッドでアップロード前にスキャン
- ウイルス検出時は例外をスローしてロールバック
- スキャン結果をログに記録

**コード抜粋**:
```php
protected function uploadImages(RequestApprovalRequest $request, Task $task): void
{
    $scanEnabled = config('security.upload.virus_scan_enabled', true);
    
    foreach ($images as $image) {
        if ($scanEnabled && $this->virusScanService->isAvailable()) {
            $isClean = $this->virusScanService->scan($image);
            
            if (!$isClean) {
                Log::warning('Virus detected in uploaded file', [...]);
                throw new \Exception('アップロードされたファイルにウイルスが検出されました。');
            }
        }
        
        $path = Storage::disk('s3')->putFile('task_approvals', $image, 'public');
        // ...
    }
}
```

#### API版: `UploadTaskImageApiAction.php`

**修正内容**:
- 同様にウイルススキャンを統合
- 感染検出時は422ステータスコードでJSON返却
- モバイルアプリからのアップロードにも対応

### 5. テスト実装

**ファイル**: `tests/Feature/Security/VirusScanServiceTest.php`

**テストケース** (全6テスト):
1. ✅ `test_clamav_is_available`: ClamAVが利用可能
2. ✅ `test_clean_file_passes_scan`: クリーンファイルは通過
3. ✅ `test_eicar_test_file_detected`: **EICAR標準テストファイルを検出**
4. ✅ `test_nonexistent_file_returns_error`: 存在しないファイルはエラー
5. ✅ `test_scan_accepts_uploaded_file_and_path`: UploadedFileとパス両対応
6. ✅ `test_scan_result_contains_expected_fields`: 結果フィールド検証

**EICAR テストファイルとは**:
- European Institute for Computer Antivirus Research (EICAR) 標準テストファイル
- 実際のウイルスではなく、アンチウイルスソフトのテスト用文字列
- 全てのアンチウイルスソフトが検出するように設計された業界標準
- 参照: https://www.eicar.org/download-anti-malware-testfile/

**テスト実行結果**:
```
PASS  Tests\Feature\Security\VirusScanServiceTest
✓ clamav is available                        0.06s
✓ clean file passes scan                    10.13s
✓ eicar test file detected                  10.02s
✓ nonexistent file returns error             0.01s
✓ scan accepts uploaded file and path       20.28s
✓ scan result contains expected fields      10.96s

Tests:    6 passed (16 assertions)
Duration: 51.49s
```

### 6. DI登録

**ファイル**: `app/Providers/AppServiceProvider.php`

```php
// インポート追加
use App\Services\Security\VirusScanServiceInterface;
use App\Services\Security\ClamAVScanService;

// register()メソッド内
$this->app->bind(VirusScanServiceInterface::class, ClamAVScanService::class);
```

## 成果と効果

### 定量的効果

| 指標 | 結果 |
|------|------|
| 検出可能なウイルス種類 | 8,724,748種類 |
| テスト成功率 | 100% (6/6テスト合格) |
| スキャン速度 | 平均10秒/ファイル (テスト実測値) |
| 追加ディスク使用量 | 32.8 MB (ClamAV本体) + 163 MB (署名DB) |

### 定性的効果

1. **Stripeセキュリティ要件達成**
   - マルウェア対策が必須項目として実装完了
   - Stripe管理者画面登録時の審査に対応

2. **セキュリティリスク低減**
   - ウイルス感染ファイルのアップロードを防止
   - ユーザー間でのマルウェア伝播リスクを排除
   - 証跡ログによる監査可能性向上

3. **保守性・拡張性向上**
   - インターフェースベース設計で実装差し替え可能
   - 環境変数で柔軟に動作制御可能
   - 他のファイルアップロード機能にも容易に適用可能

4. **運用自動化**
   - `clamav-freshclam`サービスでウイルス定義を自動更新
   - 人手不要で最新の脅威に対応

## 技術詳細

### ClamAVスキャンフロー

```
[ファイルアップロード]
        ↓
[バリデーション]
        ↓
[VirusScanService::scan()]
        ↓
    [clamscanプロセス起動]
    --no-summary (概要非表示)
    --infected (感染ファイルのみ出力)
    <ファイルパス>
        ↓
    [終了コード判定]
    ├─ 0: クリーン → アップロード継続
    ├─ 1: 感染 → 例外スロー/422エラー
    └─ 2: エラー → エラー処理
        ↓
[結果ログ記録]
user_id, task_id, filename, scan_result
        ↓
[S3アップロード] (クリーンのみ)
```

### エラーハンドリング

**Web (RequestApprovalAction)**:
- ウイルス検出 → 例外スロー → DB::transaction()ロールバック
- ユーザーに「ウイルスが検出されました」エラーメッセージ表示

**API (UploadTaskImageApiAction)**:
- ウイルス検出 → 422 Unprocessable Entity
- JSON: `{"success": false, "message": "アップロードされたファイルにウイルスが検出されました。"}`

### スキャン除外設定

スキャンを無効化する場合（開発環境など）:
```dotenv
SECURITY_VIRUS_SCAN_ENABLED=false
```

### パフォーマンス最適化

**タイムアウト設定**:
```dotenv
CLAMAV_TIMEOUT=60  # 大きなファイル用に延長可能
```

**ファイルサイズ制限**:
```dotenv
CLAMAV_MAX_FILE_SIZE=10485760  # 10MB制限（今後実装予定）
```

## セキュリティ監査ログ

全スキャン結果は以下の形式でログに記録されます:

**クリーンファイル (INFO)**:
```json
{
  "message": "File passed virus scan",
  "context": {
    "user_id": 123,
    "task_id": 456,
    "filename": "example.jpg"
  }
}
```

**感染ファイル (WARNING)**:
```json
{
  "message": "Virus detected in uploaded file",
  "context": {
    "user_id": 123,
    "task_id": 456,
    "filename": "infected.exe",
    "scan_result": {
      "status": "infected",
      "details": "EICAR-Test-File"
    }
  }
}
```

## 未完了項目・次のステップ

### 今後の推奨事項

- [ ] **定期的な全システムスキャン**: 既存ファイルの定期スキャン（夜間バッチ）
  - Artisanコマンド: `php artisan scan:storage`
  - スケジュール: 毎日深夜2時に実行
  - 対象: `storage/app/task_approvals/`

- [ ] **アバター画像のウイルススキャン**: AI生成アバター画像にもスキャン適用
  - 対象Action: `GenerateAvatarImagesJob`
  - Stable Diffusion生成画像をスキャン

- [ ] **スキャン結果の集計ダッシュボード**: 管理者画面に統計情報表示
  - 日次スキャン数
  - ウイルス検出数
  - 平均スキャン時間

- [ ] **ClamAV署名DB更新監視**: freshclamの更新失敗を検知して通知
  - ログ監視: `/var/log/clamav/freshclam.log`
  - 失敗時にSlack/メール通知

- [ ] **ファイルサイズ制限実装**: `CLAMAV_MAX_FILE_SIZE`での制限処理
  - 大きすぎるファイルはスキャンスキップ
  - ユーザーに「ファイルが大きすぎます」エラー表示

## 検証方法

### EICARテストファイルによる動作確認

**手順**:

1. **EICARテストファイル作成**:
   ```bash
   echo 'X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*' > /tmp/eicar.txt
   ```

2. **手動スキャンテスト**:
   ```bash
   clamscan /tmp/eicar.txt
   # 出力: /tmp/eicar.txt: EICAR.Test.File-6 FOUND
   ```

3. **アプリケーションテスト**:
   ```bash
   php artisan test --filter test_eicar_test_file_detected
   # 期待結果: ✓ eicar test file detected
   ```

4. **Webアップロードテスト**:
   - ブラウザでタスク完了申請画面を開く
   - EICARファイルを画像としてアップロード
   - 期待結果: 「アップロードされたファイルにウイルスが検出されました。」エラー表示

### クリーンファイルの正常動作確認

```bash
# 通常のテキストファイルで確認
echo "Clean file" > /tmp/clean.txt
clamscan /tmp/clean.txt
# 出力: /tmp/clean.txt: OK
```

## 関連ドキュメント

- [Stripe管理者セキュリティ実装ドキュメント](./stripe-admin-security-implementation.md)
- [ClamAV公式ドキュメント](https://docs.clamav.net/)
- [EICAR標準テストファイル](https://www.eicar.org/download-anti-malware-testfile/)

## トラブルシューティング

### ClamAVデーモンが起動しない

**症状**:
```
ERROR: Can't connect to clamd
```

**対処**:
```bash
sudo systemctl status clamav-daemon
sudo systemctl start clamav-daemon
```

### スキャンが遅い

**対処**:
- タイムアウトを延長: `CLAMAV_TIMEOUT=120`
- ファイルサイズ制限を追加: `CLAMAV_MAX_FILE_SIZE=5242880` (5MB)

### ウイルス定義が古い

**確認**:
```bash
sudo freshclam --version
sudo systemctl status clamav-freshclam
```

**対処**:
```bash
sudo systemctl restart clamav-freshclam
```

## まとめ

本実装により、MyTeacherプラットフォームは**Stripeが要求するマルウェア対策要件**を満たし、ユーザーアップロードファイルの安全性を確保しました。ClamAVの8.7M+署名によるリアルタイムスキャンと詳細な監査ログにより、セキュリティインシデントの予防と追跡が可能になりました。

今後は定期スキャンとダッシュボード機能の追加により、さらに堅牢なセキュリティ体制を構築していきます。
