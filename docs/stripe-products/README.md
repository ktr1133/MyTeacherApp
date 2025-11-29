# Stripe商品登録用アセット

## 概要

MyTeacherアプリのサブスクリプションプラン用の商品画像と説明文です。Stripe Dashboardでの商品登録時に使用してください。

## 商品一覧

### 1. ファミリープラン（Family Plan）

**価格**: ¥500/月（税込）

**商品画像**: 
- SVG: `family-plan-product-image-ja.svg` （PNG変換元）
- PNG: `family-plan-product-image.png` （Stripe登録用、要変換）
- サイズ: 1200×630px（Stripe推奨サイズ）
- 容量: 2MB未満

**商品名**: 
```
MyTeacher ファミリープラン
```

**説明文（日本語）**:
```
MyTeacherファミリープランで、家族のタスク管理をもっと便利に。

✅ 家族メンバー最大6名まで（管理者1名+編集権限者1名+子供4名）
✅ グループタスク割当機能
✅ 子供アカウント管理
✅ 進捗状況の統合確認
✅ グループトークン共有
✅ AI教師キャラクター機能
✅ 14日間無料トライアル

家族でのタスク管理に最適。子どもたちの学習習慣づくりをAI教師がサポートします。月額わずか500円で家族全員が使えるお得なプランです。
```

**説明文（英語）**:
```
Manage your family tasks more efficiently with MyTeacher Family Plan.

✅ Up to 6 family members (1 admin + 1 editor + 4 children)
✅ Group task assignment
✅ Children account management
✅ Integrated progress tracking
✅ Shared group tokens
✅ AI teacher character feature
✅ 14-day free trial

Perfect for family task management. AI teacher supports children's learning habits. Only ¥500/month for the whole family.
```

**Statement Descriptor** (カード明細表示名):
```
MYTEACHER FAMILY
```

---

### 2. エンタープライズプラン（Enterprise Plan）

**価格**: ¥3,000/月〜（税込）

**商品画像**: 
- SVG: `enterprise-plan-product-image-ja.svg` （PNG変換元）
- PNG: `enterprise-plan-product-image.png` （Stripe登録用、要変換）
- サイズ: 1200×630px（Stripe推奨サイズ）
- 容量: 2MB未満

**商品名**: 
```
MyTeacher エンタープライズプラン
```

**説明文（日本語）**:
```
MyTeacherエンタープライズプランで、教育機関・団体のタスク管理を実現。

✅ 基本20名まで（追加1名あたり¥150/月）
✅ グループタスク無制限
✅ 複数グループ作成可能
✅ AI教師キャラクター機能
✅ タスク分解・報酬管理
✅ 進捗レポート機能
✅ 優先サポート
✅ スケーラブルな価格設定

塾、学校、教育団体に最適。生徒数に応じて柔軟にスケールできる料金体系で、大規模なグループ学習をサポートします。
```

**説明文（英語）**:
```
Realize task management for educational institutions with MyTeacher Enterprise Plan.

✅ Up to 20 members (additional members ¥150/month each)
✅ Unlimited group tasks
✅ Multiple group creation
✅ AI teacher character feature
✅ Task decomposition & reward management
✅ Progress reporting
✅ Priority support
✅ Scalable pricing

Perfect for cram schools, schools, and educational organizations. Flexible pricing scales with your student count to support large-scale group learning.
```

**Statement Descriptor** (カード明細表示名):
```
MYTEACHER ENTERPRISE
```

---

## 無料プラン（Free Plan）の説明

Stripe登録不要（アプリ内説明用）

**商品名**: 
```
MyTeacher 無料プラン
```

**説明文**:
```
MyTeacherを無料で始められるプランです。

✅ グループメンバー最大6名まで
✅ グループタスク月3回まで無料
✅ AI教師キャラクター機能（基本）
✅ タスク分解・報酬管理
✅ 初月のみ月次実績レポート利用可能
✅ 画像アップロード機能

まずは無料で始めて、MyTeacherの便利さを体験してください。必要に応じてファミリー・エンタープライズプランにアップグレードできます。
```

---

## PNG画像の作成方法

**重要**: StripeはJPEG/PNG形式の画像のみ対応。SVGファイルをPNGに変換する必要があります。

### 方法1: オンラインツール（最も簡単・推奨）

1. [CloudConvert](https://cloudconvert.com/svg-to-png) にアクセス
2. `family-plan-product-image-ja.svg` をアップロード
3. 「Convert」をクリックしてダウンロード
4. ファイル名を `family-plan-product-image.png` に変更
5. `enterprise-plan-product-image-ja.svg` も同様に変換
6. ファイル名を `enterprise-plan-product-image.png` に変更

**他のオンラインツール**:
- [Convertio](https://convertio.co/ja/svg-png/)
- [SVGtoPNG.com](https://svgtopng.com/)

### 方法2: Webブラウザ（Chrome/Firefox）

1. SVGファイルをブラウザで開く
2. 右クリック → 「ページのソースを表示」
3. F12で開発者ツールを開く → Console
4. 以下のコードを貼り付けて実行:

```javascript
fetch(location.href)
  .then(r => r.text())
  .then(svg => {
    const canvas = document.createElement('canvas');
    canvas.width = 1200;
    canvas.height = 630;
    const ctx = canvas.getContext('2d');
    const img = new Image();
    img.onload = () => {
      ctx.drawImage(img, 0, 0);
      canvas.toBlob(blob => {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'output.png';
        a.click();
      });
    };
    img.src = 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(svg)));
  });
```

### 方法3: コマンドラインツール

#### 3-1. Inkscape（推奨）

```bash
# Inkscapeインストール
sudo apt install inkscape

# 変換
inkscape family-plan-product-image-ja.svg \
  --export-type=png \
  --export-filename=family-plan-product-image.png \
  --export-width=1200 \
  --export-height=630

inkscape enterprise-plan-product-image-ja.svg \
  --export-type=png \
  --export-filename=enterprise-plan-product-image.png \
  --export-width=1200 \
  --export-height=630
```

#### 3-2. rsvg-convert（librsvg）

```bash
# librsvg2-binインストール
sudo apt install librsvg2-bin

# 変換
rsvg-convert family-plan-product-image-ja.svg \
  -w 1200 -h 630 \
  -o family-plan-product-image.png

rsvg-convert enterprise-plan-product-image-ja.svg \
  -w 1200 -h 630 \
  -o enterprise-plan-product-image.png
```

---

## Stripe Dashboardでの登録手順

### 事前準備

1. [Stripe Dashboard](https://dashboard.stripe.com/) にログイン
2. テスト環境と本番環境を切り替え可能（左上のトグル）
3. まずはテスト環境で設定を確認することを推奨

### 1. 商品作成（ファミリープラン）

1. Stripe Dashboard → **商品カタログ** → **商品を追加**
2. 商品情報を入力:
   - **名前**: `MyTeacher ファミリープラン`
   - **説明**: 上記の日本語説明文をコピー&ペースト
   - **画像**: `family-plan-product-image.png` をアップロード
   - **メタデータ（任意）**:
     - `plan_type`: `family`
     - `max_members`: `6`
     - `max_groups`: `1`
3. **保存**をクリック

### 2. 価格設定（ファミリープラン）

1. 作成した商品の詳細ページで **価格を追加**
2. 価格情報を入力:
   - **価格モデル**: 定期的
   - **価格**: `500` JPY
   - **請求期間**: 月次（every 1 month）
   - **説明（任意）**: `月額プラン`
3. **詳細オプション**:
   - **無料トライアル**: 14日間
   - **使用量ベース**: オフ
4. **保存**
5. **Price ID** をコピー（例: `price_1ABcD2EfGhIjKlMn`）

### 3. 商品作成（エンタープライズプラン）

1. 同様に **商品を追加**
2. 商品情報を入力:
   - **名前**: `MyTeacher エンタープライズプラン`
   - **説明**: 上記の日本語説明文をコピー&ペースト
   - **画像**: `enterprise-plan-product-image.png` をアップロード
   - **メタデータ（任意）**:
     - `plan_type`: `enterprise`
     - `base_members`: `20`
     - `additional_member_price`: `150`
     - `max_groups`: `unlimited`
3. **保存**

### 4. 価格設定（エンタープライズプラン）

#### 基本料金（20名分）

1. **価格を追加**
2. 価格情報:
   - **価格モデル**: 定期的
   - **価格**: `3000` JPY（基本20名分）
   - **請求期間**: 月次
   - **説明**: `エンタープライズプラン基本料金（20名まで）`
3. **Price ID** をコピー

#### 追加メンバー料金

1. 同じ商品に **価格を追加**
2. 価格情報:
   - **価格モデル**: 使用量ベース
   - **価格**: `150` JPY
   - **請求期間**: 月次
   - **説明**: `追加メンバー1名あたり`
3. **Price ID** をコピー

### 5. 環境変数設定

`.env` ファイルに Price ID を追加:

```bash
# Stripe API Keys
STRIPE_KEY=pk_test_xxxxxxxxxxxxx  # または pk_live_xxxxxxxxxxxxx
STRIPE_SECRET=sk_test_xxxxxxxxxxxxx  # または sk_live_xxxxxxxxxxxxx

# Stripe Webhook Secret
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxx

# Stripe商品価格ID（テスト環境）
STRIPE_PRICE_FAMILY_MONTHLY=price_xxxxxxxxxxxxx
STRIPE_PRICE_ENTERPRISE_MONTHLY=price_xxxxxxxxxxxxx
STRIPE_PRICE_ENTERPRISE_ADDITIONAL_MEMBER=price_xxxxxxxxxxxxx

# 本番環境用（後で追加）
# STRIPE_PRICE_FAMILY_MONTHLY_LIVE=price_xxxxxxxxxxxxx
# STRIPE_PRICE_ENTERPRISE_MONTHLY_LIVE=price_xxxxxxxxxxxxx
# STRIPE_PRICE_ENTERPRISE_ADDITIONAL_MEMBER_LIVE=price_xxxxxxxxxxxxx
```

### 6. Webhook設定

1. Stripe Dashboard → **開発者** → **Webhook**
2. **エンドポイントを追加**:
   - **エンドポイントURL**: `https://yourdomain.com/stripe/webhook`
   - **説明**: `MyTeacher Subscription Webhook`
   - **イベント選択**:
     - `customer.subscription.created`
     - `customer.subscription.updated`
     - `customer.subscription.deleted`
     - `invoice.payment_succeeded`
     - `invoice.payment_failed`
3. **Signing secret** をコピーして `.env` の `STRIPE_WEBHOOK_SECRET` に設定

### 7. Statement Descriptorの設定

1. Stripe Dashboard → **設定** → **公開情報**
2. **Statement descriptor** セクション
3. デフォルトを設定（最大22文字）:
   - `MYTEACHER SUB` または `MYTEACHER*PLAN`
4. 商品ごとに個別設定する場合:
   - 各商品の **価格** → **詳細** → **Statement descriptor**
   - ファミリー: `MYTEACHER FAMILY`
   - エンタープライズ: `MYTEACHER ENTERPRISE`

### 8. テスト

**テストカード番号**:
- 成功: `4242 4242 4242 4242`
- 決済失敗: `4000 0000 0000 0002`
- 3Dセキュア: `4000 0027 6000 3184`

**有効期限**: 任意の未来の日付（例: `12/34`）
**CVC**: 任意の3桁（例: `123`）
**郵便番号**: 任意（例: `12345`）

### 9. 本番環境への移行

1. Stripeダッシュボードで**本番モード**に切り替え
2. 上記手順1-7を本番環境で繰り返し
3. 本番用Price IDを `.env` に追加
4. アプリケーションの環境変数を本番用に更新
5. Webhookエンドポイントが正しく設定されているか確認

---

## デザイン方針

**ファミリープラン**:
- カラー: 温かみのある青緑系グラデーション（#16a085 → #1abc9c）
- デザイン: 家族向け、親しみやすさ、円形モチーフ
- アイコン: 家族の絆を象徴

**エンタープライズプラン**:
- カラー: ビジネス/教育機関向けネイビー×ゴールド（#1e3a8a → #2563eb、アクセント#fbbf24）
- デザイン: プロフェッショナル、信頼感、矩形モチーフ
- アイコン: 卓越性を象徴（★マーク）

**共通**:
- サイズ: 1200×630px（OGP画像サイズと同じ）
- テキスト: 明瞭で読みやすい日本語フォント
- 内容: プラン名、価格、主要機能4つ

---

## 価格設定の根拠

### ファミリープラン（¥500/月）

- **ターゲット**: 家族（最大6名）
- **メンバー単価**: ¥83/人（6名で割った場合）
- **競合比較**: Netflix等のサブスク相場を参考
- **年間契約**: ¥5,000（月額¥417相当、17%オフ）将来実装予定

**コスト試算**:
- 無料ティアでは月3回のグループタスク制限
- 有料化で無制限グループタスク＋優先サポート
- 家族向けの手頃な価格設定で導入ハードルを下げる

### エンタープライズプラン（¥3,000/月〜）

- **基本20名**: ¥3,000（メンバー単価¥150/人）
- **追加1名**: ¥150/月（スケーラブル）
- **例**: 30名の塾 = ¥3,000 + (¥150 × 10) = ¥4,500/月
- **競合比較**: 教育SaaS（Classi、Google Classroom有料版）の価格帯
- **年間契約**: 割引あり（将来実装）

**柔軟性**:
- 小規模塾（20名）: ¥3,000/月で十分
- 中規模塾（50名）: ¥7,500/月（¥150×30追加）
- 大規模校（100名）: ¥15,000/月（¥150×80追加）

---

## 今後の拡張

- **年間プラン**: 17%割引で提供予定
  - ファミリー: ¥5,000/年（月額¥417相当）
  - エンタープライズ: 基本¥30,000/年（月額¥2,500相当）

- **学割プラン**: 学生・教育機関向け（30%オフ）

- **無料トライアル**: 14日間無料体験（既に実装準備済み）

---

## ファイル一覧

```
docs/stripe-products/
├── README.md                              # このファイル
├── QUICKSTART.md                          # 3ステップクイックガイド
├── family-plan-product-image-ja.svg       # ファミリープラン画像（SVG、変換元）
├── enterprise-plan-product-image-ja.svg   # エンタープライズプラン画像（SVG、変換元）
├── family-plan-product-image.png          # ファミリープラン画像（PNG、Stripe登録用）※要変換
├── enterprise-plan-product-image.png      # エンタープライズプラン画像（PNG、Stripe登録用）※要変換
└── convert-svg-to-png.sh                  # PNG変換ガイドスクリプト
```

---

**作成日**: 2025-11-29  
**更新日**: 2025-11-29  
**バージョン**: 2.0.0  
**変更内容**: ベーシック・プレミアムプランを削除、ファミリー・エンタープライズプランに統一、日本語版画像に変更
