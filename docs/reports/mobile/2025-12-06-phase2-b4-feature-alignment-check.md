# Phase 2.B-4 機能整合性チェック結果

## チェック日時
2025-12-06

## 比較対象
- **Webアプリ**: `/home/ktr/mtdev/resources/views/profile/`
- **モバイルアプリ**: `mobile/src/screens/profile/`, `mobile/src/screens/settings/`

---

## 比較結果サマリー

| 機能 | Webアプリ | モバイルアプリ | ステータス | 備考 |
|------|---------|-------------|-----------|------|
| **プロフィール編集** | ✅ | ✅ | ✅ 一致 | |
| ユーザー名編集 | ✅ `/profile/edit` | ✅ `ProfileScreen` | ✅ 一致 | |
| メールアドレス編集 | ✅ `/profile/edit` | ✅ `ProfileScreen` | ✅ 一致 | |
| 表示名編集 | ✅ `/profile/edit` | ✅ `ProfileScreen` (name) | ✅ 一致 | |
| **自己紹介（bio）** | ❌ なし | ✅ `ProfileScreen` | ⚠️ **モバイル独自** | **要確認** |
| **アバター画像アップロード** | ❌ なし | ✅ `ProfileScreen` | ⚠️ **モバイル独自** | **要確認** |
| **アカウント削除** | ❌ なし | ✅ `ProfileScreen` | ⚠️ **モバイル独自** | **要確認** |
| **タイムゾーン設定** | ✅ `/profile/timezone` | ✅ `SettingsScreen` | ✅ 一致 | |
| **テーマ切り替え** | ❌ なし（サイドバーにあり） | ✅ `SettingsScreen` | ⚠️ **配置相違** | Webアプリはサイドバーに配置 |
| **通知設定** | ❌ なし | ✅ `SettingsScreen` | ⚠️ **モバイル独自** | プッシュ通知ON/OFF |
| **アプリ情報** | ❌ なし | ✅ `SettingsScreen` | ⚠️ **モバイル独自** | バージョン、利用規約等 |
| **グループ管理リンク** | ✅ `/profile/edit` | ❌ なし | ❌ **モバイル未実装** | Phase 2.B-3で実装予定 |

---

## 詳細分析

### 1. Webアプリ `/profile/edit` の機能

**ファイル**: `resources/views/profile/edit.blade.php`

**実装されている機能**:
1. ✅ プロフィール情報編集
   - ユーザー名（username）
   - メールアドレス（email）
   - 表示名（name、任意）
2. ✅ グループ管理リンク（成人ユーザーのみ）
3. ✅ タイムゾーン設定リンク
4. ✅ パスワード変更セクション
5. ✅ アカウント削除セクション（別パーシャル）

**実装されていない機能**:
- ❌ 自己紹介（bio）フィールド
- ❌ アバター画像アップロード
- ❌ テーマ切り替え（サイドバーに存在）

---

### 2. Webアプリ `/profile/timezone` の機能

**ファイル**: `resources/views/profile/timezone.blade.php`

**実装されている機能**:
1. ✅ タイムゾーン選択（プルダウン）
2. ✅ 保存ボタン

**モバイルとの一致**:
- ✅ SettingsScreenにタイムゾーン選択実装済み

---

### 3. モバイルアプリ `ProfileScreen` の機能

**ファイル**: `mobile/src/screens/profile/ProfileScreen.tsx`

**実装されている機能**:
1. ✅ プロフィール情報編集
   - ユーザー名（username）
   - メールアドレス（email）
   - 表示名（name）
   - **⚠️ 自己紹介（bio）** - Webアプリになし
2. **⚠️ アバター画像アップロード** - Webアプリになし
3. **⚠️ アカウント削除** - Webアプリになし（別セクションで存在）

**Webアプリとの相違点**:
- ❌ グループ管理リンクなし（Phase 2.B-3で実装予定）
- ❌ パスワード変更機能なし（未実装）

---

### 4. モバイルアプリ `SettingsScreen` の機能

**ファイル**: `mobile/src/screens/settings/SettingsScreen.tsx`

**実装されている機能**:
1. ✅ タイムゾーン設定
2. **⚠️ テーマ切り替え（adult/child）** - Webアプリはサイドバーに配置
3. **⚠️ 通知設定（プッシュ通知ON/OFF）** - Webアプリになし
4. **⚠️ アプリ情報** - Webアプリになし
   - バージョン表示
   - プライバシーポリシー
   - 利用規約

**Webアプリとの相違点**:
- Webアプリには専用の「設定画面」が存在しない
- テーマ切り替えはサイドバーに存在（`resources/views/components/layouts/sidebar.blade.php`）

---

## 問題点と対応方針

### ⚠️ 問題1: 自己紹介（bio）フィールド

**現状**:
- Webアプリ: ❌ なし
- モバイルアプリ: ✅ あり（ProfileScreenに実装）
- データベース: 確認が必要

**対応方針**:
1. **即時対応（推奨）**: モバイルアプリから`bio`フィールドを削除
   - 理由: Webアプリと整合性を保つ
   - 影響範囲: ProfileScreen、useProfile Hook、ProfileService、テスト
2. **代替案**: Webアプリに`bio`フィールドを追加
   - 理由: 要件定義書に記載があるか確認
   - 影響範囲: Laravel側の実装が必要

**推奨**: **即時対応（削除）** - Webアプリとの整合性を優先

---

### ⚠️ 問題2: アバター画像アップロード

**現状**:
- Webアプリ: ❌ なし
- モバイルアプリ: ✅ あり（ProfileScreenに実装、expo-image-picker使用）
- API: `/api/v1/profile` がFormDataに対応しているか不明

**対応方針**:
1. **即時対応（推奨）**: モバイルアプリからアバター画像アップロード機能を削除
   - 理由: Webアプリと整合性を保つ
   - 影響範囲: ProfileScreen、useProfile Hook、ProfileService、テスト
2. **代替案**: Webアプリにアバター画像アップロード機能を追加
   - 理由: AI生成アバター機能とは別のユーザープロフィール画像
   - 影響範囲: Laravel側の実装が必要（`resources/views/profile/partials/update-profile-information-form.blade.php`）

**推奨**: **即時対応（削除）** - Webアプリとの整合性を優先

**注意**: AI生成アバター機能（Phase 2.B-7）とは別の機能

---

### ⚠️ 問題3: アカウント削除

**現状**:
- Webアプリ: ✅ あり（別セクション: `resources/views/profile/partials/delete-user-form.blade.php`）
- モバイルアプリ: ✅ あり（ProfileScreenに実装）

**対応方針**:
- ✅ **整合性あり** - 両方に実装されているため問題なし
- ただし、Webアプリは別パーシャルで実装（`/profile/edit`の下部セクション）

---

### ⚠️ 問題4: テーマ切り替え

**現状**:
- Webアプリ: ✅ サイドバーに実装（`resources/views/components/layouts/sidebar.blade.php`）
- モバイルアプリ: ✅ SettingsScreenに実装

**対応方針**:
- ✅ **機能は一致** - 配置場所が異なるが、機能としては問題なし
- モバイルアプリは設定画面に集約する設計として妥当

---

### ⚠️ 問題5: 通知設定・アプリ情報

**現状**:
- Webアプリ: ❌ なし
- モバイルアプリ: ✅ あり（SettingsScreenに実装）

**対応方針**:
- ✅ **モバイル固有機能として妥当**
  - プッシュ通知はモバイル特有の機能
  - アプリ情報（バージョン、利用規約）もモバイル特有
- **要件定義書に明記が必要**
  - `/home/ktr/mtdev/definitions/*.md` に追記

---

### ❌ 問題6: グループ管理リンク（モバイル未実装）

**現状**:
- Webアプリ: ✅ `/profile/edit` にグループ管理リンクあり
- モバイルアプリ: ❌ ProfileScreenになし

**対応方針**:
- **Phase 2.B-3またはPhase 2.B-4で実装予定**（計画書要確認）
- ProfileScreenまたは専用のGroupsScreenに配置

---

### ❌ 問題7: パスワード変更（モバイル未実装）

**現状**:
- Webアプリ: ✅ `/profile/edit` にパスワード変更セクションあり（`resources/views/profile/partials/update-password-form.blade.php`）
- モバイルアプリ: ❌ なし

**対応方針**:
- **Phase 2.B-4またはPhase 2.B-5で実装予定**（計画書要確認）
- SettingsScreenまたはProfileScreenに追加

---

## 推奨アクション（優先順位順）

### 🔴 優先度: 高（即時対応）

1. **`bio`フィールドを削除**
   - 削除対象:
     - `mobile/src/screens/profile/ProfileScreen.tsx` - bioフィールドとState
     - `mobile/src/hooks/useProfile.ts` - bio関連処理
     - `mobile/src/services/profile.service.ts` - bio関連処理
     - `mobile/src/types/user.types.ts` - bio型定義
     - 全テストファイル
   - 理由: Webアプリと整合性がない

2. **アバター画像アップロード機能を削除**
   - 削除対象:
     - `mobile/src/screens/profile/ProfileScreen.tsx` - pickImage()、アバター表示、画像変更ボタン
     - `mobile/src/hooks/useProfile.ts` - アバター関連処理
     - `mobile/src/services/profile.service.ts` - FormData関連処理
     - 全テストファイル
     - `mobile/package.json` - `expo-image-picker` 削除検討（他の画面で使用している場合は保持）
   - 理由: Webアプリと整合性がない

3. **mobile-rules.md更新を完了レポートに反映**
   - `docs/reports/mobile/2025-12-06-phase2-b4-profile-settings-completion-report.md` に追記:
     - 「モバイル固有機能」セクション追加
     - アバター画像アップロード、bio削除の経緯を記載

### 🟡 優先度: 中（Phase 2.B-5前に対応）

4. **グループ管理リンクをモバイルに追加**
   - ProfileScreenまたは専用画面に実装
   - Phase 2.B-3の計画と調整

5. **パスワード変更機能をモバイルに追加**
   - SettingsScreenまたはProfileScreenに実装
   - API: `/api/v1/profile/password` 確認

### 🟢 優先度: 低（Phase 2.B-7以降）

6. **AI生成アバター機能との統合**
   - Phase 2.B-7でAI生成アバターとプロフィール画像の関係を整理
   - ユーザープロフィール画像とAI生成アバターの使い分けを定義

---

## 要件定義書への追記が必要な項目

### `/home/ktr/mtdev/definitions/Task.md` または新規ドキュメント

**追記内容**:

```markdown
## モバイル固有機能

### 通知設定（SettingsScreen）
- プッシュ通知ON/OFF切り替え
- Firebase Cloud Messaging統合
- 理由: モバイル特有のプッシュ通知機能

### アプリ情報（SettingsScreen）
- アプリバージョン表示
- プライバシーポリシーリンク
- 利用規約リンク
- 理由: アプリストア要件対応
```

---

## まとめ

### 整合性チェック結果

| 項目 | 評価 | 備考 |
|------|------|------|
| **基本プロフィール編集** | ✅ 一致 | username、email、name |
| **タイムゾーン設定** | ✅ 一致 | |
| **テーマ切り替え** | ⚠️ 配置相違 | 機能は一致、配置のみ異なる |
| **アカウント削除** | ✅ 一致 | |
| **自己紹介（bio）** | ❌ **モバイル独自** | **削除推奨** |
| **アバター画像アップロード** | ❌ **モバイル独自** | **削除推奨** |
| **通知設定** | ✅ モバイル固有機能 | 要件定義書に明記必要 |
| **アプリ情報** | ✅ モバイル固有機能 | 要件定義書に明記必要 |
| **グループ管理リンク** | ❌ **モバイル未実装** | Phase 2.B-3で実装予定 |
| **パスワード変更** | ❌ **モバイル未実装** | Phase 2.B-5前に実装推奨 |

### 対応が必要な項目（2件）

1. ❌ **bio フィールド削除** - 即時対応
2. ❌ **アバター画像アップロード削除** - 即時対応

---

**作成日**: 2025-12-06  
**作成者**: GitHub Copilot  
**次のアクション**: mobile-rules.md更新完了、bio/アバター削除作業実施
