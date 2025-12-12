# Phase 2.B-4 Web/Mobile機能整合性修正 追記レポート

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-06 | GitHub Copilot | bio/avatar削除完了レポート作成 |

---

## 概要

Phase 2.B-4完了後、**mobile-rules.md「Webアプリ機能との整合性」規則**に基づき、Webアプリに存在しない機能（bio、アバター画像アップロード）をモバイルアプリから削除しました。

## 削除理由

**参照**: `docs/mobile/mobile-rules.md` - Section 4「Webアプリ機能との整合性」

**規則**:
> - **基本方針**: モバイル版は **Webアプリと同等の機能** を有すること
> - Webアプリに存在する機能は **すべてモバイル版にも実装**
> - Webアプリに存在しない機能を追加する場合は、**事前に要件定義書に明記**し、承認を得る

**差分検出結果**:
- Webアプリ（`resources/views/profile/edit.blade.php`）を1行目から最終行まで確認
- **結果**: `bio`フィールド、アバター画像アップロードUIは存在しない
- **結論**: モバイルアプリから削除が必要

**参照**: `docs/reports/mobile/2025-12-06-phase2-b4-feature-alignment-check.md`（機能整合性チェック結果）

---

## 実施内容

### 1. ProfileScreen.tsx修正（545行 → 397行、-148行）

**削除内容**:
- expo-image-pickerインポート
- `bio`, `avatarUri`, `selectedImage` state
- `pickImage()` 関数（35行）
- アバターセクションUI（25行）
- bioフィールドUI（20行）
- updateProfile呼び出し時のbio/avatarパラメータ
- アバター関連スタイル（85行）

**コメント更新**:
```diff
/**
 * ProfileScreen - プロフィール画面
 * 
 * 機能:
-- - プロフィール情報表示（ユーザー名、email、bio、アバター）
-- - プロフィール編集（テキスト情報 + アバター画像）
++ - プロフィール情報表示（ユーザー名、email、表示名）
++ - プロフィール編集（テキスト情報のみ）
```

### 2. useProfile.ts修正（192行 → 188行、-4行）

**削除内容**:
```diff
  const updateProfile = useCallback(
    async (data: {
      username?: string;
      email?: string;
      name?: string;
--    bio?: string;
--    avatar?: { uri: string; type: string; name: string; };
    }): Promise<ProfileResponse['data']> => {
```

### 3. profile.service.ts修正（180行 → 140行、-40行）

**削除内容**:
- FormData処理全体（bio, avatar appendロジック）
- `multipart/form-data` ヘッダー

**修正後**:
```typescript
async updateProfile(data: {
  username?: string;
  email?: string;
  name?: string;
}): Promise<ProfileResponse['data']> {
  try {
    const response = await api.patch<ProfileResponse>('/profile', data);
    // FormData削除、JSON形式に変更
  }
}
```

### 4. user.types.ts修正

**削除内容**:
```diff
  export interface User {
    id: number;
    username: string;
    name: string | null;
    email: string;
    avatar_path: string | null;
    avatar_url?: string | null;
--  bio: string | null;
    timezone: string;
```

### 5. テストファイル修正（5ファイル）

**ProfileScreen.test.tsx**:
- expo-image-pickerインポート削除
- mockImagePicker削除
- mockProfileからbio削除
- 画像選択関連テスト2件削除（`画像選択権限がない場合〜`, `画像を選択できる`）
- updateProfile呼び出し期待値からbio/avatar削除

**useProfile.test.ts**:
- mockProfileからbio削除

**profile.service.test.ts**:
- mockUserからbio削除
- updateProfileテストでFormData期待値をJSON形式に変更

---

## 品質保証

### TypeScript型チェック

```bash
$ cd /home/ktr/mtdev/mobile && npx tsc --noEmit
# 0 errors ✅
```

**経過**:
1. 初回エラー: useProfile.ts - useCallback構文エラー → 修正
2. 2回目エラー: user.types.ts - bio未削除 → 削除
3. 3回目エラー: テストファイル2件 - mockUserにbio残存 → 削除
4. **最終結果**: 0エラー ✅

### テスト実行結果

```bash
$ cd /home/ktr/mtdev/mobile && npm test

Test Suites: 10 passed, 10 total
Tests:       157 passed, 157 total  # ← 159から157に減少（bio/avatar関連テスト2件削除）
Snapshots:   0 total
Time:        1.907 s
Ran all test suites.
```

**テスト削減内訳**:
- ProfileScreen.test.tsx: -2テスト（画像選択権限、画像選択）
- 合計: 159 → 157テスト

### expo-image-picker依存関係

**結果**: パッケージは保持（削除不要）

**理由**:
```bash
$ grep -r "expo-image-picker" mobile/src/**
mobile/src/screens/tasks/TaskDetailScreen.tsx:23:import * as ImagePicker from 'expo-image-picker';
```

TaskDetailScreenでタスク画像アップロードに使用中のため、依存関係を保持。

---

## ファイル変更サマリー

| ファイル | 変更前 | 変更後 | 差分 | 内容 |
|---------|--------|--------|------|------|
| ProfileScreen.tsx | 545行 | 397行 | -148行 | bio UI、pickImage()、アバターセクション削除 |
| useProfile.ts | 192行 | 188行 | -4行 | bio/avatarパラメータ削除 |
| profile.service.ts | 180行 | 140行 | -40行 | FormData削除、JSON形式に変更 |
| user.types.ts | 1箇所 | 0箇所 | -1行 | bio削除 |
| ProfileScreen.test.tsx | 11テスト | 9テスト | -2テスト | 画像選択テスト削除、bio削除 |
| useProfile.test.ts | 修正 | 修正 | mockProfile bio削除 |
| profile.service.test.ts | 修正 | 修正 | mockUser bio削除、FormData期待値削除 |

**合計**: 7ファイル修正、-195行、-2テスト

---

## 参照ドキュメント

- **mobile-rules.md**: `/home/ktr/mtdev/docs/mobile/mobile-rules.md` - Section 4「Webアプリ機能との整合性」
- **機能整合性チェック**: `/home/ktr/mtdev/docs/reports/mobile/2025-12-06-phase2-b4-feature-alignment-check.md`
- **Phase 2.B-4完了レポート**: `/home/ktr/mtdev/docs/reports/mobile/2025-12-06-phase2-b4-profile-settings-completion-report.md`
- **Webアプリ**: `/home/ktr/mtdev/resources/views/profile/edit.blade.php`

---

## 次のステップ

### 高優先度（Phase 2.B-5前）

- [ ] **グループ管理リンク追加**: ProfileScreenに`route('group.edit')`相当のリンクを追加（Webアプリに存在）
- [ ] **パスワード変更機能追加**: SettingsScreenまたは専用画面に実装（Webアプリに存在）

### 今後の改善

- mobile-rules.mdの差分検出手順の有効性を検証
- 次回Phase実装時は**実装前に**差分検出を徹底実施

---

**作成者**: GitHub Copilot  
**作成日**: 2025年12月6日
