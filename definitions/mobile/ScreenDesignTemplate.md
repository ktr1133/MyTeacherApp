# {画面名} デザイン要件定義書

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| YYYY-MM-DD | GitHub Copilot | 初版作成: {画面名}デザイン要件 |

---

## 1. 概要

### 対応フェーズ

- Phase 2.B-X: {フェーズ名}

### 対応画面

- **モバイル**: `{ScreenName}.tsx`
- **Web**: `/home/ktr/mtdev/resources/views/{path}/{file}.blade.php`

### 目的

{画面の目的を簡潔に記載}

---

## 2. Webアプリとの対応関係

### 2.1 Web版Bladeファイル

**ファイルパス**: `/home/ktr/mtdev/resources/views/{path}/{file}.blade.php`

**総行数**: {行数}行

**主要セクション**:
| セクション名 | 行番号 | 説明 |
|------------|-------|------|
| ヘッダー | 10-30 | ページタイトル、説明文 |
| メインコンテンツ | 50-200 | {内容} |
| フッター | 220-250 | {内容} |

### 2.2 使用されているコンポーネント

| コンポーネント名 | ファイルパス | 行番号 | 用途 |
|---------------|------------|-------|------|
| `@include('components.xxx')` | `/home/ktr/mtdev/resources/views/components/xxx.blade.php` | 100 | {用途} |

---

## 3. UI要素一覧

### 3.1 ヘッダー

| # | 要素種別 | ラベル/テキスト | Tailwind CSS | Web行番号 | モバイル実装 | 備考 |
|---|---------|---------------|-------------|----------|-----------|------|
| 1 | タイトル | 「{タイトル}」 | `text-2xl font-bold text-gray-900` | 15 | {Component}.tsx | - |
| 2 | 説明文 | 「{説明文}」 | `text-sm text-gray-600 mt-2` | 20 | {Component}.tsx | - |

### 3.2 メインコンテンツ

| # | 要素種別 | ラベル/テキスト | Tailwind CSS | Web行番号 | モバイル実装 | 備考 |
|---|---------|---------------|-------------|----------|-----------|------|
| 3 | ボタン | 「{ボタン名}」 | `bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg` | 50 | Button.tsx | FAB化 |
| 4 | カード | {カード内容} | `bg-white shadow rounded-lg p-4 mb-4` | 80-120 | Card.tsx | - |
| 5 | リスト | {リスト項目} | `space-y-4` | 150-200 | FlatList | 無限スクロール |

### 3.3 フォーム（該当する場合）

| # | 要素種別 | ラベル | 入力タイプ | バリデーション | Web行番号 | モバイル実装 |
|---|---------|-------|----------|--------------|----------|-----------|
| 6 | テキスト入力 | 「{ラベル名}」 | text | 必須、最大255文字 | 100 | TextInput |
| 7 | ドロップダウン | 「{ラベル名}」 | select | 必須 | 120 | Picker |

### 3.4 モーダル・ダイアログ（該当する場合）

| # | 要素種別 | トリガー | 内容 | Web行番号 | モバイル実装 |
|---|---------|---------|------|----------|-----------|
| 8 | 確認ダイアログ | 削除ボタン | 「本当に削除しますか？」 | 200 | Alert.alert() |

---

## 4. Tailwind CSS → React Native StyleSheet 変換表

### 4.1 レイアウト

| Tailwind CSS | React Native StyleSheet | 実装箇所 |
|-------------|------------------------|---------|
| `flex` | `display: 'flex'` | styles.container |
| `flex-col` | `flexDirection: 'column'` | styles.container |
| `justify-between` | `justifyContent: 'space-between'` | styles.header |
| `items-center` | `alignItems: 'center'` | styles.row |
| `gap-4` | `gap: 16` | styles.list |

### 4.2 サイズ・スペーシング

| Tailwind CSS | React Native StyleSheet | 実装箇所 |
|-------------|------------------------|---------|
| `p-4` | `padding: 16` | styles.card |
| `px-6` | `paddingHorizontal: 24` | styles.container |
| `py-2` | `paddingVertical: 8` | styles.button |
| `m-4` | `margin: 16` | styles.section |
| `w-full` | `width: '100%'` | styles.input |

### 4.3 色・背景

| Tailwind CSS | React Native StyleSheet | 実装箇所 |
|-------------|------------------------|---------|
| `bg-blue-600` | `backgroundColor: '#2563EB'` | styles.button |
| `text-white` | `color: '#FFFFFF'` | styles.buttonText |
| `bg-gray-100` | `backgroundColor: '#F3F4F6'` | styles.card |

### 4.4 テキスト

| Tailwind CSS | React Native StyleSheet | 実装箇所 |
|-------------|------------------------|---------|
| `text-2xl` | `fontSize: 24` | styles.title |
| `text-sm` | `fontSize: 14` | styles.caption |
| `font-bold` | `fontWeight: 'bold'` | styles.title |
| `text-center` | `textAlign: 'center'` | styles.heading |

### 4.5 ボーダー・角丸

| Tailwind CSS | React Native StyleSheet | 実装箇所 |
|-------------|------------------------|---------|
| `rounded-lg` | `borderRadius: 8` | styles.card |
| `rounded-full` | `borderRadius: 9999` | styles.badge |
| `border border-gray-300` | `borderWidth: 1, borderColor: '#D1D5DB'` | styles.input |

### 4.6 シャドウ

| Tailwind CSS | React Native StyleSheet | 実装箇所 |
|-------------|------------------------|---------|
| `shadow` | `shadowColor: '#000', shadowOffset: {width: 0, height: 2}, shadowOpacity: 0.1, shadowRadius: 4, elevation: 2` | styles.card |
| `shadow-lg` | `shadowColor: '#000', shadowOffset: {width: 0, height: 4}, shadowOpacity: 0.15, shadowRadius: 8, elevation: 5` | styles.modal |

**注意**: Androidでは`elevation`、iOSでは`shadow*`プロパティを使用。

---

## 5. レスポンシブ対応

### 5.1 画面サイズ対応

**原則**: **Dimensions APIを使用したレスポンシブ対応**を実装する。

**必須参照**: `/home/ktr/mtdev/definitions/mobile/ResponsiveDesignGuideline.md`

```typescript
import { useResponsive, getFontSize, getSpacing } from '@/utils/responsive';
import { useChildTheme } from '@/hooks/useChildTheme';

const MyScreen = () => {
  const { width, deviceSize, isPortrait } = useResponsive();
  const isChildTheme = useChildTheme();
  const theme = isChildTheme ? 'child' : 'adult';
  
  const styles = StyleSheet.create({
    container: {
      padding: getSpacing(16, width),
    },
    title: {
      fontSize: getFontSize(24, width, theme), // 子ども向けは1.2倍
    },
  });
  
  return <View style={styles.container}>...</View>;
};
```

### 5.2 Tailwind CSSブレークポイント対応

| Tailwind CSS | 画面幅 | モバイル実装 |
|-------------|-------|------------|
| （sm:なし） | 0〜639px | **基準デザイン** - Dimensions APIで動的調整 |
| `sm:` | 640px〜 | 対応（タブレット小） |
| `md:` | 768px〜 | 対応（タブレット） |
| `lg:` | 1024px〜 | 対応（タブレット大） |

**注意**: **全ブレークポイントに対応**します。Dimensions APIで画面幅を取得し、動的にスタイルを調整します。

**ブレークポイント定義**（ResponsiveDesignGuideline.md参照）:

| カテゴリ | 画面幅範囲 | 対象デバイス |
|---------|-----------|------------|
| 超小型 | 〜320px | Galaxy Fold、iPhone SE 1st |
| 小型 | 321px〜374px | iPhone SE 2nd、Pixel 4a |
| 標準 | 375px〜413px | iPhone 12、Pixel 7 |
| 大型 | 414px〜767px | iPhone Pro Max |
| タブレット小 | 768px〜1023px | iPad mini |
| タブレット | 1024px〜 | iPad Pro |

---

## 6. アニメーション・インタラクション

### 6.1 画面遷移アニメーション

| 遷移パターン | アニメーション | 実装 |
|------------|--------------|------|
| 画面遷移（Push） | 右からスライドイン | React Navigationデフォルト |
| 画面遷移（戻る） | 左へスライドアウト | React Navigationデフォルト |
| モーダル表示 | 下からスライドアップ | `presentation: 'modal'` |

### 6.2 要素アニメーション

| 要素 | アニメーション | Web版CSS | モバイル実装 |
|-----|--------------|---------|------------|
| ボタン | ホバー時に色変更 | `hover:bg-blue-700` | `onPressIn`時に`opacity: 0.8` |
| カード | タップ時に縮小 | `active:scale-95` | `Animated.spring()`使用 |
| リスト | スクロール時にフェードイン | `transition-opacity` | `FlatList`の`onScroll`で制御 |

### 6.3 ジェスチャー

| ジェスチャー | 動作 | Web版対応 | モバイル実装 |
|------------|------|----------|------------|
| スワイプ右 | 画面戻る | なし | React Navigationデフォルト |
| スワイプ下 | プルダウンリフレッシュ | なし | `RefreshControl`使用 |
| 長押し | コンテキストメニュー表示 | 右クリック相当 | `onLongPress`イベント |

---

## 7. データ取得・表示

### 7.1 API呼び出し

| データ | APIエンドポイント | メソッド | リクエストパラメータ | レスポンス型 |
|-------|----------------|---------|-------------------|-----------|
| {データ名} | `GET /api/{path}` | GET | `page={page}` | `{ResponseType}` |
| {データ名} | `POST /api/{path}` | POST | `{RequestType}` | `{ResponseType}` |

### 7.2 ローディング状態

| 状態 | 表示内容 | Web版実装 | モバイル実装 |
|-----|---------|----------|------------|
| 初回ロード | スケルトンスクリーン | Tailwind CSS `animate-pulse` | `<Loading />` コンポーネント |
| ページング | 下部にスピナー表示 | JavaScript制御 | `FlatList`の`ListFooterComponent` |
| リフレッシュ | プルダウンスピナー | なし | `RefreshControl` |

### 7.3 エラー状態

| エラー種別 | 表示内容 | Web版実装 | モバイル実装 |
|-----------|---------|----------|------------|
| ネットワークエラー | 「ネットワーク接続を確認してください」 | Alertメッセージ | `Alert.alert()` |
| 404エラー | 「データが見つかりません」 | リダイレクト | エラーメッセージ + 画面遷移 |
| 401エラー | 「ログインしてください」 | リダイレクト | ログイン画面へ強制遷移 |

---

## 8. Webアプリとの差分

### 8.1 実装済み機能

| # | 機能 | Web版 | モバイル版 | 備考 |
|---|------|-------|----------|------|
| 1 | {機能名} | ✅ | ✅ | 同等の機能を実装 |

### 8.2 未実装機能

| # | 機能 | Web版 | モバイル版 | 未実装理由 | 実装予定 |
|---|------|-------|----------|----------|---------|
| 1 | {機能名} | ✅ | ❌ | {理由} | Phase X |

### 8.3 モバイル独自機能

| # | 機能 | Web版 | モバイル版 | 追加理由 |
|---|------|-------|----------|---------|
| 1 | {機能名} | ❌ | ✅ | {理由} |

**例**:
- FAB（フローティングアクションボタン）: Webは固定ボタン、モバイルは右下FAB
- スワイプジェスチャー: モバイル特有の操作性向上
- プルダウンリフレッシュ: モバイルの標準UI/UX

---

## 9. パフォーマンス要件

### 9.1 レンダリング速度

| 項目 | 目標値 | 測定方法 |
|-----|-------|---------|
| 初回レンダリング | 1秒以内 | React DevToolsで測定 |
| リスト表示 | 60FPS | `react-native-performance`で測定 |

### 9.2 メモリ使用量

| 項目 | 目標値 | 対策 |
|-----|-------|------|
| 画像キャッシュ | 50MB以内 | `expo-image`のキャッシュ設定 |
| リストアイテム | 画面内+上下10件のみ保持 | `FlatList`の`windowSize: 10` |

---

## 10. テスト要件

### 10.1 単体テスト

```typescript
// {ScreenName}.test.tsx
describe('{ScreenName}', () => {
  it('正常にレンダリングされる', () => {
    // テストコード
  });
  
  it('{機能}が動作する', () => {
    // テストコード
  });
});
```

### 10.2 統合テスト

- [ ] API呼び出しが正常に動作する
- [ ] データが正しく表示される
- [ ] エラー時の表示が正しい

### 10.3 E2Eテスト（Phase 2.B-8）

- [ ] 画面遷移が正常に動作する
- [ ] ユーザー操作フローが完了する

---

## 11. 実装ファイル

### 11.1 画面コンポーネント

```
/home/ktr/mtdev/mobile/src/screens/{category}/{ScreenName}.tsx
```

### 11.2 スタイルシート

```typescript
// {ScreenName}.tsx 内
const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F9FAFB', // bg-gray-50
  },
  // ... 他のスタイル
});
```

### 11.3 サービス層

```
/home/ktr/mtdev/mobile/src/services/{category}.service.ts
```

### 11.4 カスタムフック

```
/home/ktr/mtdev/mobile/src/hooks/use{Category}.ts
```

### 11.5 型定義

```
/home/ktr/mtdev/mobile/src/types/{category}.types.ts
```

### 11.6 テストファイル

```
/home/ktr/mtdev/mobile/__tests__/{category}/{ScreenName}.test.tsx
```

---

## 12. 参考資料

- **Web版Bladeファイル**: `/home/ktr/mtdev/resources/views/{path}/{file}.blade.php`
- **OpenAPI仕様**: `/home/ktr/mtdev/docs/api/openapi.yaml` - `{endpoint}` エンドポイント
- **マイグレーション**: `/home/ktr/mtdev/database/migrations/{migration_file}.php`
- **モデル**: `/home/ktr/mtdev/app/Models/{Model}.php`
- **画面遷移フロー**: `/home/ktr/mtdev/definitions/mobile/NavigationFlow.md`
- **モバイル開発規則**: `/home/ktr/mtdev/docs/mobile/mobile-rules.md`

---

## 13. 質疑応答履歴

### Q1: {質問}
**質問**: {質問内容}  
**回答**: {回答内容}  
**決定事項**: {実装内容}  
**影響範囲**: {変更ファイル}

---

## 14. 実装チェックリスト

- [ ] Bladeファイルを1行目から最終行まで読解した
- [ ] UI要素を構造化リスト（表形式）にまとめた
- [ ] Tailwind CSSクラスをReact Native StyleSheetに変換した
- [ ] レスポンシブ対応（小型デバイス）を実装した
- [ ] Webアプリの全UI要素をモバイル版に実装した
- [ ] Webアプリとの差分を明記した
- [ ] APIエンドポイントを確認した
- [ ] 型定義を作成した
- [ ] サービス層を実装した
- [ ] カスタムフックを実装した
- [ ] 画面コンポーネントを実装した
- [ ] テストファイルを作成した
- [ ] 静的解析（`npx tsc --noEmit`）でエラーがない
- [ ] テスト（`npm test`）が成功する
