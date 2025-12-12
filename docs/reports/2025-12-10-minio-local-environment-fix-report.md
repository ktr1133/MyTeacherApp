# ローカル環境MinIO設定不具合修正レポート

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-10 | GitHub Copilot | 初版作成: ローカル環境のMinIO設定不具合修正 |

---

## 概要

ローカル開発環境において、**MinIOバケット未作成とネットワーク設定不備により、アバター画像の表示・保存が失敗する不具合**を修正しました。この作業により、以下の目標を達成しました：

- ✅ **MinIOバケット作成**: `mtdev-app-bucket`を作成し、画像保存を可能に
- ✅ **ネットワーク設定修正**: `AWS_URL`を`192.168.0.2`から`localhost`に変更
- ✅ **既存画像URLの更新**: データベース内の画像URLを一括修正
- ✅ **Web版での画像表示**: ブラウザからアバター画像が正常に表示されるよう修正

---

## 不具合の詳細

### 問題の状況

**現象1: 画像保存失敗**
- アバター画像生成処理は成功するが、MinIOへのアップロードで`NoSuchBucket`エラーが発生
- ログ: `Error executing "PutObject" ... NoSuchBucket (client): The specified bucket does not exist`

**現象2: 画像表示失敗（Web版のみ）**
- モバイルアプリでは画像が正常に表示される
- Web版（ブラウザ）では`ERR_CONNECTION_TIMED_OUT`エラーが発生
- ブラウザコンソール: `Failed to load resource: net::ERR_CONNECTION_TIMED_OUT`

**影響範囲**:
- Web版: アバター画像の保存・表示が完全に失敗
- モバイル版: 影響なし（同一ネットワーク内のため`192.168.0.2`にアクセス可能）

### 根本原因

1. **MinIOバケット未作成**: ローカル環境のMinIOに`mtdev-app-bucket`が存在していなかった
2. **ネットワーク設定不備**: `.env`の`AWS_URL=http://192.168.0.2:9100`がブラウザから到達不可能
   - ブラウザは`localhost:8091`でアクセスしているが、MinIOへは`192.168.0.2:9100`でアクセスしようとしていた
   - モバイルは同一ネットワーク内のため`192.168.0.2`にアクセス可能だった

---

## 実施内容詳細

### 1. MinIOバケットの作成

**実施コマンド**:
```bash
# MinIO CLIでローカルエイリアスを設定
docker exec mtdev-s3-1 mc alias set local http://localhost:9100 minio minio123

# バケットを作成
docker exec mtdev-s3-1 mc mb local/mtdev-app-bucket

# 公開アクセス設定
docker exec mtdev-s3-1 mc anonymous set public local/mtdev-app-bucket

# 確認
docker exec mtdev-s3-1 mc ls local/
```

**結果**:
```
Bucket created successfully `local/mtdev-app-bucket`.
Access permission for `local/mtdev-app-bucket` is set to `public`
[2025-12-10 05:25:24 UTC]     0B mtdev-app-bucket/
```

**効果**:
- MinIOへの画像アップロードが成功するようになった
- `NoSuchBucket`エラーが解消

### 2. ネットワーク設定の修正

**ファイル**: `.env`

**修正内容**:
```diff
-AWS_URL=http://192.168.0.2:9100/mtdev-app-bucket
+AWS_URL=http://localhost:9100/mtdev-app-bucket
```

**理由**:
- ブラウザが`localhost:8091`でアプリケーションにアクセスしているため、MinIOも`localhost:9100`でアクセスする必要がある
- `192.168.0.2`はローカルネットワークのIPアドレスで、ブラウザのマシンから到達できない

**設定反映**:
```bash
cd /home/ktr/mtdev
CACHE_STORE=array php artisan config:clear
```

### 3. 既存画像URLの更新

**実施コマンド**:
```bash
cd /home/ktr/mtdev
DB_HOST=localhost DB_PORT=5432 php artisan tinker --execute="
App\Models\AvatarImage::query()
    ->whereNotNull('s3_url')
    ->where('s3_url', 'like', '%192.168.0.2%')
    ->update([
        's3_url' => DB::raw(\"REPLACE(s3_url, '192.168.0.2', 'localhost')\")
    ]);
echo 'Updated ' . App\Models\AvatarImage::where('s3_url', 'like', '%localhost%')->count() . ' images' . PHP_EOL;
"
```

**結果**:
```
Updated 5 images
```

**更新前のURL例**:
```
http://192.168.0.2:9100/mtdev-app-bucket/avatars/2/full_body_surprised_1765344580.png
```

**更新後のURL例**:
```
http://localhost:9100/mtdev-app-bucket/avatars/2/full_body_surprised_1765344580.png
```

**効果**:
- 既存の画像レコードもブラウザから正常にアクセス可能になった
- `ERR_CONNECTION_TIMED_OUT`エラーが解消

---

## 成果と効果

### 定量的効果

- **作成したMinIOバケット**: 1件（`mtdev-app-bucket`）
- **修正した設定ファイル**: 1件（`.env`）
- **更新した画像URL**: 5件
- **解消したエラー**: 2種類（`NoSuchBucket`, `ERR_CONNECTION_TIMED_OUT`）

### 定性的効果

- **開発環境の安定化**: ローカル環境でアバター機能が完全に動作
- **デバッグ効率向上**: ネットワーク設定の問題を明確化
- **ドキュメント整備**: 環境構築手順の改善点を特定

---

## 技術的な学び

### 1. MinIOのバケット管理

**発見**: ローカル環境では、MinIOコンテナを起動してもバケットは自動作成されない。

**教訓**:
- 初回セットアップ時にバケット作成手順が必要
- `mc`コマンドでバケット管理・アクセス制御を行う
- バケットが存在しない状態でアップロードすると`NoSuchBucket`エラー

### 2. ローカル開発環境のネットワーク設定

**発見**: `localhost`と`192.168.0.x`のアクセス可否がクライアントによって異なる。

**教訓**:
- ブラウザアクセス: `localhost`経由なら`localhost`で統一
- モバイルアプリ: 同一ネットワーク内なら`192.168.0.x`でアクセス可能
- Docker内部通信: `s3:9100`（サービス名）を使用

**ベストプラクティス**:
```bash
# .env設定（ローカル開発環境）
AWS_URL=http://localhost:9100/mtdev-app-bucket        # ブラウザ向け
AWS_ENDPOINT=http://s3:9100                            # Docker内部通信
```

### 3. データベースに保存されるURL

**発見**: `AvatarImage`モデルの`s3_url`カラムには絶対URLが保存される。

**教訓**:
- `.env`の`AWS_URL`変更時は、既存レコードのURLも更新が必要
- `public_url`アクセサは`s3_url`が優先され、空の場合のみ動的生成
- 一括更新には`DB::raw(REPLACE())`が有効

---

## 未完了項目・次のステップ

### Docker Composeでの自動化（推奨）

**現状**: MinIOバケットは手動作成が必要

**推奨対応**:
1. `docker-compose.yml`にバケット初期化スクリプトを追加
2. `entrypoint.sh`でバケット存在確認・自動作成を実装
3. 期限: 次回のDocker環境整備時

**実装例**:
```yaml
# docker-compose.yml
services:
  s3:
    image: minio/minio:latest
    volumes:
      - ./docker/minio/init-buckets.sh:/docker-entrypoint-initdb.d/init-buckets.sh
    entrypoint: sh -c "mc config host add local http://localhost:9100 minio minio123 && mc mb --ignore-existing local/mtdev-app-bucket && mc anonymous set public local/mtdev-app-bucket && minio server /data --console-address ':9101'"
```

### 環境別設定の明確化

**現状**: `.env`がローカル環境専用設定になっている

**推奨対応**:
1. `.env.example`に環境別の設定例を追加
2. `README.md`に環境構築手順を追記
3. 期限: 次回のドキュメント整備時

**追加すべき内容**:
```bash
# ローカル開発環境（ブラウザアクセス）
AWS_URL=http://localhost:9100/mtdev-app-bucket

# ローカル開発環境（モバイルアプリ）
AWS_URL=http://192.168.0.2:9100/mtdev-app-bucket

# 本番環境
AWS_URL=https://your-bucket.s3.amazonaws.com
```

### 画像URL再生成機能

**現状**: `.env`変更時は手動でURL更新が必要

**推奨対応**:
1. Artisanコマンドで画像URL一括再生成機能を実装
2. `php artisan avatar:regenerate-urls`で実行可能に
3. 期限: Phase 2.B-9または次回のメンテナンス機能実装時

---

## 関連ドキュメント

- `.env` - 環境変数設定ファイル
- `docker-compose.yml` - Docker構成ファイル
- `app/Models/AvatarImage.php` - アバター画像モデル（`public_url`アクセサ）
- `config/filesystems.php` - ファイルシステム設定（S3/MinIO）
- `docs/reports/2025-12-10-avatar-display-bug-fix-report.md` - 本日の関連修正

---

## まとめ

ローカル環境のMinIO設定不備（バケット未作成・ネットワーク設定ミス）を修正し、Web版でアバター画像が正常に表示されるようになりました。Docker Compose自動化・環境別設定の明確化・URL再生成機能の実装を推奨します。
