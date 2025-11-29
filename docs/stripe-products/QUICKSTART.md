# Stripe商品登録 - クイックスタートガイド

## 📦 作成されたファイル

```
docs/stripe-products/
├── README.md                              # 詳細ドキュメント（商品説明、登録手順）
├── family-plan-product-image-ja.svg       # ファミリープラン商品画像（SVG、変換元）
├── enterprise-plan-product-image-ja.svg   # エンタープライズプラン商品画像（SVG、変換元）
├── family-plan-product-image.png          # ファミリープラン画像（PNG、Stripe登録用）※要変換
├── enterprise-plan-product-image.png      # エンタープライズプラン画像（PNG、Stripe登録用）※要変換
└── convert-svg-to-png.sh                  # PNG変換ガイドスクリプト
```

## 🚀 すぐに使う場合（3ステップ）

### Step 1: SVGをPNGに変換

**StripeはJPEG/PNGのみ対応** - SVGファイルを必ずPNGに変換してください。

**方法A: オンラインツール（最も簡単）**
1. [CloudConvert](https://cloudconvert.com/svg-to-png) にアクセス
2. `family-plan-product-image-ja.svg` をアップロード
3. ダウンロードして `family-plan-product-image.png` にリネーム
4. `enterprise-plan-product-image-ja.svg` も同様に変換
5. `enterprise-plan-product-image.png` にリネーム

**方法B: コマンドライン（Inkscape）**
```bash
# Inkscapeインストール
sudo apt install inkscape

# 変換実行
cd /home/ktr/mtdev/docs/stripe-products
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

### Step 2: Stripeに商品登録

1. [Stripe Dashboard](https://dashboard.stripe.com/) にログイン
2. **商品カタログ** → **商品を追加**

**ファミリープラン**:
- 商品名: `MyTeacher ファミリープラン`
- 価格: `¥500/月`
- 画像: `family-plan-product-image.png` をアップロード
- 説明文: READMEから該当箇所をコピー

**エンタープライズプラン**:
- 商品名: `MyTeacher エンタープライズプラン`
- 価格: `¥3,000/月`（基本20名）
- 追加料金: `¥150/月`（1名あたり）
- 画像: `enterprise-plan-product-image.png` をアップロード
- 説明文: READMEから該当箇所をコピー

### Step 3: Price IDを.envに設定

```bash
# .env ファイル
STRIPE_PRICE_FAMILY_MONTHLY=price_xxxxxxxxxxxxx
STRIPE_PRICE_ENTERPRISE_MONTHLY=price_xxxxxxxxxxxxx
STRIPE_PRICE_ENTERPRISE_ADDITIONAL_MEMBER=price_xxxxxxxxxxxxx
```

## 📋 商品概要

### ファミリープラン（¥500/月）

| 項目 | 内容 |
|------|------|
| **メンバー数** | 最大6名（管理者1+編集者1+子供4） |
| **グループタスク** | 割当機能あり |
| **AI教師** | 基本機能 |
| **トライアル** | 14日間無料 |
| **対象** | 家族 |

### エンタープライズプラン（¥3,000/月〜）

| 項目 | 内容 |
|------|------|
| **メンバー数** | 基本20名（追加¥150/名） |
| **グループタスク** | 無制限 |
| **AI教師** | 全機能 |
| **サポート** | 優先対応 |
| **対象** | 塾・学校・教育団体 |

## 🎨 画像デザイン

**ファミリープラン**（日本語版）:
- 温かみのある青緑系グラデーション（#16a085 → #1abc9c）
- 家族向け親しみやすいデザイン、円形モチーフ
- サイズ: 1200×630px
- テキスト: すべて日本語

**エンタープライズプラン**（日本語版）:
- ビジネス向けネイビー×ゴールド（#1e3a8a → #2563eb）
- プロフェッショナルデザイン、矩形モチーフ
- 金色の星マーク（★★★）で卓越性を表現
- サイズ: 1200×630px
- テキスト: すべて日本語

## 📝 商品説明文（コピペ用）

### ファミリープラン

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

### エンタープライズプラン

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

## 🔗 次のステップ

1. ✅ **商品画像SVG**: 作成済み（日本語版）
2. ⏳ **PNG変換**: オンラインツールまたはInkscapeで実施
3. ⏳ **Stripe登録**: [README.md](./README.md) の手順に従って実施
4. ⏳ **Webhook設定**: Stripeダッシュボードで設定
5. ⏳ **実装**: Phase 1.1の実装計画に従って開発
6. ⏳ **テスト**: テストカードで動作確認

## 💡 ヒント

- **画像要件**: 1200×630px、2MB未満、JPEG/PNG形式
- **価格設定**: テスト環境で先に確認してから本番環境に移行
- **トライアル**: ファミリープランは14日間無料トライアル設定推奨
- **スケーラブル料金**: エンタープライズは基本料金+追加メンバー料金の2つのPrice IDが必要

## 📚 詳細情報

- 詳細な登録手順: [README.md](./README.md)
- 実装計画: [phase1-1-stripe-subscription-plan.md](../plans/phase1-1-stripe-subscription-plan.md)
- テストカード: Stripe Dashboard → 開発者 → テストカード

---

**作成日**: 2025-11-29  
**更新日**: 2025-11-29  
**バージョン**: 2.0.0  
**変更内容**: ファミリー・エンタープライズプランに統一、日本語版画像に変更
