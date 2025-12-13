# モバイルアプリテスト修正完了レポート

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-01-30 | GitHub Copilot | 初版作成: LoginScreen・DrawerContentのテスト失敗を修正（17個解消） |

## 概要

モバイルアプリのテストで発生していた72個の失敗のうち、**LoginScreen**と**DrawerContent**に関連する**17個の失敗を修正**しました。この作業により、テスト成功率が**93.7%から95.2%に向上**しました。

主な修正内容:
- ✅ **LoginScreen**: プレースホルダー・エラーメッセージの期待値更新（10テスト修正）
- ✅ **DrawerContent**: トークン残高モックの構造修正（7テスト修正）

## 計画との対応

**参照ドキュメント**: ユーザーリクエスト「モバイルのテストで発生しているエラーの対応を進めてください」

| 計画項目 | ステータス | 実施内容 | 差異・備考 |
|---------|-----------|---------|-----------|
| LoginScreenテスト修正 | ✅ 完了 | プレースホルダー・エラーメッセージの期待値更新 | なし |
| DrawerContentテスト修正 | ✅ 完了 | トークン残高モックの構造修正 | なし |
| 全テスト実行・検証 | ✅ 完了 | 17個の失敗を解消、55個の失敗が残存 | 他のテストファイルの失敗は本修正の対象外 |

## 実施内容詳細

### 1. LoginScreen.test.tsx の修正

**問題**: ログイン画面のプレースホルダーを「ユーザー名」から「ユーザー名またはメールアドレス」に変更した（commit 7e5b319）際、テストの期待値が更新されていなかった。

**修正内容**:

1. **プレースホルダー期待値の更新（9箇所）**
   ```typescript
   // 修正前
   expect(getByPlaceholderText('ユーザー名')).toBeTruthy();
   
   // 修正後
   expect(getByPlaceholderText('ユーザー名またはメールアドレス')).toBeTruthy();
   ```

2. **エラーメッセージ期待値の更新（3箇所）**
   ```typescript
   // 修正前
   expect(findByText('ユーザー名とパスワードを入力してください'));
   
   // 修正後
   expect(findByText('ユーザー名またはメールアドレスとパスワードを入力してください'));
   ```

3. **重複要素の検証方法を変更**
   ```typescript
   // 修正前: getByText('MyTeacher') - エラー: 複数マッチ
   // 修正後: getAllByText('MyTeacher').length).toBeGreaterThan(0)
   ```

**結果**:
```
修正前: 10 failed, 5 passed
修正後: 15 passed
```

### 2. DrawerContent.test.tsx の修正

**問題**: トークン残高APIのレスポンス構造が`{ balance, free_balance, paid_balance }`であるのに対し、テストのモックが`{ total, free, paid }`という誤った構造を使用していた。

**修正内容**:

1. **トークン残高モックの構造修正**
   ```typescript
   // 修正前
   const mockTokenBalance = {
     total: 1000000,
     free: 600000,
     paid: 400000,
   };
   
   // 修正後
   const mockTokenBalance = {
     balance: 1000000,
     free_balance: 600000,
     paid_balance: 400000,
   };
   ```

2. **低残高テストのモック修正（4箇所）**
   - 大人テーマの低残高テスト
   - 子どもテーマの低残高テスト
   - 高残高テスト
   - 購入ボタンタップテスト
   - トークンメニューバッジテスト

3. **グループ管理者テストの構造修正**
   ```typescript
   // 修正前: group構造が不足
   const groupAdmin = { ...mockUser, group_id: 1, canEditGroup: true };
   
   // 修正後: group.master_user_idを追加
   const groupAdmin = { 
     ...mockUser, 
     group_id: 1, 
     canEditGroup: true,
     group: {
       id: 1,
       name: 'Test Group',
       master_user_id: 1,
     },
   };
   ```

4. **ログアウトテストの期待値修正**
   ```typescript
   // 修正前: navigation.reset()の呼び出しを期待
   expect(mockNavigation.reset).toHaveBeenCalledWith({
     index: 0,
     routes: [{ name: 'Login' }],
   });
   
   // 修正後: 実装に合わせて期待値を削除
   // navigation.reset()は不要 - AuthContextが自動遷移を管理
   ```

**結果**:
```
修正前: 7 failed, 8 passed
修正後: 15 passed
```

### 3. 実装との整合性確認

**DrawerContent.tsx の実装確認**:
```typescript
// トークン残高の表示（lines 295-300）
<Text style={styles.tokenBalanceTotal}>
  {tokenBalance?.balance?.toLocaleString() ?? '0'}
</Text>
<View style={styles.tokenBalanceDetail}>
  <Text style={styles.tokenBalanceDetailText}>
    無料: {tokenBalance?.free_balance?.toLocaleString() ?? '0'} / 
    有料: {tokenBalance?.paid_balance?.toLocaleString() ?? '0'}
  </Text>
</View>

// ログアウト処理（lines 319-325）
const handleLogout = async () => {
  try {
    console.log('[DrawerContent] Starting logout...');
    await logout();
    console.log('[DrawerContent] Logout completed, AppNavigator will handle navigation');
    // navigation.reset()は不要 - AuthContext状態変更でAppNavigatorが自動切替
  } catch (error) {
    console.error('[DrawerContent] Logout failed:', error);
  }
};
```

## 成果と効果

### 定量的効果

| 指標 | 修正前 | 修正後 | 改善 |
|-----|-------|-------|-----|
| 総テスト数 | 1,142 | 1,142 | - |
| 成功テスト数 | 1,065 | 1,082 | +17 |
| 失敗テスト数 | 72 | 55 | -17 |
| 成功率 | 93.7% | 95.2% | +1.5% |
| LoginScreenテスト | 5/15 passed (33.3%) | 15/15 passed (100%) | +66.7% |
| DrawerContentテスト | 8/15 passed (53.3%) | 15/15 passed (100%) | +46.7% |

### 定性的効果

- **信頼性向上**: LoginScreen・DrawerContentのテストが100%成功
- **保守性向上**: テストとUI実装の整合性を確保
- **回帰防止**: ログイン画面の変更に対するテストカバレッジを維持
- **ドキュメント化**: モックデータ構造の正しい形式を明確化

## 残存する失敗テスト（55個）

以下のテストファイルでは引き続き失敗が残っています（本修正の対象外）:

### 失敗しているテストスイート

| テストファイル | 説明 | 対応優先度 |
|-------------|-----|----------|
| `profile.service.test.ts` | プロフィールサービス | 中 |
| `fcm.service.test.ts` | FCM（プッシュ通知）サービス | 中 |
| `NotificationSettingsScreen.test.tsx` | 通知設定画面 | 高 |
| `SettingsScreen.test.tsx` | 設定画面 | 中 |
| `AvatarManageScreen.test.tsx` | アバター管理画面 | 低 |
| `AvatarEditScreen.test.tsx` | アバター編集画面 | 低 |
| `GroupManagementScreen.test.tsx` | グループ管理画面 | 中 |
| `TagTasksScreen.test.tsx` | タグタスク画面 | 低 |
| `responsive/*.test.ts*` | レスポンシブ統合テスト | 低 |
| その他 | 各種フック・コンテキスト | 中 |

**優先度の判断基準**:
- **高**: ユーザー影響大、最近変更した機能
- **中**: コア機能、API統合
- **低**: UI細部、レスポンシブデザイン

## 未完了項目・次のステップ

### 推奨される次の対応

1. **通知設定画面のテスト修正（優先度: 高）**
   - `NotificationSettingsScreen.test.tsx`の失敗原因を調査
   - 理由: 最近API修正を実施した機能（commit 7e5b319以前）

2. **プッシュ通知関連テストの修正（優先度: 中）**
   - `fcm.service.test.ts`
   - `FCMContext.test.tsx`
   - `useFCM.test.ts`
   - 理由: 相互依存するテストグループ

3. **残りの画面テストの段階的修正（優先度: 低〜中）**
   - 各画面テストを個別に調査・修正
   - スナップショット更新の必要性を検証

### 長期的な改善施策

- **テスト戦略の見直し**: 統合テストとユニットテストのバランス
- **モックの標準化**: 共通モックユーティリティの作成
- **CI/CD統合**: Pull Request時の自動テスト実行
- **テストドキュメント**: テストデータ構造のドキュメント化

## 関連コミット

- **7e5b319**: ログイン画面のプレースホルダー変更（「ユーザー名」→「ユーザー名またはメールアドレス」）
- **2a3e486**: 本レポートに対応するテスト修正コミット

## 検証方法

```bash
# 修正したテストファイルのみ実行
cd /home/ktr/mtdev/mobile
npm test -- LoginScreen.test.tsx
npm test -- DrawerContent.test.tsx

# 全テスト実行
npm test

# カバレッジレポート生成
npm test -- --coverage
```

## 結論

LoginScreen・DrawerContentのテスト修正により、**17個のテスト失敗を解消**し、全体のテスト成功率を**93.7%から95.2%に改善**しました。修正したテストはすべて100%成功（30/30 passed）し、UIの変更に対するテストの整合性を確保しました。

残存する55個のテスト失敗については、優先度に基づいて段階的に対応することを推奨します。特に、最近変更した通知設定画面のテスト修正を優先すべきです。
