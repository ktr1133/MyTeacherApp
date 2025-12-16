# プライバシーポリシー・利用規約 要件定義書

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-16 | GitHub Copilot | 初版作成: プライバシーポリシー・利用規約の要件定義 |

---

## 1. 概要

### 1.1 目的

**My Teacher** アプリケーション（Web版・モバイル版）において、法令遵守と利用者保護のため、以下の法的文書を整備する:

1. **プライバシーポリシー**: 個人情報の取り扱い、AI利用、第三者提供等を明記
2. **利用規約**: サービス利用条件、禁止事項、免責事項等を明記

### 1.2 背景

- **App Store/Google Play審査対応**: プライバシーポリシー・利用規約のURL提示が必須
- **法令遵守**: 個人情報保護法、COPPA（米国児童オンラインプライバシー保護法）、GDPR（EU一般データ保護規則）対応
- **AI利用の透明性**: OpenAI、Stable Diffusion等のAIサービス利用を明示
- **決済機能**: Stripe連携による有料トークン購入機能あり

### 1.3 適用範囲

- **Webアプリケーション**: `https://yourdomain.com` （Laravel製）
- **モバイルアプリケーション**: iOS版・Android版（React Native + Expo製）

---

## 2. アプリケーション情報

### 2.1 基本情報

| 項目 | 内容 |
|------|------|
| **アプリケーション名** | My Teacher |
| **運営者** | 個人（氏名非公開） |
| **所在地** | 〒133-0061 東京都江戸川区篠崎町4-26-14 タブララサA号室 |
| **連絡先メールアドレス** | famicoapp@gmail.com |
| **サービス内容** | AI支援タスク管理・教育支援アプリケーション |
| **対象ユーザー** | 全年齢（未成年者含む、保護者同意必須） |
| **公開予定** | App Store、Google Play Store |

### 2.2 収集・利用する情報

#### 2.2.1 個人情報

| 項目 | 収集方法 | 利用目的 | 保存場所 |
|------|---------|---------|---------|
| メールアドレス | ユーザー登録 | アカウント管理、通知送信 | PostgreSQL (AWS RDS) |
| パスワード（ハッシュ化） | ユーザー登録 | 認証 | PostgreSQL (AWS RDS) |
| ユーザー名 | ユーザー登録 | 表示名、アバター生成 | PostgreSQL (AWS RDS) |
| プロフィール画像 | 任意アップロード | ユーザー識別 | MinIO/S3 |
| グループメンバー情報 | グループ作成時 | グループタスク管理 | PostgreSQL (AWS RDS) |
| 生年月日（任意） | プロフィール設定 | 年齢確認、保護者同意プロセス | PostgreSQL (AWS RDS) |

#### 2.2.2 タスク・行動データ

| 項目 | 収集方法 | 利用目的 | 保存場所 |
|------|---------|---------|---------|
| タスク内容（タイトル、説明文） | タスク作成 | タスク管理、AIタスク分解 | PostgreSQL (AWS RDS) |
| タスク添付画像 | タスク作成・承認 | タスク証跡管理 | MinIO/S3 |
| タスク完了状況 | タスク操作 | 進捗管理、レポート生成 | PostgreSQL (AWS RDS) |
| スケジュール情報 | スケジュールタスク設定 | 自動タスク生成 | PostgreSQL (AWS RDS) |
| タグ情報 | タスク作成・編集 | タスク分類、検索 | PostgreSQL (AWS RDS) |

#### 2.2.3 AI生成データ

| 項目 | 送信先 | 利用目的 | 保存場所 |
|------|--------|---------|---------|
| タスク分解プロンプト | OpenAI API (米国) | タスク自動分解 | 送信のみ（保存なし） |
| アバター生成プロンプト | Replicate API (米国) | 教師アバター生成 | PostgreSQL (AWS RDS) |
| 生成アバター画像 | - | アバター表示 | MinIO/S3 |

#### 2.2.4 決済情報

| 項目 | 送信先 | 利用目的 | 保存場所 |
|------|--------|---------|---------|
| クレジットカード情報 | Stripe (米国) | トークン購入決済 | Stripeが管理（自社保存なし） |
| 購入履歴 | - | トランザクション管理 | PostgreSQL (AWS RDS) |
| トークン残高 | - | 利用可能トークン管理 | PostgreSQL (AWS RDS) |

#### 2.2.5 デバイス・通知情報

| 項目 | 収集方法 | 利用目的 | 保存場所 |
|------|---------|---------|---------|
| Push通知トークン | Firebase (Google) | プッシュ通知送信 | Firebase + PostgreSQL (AWS RDS) |
| デバイス情報（OS、バージョン） | アプリ起動時 | 不具合調査、最適化 | ログ（CloudWatch） |
| IPアドレス | アクセス時 | セキュリティ、不正利用防止 | ログ（CloudWatch） |

#### 2.2.6 Web解析データ（予定）

| 項目 | 収集方法 | 利用目的 | 保存場所 |
|------|---------|---------|---------|
| Cookie、閲覧履歴 | Google Analytics（予定） | サービス改善、利用分析 | Google Analytics |

---

## 3. データ利用・第三者提供

### 3.1 利用目的

1. **サービス提供**
   - タスク管理機能の提供
   - AIによるタスク分解・アバター生成
   - グループ機能・承認フロー
   - レポート生成

2. **サービス改善**
   - **AIモデルの精度向上**: タスク分解結果、アバター生成結果を分析（個人識別情報は除外）
   - **統計分析**: タスク完了率、利用傾向等を集計し、機能改善に活用

3. **統計情報の第三者提供**
   - **提供先**: 第三者（研究機関、広告主等、具体的提供先は個別通知）
   - **提供内容**: **個人を特定できない集計データのみ**（例: 年齢層別タスク完了率、平均利用時間等）
   - **提供方法**: 匿名化・統計処理後のデータのみ提供

### 3.2 第三者提供（外部サービス連携）

| サービス名 | 提供国 | 提供データ | 目的 | プライバシーポリシー |
|-----------|-------|-----------|------|---------------------|
| **OpenAI** | 米国 | タスクタイトル、説明文 | タスク自動分解 | https://openai.com/policies/privacy-policy |
| **Replicate** | 米国 | アバター生成プロンプト | アバター画像生成 | https://replicate.com/privacy |
| **Stripe** | 米国 | 決済情報（Stripe管理） | トークン購入決済 | https://stripe.com/privacy |
| **Firebase (Google)** | 米国 | Push通知トークン、デバイス情報 | プッシュ通知送信 | https://firebase.google.com/support/privacy |
| **AWS** | 米国（リージョン: ap-northeast-1） | 全データ | インフラ・ストレージ | https://aws.amazon.com/privacy/ |
| **Google Analytics**（予定） | 米国 | Cookie、閲覧履歴 | アクセス解析 | https://policies.google.com/privacy |

**注意**: 上記サービスへのデータ転送により、データが日本国外（米国等）に移転されます。

---

## 4. データ保持・削除

### 4.1 データ保持期間

| データ種別 | 保持期間 | 削除方法 |
|-----------|---------|---------|
| アカウント情報 | アカウント削除後 **90日間** | バッチ処理による自動削除 |
| タスクデータ | アカウント削除後 **90日間** | バッチ処理による自動削除 |
| 画像ファイル（S3） | アカウント削除後 **90日間** | バッチ処理による自動削除 |
| バックアップデータ | 最大 **90日間** | 自動ローテーション |
| ログデータ | 最大 **90日間** | CloudWatch自動削除 |
| 決済履歴（Stripe） | Stripe規約に準拠 | Stripe側で管理 |

### 4.2 削除バッチ処理（新規実装）

**実装要件**:
- **バッチコマンド**: `php artisan batch:delete-inactive-users`
- **実行頻度**: 日次（深夜1:00 JST）
- **処理内容**:
  1. `deleted_at` が90日以前のユーザーを抽出
  2. 関連データを物理削除:
     - `users` テーブルレコード
     - `tasks` テーブルレコード（`user_id` 紐付け）
     - `scheduled_group_tasks` テーブルレコード
     - S3オブジェクト（`avatars/{user_id}/`, `task_approvals/{user_id}/`）
  3. 削除ログを記録（CloudWatch）
- **エラーハンドリング**: 削除失敗時はSlack通知（運用設定次第）

**スケジューラー設定** (`app/Console/Kernel.php`):
```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('batch:delete-inactive-users')
        ->dailyAt('01:00')
        ->timezone('Asia/Tokyo');
}
```

---

## 5. 未成年者対応（COPPA対応）

### 5.1 対象年齢

- **全年齢対応**（13歳未満の児童を含む）
- **保護者同意が必須**

### 5.2 保護者同意プロセス（新規実装）

#### 5.2.1 ユーザー登録フロー

```
[ユーザー登録画面]
↓
[生年月日入力]
↓
├─ 13歳以上 → 通常登録
└─ 13歳未満 → 保護者同意画面へ
    ↓
    [保護者メールアドレス入力]
    ↓
    [保護者へ同意依頼メール送信]
    ↓
    [保護者が同意リンクをクリック]
    ↓
    [アカウント有効化]
```

#### 5.2.2 実装詳細

**データベース追加カラム**:
```sql
-- usersテーブル
ALTER TABLE users ADD COLUMN birthdate DATE NULL;
ALTER TABLE users ADD COLUMN parent_email VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN parent_consent_token VARCHAR(64) NULL;
ALTER TABLE users ADD COLUMN parent_consented_at TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN is_minor BOOLEAN DEFAULT FALSE;
```

**保護者同意メール送信**:
- メール件名: 「[My Teacher] お子様のアカウント登録に関する同意のお願い」
- 同意リンク: `https://yourdomain.com/parent-consent/{token}`
- リンク有効期限: 7日間

**同意確認画面**:
- 保護者情報入力フォーム（氏名、続柄）
- プライバシーポリシー・利用規約への同意チェックボックス
- 「同意する」ボタン

**未同意時の制限**:
- アカウント仮登録状態（ログイン不可）
- 7日以内に同意がない場合、アカウント自動削除

### 5.3 未成年者のデータ保護

- **13歳未満のユーザーデータは最小限に収集**:
  - 必須: メールアドレス、ユーザー名、生年月日、保護者メールアドレス
  - 任意: プロフィール画像（保護者が許可した場合のみ）
- **マーケティング目的の利用禁止**
- **第三者への統計情報提供時は年齢情報を除外**

---

## 6. 国際データ転送・GDPR対応

### 6.1 国際データ転送

- **データ転送先**: 米国（OpenAI、Replicate、Stripe、Firebase）
- **法的根拠**: 契約に基づく移転（サービス提供に必要）
- **保護措置**: 各サービスプロバイダーのプライバシーポリシーに準拠

### 6.2 GDPR対応（EU圏ユーザー向け）

**対象**: EU圏からアクセスするユーザー

**実装要件**:
1. **Cookie同意バナー**（Web版）
   - 初回アクセス時に表示
   - 「必須Cookie」「解析Cookie」を分離
   - 拒否可能

2. **データポータビリティ**
   - ユーザーがデータエクスポートを要求可能
   - JSON形式でダウンロード提供

3. **削除権（Right to be Forgotten）**
   - アカウント削除 = 全データ削除（90日後物理削除）

4. **プライバシーポリシーの多言語対応**
   - 英語版を作成（将来対応）

---

## 7. 画面・UI要件

### 7.1 Web版（Laravel）

#### 7.1.1 プライバシーポリシー画面

**URL**: `/privacy-policy`

**実装ファイル**:
- **View**: `resources/views/legal/privacy-policy.blade.php`
- **Route**: `routes/web.php` （認証不要）
- **Action**: 不要（単純な静的ページ）

**デザイン要件**:
- **レイアウト**: 最小限のヘッダー・フッター（ロゴ、戻るリンクのみ）
- **スタイル**: Tailwind CSS使用、ダークモード対応
- **構成**:
  ```html
  <header>ロゴ + 戻るリンク</header>
  <main>
    <h1>プライバシーポリシー</h1>
    <section id="overview">概要</section>
    <section id="collection">収集する情報</section>
    <section id="usage">利用目的</section>
    <section id="third-party">第三者提供</section>
    <section id="retention">データ保持期間</section>
    <section id="minors">未成年者対応</section>
    <section id="gdpr">国際データ転送</section>
    <section id="contact">お問い合わせ</section>
  </main>
  <footer>最終更新日</footer>
  ```
- **目次リンク**: 各セクションへのアンカーリンク
- **印刷対応**: `@media print` でヘッダー・フッター最小化

#### 7.1.2 利用規約画面

**URL**: `/terms-of-service`

**実装ファイル**:
- **View**: `resources/views/legal/terms-of-service.blade.php`
- **Route**: `routes/web.php` （認証不要）

**デザイン要件**:
- プライバシーポリシーと同様のレイアウト
- **構成**:
  ```html
  <main>
    <h1>利用規約</h1>
    <section id="agreement">利用契約</section>
    <section id="services">提供サービス</section>
    <section id="account">アカウント管理</section>
    <section id="prohibited">禁止事項</section>
    <section id="payment">有料サービス</section>
    <section id="disclaimer">免責事項</section>
    <section id="termination">サービス停止・解約</section>
    <section id="changes">規約変更</section>
    <section id="governing-law">準拠法・管轄裁判所</section>
  </main>
  ```

#### 7.1.3 共通レイアウト（任意）

**ファイル**: `resources/views/legal/layout.blade.php`

**機能**:
- ヘッダー: ロゴ + 「プライバシーポリシー」「利用規約」タブ切り替え
- フッター: 最終更新日、お問い合わせリンク

### 7.2 モバイル版（React Native）

#### 7.2.1 プライバシーポリシー画面

**ファイル**: `mobile/src/screens/legal/PrivacyPolicyScreen.tsx`

**実装方法**: **WebView表示**

**実装内容**:
```tsx
import React from 'react';
import { View, StyleSheet } from 'react-native';
import WebView from 'react-native-webview';
import { useNavigation } from '@react-navigation/native';
import { useThemedColors } from '../../hooks/useThemedColors';

export const PrivacyPolicyScreen: React.FC = () => {
  const navigation = useNavigation();
  const { colors } = useThemedColors();
  
  return (
    <View style={styles.container}>
      {/* ヘッダー: 戻るボタン */}
      <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backButton}>
        <MaterialIcons name="arrow-back" size={24} color={colors.text.primary} />
        <Text style={styles.backText}>戻る</Text>
      </TouchableOpacity>
      
      {/* WebView */}
      <WebView
        source={{ uri: 'https://yourdomain.com/privacy-policy' }}
        style={styles.webview}
        startInLoadingState={true}
        renderLoading={() => <ActivityIndicator />}
      />
    </View>
  );
};
```

**デザイン要件**:
- **ヘッダー**: 戻るボタン + タイトル「プライバシーポリシー」
- **ダークモード対応**: WebView背景色をテーマに合わせる
- **オフライン対応**: `onError` でフォールバック画面表示
- **レスポンシブ**: Dimensions APIでタブレット対応（`ResponsiveDesignGuideline.md` 準拠）

#### 7.2.2 利用規約画面

**ファイル**: `mobile/src/screens/legal/TermsOfServiceScreen.tsx`

**実装内容**: プライバシーポリシー画面と同様、URL変更のみ
- `source={{ uri: 'https://yourdomain.com/terms-of-service' }}`

#### 7.2.3 ナビゲーション設定

**ファイル**: `mobile/src/navigation/AppNavigator.tsx`

**追加ルート**:
```tsx
<Stack.Screen
  name="PrivacyPolicy"
  component={PrivacyPolicyScreen}
  options={{ title: 'プライバシーポリシー', headerShown: false }}
/>
<Stack.Screen
  name="TermsOfService"
  component={TermsOfServiceScreen}
  options={{ title: '利用規約', headerShown: false }}
/>
```

#### 7.2.4 アプリ情報画面からのリンク

**ファイル**: `mobile/src/screens/profile/SettingsScreen.tsx` または `mobile/src/screens/profile/ProfileScreen.tsx`

**追加セクション**:
```tsx
<View style={styles.section}>
  <Text style={styles.sectionTitle}>法的情報</Text>
  
  <TouchableOpacity onPress={() => navigation.navigate('PrivacyPolicy')}>
    <View style={styles.menuItem}>
      <Text style={styles.menuLabel}>プライバシーポリシー</Text>
      <MaterialIcons name="chevron-right" size={24} />
    </View>
  </TouchableOpacity>
  
  <TouchableOpacity onPress={() => navigation.navigate('TermsOfService')}>
    <View style={styles.menuItem}>
      <Text style={styles.menuLabel}>利用規約</Text>
      <MaterialIcons name="chevron-right" size={24} />
    </View>
  </TouchableOpacity>
</View>
```

---

## 8. プライバシーポリシー本文（概要）

### 8.1 必須記載事項

1. **事業者情報**
   - 運営者名、所在地、連絡先メールアドレス

2. **収集する個人情報の項目**
   - アカウント情報、タスクデータ、AI生成データ、決済情報、デバイス情報

3. **利用目的**
   - サービス提供、AI精度向上、統計分析

4. **第三者提供**
   - OpenAI、Replicate、Stripe、Firebase、AWS、Google Analytics（予定）
   - 統計情報の第三者提供（匿名化・個人特定不可）

5. **国際データ転送**
   - 米国への転送事実、法的根拠

6. **未成年者対応**
   - 保護者同意プロセス、13歳未満のデータ保護

7. **データ保持期間**
   - アカウント削除後90日、バックアップ90日

8. **ユーザーの権利**
   - データ開示請求、訂正・削除、利用停止、エクスポート（GDPR対応）

9. **Cookie・トラッキング**
   - Google Analytics使用（予定）、オプトアウト方法

10. **お問い合わせ**
    - famicoapp@gmail.com

11. **プライバシーポリシー変更**
    - 変更時の通知方法（アプリ内通知 + メール）

12. **最終更新日**

### 8.2 文面サンプル（一部抜粋）

```markdown
# プライバシーポリシー

最終更新日: 2025年12月16日

## 1. はじめに

My Teacher（以下「本サービス」）は、個人（以下「当方」）が運営するAI支援タスク管理・教育支援アプリケーションです。当方は、お客様の個人情報保護を最優先とし、以下のとおりプライバシーポリシーを定めます。

## 2. 事業者情報

- 運営者: 個人
- 所在地: 〒133-0061 東京都江戸川区篠崎町4-26-14 タブララサA号室
- お問い合わせ: famicoapp@gmail.com

## 3. 収集する個人情報

### 3.1 アカウント情報
- メールアドレス
- パスワード（ハッシュ化）
- ユーザー名
- 生年月日（未成年者の場合必須）
- プロフィール画像（任意）

### 3.2 AI利用データ
本サービスでは、以下のAIサービスを利用しており、お客様のデータが送信されます:
- **OpenAI API（米国）**: タスクタイトル・説明文をタスク分解のため送信
- **Replicate API（米国）**: アバター生成プロンプトを画像生成のため送信

### 3.3 未成年者の個人情報
13歳未満のお客様については、保護者の同意を得た場合にのみアカウント作成が可能です。保護者のメールアドレスを収集し、同意確認を行います。

[... 続く ...]
```

---

## 9. 利用規約本文（概要）

### 9.1 必須記載事項

1. **定義**
   - 本サービス、ユーザー、コンテンツ等の用語定義

2. **利用契約**
   - 規約への同意、アカウント作成

3. **アカウント管理**
   - パスワード管理責任、不正利用時の対応

4. **禁止事項**
   - 不正アクセス、誹謗中傷、著作権侵害、第三者へのパスワード共有等

5. **有料サービス**
   - トークン購入、Stripe決済、返金ポリシー

6. **免責事項**
   - サービス停止、データ損失、AI生成結果の精度保証なし

7. **サービス停止・解約**
   - 当方による一方的なサービス停止権、ユーザーによるアカウント削除

8. **知的財産権**
   - AI生成アバターの著作権（ユーザーに帰属）

9. **規約変更**
   - 変更時の通知方法

10. **準拠法・管轄裁判所**
    - 日本法準拠、東京地方裁判所を専属的合意管轄裁判所とする

---

## 10. 実装タスク

### 10.1 バックエンド（Laravel）

#### Phase 1: データベース・バッチ処理

| # | タスク | ファイル | 優先度 |
|---|--------|---------|-------|
| 1 | `users`テーブルに未成年者対応カラム追加 | マイグレーション | 高 |
| 2 | 削除バッチコマンド実装 | `app/Console/Commands/DeleteInactiveUsersCommand.php` | 高 |
| 3 | スケジューラー登録 | `app/Console/Kernel.php` | 高 |
| 4 | 保護者同意メール送信機能 | `app/Notifications/ParentConsentRequest.php` | 高 |
| 5 | 保護者同意確認Action | `app/Http/Actions/ParentConsentAction.php` | 高 |

#### Phase 2: Web画面

| # | タスク | ファイル | 優先度 |
|---|--------|---------|-------|
| 6 | プライバシーポリシー画面 | `resources/views/legal/privacy-policy.blade.php` | 高 |
| 7 | 利用規約画面 | `resources/views/legal/terms-of-service.blade.php` | 高 |
| 8 | 共通レイアウト（任意） | `resources/views/legal/layout.blade.php` | 中 |
| 9 | ルート追加 | `routes/web.php` | 高 |

#### Phase 3: 保護者同意フロー

| # | タスク | ファイル | 優先度 |
|---|--------|---------|-------|
| 10 | 登録時生年月日入力 | `resources/views/auth/register.blade.php` | 高 |
| 11 | 保護者メール入力画面 | `resources/views/auth/parent-email.blade.php` | 高 |
| 12 | 保護者同意画面 | `resources/views/auth/parent-consent.blade.php` | 高 |
| 13 | 未同意アカウント自動削除バッチ | `app/Console/Commands/DeleteUnconsentedMinorsCommand.php` | 高 |

### 10.2 モバイル（React Native）

| # | タスク | ファイル | 優先度 |
|---|--------|---------|-------|
| 14 | `react-native-webview`インストール | `package.json` | 高 |
| 15 | プライバシーポリシー画面 | `mobile/src/screens/legal/PrivacyPolicyScreen.tsx` | 高 |
| 16 | 利用規約画面 | `mobile/src/screens/legal/TermsOfServiceScreen.tsx` | 高 |
| 17 | ナビゲーション設定 | `mobile/src/navigation/AppNavigator.tsx` | 高 |
| 18 | プロフィール画面にリンク追加 | `mobile/src/screens/profile/ProfileScreen.tsx` または `SettingsScreen.tsx` | 高 |
| 19 | 登録時生年月日入力 | `mobile/src/screens/auth/RegisterScreen.tsx` | 高 |
| 20 | 保護者メール入力画面 | `mobile/src/screens/auth/ParentEmailScreen.tsx` | 高 |

### 10.3 ドキュメント

| # | タスク | ファイル | 優先度 |
|---|--------|---------|-------|
| 21 | プライバシーポリシー本文作成 | `/home/ktr/mtdev/docs/legal/privacy-policy.md` | 高 |
| 22 | 利用規約本文作成 | `/home/ktr/mtdev/docs/legal/terms-of-service.md` | 高 |

---

## 11. テスト要件

### 11.1 Web版

- [ ] プライバシーポリシー画面が正常に表示される
- [ ] 利用規約画面が正常に表示される
- [ ] ダークモード対応が正しく動作する
- [ ] 印刷時にレイアウトが崩れない
- [ ] 保護者同意メールが送信される
- [ ] 保護者同意リンクから同意画面にアクセスできる
- [ ] 削除バッチが90日後に正常に実行される

### 11.2 モバイル版

- [ ] WebView内でプライバシーポリシーが表示される
- [ ] WebView内で利用規約が表示される
- [ ] 戻るボタンで前画面に戻れる
- [ ] オフライン時にエラー画面が表示される
- [ ] ダークモード対応が正しく動作する
- [ ] タブレットでのレスポンシブ表示が正しい
- [ ] 生年月日入力で13歳未満判定が正しく動作する
- [ ] 保護者メール入力後、仮登録状態になる

---

## 12. 非機能要件

### 12.1 パフォーマンス

- プライバシーポリシー・利用規約画面の表示: **2秒以内**
- WebView読み込み時間: **3秒以内**（モバイル）

### 12.2 セキュリティ

- プライバシーポリシー・利用規約は **認証不要** でアクセス可能
- 保護者同意トークンは **64文字のランダム文字列**（予測不可能）
- 保護者同意リンクの有効期限: **7日間**

### 12.3 可用性

- プライバシーポリシー・利用規約の更新は **ダウンタイムなし**
- 削除バッチの失敗時は **リトライ3回**

---

## 13. 備考

### 13.1 今後の対応

- **英語版プライバシーポリシー**: GDPR対応のため将来作成
- **Cookie同意バナー**: Google Analytics導入時に実装
- **データエクスポート機能**: GDPR対応のため将来実装

### 13.2 参考リンク

- 個人情報保護法: https://www.ppc.go.jp/
- COPPA（米国）: https://www.ftc.gov/legal-library/browse/rules/childrens-online-privacy-protection-rule-coppa
- GDPR（EU）: https://gdpr-info.eu/

---

## 14. 承認

| 役割 | 氏名 | 承認日 | 署名 |
|------|------|-------|------|
| 要件定義作成者 | GitHub Copilot | 2025-12-16 | - |
| 承認者 | （運営者） | - | - |
