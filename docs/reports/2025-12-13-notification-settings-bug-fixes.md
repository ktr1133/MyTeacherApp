# 通知設定機能の不具合修正レポート

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-13 | GitHub Copilot | 初版作成: 通知設定画面の404エラー・UI更新不具合・タイトル重複の修正完了 |

---

## 概要

MyTeacherモバイルアプリの**通知設定機能における3つの不具合**を修正しました。この作業により、以下の目標を達成しました：

- ✅ **APIエンドポイントエラーの解消**: 404エラーによる通知設定取得失敗を修正
- ✅ **画面更新不具合の解消**: Push通知のON/OFF設定が画面に反映されない問題を修正
- ✅ **UI改善**: タイトル重複を解消し、設定画面への戻るボタンを追加

---

## 発生した不具合

### 不具合1: 通知設定APIが404エラー

**現象**:
```
ERROR  [API] Response error: [AxiosError: Request failed with status code 404]
ERROR  [API] Response error data: {
  "message": "The route api/api/v1/profile/notification-settings could not be found."
}
```

**原因**:
- `API_CONFIG.BASE_URL`が既に`/api`を含んでいる（`https://fizzy-formless-sandi.ngrok-free.dev/api`）
- サービスファイルで`/api/v1/profile/notification-settings`を指定
- 結果: `/api/api/v1/...`という二重プレフィックスになり404エラー

**影響範囲**:
- 通知設定画面への遷移時に設定が取得できない
- 通知設定の更新が不可能

### 不具合2: Push通知設定の画面更新が反映されない

**現象**:
- SwitchコンポーネントでPush通知をONにしても画面が更新されない
- 設定変更後のアラートは表示されるが、Switchの状態が変わらない

**原因**:
- バックエンドのレスポンス構造: `{ success: true, data: {...} }`
- フロントエンドが`response.data`を直接使用（正しくは`response.data.data`）
- `profile.service.ts`は正しく`response.data.data`を使用しているが、`notification-settings.service.ts`のみ誤っていた

**影響範囲**:
- 通知設定の取得時にundefinedを受け取る
- 設定更新時にundefinedを受け取り、画面が更新されない

### 不具合3: タイトルが2か所表示される

**現象**:
- ナビゲーションヘッダーに「通知設定」タイトル
- 画面内にも「通知設定」タイトル（重複）

**原因**:
- `DrawerNavigator.tsx`でヘッダータイトルを設定
- `NotificationSettingsScreen.tsx`でも画面内にタイトルを表示

**影響範囲**:
- UI/UXの低下（冗長な表示）
- 設定画面への戻るボタンがなく、ユーザビリティの問題

---

## 修正内容

### 修正1: APIエンドポイントパスの修正

**ファイル**: `/home/ktr/mtdev/mobile/src/services/notification-settings.service.ts`

**変更内容**:
```typescript
// ❌ 修正前
const response = await api.get<NotificationSettings>('/api/v1/profile/notification-settings');

// ✅ 修正後
const response = await api.get<{ success: boolean; data: NotificationSettings }>('/profile/notification-settings');
```

**修正箇所**:
1. GETエンドポイント: `/api/v1/profile/notification-settings` → `/profile/notification-settings`
2. PUTエンドポイント: `/api/v1/profile/notification-settings` → `/profile/notification-settings`
3. ドキュメントコメント内のエンドポイント記載も修正

**参考実装**:
- `profile.service.ts`, `fcm.service.ts`, `token.service.ts`と同様のパターンに統一

### 修正2: APIレスポンス処理の修正

**ファイル**: `/home/ktr/mtdev/mobile/src/services/notification-settings.service.ts`

**変更内容**:
```typescript
// ❌ 修正前
async getNotificationSettings(): Promise<NotificationSettings> {
  const response = await api.get<NotificationSettings>('/profile/notification-settings');
  return response.data; // ← 誤り: { success: true, data: {...} } が返る
}

// ✅ 修正後
async getNotificationSettings(): Promise<NotificationSettings> {
  const response = await api.get<{ success: boolean; data: NotificationSettings }>('/profile/notification-settings');
  console.log('[NotificationSettingsService] Raw response:', response.data);
  return response.data.data; // ← 正しい: data.data からデータ取得
}
```

**修正箇所**:
1. `getNotificationSettings()`: `response.data` → `response.data.data`
2. `updateNotificationSettings()`: `response.data` → `response.data.data`
3. 型定義を`{ success: boolean; data: NotificationSettings }`に変更
4. デバッグログを追加（レスポンス構造の確認用）

**バックエンド実装の確認**:
```php
// app/Http/Responders/Api/Profile/NotificationSettingsResponder.php
public function success(array $settings): JsonResponse
{
    return response()->json([
        'success' => true,
        'data' => $settings, // ← ここにネストされている
    ], 200);
}
```

### 修正3: UI改善（タイトル重複解消・戻るボタン追加）

#### 3-1. 画面内タイトルの削除

**ファイル**: `/home/ktr/mtdev/mobile/src/screens/settings/NotificationSettingsScreen.tsx`

**変更内容**:
```tsx
// ❌ 修正前
return (
  <ScrollView style={styles.container}>
    <View style={styles.content}>
      {/* ヘッダー */}
      <Text style={styles.title}>
        {theme === 'child' ? 'つうちのせってい' : '通知設定'}
      </Text>
      
      {/* エラー表示 */}
      {error && (
        <View style={styles.errorBanner}>

// ✅ 修正後
return (
  <ScrollView style={styles.container}>
    <View style={styles.content}>
      {/* エラー表示 */}
      {error && (
        <View style={styles.errorBanner}>
```

**削除内容**:
- 画面内の「通知設定」タイトル（192-194行目）
- ヘッダーのみに表示することでUI統一

#### 3-2. ヘッダーに戻るボタンを追加

**ファイル**: `/home/ktr/mtdev/mobile/src/navigation/DrawerNavigator.tsx`

**変更内容**:
```tsx
// ❌ 修正前
<Drawer.Screen
  name="NotificationSettings"
  component={NotificationSettingsScreen}
  options={{
    title: '通知設定',
  }}
/>

// ✅ 修正後
<Drawer.Screen
  name="NotificationSettings"
  component={NotificationSettingsScreen}
  options={({ navigation }) => ({
    title: '通知設定',
    headerLeft: () => (
      <TouchableOpacity
        onPress={() => navigation.navigate('Settings')}
        style={{ marginLeft: 15 }}
      >
        <Text style={{ fontSize: 18, color: '#4F46E5' }}>←</Text>
      </TouchableOpacity>
    ),
  })}
/>
```

**追加内容**:
- ヘッダー左側に「←」ボタンを配置
- タップすると「設定」画面に戻る
- 他の画面（TagDetail、TaskEditなど）と同じ実装パターン

---

## 修正後の動作

### 通常フロー

1. **設定画面から通知設定画面に遷移**
   - ✅ API GET `/profile/notification-settings` が成功（200 OK）
   - ✅ レスポンス: `{ success: true, data: { push_enabled: true, ... } }`
   - ✅ `response.data.data`から正しくデータ取得
   - ✅ 画面に現在の設定が表示される

2. **Push通知をONに切り替え**
   - ✅ 楽観的UI更新: 即座にSwitchがONになる
   - ✅ API PUT `/profile/notification-settings` を呼び出し
   - ✅ レスポンス: `{ success: true, message: "...", data: { push_enabled: true, ... } }`
   - ✅ `response.data.data`から更新後のデータ取得
   - ✅ 画面の状態が正しく更新される
   - ✅ アラート表示: 「Push通知を有効にしました」

3. **設定画面に戻る**
   - ✅ ヘッダー左側の「←」ボタンをタップ
   - ✅ 設定画面に戻る

### エラーハンドリング

- **API失敗時**: 楽観的UI更新をロールバック（元の値に戻す）
- **ネットワークエラー**: エラーアラート表示

---

## 技術的詳細

### APIレスポンス構造の統一

**バックエンド（Laravel）の標準レスポンス形式**:
```json
{
  "success": true,
  "data": { /* 実際のデータ */ },
  "message": "操作が成功しました" // 更新時のみ
}
```

**フロントエンド（React Native）の処理パターン**:
```typescript
// 正しいパターン（profile.service.ts等）
const response = await api.get<{ success: boolean; data: T }>('/endpoint');
return response.data.data;

// 誤ったパターン（修正前のnotification-settings.service.ts）
const response = await api.get<T>('/endpoint');
return response.data; // ← { success: true, data: T } が返るため誤り
```

### レスポンシブ設計の遵守

**参照ドキュメント**: `/home/ktr/mtdev/definitions/mobile/ResponsiveDesignGuideline.md`

- ✅ `useResponsive()`フックを使用（既存実装）
- ✅ `getFontSize()`, `getSpacing()`でスケーリング対応
- ✅ デバイスサイズ（xs, sm, md, lg, tablet-sm, tablet）に応じた表示
- ✅ 縦向き・横向き両対応

### テーマ対応の維持

**大人向け（Adult Theme）**:
- フォント: 標準サイズ（デバイスに応じてスケール）
- 配色: 青系（#3b82f6等）

**子ども向け（Child Theme）**:
- テキスト: ひらがな表示（「つうちのせってい」）
- フォント: 大きめ（1.1x〜1.2x）
- 配色: 明るい色

---

## テスト確認項目

### 機能テスト

- [x] 通知設定画面への遷移が成功する
- [x] 現在の通知設定が正しく表示される
- [x] Push通知全体のON/OFF切り替えが動作する
- [x] カテゴリ別通知設定（タスク、グループ、トークン、システム）が動作する
- [x] 通知音・バイブレーション設定が動作する
- [x] 設定変更後にアラートが表示される
- [x] 戻るボタンで設定画面に戻れる

### UI/UXテスト

- [x] タイトルがヘッダーのみに表示される（重複なし）
- [x] ヘッダーに戻るボタンが表示される
- [x] 戻るボタンのタップで設定画面に遷移する
- [x] 大人向けテーマでの表示が適切
- [x] 子ども向けテーマでの表示が適切（ひらがな表記）

### エラーハンドリングテスト

- [x] API失敗時に楽観的UI更新がロールバックされる
- [x] ネットワークエラー時にエラーアラートが表示される
- [x] 認証エラー時の処理が適切

---

## 影響範囲

### 修正ファイル

1. `/home/ktr/mtdev/mobile/src/services/notification-settings.service.ts`
   - APIエンドポイントパスの修正（2箇所）
   - レスポンス処理の修正（2箇所）
   - 型定義の修正（2箇所）

2. `/home/ktr/mtdev/mobile/src/screens/settings/NotificationSettingsScreen.tsx`
   - 画面内タイトルの削除（3行）

3. `/home/ktr/mtdev/mobile/src/navigation/DrawerNavigator.tsx`
   - ヘッダーに戻るボタン追加（8行）

### 影響を受ける画面

- ✅ 通知設定画面（NotificationSettingsScreen）
- ✅ 設定画面（SettingsScreen）からの遷移

### 影響を受けないコンポーネント

- ❌ 他の設定画面（プロフィール、パスワード変更等）
- ❌ タスク管理機能
- ❌ グループ管理機能
- ❌ 通知一覧画面

---

## 今後の対応事項

### 優先度: 高

なし（すべて修正完了）

### 優先度: 中

1. **統合テストの追加**
   - 通知設定の取得・更新のE2Eテスト
   - レスポンス構造の検証テスト

2. **エラーメッセージの多言語対応**
   - 現在は日本語のみ
   - 英語版アプリ対応時に要対応

### 優先度: 低

3. **楽観的UI更新のアニメーション改善**
   - Switch切り替え時のフィードバック強化
   - ローディング状態の視覚化

4. **通知設定のローカルキャッシュ**
   - AsyncStorageにキャッシュして起動時の読み込み高速化

---

## 参考ドキュメント

| ドキュメント | パス | 参照箇所 |
|------------|------|---------|
| モバイルアプリ開発規則 | `/home/ktr/mtdev/docs/mobile/mobile-rules.md` | Service層・Hook層のメソッド命名規則、エラーハンドリング |
| レスポンシブ設計ガイドライン | `/home/ktr/mtdev/definitions/mobile/ResponsiveDesignGuideline.md` | デバイスサイズ対応、フォントスケーリング |
| プロジェクト開発規則 | `/home/ktr/mtdev/.github/copilot-instructions.md` | 不具合対応方針、コード修正時の遵守事項 |
| Push通知機能要件定義 | `/home/ktr/mtdev/definitions/mobile/PushNotification.md` | 通知設定の仕様、API仕様 |

---

## 結論

**修正完了**: MyTeacherモバイルアプリの通知設定機能における3つの不具合（404エラー、画面更新不具合、タイトル重複）をすべて解消しました。

**動作確認**: 実機テストで以下を確認済み
- ✅ 通知設定の取得・更新が正常に動作
- ✅ Push通知のON/OFF切り替えが画面に即座に反映
- ✅ UIが改善され、ユーザビリティが向上

**次のアクション**: 変更内容をコミットし、プッシュ前のテストレポートと統合して本番環境にデプロイ
