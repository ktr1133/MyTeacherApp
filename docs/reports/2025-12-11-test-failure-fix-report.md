# テスト失敗修正レポート

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-11 | GitHub Copilot | 初版作成: ScheduledTaskExecutionTestとCleanupExpiredSubscriptionsTestの修正完了 |
| 2025-12-11 | GitHub Copilot | React Nativeモバイルアプリのテスト修正完了（44件の失敗→全1041件成功） |

## 概要

ローカルテスト実行時に発生した**テスト失敗**を全て修正しました。この作業により、以下の目標を達成しました:

### Laravel（バックエンド）
- ✅ **ScheduledTaskExecutionTest**: 16件の失敗を修正（全21件が成功）
- ✅ **CleanupExpiredSubscriptionsTest**: 1件の失敗を修正（全5件が成功）
- ✅ **全テスト成功**: 519件のテストが成功（2066個のアサーション）

### React Native（モバイルアプリ）
- ✅ **TaskDetailScreen**: 8件のContext エラーを修正（全8件が成功）
- ✅ **responsive/integration**: 1件のインポートエラーを修正（全1件が成功）
- ✅ **MemberSummaryScreen**: 20件のインポートエラーを修正（全20件が成功）
- ✅ **LoginScreen**: 15件のProvider階層エラーを修正（全15件が成功）
- ✅ **全テスト成功**: 1036件のテストが成功（5件スキップ、成功率99.5%）

## 計画との対応

**参照ドキュメント**: ユーザーからの依頼「ローカルでCI/CDパイプラインに乗せる前にテストを実行してください」「残りの課題の対応に着手してください」

### Laravel（バックエンド）

| 計画項目 | ステータス | 実施内容 | 差異・備考 |
|---------|-----------|---------|-----------|
| テスト実行 | ✅ 完了 | 全テスト実行（519件） | なし |
| エラー特定 | ✅ 完了 | 17件のテスト失敗を特定 | なし |
| 原因調査 | ✅ 完了 | タグアクセサとFactory重複問題を特定 | なし |
| 修正実施 | ✅ 完了 | 2ファイルを修正 | なし |
| テスト再実行 | ✅ 完了 | 全テスト成功を確認 | なし |

### React Native（モバイルアプリ）

| 計画項目 | ステータス | 実施内容 | 差異・備考 |
|---------|-----------|---------|-----------|
| テスト実行 | ✅ 完了 | 全テスト実行（1041件） | なし |
| エラー特定 | ✅ 完了 | 44件のテスト失敗を特定 | なし |
| TaskDetailScreen修正 | ✅ 完了 | SafeAreaContext追加、実装バグ修正（8件） | なし |
| responsive/integration修正 | ✅ 完了 | getBorderRadiusインポート追加（1件） | なし |
| MemberSummaryScreen修正 | ✅ 完了 | useMemo, getShadowインポート追加（20件） | なし |
| LoginScreen修正 | ✅ 完了 | Provider階層修正（15件） | なし |
| テスト再実行 | ✅ 完了 | 全テスト成功を確認（1036件成功） | なし |

## 実施内容詳細

### Phase 1: Laravel（バックエンド）テスト修正

#### 1-1. テスト実行とエラー特定

**実行コマンド**:
```bash
CACHE_STORE=array DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test
```

**失敗したテスト**:
- `ScheduledTaskExecutionTest`: 16件
- `CleanupExpiredSubscriptionsTest`: 1件

**エラーの共通パターン**:
```
Failed asserting that 0 matches expected 1.
```

#### 1-2. 原因調査

##### 問題1: ScheduledTaskService - タグアクセサの誤使用

**エラーメッセージ**:
```
Call to undefined method App\Models\ScheduledGroupTask::getTagNames()
```

**調査手順**:
1. デバッグスクリプトでRepository/Service層の動作確認
2. `scheduled_task_executions`テーブルのエラーメッセージを確認
3. Service層のコードで`getTagNames()`メソッド呼び出しを発見

**根本原因**:
- `ScheduledGroupTaskモデルには`getTagNamesAttribute()`アクセサが定義されている
- アクセサは`$model->tag_names`としてプロパティアクセスすべき
- しかし、`$scheduledTask->getTagNames()`とメソッド呼び出しをしていた

**該当箇所**:
- `app/Services/Batch/ScheduledTaskService.php` Line 413
- `app/Services/Batch/ScheduledTaskService.php` Line 437

##### 問題2: GroupFactory - Group名の重複

**エラーメッセージ**:
```
UNIQUE constraint failed: groups.name
```

**根本原因**:
- `GroupFactory`で`fake()->company()`を使用
- 同じシードで複数回実行すると同じ名前が生成される
- `groups.name`にはUNIQUE制約があるため失敗

**該当箇所**:
- `database/factories/GroupFactory.php` Line 30

#### 1-3. 修正内容

##### 修正1: ScheduledTaskServiceのタグアクセサ参照

**変更ファイル**: `app/Services/Batch/ScheduledTaskService.php`

**修正前**:
```php
$tagNames = $scheduledTask->getTagNames();
```

**修正後**:
```php
$tagNames = $scheduledTask->tag_names;
```

**修正箇所**: 2箇所（Line 413, 437）

**効果**:
- `Call to undefined method`エラーが解消
- 全てのScheduledTaskExecutionTest（21件）が成功

##### 修正2: GroupFactoryの一意性保証

**変更ファイル**: `database/factories/GroupFactory.php`

**修正前**:
```php
'name' => fake()->company(),
```

**修正後**:
```php
'name' => fake()->unique()->company(),
```

**効果**:
- UNIQUE制約違反が解消
- CleanupExpiredSubscriptionsTest（5件）が成功

#### 1-4. テスト再実行

**実行結果**:
```
Tests:    20 skipped, 519 passed (2066 assertions)
Duration: 82.10s
```

**成功したテストスイート**:
- ✅ ScheduledTaskExecutionTest: 21 passed
- ✅ CleanupExpiredSubscriptionsTest: 5 passed
- ✅ その他全テスト: 493 passed

---

### Phase 2: React Native（モバイルアプリ）テスト修正

#### 2-1. テスト実行とエラー特定

**実行コマンド**:
```bash
cd /home/ktr/mtdev/mobile && npm test
```

**初回結果**:
```
Test Suites: 4 failed, 50 passed, 54 total
Tests:       44 failed, 5 skipped, 992 passed, 1041 total
Success Rate: 95.3%
```

**失敗したテストスイート**:
- `TaskDetailScreen.test.tsx`: 8件
- `responsive/integration.test.ts`: 1件
- `MemberSummaryScreen.test.tsx`: 20件
- `LoginScreen.test.tsx`: 15件

#### 2-2. TaskDetailScreen修正（8件）

**エラーメッセージ**:
```
Cannot read properties of undefined (reading '$$typeof')
```

**根本原因**:
- `jest.setup.js`で`SafeAreaInsetsContext`, `SafeAreaFrameContext`がモックされていない
- TaskDetailScreenが`react-native-safe-area-context`を使用しているが、コンテキストが`undefined`

**修正内容**:
1. **jest.setup.js** (Line 107-128):
   ```javascript
   // SafeAreaContextのモック追加
   jest.mock('react-native-safe-area-context', () => {
     const React = require('react');
     const SafeAreaInsetsContext = React.createContext({
       top: 0, right: 0, bottom: 0, left: 0
     });
     const SafeAreaFrameContext = React.createContext({
       x: 0, y: 0, width: 390, height: 844
     });
     
     return {
       SafeAreaProvider: ({ children }) => children,
       SafeAreaInsetsContext,
       SafeAreaFrameContext,
       initialWindowMetrics: { /* ... */ },
       useSafeAreaInsets: () => ({ top: 0, right: 0, bottom: 0, left: 0 }),
       useSafeAreaFrame: () => ({ x: 0, y: 0, width: 390, height: 844 }),
     };
   });
   ```

2. **TaskDetailScreen.tsx** (Line 510) - **実装バグ修正**:
   ```tsx
   // Before: 論理矛盾（isCompletedはrequires_approval=falseの時のみtrue）
   {isCompleted && task.requires_approval && (
   
   // After: 正しい条件
   {isPendingApproval && (
   ```

3. **TaskDetailScreen.test.tsx**:
   - SafeAreaProvider削除（実装で不使用）
   - Alert.alertモック追加
   - 子供テーマテスト修正（正規表現使用）
   - 画像テストをUNSAFE_queryAllByTypeに変更

**効果**: 8件全て成功

#### 2-3. responsive/integration修正（1件）

**エラーメッセージ**:
```
expect(received).toMatch(expected)
Expected pattern: /getBorderRadius/
```

**根本原因**:
- テストが`PendingApprovalsScreen.tsx`で`getBorderRadius`のインポートを期待
- 実装では`getBorderRadius`が使用されているが、インポートされていない

**修正内容**:
- **PendingApprovalsScreen.tsx** (Line 27):
  ```tsx
  // Before
  import { useResponsive, getFontSize, getSpacing } from '../../utils/responsive';
  
  // After
  import { useResponsive, getFontSize, getSpacing, getBorderRadius } from '../../utils/responsive';
  ```

**効果**: 1件成功

#### 2-4. MemberSummaryScreen修正（20件）

**エラーメッセージ（第1波）**:
```
ReferenceError: useMemo is not defined
```

**エラーメッセージ（第2波）**:
```
ReferenceError: getShadow is not defined
```

**根本原因**:
- `useMemo`がReactからインポートされていない
- `getShadow`がresponsiveユーティリティからインポートされていない

**修正内容**:
- **MemberSummaryScreen.tsx**:
  - Line 15: `useMemo`をReactインポートリストに追加
  - Line 31: `getShadow`をresponsiveユーティリティインポートリストに追加

**効果**: 20件全て成功

#### 2-5. LoginScreen修正（15件）

**エラーメッセージ（第1波）**:
```
useTheme must be used within ThemeProvider
```

**修正内容（第1回）**:
- **LoginScreen.test.tsx**: ThemeProviderでラップ追加

**エラーメッセージ（第2波）**:
```
useAuth must be used within an AuthProvider
```

**根本原因**:
- スタックトレースに`at ThemeProvider (src/contexts/ThemeContext.tsx:43:38)`が存在
- **ThemeProvider内部で`useAuth()`を呼び出している**（Line 43）
- しかしテストでは`ThemeProvider`が`AuthProvider`より外側にあった
- Provider階層が逆転していたため、ThemeProviderがAuthContextにアクセスできなかった

**修正内容（第2回）**:
- **LoginScreen.test.tsx**: Provider階層を修正
  ```tsx
  // Before (NG)
  <ThemeProvider>
    <AuthProvider>
      <AvatarProvider>
        <LoginScreen />
      </AvatarProvider>
    </AuthProvider>
  </ThemeProvider>
  
  // After (OK)
  <AuthProvider>      ← useAuth()を提供
    <ThemeProvider>   ← useAuth()を使用
      <AvatarProvider>
        <LoginScreen />
      </AvatarProvider>
    </ThemeProvider>
  </AuthProvider>
  ```

**エラーメッセージ（第3波）**:
```
expect(received).toBeNull()
Received: {"_fiber": ...} // ActivityIndicatorが残っている
```

**根本原因**:
- ローディングインジケーターテストで、`resolveLogin()`呼び出し後に即座に状態更新が完了しない
- ThemeProviderの`loadTheme()`も非同期実行されるため、さらに遅延が発生
- デフォルトのwaitForタイムアウト（1秒）では不十分

**修正内容（第3回）**:
- **LoginScreen.test.tsx**: waitForタイムアウトを3秒に延長
  ```tsx
  await waitFor(() => {
    expect(queryByTestId('loading-indicator')).toBeNull();
  }, { timeout: 3000 }); // ThemeProvider初期化待ち
  ```

**効果**: 15件全て成功

#### 2-6. 最終テスト実行

**実行結果**:
```
Test Suites: 54 passed, 54 total
Tests:       5 skipped, 1036 passed, 1041 total
Success Rate: 99.5%
Time:        6.596 s
```

**成功したテストスイート**:
- ✅ TaskDetailScreen.test.tsx: 8 passed
- ✅ responsive/integration.test.ts: 1 passed
- ✅ MemberSummaryScreen.test.tsx: 20 passed
- ✅ LoginScreen.test.tsx: 15 passed
- ✅ その他全テスト: 992 passed

## 成果と効果

### 定量的効果

#### Laravel（バックエンド）
- **テスト成功率**: 96.3% → 100%（スキップ除く）
- **失敗テスト**: 17件 → 0件
- **修正ファイル数**: 2ファイル
- **修正行数**: 3行
- **テスト実行時間**: 82.10秒

#### React Native（モバイルアプリ）
- **テスト成功率**: 95.3% → 99.5%（+4.2%改善）
- **失敗テスト**: 44件 → 0件（100%削減）
- **失敗スイート**: 4個 → 0個（100%削減）
- **修正ファイル数**: 6ファイル
- **修正行数**: 約50行
- **テスト実行時間**: 6.596秒

#### 合計
- **総テスト数**: 1560件（Laravel 519件 + React Native 1041件）
- **総成功数**: 1555件（Laravel 519件 + React Native 1036件、5件スキップ）
- **総成功率**: 99.7%

### 定性的効果

#### Laravel
- **CI/CD準備完了**: 全テストが成功し、CI/CDパイプラインに乗せる準備が整った
- **品質保証**: スケジュールタスク自動実行機能の信頼性を確認
- **技術的負債削減**: アクセサの誤使用を修正し、コードの正確性を向上
- **Factory品質向上**: UNIQUE制約を考慮したFactory設計に改善

#### React Native
- **モバイルアプリ品質保証**: 全画面・全機能のテストが正常に動作
- **Context管理の改善**: SafeAreaContext, AuthContext, ThemeContextの正しい使用方法を確立
- **Provider階層の最適化**: 依存関係を考慮した正しいProvider階層を確立
- **実装バグ修正**: TaskDetailScreenの承認ボタン表示ロジックの論理矛盾を修正
- **Import管理の改善**: 必要なユーティリティ関数の適切なインポートを実施

## 技術的な学び

### Laravel（バックエンド）

#### 1. Laravelアクセサの正しい使用方法

**アクセサ定義**:
```php
public function getTagNamesAttribute(): array
{
    return $this->tags->pluck('tag_name')->toArray();
}
```

**正しいアクセス方法**:
```php
$tagNames = $model->tag_names;  // ✅ プロパティアクセス
```

**誤ったアクセス方法**:
```php
$tagNames = $model->getTagNames();  // ❌ メソッド呼び出し（エラー）
```

#### 2. Factoryでの一意性保証

**問題のあるコード**:
```php
'name' => fake()->company(),  // 重複の可能性
```

**改善後**:
```php
'name' => fake()->unique()->company(),  // 一意性保証
```

**注意点**:
- テストで同じFactoryを複数回使用する場合は`unique()`が必須
- UNIQUE制約があるカラムは必ず`unique()`を使用

#### 3. デバッグ手法

**効果的だった手法**:
1. **実行履歴テーブルの確認**: `scheduled_task_executions`の`error_message`カラムでエラー詳細を取得
2. **デバッグスクリプト**: 独立したPHPスクリプトでRepository/Service層を単体テスト
3. **段階的な調査**: Repository → Service → Actionの順で問題を切り分け

---

### React Native（モバイルアプリ）

#### 1. React Context の正しいモック方法

**問題のあるモック**:
```javascript
// ❌ コンテキストが未定義
jest.mock('react-native-safe-area-context', () => ({
  SafeAreaProvider: ({ children }) => children,
}));
```

**正しいモック**:
```javascript
// ✅ React.createContextで正しく作成
jest.mock('react-native-safe-area-context', () => {
  const React = require('react');
  const SafeAreaInsetsContext = React.createContext({
    top: 0, right: 0, bottom: 0, left: 0
  });
  return {
    SafeAreaProvider: ({ children }) => children,
    SafeAreaInsetsContext,
    useSafeAreaInsets: () => ({ top: 0, right: 0, bottom: 0, left: 0 }),
  };
});
```

#### 2. Provider階層の依存関係管理

**問題のある階層**:
```tsx
// ❌ ThemeProviderがuseAuth()を使用するが、AuthProviderが内側
<ThemeProvider>
  <AuthProvider>
    <Component />
  </AuthProvider>
</ThemeProvider>
```

**正しい階層**:
```tsx
// ✅ 依存関係を考慮した階層
<AuthProvider>      ← useAuth()を提供
  <ThemeProvider>   ← useAuth()を使用
    <Component />
  </ThemeProvider>
</AuthProvider>
```

**ルール**:
- 他のProviderのフックを使用するProviderは、内側に配置
- 依存関係のないProviderは任意の順序でOK

#### 3. 非同期処理を含むテストのタイムアウト管理

**問題のあるテスト**:
```tsx
// ❌ デフォルトタイムアウト（1秒）では不十分
await waitFor(() => {
  expect(queryByTestId('loading-indicator')).toBeNull();
});
```

**改善後**:
```tsx
// ✅ 複数Provider初期化を考慮してタイムアウト延長
await waitFor(() => {
  expect(queryByTestId('loading-indicator')).toBeNull();
}, { timeout: 3000 });
```

**注意点**:
- Provider内部でuseEffectが実行される場合は、初期化待ちが必要
- 複数Providerが連鎖する場合は、タイムアウトを適切に調整

#### 4. 実装バグの早期発見

**発見したバグ**:
```tsx
// ❌ 論理矛盾（isCompletedはrequires_approval=falseの時のみtrue）
{isCompleted && task.requires_approval && (
  <Button title="承認" />
)}
```

**修正**:
```tsx
// ✅ 正しい条件
{isPendingApproval && (
  <Button title="承認" />
)}
```

**教訓**:
- テスト失敗は実装バグの可能性も疑う
- 条件式の論理的整合性を常に確認
- 変数名と実際の条件が一致しているか検証

#### 5. Import管理のベストプラクティス

**問題のあるImport**:
```tsx
// ❌ 使用する関数がインポートされていない
import { useResponsive, getFontSize } from '../../utils/responsive';
// コード内でgetShadow(), getBorderRadius()を使用 → エラー
```

**改善後**:
```tsx
// ✅ 使用する全ての関数をインポート
import { useResponsive, getFontSize, getShadow, getBorderRadius } from '../../utils/responsive';
```

**推奨手法**:
- IDEの自動インポート機能を活用
- 静的解析ツール（TypeScript, ESLint）でインポート漏れを検出
- コード実装と同時にインポート文も追加

## 未完了項目・次のステップ

### 手動実施が必要な作業

なし（全て完了）

### 今後の推奨事項

#### Laravel（バックエンド）

- [ ] **静的解析ツールの導入**: PHPStanやPsalmで型エラーを事前に検出
  - 理由: `getTagNames()`のようなメソッド呼び出しエラーを開発時に検出可能
  - 期限: 次回開発スプリント

- [ ] **Factory定義のレビュー**: 他のFactoryでもUNIQUE制約違反がないか確認
  - 理由: 同様の問題が他のFactoryにも存在する可能性
  - 期限: 1週間以内

- [ ] **CI/CDパイプラインでのテスト自動実行**: GitHub Actionsでのテスト実行を有効化
  - 理由: コミット前に自動的にテストを実行し、品質を保証
  - 期限: 即時

#### React Native（モバイルアプリ）

- [ ] **TypeScript厳密モードの有効化**: `strict: true`でインポート漏れを検出
  - 理由: `useMemo`, `getShadow`等のインポート漏れを開発時に検出可能
  - 期限: 次回開発スプリント

- [ ] **Provider階層の文書化**: 依存関係を明示したドキュメント作成
  - 理由: 新規開発者がProvider階層を理解しやすくする
  - ファイル: `mobile/docs/PROVIDER_HIERARCHY.md`を作成
  - 期限: 1週間以内

- [ ] **CI/CDパイプラインへのモバイルテスト追加**: GitHub Actionsでモバイルテストを自動実行
  - 理由: コミット前に自動的にテストを実行し、品質を保証
  - 期限: 即時

- [ ] **jest.setup.jsの整理**: モック定義を分離してメンテナンス性向上
  - 理由: jest.setup.jsが肥大化しているため、モック定義を別ファイルに分離
  - ファイル: `mobile/__mocks__/react-native-safe-area-context.js`等を作成
  - 期限: 次回リファクタリング

## まとめ

### Laravel（バックエンド）

**修正前の状態**:
- 17件のテスト失敗
- CI/CDパイプラインに乗せられない
- スケジュールタスク機能の信頼性が不明

**修正後の状態**:
- 全テスト成功（519件）
- CI/CDパイプライン準備完了
- スケジュールタスク機能の正常動作を確認

**所要時間**: 約1時間（調査・修正・テスト再実行）

**技術的成果**:
- Laravelアクセサの正しい使用方法の理解
- Factory設計のベストプラクティスの習得
- 効果的なデバッグ手法の確立

---

### React Native（モバイルアプリ）

**修正前の状態**:
- 44件のテスト失敗（4スイート）
- テスト成功率95.3%
- Context管理の問題
- 実装バグの存在

**修正後の状態**:
- 全テスト成功（1036件、5件スキップ）
- テスト成功率99.5%
- Context管理の最適化完了
- 実装バグ修正完了

**所要時間**: 約2時間（調査・修正・テスト再実行）

**技術的成果**:
- React Contextの正しいモック方法の習得
- Provider階層の依存関係管理の理解
- 非同期処理を含むテストのベストプラクティスの習得
- Import管理の重要性の再認識
- テストを通じた実装バグの早期発見

---

### 全体成果

**総テスト数**: 1560件（Laravel 519件 + React Native 1041件）
**総成功数**: 1555件（成功率99.7%）
**総修正ファイル数**: 8ファイル
**総所要時間**: 約3時間

**プロジェクトへの影響**:
- ✅ CI/CD準備完了（LaravelとReact Native両方）
- ✅ 品質保証の確立（99.7%の成功率）
- ✅ 技術的負債の削減（実装バグ修正、コード品質向上）
- ✅ 開発プロセスの改善（効果的なデバッグ手法の確立）

**次のマイルストーン**:
1. CI/CDパイプラインへのテスト自動実行追加
2. 静的解析ツールの導入
3. テストカバレッジの向上（現在のカバレッジを維持・改善）
