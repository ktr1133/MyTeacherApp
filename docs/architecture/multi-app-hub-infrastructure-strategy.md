# 個人開発者向け シンプル統合アーキテクチャ（要件確定版）

## 📁 ドキュメント構造と位置づけ

このドキュメントは **MyTeacherプロジェクトのマスタープラン** であり、全Phase（0.5～4）の概要を記載しています。

### ドキュメント階層

```
docs/
├── architecture/
│   ├── multi-app-hub-infrastructure-strategy.md  ← 本ドキュメント（マスタープラン）
│   └── phase-plans/                              ← 各Phase詳細計画
│       ├── phase1-mobile-api-plan.md             Phase 1詳細: API化計画
│       ├── phase1.5-mobile-app-plan.md           Phase 1.5詳細: モバイルアプリ開発
│       ├── phase2-portal-parentshare-plan.md     Phase 2詳細: Portal独立化
│       └── phase3-ai-sensei-plan.md              Phase 3詳細: AI-Sensei開発
├── plans/                                        ← 技術詳細計画
│   ├── api-design-guidelines.md                  API設計ガイドライン
│   ├── openapi-specification-plan.md             OpenAPI仕様書作成計画
│   ├── phase1-b-1-stripe-subscription-plan.md   Phase 1.B詳細: Stripeサブスクリプション
│   └── phase1-b-2-stripe-one-time-payment-plan.md Phase 1.B詳細: Stripe都度決済
└── reports/                                      ← 実装完了レポート
    └── YYYY-MM-DD-{phase}-completion-report.md   Phase完了レポート
```

### マスタープランの役割

- **Phase別の概要**: 各Phaseの目的、主要成果物、期間、コストを記載
- **アーキテクチャ全体像**: システム構成、データフロー、認証方式の全体設計
- **実装優先順位**: Phase間の依存関係、移行戦略
- **進捗管理**: 各Phaseの実装状況（✅完了、🔄進行中、📅計画）

### 詳細計画（phase-plans/）の役割

- **実装手順**: ステップバイステップの作業内容
- **技術詳細**: API仕様、データベース設計、コード例
- **タスク分解**: チェックリスト形式の細分化タスク
- **見積もり**: 工数、スケジュール、リスク

### 用語統一

| 用語 | 説明 | 使用例 |
|------|------|--------|
| **Phase 0.5** | AWS Fargate基盤構築 | マスタープラン、レポート |
| **Phase 1** | バックエンドAPI化 | phase1-mobile-api-plan.md |
| **Phase 2** | モバイルアプリ開発 | phase2-mobile-app-plan.md |
| **Phase 3** | Portal独立化 + ParentShare | phase3-portal-parentshare-plan.md |
| **Phase 4** | AI-Sensei開発 | phase4-ai-sensei-plan.md |
| **Phase 5** | SSO統合・機能拡張 | 将来作成 |

---

> **📌 Phase実装状況**
> - ✅ **Phase 0.5完了**: AWS Fargate構築（$164/月、~¥25,000）- ECS, RDS, ElastiCache, S3, CloudFront
> - 🔄 **Phase 1進行中**: バックエンドAPI化（14/60+ Actions完了、約23%）
>   - Phase 1.A: AuthHelper + helpers.php実装 ✅
>   - Phase 1.B: Stripe実装完了（都度決済 + サブスクリプション、本番確認済み）✅
>   - Phase 1.C: タスクAPI実装（14 Actions）✅
>   - Phase 1.D: タスク機能包括的テスト（93テスト、348アサーション、100%パス率）✅
>   - Phase 1.E: 残り46+ ActionsのAPI化 🔄 **← 現在地**
>   - Phase 1.F: OpenAPI仕様書作成 + Swagger UI導入 📅
> - 📅 **Phase 2**: モバイルアプリ開発（React Native/Flutter）
> - 📅 **Phase 3-5**: 将来計画（Portal独立化、ParentShare、AI-Sensei）
> 
> **現在のフォーカス**: Phase 1.E - 全機能のAPI化（グループ管理、アバター、レポート等）

## 📋 アーキテクチャ設計の考慮要件（修正版）

### 1. 個人開発者の制約条件
- **開発リソース**: 一人での開発・運用・保守
- **予算制約**: 資本金ゼロ、サラリーマン給料からの支払い
- **ユーザー基盤**: 現在ゼロ、初期投資リスク最小化が必須
- **時間制約**: 本業と並行、効率的な開発・運用が必要
- **実際のコスト**: 月額$164 (~¥25,000) ← Phase 0.5でAWS Fargate構築済み

### 2. 既存システムの実態

#### MyTeacher Web版（完全稼働中）
- **フレームワーク**: Laravel 12
- **アーキテクチャ**: Action-Service-Repositoryパターン（コントローラーレス）
- **主要機能**:
  - タスク管理（グループタスク、スケジュールタスク、承認フロー）
  - AI統合（OpenAI タスク分解、Stable Diffusion アバター生成）
  - トークンシステム（**Stripe実装済み**: 都度決済 + サブスクリプション）
  - アバターシステム（AI生成教師キャラクター、コンテキスト別コメント）
  - スケジュール機能（Cron実行、祝日対応、ランダム割当）
  - 実績・レポート機能（月次レポート、メンバーサマリーPDF）
  - 通知システム（お知らせ、承認待ち通知）

#### ポータルサイト（現在Laravel統合、将来独立予定）
- **現在の実装**: Laravel内に完全実装済み（`/portal/*` ルート）
- **将来の方針**: 独立したハブサイトとして分離
- **現在の機能**:
  - FAQ管理（`Faq`モデル、管理画面あり）
  - お知らせ管理（`AppUpdate`モデル、管理画面あり）
  - メンテナンス通知（`Maintenance`モデル、管理画面あり）
  - お問い合わせ（`ContactSubmission`モデル、管理画面あり）
  - 使い方ガイド（静的ページ）
- **追加予定機能**:
  - 開発ブログ（開発秘話・技術記事、公開）
- **画面**: `resources/views/portal/` + `resources/views/admin/portal/`
- **アクセス制御**: 
  - 基本は公開（未ログインユーザーアクセス可）
  - 一部コンテンツはログイン必須の可能性あり

#### インフラ構成
- **開発環境**: Docker Compose（PostgreSQL 16 + Redis + MinIO）
- **本番環境**: AWS Fargate (ECS) + RDS + ElastiCache + S3 + CloudFront
- **ファイルストレージ**: 
  - 開発: MinIO (ローカル)
  - 本番: S3 ✅ **運用中**（myteacher-storage-production）
  - 用途: タスク画像、アバター画像、月次レポートPDF
- **対応環境**: PCブラウザ、スマホブラウザ（レスポンシブ対応済み）

#### 外部API連携状況
- ✅ **OpenAI API**: 実装済み（タスク分解、DALL-E、月次レポートコメント生成）
- ✅ **Replicate API**: 実装済み（Stable Diffusion - アバター生成）
- ✅ **Stripe API**: 実装済み（都度決済 + サブスクリプション、Webhook処理、Billing Portal）
- 📅 **Firebase**: 未実装（Phase 1.5でプッシュ通知用に導入予定）

### 3. ユーザー要望・機能要件

#### MyTeacher拡張
- **ネイティブモバイルアプリ**: 親世代向けスマホアプリ
- **PWA不要**: 既存Webがスマホ対応済み、追加開発不要

#### ポータル機能（将来的に独立ハブサイト化）
- **現在**: MyTeacher Laravel内に統合実装
- **将来**: 独立したハブサイトとして分離
- **役割**:
  - 各アプリ（MyTeacher・ParentShare・AI-Sensei）へのリンク提供
  - FAQ・お知らせ・メンテナンス通知の横断管理
  - アプリ利用前の情報収集（ランディングページ機能）
  - 各アプリ利用中のヘルプ・サポート
  - 複数アプリの横断的な情報確認
- **アクセス**: 基本公開、一部ログイン必須コンテンツあり

#### 将来アプリ追加
- **別リポジトリ可**: リポジトリ肥大化によるPull失敗リスク回避
- **独立DB**: 各アプリ専用データベース構成
- **API連携**: MyTeacherと将来アプリ間のデータ連携

### 4. ポータルサイトの将来構想

#### 独立ハブサイトとしての役割
- **独立時期**: Phase 2以降（ParentShare開発時または前）- **未実装**
- **技術スタック**: Laravel新規アプリケーション（MyTeacherから分離）
- **ホスティング**: 独立デプロイ（別ドメイン想定）
- **データベース**: 独立DB（ポータル専用）
- **認証連携**: 
  - 基本機能は未ログインでアクセス可能
  - ログイン必須コンテンツは各アプリの認証情報を活用（SSO検討）

#### ポータルが提供する機能（Phase 2計画）
1. **ランディングページ**: サービス全体の紹介・各アプリへの誘導
2. **統合FAQ**: MyTeacher・ParentShare・AI-Sensei横断のヘルプ
3. **横断お知らせ**: 複数アプリに影響するメンテナンス・更新通知
4. **お問い合わせ**: サービス全体の問い合わせ窓口
5. **アプリリンク**: 各アプリへのナビゲーション
6. **使い方ガイド**: 各アプリの操作説明（横断・個別両対応）
7. **開発ブログ**: 開発者の開発秘話・技術記事・プロジェクト裏話（公開、誰でも閲覧可能）

#### 利用シーン
- ✅ アプリ利用前の情報収集（新規ユーザー獲得）
- ✅ 各アプリ利用中のヘルプアクセス（既存ユーザーサポート）
- ✅ 複数アプリの横断的な情報確認（統合管理）

### 5. 将来開発予定アプリ（Phase 2-3 計画、未実装）

#### アプリ① 子育て技術共有プラットフォーム（仮称: ParentShare）- **Phase 2**
- **目的**: 利用者同士で子育て技術を共有・交流し、スキル向上を促進
- **主要機能**:
  - 子育てノウハウ投稿・検索
  - 日々の行動としてのタスク化
  - MyTeacher使用例の公開・適用
- **MyTeacher連携**: 公開された使用例を自分のMyTeacherにワンクリック適用

#### アプリ② AI習い事プラットフォーム（仮称: AI-Sensei）- **Phase 3**
- **目的**: 習い事と独学の中間、AIが個別指導の代替となる
- **主要機能**:
  - AIによる目標設定・カリキュラム生成
  - 段階的指導コンテンツ提供
  - 進捗管理・フィードバック
- **MyTeacher連携**: AI提示の指導内容をタスク化してMyTeacherに登録

#### API連携要件
1. **ParentShare → MyTeacher**:
   - 子育てノウハウのタスクテンプレート転送
   - タスク構成データのJSON形式での受け渡し
   - グループタスク・スケジュールタスクの一括適用
   
2. **AI-Sensei → MyTeacher**:
   - AIが生成した学習計画のタスク化
   - 期限付きタスクの自動登録
   - 進捗状況のフィードバック連携

### 6. 技術的要件

#### コードベース保護
- **既存MyTeacher機能**: 変更なし、完全動作保証
- **Action-Service-Repositoryパターン**: 継続維持
- **ポータル機能**: 既存実装を活用

#### 段階的拡張戦略
- **Phase 1**: MyTeacherモバイルアプリ開発（Firebase統合）
- **Phase 2**: ポータルサイト独立化 + ParentShare開発
- **Phase 3**: AI-Sensei開発
- **Phase 4**: 必要に応じてSSO統合・決済機能追加

#### データベース設計
- **MyTeacher DB**: 既存PostgreSQL継続
- **Portal DB**: 独立PostgreSQLインスタンス（Phase 2で分離）
- **ParentShare DB**: 独立PostgreSQLインスタンス
- **AI-Sensei DB**: 独立PostgreSQLインスタンス
- **データ連携**: REST API経由、DB直接接続なし

#### 認証設計
- **MyTeacher Phase 1（✅ 実装済み）**: Cognito JWT認証（VerifyCognitoToken + AuthHelper）
- **Portal Phase 2（計画）**: 基本公開、ログイン必須コンテンツは各アプリ認証連携
- **将来アプリ（Phase 2-3）**: 各アプリ独自認証 + API連携用トークン
- **SSO検討**: 必要に応じて将来的に統合（Phase 4以降）

#### 外部API統合
- ✅ OpenAI API（継続）
- ✅ Replicate API（継続）
- 🔲 Stripe API（将来実装）
- 🔲 Firebase（モバイルアプリで実装予定）

### 7. 運用要件
- **管理負荷最小化**: 一人で管理可能な範囲
- **デバッグ容易性**: 統合ログ・監視、明確なエラーハンドリング
- **実際のコスト**: 月額$164 (~¥25,000) ← Phase 0.5でAWS Fargate構築済み
- **保守性**: 一人で理解・修正可能なコード構造
- **スケーラビリティ**: 段階的成長対応（過剰設計は避ける）

### 8. 設計制約（避けるべき事項）
- **❌ マイクロサービス化**: 複雑な分散システムは避ける（Phase 0.5でTask Service等削除完了）
- **❌ 複数AWSサービス（過剰）**: API Gateway・Cognito User Pool・EventBridge等の複雑構成回避
- **❌ PWA開発**: 既存Webで十分、追加開発不要
- **❌ オーバーエンジニアリング**: 年間売上1000万円前提の複雑設計は不要
- **✅ リポジトリ分割**: 必要に応じて分割可（phase1-initial-planning.mdの3案は採用せず）

### 9. リポジトリ戦略
- **MyTeacher**: 現在のリポジトリ継続（Phase 2までポータル統合）
- **Portal**: Phase 2で独立リポジトリ化
- **ParentShare**: 独立リポジトリ可（肥大化時）
- **AI-Sensei**: 独立リポジトリ可（肥大化時）
- **共通ライブラリ**: 必要に応じてComposerパッケージ化

### 10. 非機能要件
- **可用性**: 99%程度（個人開発レベル）
- **パフォーマンス**: 既存Web版と同等以上
- **セキュリティ**: HTTPS・認証・ファイルアクセス制御（既存レベル継続）
- **API レート制限**: アプリ間連携時の過負荷防止
- **データ整合性**: API連携時のトランザクション管理

---

## 🎯 要件確定後の設計方針

### アーキテクチャ基本方針
1. **MyTeacher**: Laravel統合モノリス継続（Phase 1まではポータル含む）
2. **Portal**: Phase 2で独立ハブサイト化（Laravel新規アプリ）- **未実装**
3. **将来アプリ**: 独立Laravelアプリケーション × 2（別リポジトリ可）- **Phase 2-3計画**
4. **連携方式**: REST API による疎結合
5. **データベース**: 各アプリ独立、直接接続なし
6. **認証**: Portal基本公開、各アプリは独自認証 + API連携用トークン

### 段階的実装優先順位
1. **Phase 0.5（✅ 完了）**: AWS Fargate構築（ECS, RDS, ElastiCache, S3, CloudFront）- $164/月
2. **Phase 1（🔄 進行中）**: バックエンドAPI化 + OpenAPI仕様書
   - Phase 1.A-D: ✅ 完了（Cognito JWT、タスクAPI 14 Actions、Stripe決済、テスト93個）
   - Phase 1.E: 🔄 進行中（残り46+ ActionsのAPI化）
   - Phase 1.F: 📅 計画（OpenAPI仕様書作成 + Swagger UI導入）
3. **Phase 2（📅 次期計画）**: モバイルアプリ開発（React Native/Flutter + Firebase）
4. **Phase 3（📅 中期計画）**: ポータル独立化 + ParentShare開発 + API連携
5. **Phase 4（📅 長期計画）**: AI-Sensei開発 + API連携
6. **Phase 5（📅 将来計画）**: SSO統合・決済機能拡張

### Phase 1実装完了サマリー

#### Phase 1 実装済みサブフェーズ（A-D）

**Phase 1.A: AuthHelper + helpers.php実装（2025-11-29完了）**
- Cognito JWT認証基盤構築
- `AuthHelper::getOrCreateCognitoUser()` 実装
- `VerifyCognitoToken` ミドルウェア実装
- 参照: Cognito認証設計書

**Phase 1.B: Stripe実装完了（都度決済 + サブスクリプション、2025-12-03完了）**

**計画書**: 
- Phase 1.B-1: `docs/plans/phase1-b-1-stripe-subscription-plan.md` (サブスクリプション実装計画)
- Phase 1.B-2: `docs/plans/phase1-b-2-stripe-one-time-payment-plan.md` (都度決済実装計画)

- **都度決済（Checkout Session）**: TokenPurchaseService、CreateTokenCheckoutSessionAction（7ファイル、782行）
  - Webhook処理: 統合WebhookハンドラーでCheckout完了時にトークン自動付与
  - 本番確認: 3パッケージ（100万/300万/500万トークン）購入成功確認済み
  - 参照: `docs/reports/2025-12-04-phase-1-2-status-and-next-steps.md`
- **サブスクリプション（Laravel Cashier + 管理画面統合）**: 2プラン実装 + 統合管理機能
  - 2プラン実装: Family（月額¥1,980）、Enterprise（月額¥9,800 + メンバー課金）
  - 統合管理画面: プラン選択・変更・キャンセル・請求履歴を1画面に統合（657行）
  - プラン変更・キャンセル時の確認モーダル実装（誤操作防止）
  - Stripe Billing Portal統合（支払い情報管理）
  - トライアル期間動的表示（残り日数自動計算）
  - 参照: `docs/reports/2025-12-01-phase1-1-5-subscription-management-completion-report.md`
- **テスト**: 21テスト実装（63%パス率 - 一部修正中）

**Phase 1.C: タスクAPI実装（2025-11-29完了）**
- 14 API Actions実装（Task CRUD, Approval, Image, Search, Pagination）
- routes/api.phpに/api/v1 prefix設定
- cognitoミドルウェア適用
- Use statements統一、コードクリーンアップ

**Phase 1.D: タスク機能包括的テスト実装（2025-11-29～2025-12-05完了）**

1. **Cognito認証テスト（2025-11-29）**
   - `CognitoAuthTest.php` - Cognito JWT認証（12テスト）
   - `TaskApiTest.php` - 13 API Actions統合テスト（15テスト）
   - `EmailValidationTest.php` - メールバリデーション（6テスト）
   - `AddMemberTest.php` - グループメンバー追加（9テスト）
   - `ProfileUpdateTest.php` - プロフィール更新（10テスト）
   - `AuthHelperTest.php` - AuthHelper機能（12テスト）
   - 参照: `docs/reports/2025-11-29-phase1-5-test-infrastructure-fix-report.md`

2. **タスク機能テスト（2025-12-05）**
   - StoreTaskTest - 通常タスク登録（19テスト）
   - TaskDecompositionTest - AI分解機能（20テスト、OpenAI Mock実装）
   - GroupTaskTest - グループタスク割当（16テスト、承認フロー検証）
   - DeleteTaskTest - タスク削除（12テスト、ソフトデリート実装）
   - UpdateTaskTest - タスク更新（22テスト、画像更新含む）
   - コード品質向上（ドキュメント追加、共通パターン統一）
   - **テストスイート**: 93テスト、348アサーション、100%パス率達成
   - 参照: `docs/reports/2025-12-05-task-feature-test-completion-report.md`

**テスト対象API Actions（14個）**:
1. StoreTaskApiAction - タスク作成
2. IndexTaskApiAction - タスク一覧取得
3. GetTasksPaginatedApiAction - タスク一覧取得（ページネーション付き）
4. UpdateTaskApiAction - タスク更新
5. DestroyTaskApiAction - タスク削除
6. ToggleTaskCompletionApiAction - 完了トグル
7. ApproveTaskApiAction - タスク承認
8. RejectTaskApiAction - タスク却下
9. UploadTaskImageApiAction - 画像アップロード
10. DeleteTaskImageApiAction - 画像削除
11. BulkCompleteTasksApiAction - 一括完了
12. RequestApprovalApiAction - 完了申請
13. ListPendingApprovalsApiAction - 承認待ち一覧
14. SearchTasksApiAction - タスク検索

**テストカバレッジ**:
- 認証フロー: Cognito JWT認証、ユーザー自動作成、重複処理
- CRUD操作: 作成・読取・更新・削除の全パターン
- 承認フロー: 承認・却下・完了申請
- 一括操作: 複数タスク同時完了
- 画像管理: アップロード・削除・S3連携
- 検索機能: タイトル・タグ検索、AND/OR演算
- バリデーション: 重複チェック、自己除外、権限制御
- エラーハンドリング: 認証エラー、権限エラー、データエラー

> **Phase 1 サブフェーズ A-D 完了（2025-12-05）**: 
> - **Cognito JWT認証**: 14 API Actions実装完了
> - **Stripe都度決済**: トークン購入機能実装完了（本番確認済み）
> - **包括的テスト**: 93テスト、348アサーション、100%パス率達成
> 
> 次はPhase 1.E（残り46+ ActionsのAPI化）に移行します。

### システム構成イメージ（Phase 2完了時）
```
┌─────────────────────────────────────────────┐
│         Portal Hub (独立Laravelアプリ)         │
│  - ランディングページ                            │
│  - 統合FAQ/お知らせ/メンテナンス通知              │
│  - 各アプリへのリンク                            │
│  - 基本公開（一部ログイン必須）                   │
└─────────────────────────────────────────────┘
              ↓ リンク/API連携
    ┌─────────┴─────────┬─────────────┐
    ↓                   ↓             ↓
┌─────────┐      ┌─────────┐    ┌─────────┐
│MyTeacher│      │ParentShare│    │AI-Sensei│
│独立DB   │←API→│独立DB    │←API→│独立DB   │
└─────────┘      └─────────┘    └─────────┘
```

この要件定義に基づき、次のステップでアーキテクチャ詳細を設計いたします。

---

## 🏗️ アーキテクチャ詳細設計

### システム全体構成図（Phase 2完了時）

```
┌─────────────────────────────────────────────────────────────┐
│                    インターネット                              │
└────────────────┬──────────────────────┬─────────────────────┘
                 │                      │
    ┌────────────▼──────────┐  ┌──────▼────────────┐
    │   Portal Hub (公開)    │  │  各アプリ(認証済み) │
    │   myteacher-hub.jp    │  │  myteacher.jp     │
    │   - ランディング       │  │  parentshare.jp   │
    │   - FAQ/お知らせ      │  │  ai-sensei.jp     │
    │   - 開発ブログ        │  └───────────────────┘
    │   - お問い合わせ      │
    └────────────┬──────────┘
                 │
    ┌────────────▼──────────────────────────────────────┐
    │              アプリケーション層                      │
    ├──────────────┬──────────────┬──────────────────────┤
    │ Portal       │ MyTeacher    │ Future Apps          │
    │ (Laravel独立)│ (Laravel既存)│ (Laravel独立 × 2)    │
    │              │              │                      │
    │ - CMS        │ - タスク管理 │ - ParentShare        │
    │ - FAQ        │ - AI統合     │ - AI-Sensei          │
    │ - ブログ     │ - トークン   │                      │
    │ - お問合せ   │ - アバター   │ API連携 ←───────────→│
    └──────┬───────┴──────┬───────┴──────────┬───────────┘
           │              │                  │
    ┌──────▼──────┐┌─────▼──────┐  ┌───────▼──────┐
    │ Portal DB   ││MyTeacher DB│  │Future Apps DB│
    │ PostgreSQL  ││PostgreSQL  │  │PostgreSQL × 2│
    └─────────────┘└────────────┘  └──────────────┘
                       │
              ┌────────┴────────┐
              │  S3/MinIO       │
              │  (ファイル保存)  │
              └─────────────────┘
```

### データフロー図

```
[ユーザー] → [Portal Hub]
              ↓
       各アプリへリンク
              ↓
    ┌─────────┴──────────┬────────────┐
    ↓                    ↓            ↓
[MyTeacher]        [ParentShare]  [AI-Sensei]
    │                    │            │
    │ API連携リクエスト   │            │
    ├────────────────────→            │
    │ タスクテンプレート  │            │
    │←────────────────────┤            │
    │                    │            │
    │ API連携リクエスト   │            │
    ├─────────────────────────────────→
    │ 学習計画データ      │            │
    │←─────────────────────────────────┤
```

---

## 📊 Phase別システム構成

### Phase 1: MyTeacherモバイルアプリ（Phase 0.5完了後 → バックエンド実装完了）

**構成**:
```
MyTeacher Web + モバイルアプリ
├── Laravel 12 (既存)
│   ├── Web UI (Blade)
│   ├── API (JSON + Cognito JWT) ✅ 実装完了
│   ├── Portal統合（/portal/*）
│   └── Stripe都度決済 ✅ 本番稼働中
├── React Native/Expo アプリ（予定）
├── PostgreSQL (Phase 0.5で構築済み: RDS db.t4g.micro)
├── Redis (Phase 0.5で構築済み: ElastiCache cache.t4g.micro)
├── S3 (Phase 0.5で構築済み: myteacher-storage-production)
└── Firebase (プッシュ通知 - 予定)
```

**開発内容**:
1. ✅ 既存Action → API Action拡張（JSON レスポンダー）- 14 API Actions完了
2. ✅ Cognito JWT認証対応（VerifyCognitoToken middleware + AuthHelper）
3. ✅ Stripe都度決済実装（Checkout Session + Webhook + 本番確認済み）
4. ✅ 包括的テスト実装（93テスト、348アサーション、100%パス率）
5. ⏳ React Nativeアプリ開発（未着手）
6. ⏳ Firebaseプッシュ通知統合（未着手）

**インフラ**:
- Phase 0.5で構築済み: AWS Fargate (ECS) + RDS + ElastiCache + S3 + CloudFront
- デプロイ: GitHub Actions → ECR → ECS Fargate（自動デプロイ）
- 月額: **$164 (~¥25,000)** ← Phase 0.5で既に稼働中

### Phase 2: ポータル独立化 + ParentShare（仮称）（6ヶ月後）

**構成**:
```
Portal Hub (新規独立)
├── Laravel 12 新規アプリ
├── Portal専用DB (PostgreSQL)
├── S3 (画像・添付ファイル)
└── 各アプリへのリンク・API連携

MyTeacher (既存継続)
├── Laravel 12 (既存)
├── MyTeacher DB (既存)
├── API連携エンドポイント追加
└── Portal認証連携

ParentShare (新規開発)（仮称）
├── Laravel 12 新規アプリ
├── ParentShare専用DB (PostgreSQL)
├── API連携エンドポイント
└── MyTeacher連携機能
```

**API連携例**:
```php
// ParentShare → MyTeacher タスクテンプレート転送
POST /api/v1/tasks/import-template
{
  "source_app": "parentshare",
  "template_id": "uuid",
  "user_id": 123,
  "task_data": {
    "title": "朝の準備ルーティン",
    "tasks": [...]
  }
}

// MyTeacher → ParentShare 利用状況フィードバック
POST /api/v1/usage-feedback
{
  "template_id": "uuid",
  "completion_rate": 85,
  "user_count": 10
}
```

**インフラ**:
- 各アプリ独立デプロイ
- 独立DB × 3（Portal, MyTeacher, ParentShare）
- 共通S3バケット（アプリ別prefix）
- 月額: ¥10,000-15,000

### Phase 3: AI-Sensei（仮称）追加（12ヶ月後）

**構成**:
```
4アプリ体制
├── Portal Hub (ハブサイト)
├── MyTeacher (タスク管理)
├── ParentShare（仮称） (子育て共有)
└── AI-Sensei（仮称） (AI習い事) ← 新規追加
    ├── Laravel 12 新規アプリ
    ├── AI-Sensei専用DB
    ├── OpenAI API統合
    └── MyTeacher連携API
```

**API連携例**:
```php
// AI-Sensei → MyTeacher 学習計画タスク化
POST /api/v1/tasks/import-curriculum
{
  "source_app": "ai-sensei",
  "curriculum_id": "uuid",
  "user_id": 123,
  "learning_plan": {
    "goal": "英会話マスター",
    "duration_weeks": 12,
    "tasks": [
      {
        "week": 1,
        "title": "基本フレーズ習得",
        "deadline": "2025-12-07"
      }
    ]
  }
}
```

**インフラ**:
- 独立DB × 4
- 月額: ¥15,000-20,000

---

## 🔐 認証・セキュリティ設計

### Phase 1: Cognito JWT認証（✅ 実装済み）

```php
// MyTeacher Laravel Cognito JWT
// Middleware: VerifyCognitoToken
// routes/api.php
Route::middleware('cognito')->group(function () {
    Route::post('/tasks', StoreTaskApiAction::class);
    // ... 他の13 API Actions
});

// AuthHelper: Cognito情報から自動ユーザー作成・取得
$user = AuthHelper::getOrCreateCognitoUser($cognitoSub, $email, $username);

// モバイルアプリ認証
Authorization: Bearer {cognito_jwt_token}
```

### Phase 2: アプリ間API認証

```php
// 各アプリにAPI認証トークン設定
config/services.php:
'myteacher' => [
    'api_url' => env('MYTEACHER_API_URL'),
    'api_token' => env('MYTEACHER_API_TOKEN'),
],

// API呼び出し
Http::withToken(config('services.myteacher.api_token'))
    ->post(config('services.myteacher.api_url') . '/api/v1/tasks/import-template', [...]);
```

### Phase 4: SSO統合（将来）

```php
// Laravel Socialite + 各アプリ統合
// または Auth0/Okta等の外部SSOサービス
```

---

## 💾 データベース設計

### 各アプリDB独立構成

**Portal DB** (PostgreSQL):
```sql
-- ポータル専用テーブル
portal_pages           -- ページ管理
portal_faqs            -- FAQ
portal_news            -- お知らせ
portal_blog_posts      -- 開発ブログ
portal_contacts        -- お問い合わせ
portal_maintenances    -- メンテナンス通知
```

**MyTeacher DB** (PostgreSQL - 既存):
```sql
-- 既存テーブル継続
users
tasks
teacher_avatars
token_transactions
scheduled_group_tasks
task_images
avatar_images
...
```

**ParentShare DB** (PostgreSQL):
```sql
-- ParentShare専用テーブル
ps_users               -- ParentShareユーザー
ps_posts               -- ノウハウ投稿
ps_templates           -- タスクテンプレート
ps_comments            -- コメント
ps_likes               -- いいね
ps_usage_stats         -- 利用統計
```

**AI-Sensei DB** (PostgreSQL):
```sql
-- AI-Sensei専用テーブル
as_users               -- AI-Senseiユーザー
as_curriculums         -- カリキュラム
as_learning_plans      -- 学習計画
as_progress            -- 進捗管理
as_ai_feedback         -- AIフィードバック
```

### データ連携戦略

```php
// API経由でのデータ転送（DB直接接続なし）
// 各アプリは独立、疎結合を維持
```

---

## 📁 ファイルストレージ設計

### S3バケット構成

```
myteacher-storage/
├── portal/
│   ├── blog/              -- ブログ画像
│   ├── news/              -- お知らせ画像
│   └── uploads/           -- お問い合わせ添付
├── myteacher/
│   ├── task_approvals/    -- タスク承認画像
│   ├── avatars/           -- アバター画像
│   └── task_images/       -- タスク画像
├── parentshare/
│   ├── posts/             -- 投稿画像
│   └── templates/         -- テンプレート画像
└── ai-sensei/
    ├── materials/         -- 学習教材
    └── feedback/          -- フィードバック添付
```

### Laravel設定

```php
// 各アプリ共通S3設定、prefixで分離
's3' => [
    'driver' => 's3',
    'bucket' => env('AWS_BUCKET'),
    'prefix' => env('APP_NAME'),  // 'portal', 'myteacher', 'parentshare'
    ...
]
```

---

## 🔄 API連携仕様

### API認証フロー

```
1. 各アプリは固有のAPI_TOKENを保持
2. アプリ間通信はBearer認証
3. IPホワイトリスト（本番環境）
4. レート制限（1000req/hour/app）
```

### 共通APIレスポンス形式

```json
{
  "success": true,
  "data": { ... },
  "message": "Operation successful",
  "meta": {
    "timestamp": "2025-11-29T10:00:00Z",
    "version": "v1"
  }
}
```

### エラーハンドリング

```json
{
  "success": false,
  "error": {
    "code": "INVALID_TEMPLATE",
    "message": "Template not found",
    "details": { ... }
  }
}
```

---

## 🚀 デプロイ戦略

### Phase 1: 単純デプロイ

```yaml
# GitHub Actions
name: Deploy MyTeacher
on:
  push:
    branches: [main]
jobs:
  deploy:
    - composer install
    - npm run build
    - php artisan migrate
    - Deploy to Heroku/Railway
```

### Phase 2: マルチアプリデプロイ

```yaml
# 各アプリ独立リポジトリ → 独立デプロイ
myteacher-app/     → myteacher.jp
portal-hub/        → myteacher-hub.jp
parentshare-app/   → parentshare.jp
```

### Phase 3: Infrastructure as Code

```hcl
# Terraform (将来必要に応じて)
resource "aws_db_instance" "portal" { ... }
resource "aws_db_instance" "myteacher" { ... }
resource "aws_db_instance" "parentshare" { ... }
```

---

## 💰 Phase別コスト見積

### Phase 1: MyTeacher + モバイル（✅ Phase 0.5インフラで稼働中）

| 項目 | 月額 |
|------|------|
| ECS Fargate (2-8 tasks Auto Scaling) | $45-65 |
| Application Load Balancer | $16-23 |
| RDS PostgreSQL (db.t4g.micro) | $15-20 |
| ElastiCache Redis (cache.t4g.micro) | $15-20 |
| S3 (画像ストレージ) | $3-5 |
| CloudFront (CDN) | $5-10 |
| その他（Route 53, CloudWatch等） | $5-10 |
| Firebase (無料枠予定) | ¥0 |
| Expo EAS (年間予定) | ¥250/月 |
| **合計** | **$164/月 (~¥25,000)** |

### Phase 2: Portal + ParentShare追加

| 項目 | 月額 |
|------|------|
| Heroku/Railway × 3 | ¥3,000 |
| PostgreSQL × 3 | ¥0 (含まれる) |
| S3 (50GB) | ¥1,500 |
| Firebase | ¥0-500 |
| **合計** | **¥4,500-5,000/月** |

### Phase 3: AI-Sensei追加

| 項目 | 月額 |
|------|------|
| Heroku/Railway × 4 | ¥4,000 |
| PostgreSQL × 4 | ¥0 (含まれる) |
| S3 (100GB) | ¥3,000 |
| OpenAI API | ¥5,000-10,000 |
| Firebase | ¥1,000 |
| **合計** | **¥13,000-18,000/月** |

**重要**: 上記は最小構成、トラフィック増加で段階的にスケールアップ

---

## 📋 実装チェックリスト

### Phase 1 完了条件

- [x] MyTeacher API Action実装（全既存機能）← 13 API Actions完了
- [x] Cognito JWT認証実装（VerifyCognitoToken + AuthHelper）
- [x] API Routes設定（/v1 prefix, cognito middleware）
- [x] テストコード作成（34テストメソッド: AuthHelper, CognitoAuth, TaskApi）
- [ ] React Nativeアプリ開発（iOS/Android）
- [ ] Firebaseプッシュ通知統合
- [ ] App Store/Google Play申請・公開
- [x] 既存Web機能継続動作確認（AWS Fargate稼働中）
- [x] ポータル機能（Laravel統合）動作確認

### Phase 2 完了条件

- [ ] Portal独立Laravel新規アプリ作成
- [ ] Portal DBマイグレーション
- [ ] FAQ/お知らせ/ブログ管理画面実装
- [ ] 開発ブログ機能実装
- [ ] ParentShare Laravel新規アプリ作成
- [ ] ParentShare DBマイグレーション
- [ ] MyTeacher ⇔ ParentShare API連携実装
- [ ] 各アプリ独立デプロイ確認

### Phase 3 完了条件

- [ ] AI-Sensei Laravel新規アプリ作成
- [ ] AI-Sensei DBマイグレーション
- [ ] OpenAI API統合
- [ ] MyTeacher ⇔ AI-Sensei API連携実装
- [ ] カリキュラム生成機能実装
- [ ] 進捗管理機能実装

---

## 🎯 成功の鍵

### 1. 段階的実装の徹底
- Phase 1完了後、必ず運用評価期間を設ける
- ユーザーフィードバック収集→改善→次Phase
- 焦らず、確実に

### 2. シンプルさの維持
- 「これ本当に必要？」を常に問う
- 複雑な機能は後回し
- MVPを素早くリリース

### 3. コスト意識
- 月額2万円を超える前に収益化戦略
- 無料枠を最大活用
- 従量課金の監視アラート設定

### 4. 個人開発の強み活用
- 意思決定が早い
- ユーザーの声に即座に対応
- 技術選択の自由度

---

この設計により、**個人開発者が無理なく運用できる**、**段階的に成長できる**、**コストを抑えた**統合アーキテクチャを実現します。
    
### AWS Fargateベースのアーキテクチャ

#### 現在の構成 (Phase 0.5完了時点)

**本番環境リソース**:
- **ECS Fargate**: myteacher-production-cluster (CPU 512, Memory 1024, 2-8タスク Auto Scaling)
- **RDS PostgreSQL**: db.t4g.micro (myteacher-production-db)
- **ElastiCache Redis**: cache.t4g.micro (myteacher-production-redis)
- **S3**: myteacher-storage-production (画像), myteacher-portal-site (静的ポータル)
- **CloudFront**: HTTPS CDN配信 (my-teacher-app.com)
- **Route 53**: DNS管理
- **ALB**: Application Load Balancer
- **ECR**: Container Registry
- **EFS**: ファイルシステム (ログ等)

**月額コスト**: 約$164/月 (~¥25,000)

**デプロイ方法**: GitHub Actions → ECR → ECS Fargate (自動デプロイ、複数回/日)

#### マイクロサービス関連リソース (削除対象)

**Task Service** (Node.js):
- **ECS Fargate**: 別クラスターで稼働
- **RDS**: db.t3.micro (task-service-db)
- **GitHub Actions**: CI/CD構築済み
- **実装状況**: 75%完成、37ファイル

**AI Service** (Lambda):
- **AWS SAM**: Lambda実装予定
- **実装状況**: 60%完成、22ファイル

**Portal用インフラ** (削除対象):
- **Cognito**: User Pool + Identity Pool
- **API Gateway**: REST API
- **DynamoDB**: 4テーブル (app_updates, contacts, faqs, maintenances)

**削除効果**:
- **コスト削減**: 約$30-50/月削減見込み
- **管理負荷軽減**: ECS別クラスター、RDS追加インスタンス、Cognito、API Gateway、DynamoDB削除
- **ファイル削除**: 76ファイル (4人日相当)

---

## 🏗️ アーキテクチャ詳細設計 (AWS Fargate継続版)

### Phase 1完了時点の構成図

```
┌─────────────────────────────────────────────────────────────┐
│                      インターネット                            │
└────────────────┬──────────────────────┬─────────────────────┘
                 │                      │
    ┌────────────▼──────────┐  ┌──────▼────────────┐
    │   Route 53 DNS        │  │  CloudFront CDN   │
    │  my-teacher-app.com   │  │  (HTTPS配信)      │
    └───────────┬───────────┘  └──────┬────────────┘
                │                     │
                └────────┬────────────┘
                         │
                ┌────────▼───────────┐
                │  ALB (HTTPS)       │
                │  セキュリティ検証   │
                └────────┬───────────┘
                         │
            ┌────────────▼────────────┐
            │  ECS Fargate Cluster    │
            │  ┌──────────────────┐   │
            │  │ MyTeacher App    │   │
            │  │ Laravel 12       │   │
            │  │ (2-8 tasks)      │   │
            │  │ + Mobile API     │   │
            │  └──────────────────┘   │
            └─────────┬───┬────────────┘
                      │   │
        ┌─────────────┼───┼─────────────┐
        │             │   │             │
┌───────▼─────┐  ┌───▼───▼───┐  ┌──────▼──────┐
│ RDS PostgreSQL│ │ElastiCache│  │  S3 Buckets │
│ db.t4g.micro │  │   Redis   │  │ - storage   │
│ (MyTeacher)  │  │cache.t4g.micro│ │ - portal  │
└──────────────┘  └───────────┘  └─────────────┘
```

### Phase 2完了時点の構成図 (Portal独立化 + ParentShare追加)

```
┌──────────────────────────────────────────────────────────────┐
│                      インターネット                             │
└────────────┬─────────────────┬───────────────────────────────┘
             │                 │
    ┌────────▼────────┐  ┌────▼───────────┐
    │  Route 53 DNS   │  │ CloudFront CDN │
    │  - my-teacher... │  │  (共通HTTPS)   │
    │  - portal...    │  │                │
    │  - parentshare..│  │                │
    └────────┬────────┘  └────┬───────────┘
             │                │
             └───────┬────────┘
                     │
            ┌────────▼─────────┐
            │  ALB (共通)      │
            │  パスベースルーティング │
            └────────┬─────────┘
                     │
        ┌────────────┼────────────┐
        │            │            │
┌───────▼────┐  ┌───▼───┐  ┌────▼────────┐
│ECS Fargate │  │ECS Far│  │ECS Fargate  │
│MyTeacher   │  │Portal │  │ParentShare  │
│(既存)      │  │(新規) │  │(新規)       │
└───┬────────┘  └───┬───┘  └────┬────────┘
    │               │            │
┌───▼──────┐  ┌────▼───┐  ┌─────▼───────┐
│RDS (既存)│  │RDS (新)│  │RDS (新)     │
│MyTeacher │  │Portal  │  │ParentShare  │
└──────────┘  └────────┘  └─────────────┘
    │               │            │
    └───────────────┴────────────┴─────────┐
                    │                       │
            ┌───────▼────────┐      ┌──────▼──────┐
            │ElastiCache Redis│      │ S3 (共通)  │
            │  (共通セッション) │      │ - storage  │
            └────────────────┘      │ - portal   │
                                     │ - parent...│
                                     └────────────┘
```

### 設計方針

1. **マイクロサービス削除**:
   - Task Service (ECS Fargate別クラスター) → 削除
   - Task Service DB (RDS db.t3.micro) → 削除
   - Cognito/API Gateway/DynamoDB → 削除
   - MyTeacher LaravelにタスクAPI統合 (既存コード活用)

2. **Phase 1: MyTeacher Mobile対応**:
   - 既存ECS Fargateで継続稼働
   - Laravel Sanctum APIをモバイルアプリから呼び出し
   - React Native/Flutter等でモバイルアプリ開発
   - Firebase Cloud Messagingでプッシュ通知

3. **Phase 2: Portal独立化 + ParentShare追加**:
   - Portal: 新規ECS Fargate task + 新規RDS
   - ParentShare: 新規ECS Fargate task + 新規RDS
   - ALBでパスベースルーティング (`/portal/*`, `/parentshare/*`)
   - 各アプリ独立DB、API連携

4. **Phase 3: AI-Sensei追加**:
   - AI-Sensei: 新規ECS Fargate task + 新規RDS
   - Phase 2と同様のパターンで追加

---

## 📂 実装構造 (AWS Fargateベース)

### Phase 1: MyTeacher Mobile API追加

**Laravel既存コード拡張**:
```
laravel/app/Http/Actions/
├── Task/                        # 既存Web版Action継続
│   ├── StoreTaskAction.php
│   └── ApproveTaskAction.php
└── Api/                         # 新規モバイルAPI
    └── Task/
        ├── StoreTaskApiAction.php  # 既存ServiceをそのままDI
        └── ApproveTaskApiAction.php

laravel/routes/api.php           # モバイル用APIルート追加
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/tasks', \App\Http\Actions\Api\Task\StoreTaskApiAction::class);
});
```

**モバイルアプリ (React Native/Flutter)**:
- GitHub独立リポジトリ: `myteacher-mobile`
- Expo/React Native or Flutter
- Laravel SanctumトークンでAPI認証
- Firebase Cloud Messaging (プッシュ通知)

**インフラ変更**: なし (既存ECS Fargateで対応)

### Phase 2: Portal独立化 + ParentShare追加

**新規Laravelアプリ (Portal)**:
- GitHub独立リポジトリ: `portal-hub`
- Laravel 12新規プロジェクト
- 既存 `resources/views/portal/*` を移行
- 独立RDS PostgreSQL (db.t4g.micro)
- 独立ECS Fargate task

**新規Laravelアプリ (ParentShare)**:
- GitHub独立リポジトリ: `parentshare`
- Laravel 12新規プロジェクト
- 子育てノウハウ投稿・検索機能
- 独立RDS PostgreSQL (db.t4g.micro)
- 独立ECS Fargate task

**API連携**:
```php
// ParentShare → MyTeacher (ノウハウをタスク化)
POST https://my-teacher-app.com/api/external/import-task
Authorization: Bearer {API_TOKEN}
Body: {
  "template_id": "parent-123",
  "task_data": {...}
}

// MyTeacher → ParentShare (使用例を公開)
POST https://parentshare-app.com/api/external/publish-template
Authorization: Bearer {API_TOKEN}
Body: {
  "user_id": "456",
  "task_group_id": "uuid-xxx",
  "visibility": "public"
}
```

**ALB設定 (パスベースルーティング)**:
```hcl
# Terraform: alb.tf
resource "aws_lb_listener_rule" "portal" {
  listener_arn = aws_lb_listener.https.arn

  action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.portal.arn
  }

  condition {
    path_pattern {
      values = ["/portal/*"]
    }
  }
}

resource "aws_lb_listener_rule" "parentshare" {
  listener_arn = aws_lb_listener.https.arn

  action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.parentshare.arn
  }

  condition {
    path_pattern {
      values = ["/parentshare/*"]
    }
  }
}
```

---

## 🚀 段階的実装ロードマップ

### Phase 0.6: マイクロサービス削除 (1-2週間)

**作業内容**:
1. Task Service停止・削除 (ECS, RDS db.t3.micro)
2. Cognito/API Gateway/DynamoDB削除
3. MyTeacher Laravel内にタスクAPI統合 (既存Service活用)
4. Terraform状態更新

**コスト削減**: 約$30-50/月

**削除ファイル**: 76ファイル

### Phase 1: MyTeacher Mobile対応 (2-3ヶ月)

**作業内容**:
1. **Cognito JWT認証実装** ⚠️
   - ヘルパー関数作成 (`app/Helpers/AuthHelper.php`)
   - ミドルウェア拡張 (`VerifyCognitoToken`)
   - 既存コードの `$request->user()` 互換性確保
   - Cognitoユーザー ⇔ Laravelユーザー自動マッピング
2. **Laravel API Action実装**
   - Cognito認証対応のAPI Action作成
   - グループタスク対応
   - Responder使用（JSON レスポンス整形）
   - エンドポイント: `/v1/tasks`, `/v1/tasks/{id}`
3. React Native/Flutterアプリ開発
4. Firebase Cloud Messaging統合
5. App Store/Google Play申請

**Cognito実装注意事項** ⚠️:

1. **ヘルパー関数必須**:
   - `AuthHelper::getOrCreateCognitoUser()` 実装
   - 初回ログイン時の自動ユーザー作成
   - `$request->user()` の動作保証

2. **既存コード影響範囲**:
   - `$request->user()` 使用箇所: 全API Actionで動作確認
   - ミドルウェアの `setUserResolver()` で自動解決
   - ヘルパー関数化により既存コードの変更最小化

3. **データベース確認**:
   - `users.cognito_sub` カラム存在確認 ✅（既存マイグレーション）
   - `users.auth_provider` ENUM('breeze', 'cognito') ✅

4. **ログ・監視**:
   - Cognitoユーザー作成ログ記録
   - JWT検証失敗ログ記録
   - 認証プロバイダー判定ログ

**API Action作成時の注意事項** ⚠️（重要）:

1. **既存Web版Actionの全量確認必須**:
   - `find app/Http/Actions/{ドメイン} -name "*.php"` で全Actionを列挙
   - 各Actionの `__invoke` メソッドシグネチャを確認
   - `routes/web.php` で実際に使用されているActionを特定

2. **モバイルAPI必要性の体系的判断**:
   - **必須CRUD**: Store, Index, Update, Destroy
   - **必須状態変更**: ToggleCompletion（完了/未完了）
   - **必須承認フロー**: Approve, Reject（グループタスク対応）
   - **必須画像機能**: UploadImage, DeleteImage（証拠画像対応）
   - **任意機能**: 検索、AI提案、一括操作（Phase 2以降検討）
   - **Web専用**: フォーム表示Action（Create, Edit画面）

3. **API Action作成チェックリスト**:
   - [ ] 既存Web版Actionを参照（ビジネスロジックの理解）
   - [ ] Service/Repositoryの再利用（DI）
   - [ ] Cognito認証対応（`$request->user()`）
   - [ ] 所有権チェック（`task->user_id === $user->id`）
   - [ ] JSON レスポンス形式統一（`success`, `message`, `data`）
   - [ ] エラーハンドリング（try-catch + ログ記録）
   - [ ] routes/api.php にルート追加（use文も忘れずに）

4. **見落とし防止のための確認方法**:
   ```bash
   # 全Task Actionを列挙
   find app/Http/Actions/Task -name "*.php" -type f | sort
   
   # 各Actionのメソッドシグネチャ確認
   grep -A 3 "public function __invoke" app/Http/Actions/Task/*.php
   
   # routes/web.php で実際に使用されているAction確認
   grep "Action::class" routes/web.php | grep Task
   ```

5. **既存Action → API Action変換パターン**:
   - Web版が `RedirectResponse` → API版は `JsonResponse`
   - Web版が `view()` → API版は `response()->json()`
   - Web版が `withErrors()` → API版は `['success' => false, 'message' => ...]`
   - Web版が `session()->flash()` → API版はレスポンスに含める
   - Serviceロジックは**そのまま再利用**（重複実装禁止）

**参照ドキュメント**:
- `docs/operations/cognito-user-mapping-design.md` - Cognitoマッピング設計書

**追加コスト**:
- Firebase: $0/月 (無料枠)
- Apple Developer: $99/年
- Google Play: $25 (初回のみ)

**月額換算**: 約$10/月 (アプリストア費用のみ)

### Phase 2: Portal独立化 + ParentShare (3-4ヶ月)

**作業内容**:
1. Portal独立Laravelアプリ作成 (新規ECS + RDS)
2. ParentShare Laravelアプリ作成 (新規ECS + RDS)
3. ALBパスベースルーティング設定
4. API連携実装 (MyTeacher ⇔ ParentShare)
5. 既存 `resources/views/portal/*` 移行

**追加コスト**:
- ECS Fargate × 2 (Portal + ParentShare): $71/月 × 2 = $142/月
- RDS db.t4g.micro × 2: $13/月 × 2 = $26/月
- **Phase 2追加**: 約$168/月

**Phase 2完了時点の総コスト**: $164 (Phase 0.5) - $40 (マイクロサービス削除) + $168 (Phase 2追加) = **$292/月** (~¥45,000)

### Phase 3: AI-Sensei追加 (4-5ヶ月)

**作業内容**:
1. AI-Sensei Laravelアプリ作成 (新規ECS + RDS)
2. OpenAI API統合 (目標設定・カリキュラム生成)
3. API連携実装 (AI-Sensei → MyTeacher)

**追加コスト**:
- ECS Fargate: $71/月
- RDS db.t4g.micro: $13/月
- **Phase 3追加**: 約$84/月

**Phase 3完了時点の総コスト**: $292 (Phase 2) + $84 (Phase 3) = **$376/月** (~¥58,000)

---

## 💰 コスト分析

### Phase別コスト推移

| Phase | 月額コスト (USD) | 月額コスト (JPY) | 主な追加項目 |
|-------|-----------------|-----------------|------------|
| **Phase 0.5 (現在)** | $164 | ~¥25,000 | MyTeacher本番環境 |
| **Phase 0.6 (削除)** | $124 | ~¥19,000 | マイクロサービス削除 (-$40) |
| **Phase 1 (Mobile)** | $134 | ~¥20,500 | モバイルアプリ (+$10) |
| **Phase 2 (Portal+ParentShare)** | $292 | ~¥45,000 | Portal+ParentShare ECS+RDS (+$158) |
| **Phase 3 (AI-Sensei)** | $376 | ~¥58,000 | AI-Sensei ECS+RDS (+$84) |

### コスト削減策

**Phase 2/3でのコスト最適化**:
1. **ECS Fargateスポットインスタンス**: 最大70%削減 (開発環境)
2. **RDS Reserved Instances**: 1年契約で30%削減
3. **CloudFront PriceClass変更**: アジア限定でコスト削減
4. **Auto Scaling**: 低負荷時は1タスクに削減

**最適化後の予想コスト (Phase 3)**:
- ECS Fargate × 4アプリ: $284/月 → $170/月 (RI + Spot)
- RDS × 4インスタンス: $52/月 → $36/月 (RI)
- **最適化後**: 約**$280/月** (~¥43,000)

---

## 🎯 推奨アクション

### 即座に実施 (Phase 0.6: マイクロサービス削除)

1. **Task Service停止**:
   ```bash
   aws ecs update-service --cluster task-service-cluster --service task-service --desired-count 0
   ```

2. **Terraform destroy**:
   ```bash
   cd infrastructure/terraform
   terraform destroy -target=module.task_service
   terraform destroy -target=aws_db_instance.task_service_db
   terraform destroy -target=aws_cognito_user_pool.main
   terraform destroy -target=aws_apigatewayv2_api.main
   terraform destroy -target=aws_dynamodb_table.portal_*
   ```

3. **ファイル削除**:
   ```bash
   rm -rf services/task-service/
   rm -rf services/ai-service/
   rm -rf laravel/app/Http/Actions/Api/Task/  # 古いマイクロサービス用
   ```

4. **Laravel統合**:
   - `laravel/app/Http/Actions/Api/Task/StoreTaskApiAction.php` 作成
   - 既存 `TaskManagementService` をDI

### 3ヶ月以内 (Phase 1: Mobile)

1. **Sanctum API拡張**:
   ```bash
   cd laravel
   php artisan make:action Api/Task/StoreTaskApiAction
   ```

2. **React Nativeアプリ開始**:
   ```bash
   npx create-expo-app myteacher-mobile
   ```

3. **Firebase統合**:
   ```bash
   npm install @react-native-firebase/app @react-native-firebase/messaging
   ```

この戦略により、**既存AWS Fargate基盤を最大活用**しながら、**マイクロサービスの複雑性を排除**し、**個人開発者が管理可能な範囲**でマルチアプリ展開を実現できます。