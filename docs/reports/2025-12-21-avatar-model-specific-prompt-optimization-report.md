# アバター画像生成 - モデル別プロンプト最適化レポート

## 実施日
2025-12-21

## 概要
アバター画像生成処理において、使用する描画モデルに応じたプロンプト最適化を実装しました。Booru-styleモデル（anything-v4.0, animagine-xl-3.1）では Danbooru タグ形式、自然言語モデル（stable-diffusion-3.5-medium）では詳細な長文記述を使用することで、各モデルの特性に最適化されたプロンプトを生成します。

## 実装内容

### 1. モデル別プロンプト生成ロジック

#### 新規メソッド

**モデル判定**:
```php
private function isBooruStyleModel(string $model): bool
{
    return in_array($model, ['anything-v4.0', 'animagine-xl-3.1'], true);
}
```

**Booru-styleプロンプト生成**:
```php
private function buildBasePromptBooruStyle(TeacherAvatar $avatar): string
```
- Danbooruタグ形式: `1girl, solo, long_hair, black_hair, brown_eyes, business_suit, glasses, anime`
- アンダースコア区切り
- 簡潔なタグ列挙

**自然言語プロンプト生成**:
```php
private function buildBasePromptNaturalLanguage(TeacherAvatar $avatar): string
```
- 詳細な記述: `1person, solo character, anime style teacher character ID 12345, wearing professional business suit, black hair, brown eyes...`
- スペース区切り
- 長文での詳細指定

### 2. 表情・ポーズの変換ロジック

**表情変換**:
```php
private function convertExpressionToBooruStyle(string $expressionPrompt): string
```
- 自然言語 → Booru-style タグ
- 例: `"happy expression"` → `"smile, happy"`

**ポーズ変換**:
```php
private function convertPoseToBooruStyle(string $poseDescription): string
```
- 自然言語 → Booru-style タグ
- 例: `"full body standing pose"` → `"full_body, standing"`

### 3. 画像品質チェック機能

**新規メソッド**:
```php
private function validateImageQuality(string $imageUrl, string $poseType, string $expressionType): bool
```

**検証項目**:
1. ファイルサイズ: 10KB ～ 10MB
2. 画像フォーマット: PNG/JPEG/WEBP
3. 最低解像度: 256x256 以上
4. 画像タイプ: 有効な画像データ

**動作**:
- S3アップロード前に検証
- 品質チェック失敗時は警告ログ（アップロードは継続）

## Replicate公式ドキュメント調査結果

### anything-v4.0
- **推奨プロンプト**: Danbooru-style tags
- **デフォルト設定**: guidance_scale=7, steps=20
- **特徴**: アニメスタイルに特化、booru-styleタグで最適動作

### animagine-xl-3.1
- **推奨プロンプト**: Booru-style tags（`1girl, character_name, series`形式）
- **特徴**: Danbooru dataset学習、スタイルプリセット対応

### stable-diffusion-3.5-medium
- **推奨プロンプト**: 自然言語の長文（最大256トークン）
- **デフォルト設定**: cfg=5, steps=40推奨
- **特徴**: 複雑なプロンプト理解、T5-xxl使用、QK-normalization

## テスト実施結果

### ユニットテスト

**ファイル**: `tests/Feature/Avatar/ModelSpecificPromptTest.php`

**テストケース** (5件、33アサーション):
1. ✅ Booru-styleモデル判定
2. ✅ Booru-styleプロンプト生成
3. ✅ 自然言語プロンプト生成
4. ✅ ちびキャラプロンプト（Booru-style）
5. ✅ 表情・ポーズのBooru-style変換

**実行結果**:
```
Tests:    5 passed (33 assertions)
Duration: 0.94s
```

### プロンプト例

#### Booru-style（anything-v4.0）
```
1girl, solo, long_hair, black_hair, brown_eyes, business_suit, glasses, 
average_build, anime, smile, happy, upper_body, portrait, masterpiece, 
best_quality, high_quality, detailed_face, clear_features, 
white_background, simple_background
```

#### 自然言語（stable-diffusion-3.5-medium）
```
(happy expression:1.3), bright smile, joyful face, 1person, solo, 
single character only, anime style teacher character ID 12345, 
female, long hair, black hair, brown eyes, wearing professional business suit, 
wearing glasses, average build, upper body portrait from shoulders up, 
high quality, masterpiece, best quality, (detailed face:1.2), 
plain white background
```

## 画像品質チェックについて

### 質問への回答

**Q**: プログラムで画像の質を判定することはできますか？

**A**: 基本的な品質保証は可能ですが、視覚的品質（絵柄の良し悪し、表情の適切さ等）は人間の目視確認が必要です。

### 実装した自動チェック

**可能な検証**:
- ✅ ファイルサイズ（極端に小さい/大きいファイルの検出）
- ✅ 画像フォーマット（PNG/JPEG/WEBP）
- ✅ 解像度（最低256x256）
- ✅ 画像データの有効性

**不可能な検証**:
- ❌ 絵柄の品質（美しさ、クオリティ）
- ❌ 表情の適切さ（指定通りの表情か）
- ❌ キャラクターの一貫性（複数画像で同一人物か）
- ❌ アーティファクトやノイズの検出

### 推奨検証方法

**本番適用前**:
1. 各モデルで小規模テスト（3種類 × 2ポーズ × 2表情 = 12画像）
2. 管理画面で目視確認
3. プロンプトログ確認（`Log::info('[GenerateAvatarImages] Generating image'`）

**本番適用後**:
- ログ監視: 品質チェック失敗の警告確認
- ユーザーフィードバック収集
- S3アップロード済み画像の定期的なサンプルチェック

## 互換性と影響範囲

### 既存アバターへの影響

**アバター再生成時**:
- ✅ 別人物になるのはOK（seedが変われば別人物）
- ✅ 1回の生成過程で作成される複数画像（異なるポーズ・表情）は同一人物（seed固定で保証）

**データ互換性**:
- `teacher_avatars.draw_model_version`カラムを使用して自動判定
- 既存データは再生成時に新形式プロンプト適用
- 手動でのマイグレーション不要

## ファイル変更

### 修正ファイル
- `app/Jobs/GenerateAvatarImagesJob.php` (+537行)
  - `isBooruStyleModel()` 追加
  - `buildBasePromptBooruStyle()` 追加
  - `buildBasePromptNaturalLanguage()` 追加（既存ロジック分離）
  - `buildFullPromptBooruStyle()` 追加
  - `buildFullPromptNaturalLanguage()` 追加（既存ロジック分離）
  - `convertExpressionToBooruStyle()` 追加
  - `convertPoseToBooruStyle()` 追加
  - `validateImageQuality()` 追加

### 新規ファイル
- `tests/Feature/Avatar/ModelSpecificPromptTest.php` (+204行)

## コミット情報

```
commit 2f5fb35
feat(avatar): モデル別プロンプト最適化を実装

- Booru-styleモデル（anything-v4.0, animagine-xl-3.1）用のタグ形式プロンプト生成
- 自然言語モデル（stable-diffusion-3.5-medium）用の詳細記述プロンプト生成
- モデル判定ロジックでプロンプト形式を自動切り替え
- 画像品質チェック機能を追加（サイズ、フォーマット、解像度検証）
```

## 次のステップ（推奨）

### 本番適用前の検証

1. **ステージング環境でのテスト**:
   ```bash
   # 小規模テスト実施
   php artisan tinker
   $avatar = TeacherAvatar::find(1);
   GenerateAvatarImagesJob::dispatch($avatar->id);
   ```

2. **ログ監視設定**:
   ```bash
   # プロンプト生成ログ確認
   tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep "GenerateAvatarImages"
   ```

3. **画像品質確認**:
   - 各モデルで3パターン生成
   - ポーズ・表情の整合性確認
   - Booru-styleと自然言語の品質比較

### オプション改善

1. **A/Bテスト実装**:
   - 旧形式と新形式の品質比較
   - ユーザー満足度調査

2. **プロンプトチューニング**:
   - ネガティブプロンプトの最適化
   - 品質タグの調整

3. **監視強化**:
   - 画像品質チェック失敗率の監視
   - モデル別の生成成功率ダッシュボード

## まとめ

✅ **実装完了**:
- モデル別プロンプト最適化
- 画像品質チェック機能
- ユニットテスト（5件、全合格）

✅ **技術検証**:
- Replicate公式ドキュメント調査
- Booru-style vs 自然言語の違い明確化
- 各モデルの推奨パラメータ特定

✅ **品質保証**:
- 自動化可能な範囲での品質チェック実装
- 視覚的品質は人間の目視確認が必要と明確化

⚠️ **注意事項**:
- 本番適用前に小規模テスト推奨
- プロンプト変更により既存と異なる絵柄が生成される可能性
- ログ監視で品質チェック失敗を追跡
