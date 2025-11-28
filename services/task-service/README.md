# MyTeacher Task Service

タスク管理機能を提供するNode.js マイクロサービス

## 概要

- **フレームワーク**: Express.js
- **言語**: Node.js 18+
- **データベース**: PostgreSQL
- **キャッシュ**: Redis
- **認証**: JWT
- **ログ**: Winston

## 機能

- タスクCRUD操作
- ユーザー認証・認可
- タスク分解AI連携
- リアルタイム通知
- ヘルスチェック

## セットアップ

```bash
# 依存関係インストール
npm install

# 環境変数設定
cp .env.example .env

# 開発サーバー起動
npm run dev

# テスト実行
npm test
```

## API エンドポイント

- `GET /health` - ヘルスチェック
- `GET /api/tasks` - タスク一覧
- `POST /api/tasks` - タスク作成
- `PUT /api/tasks/:id` - タスク更新
- `DELETE /api/tasks/:id` - タスク削除

## 環境変数

```bash
NODE_ENV=development
PORT=3000
DB_HOST=localhost
DB_PORT=5432
DB_NAME=myteacher
DB_USER=postgres
DB_PASSWORD=password
REDIS_URL=redis://localhost:6379
JWT_SECRET=your-secret-key
API_BASE_URL=http://localhost:8080
```

## Docker

```bash
# イメージビルド
docker build -t myteacher-task-service .

# コンテナ実行
docker run -p 3000:3000 myteacher-task-service
```

## CI/CD

GitHub Actions でテスト・ビルド・デプロイを自動化:
- プッシュ時テスト実行
- ECR へイメージプッシュ
- ECS へのデプロイ（Blue/Green）