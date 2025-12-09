# レスポンシブ対応テスト

## 概要

MyTeacherモバイルアプリの**レスポンシブデザイン実装**に関する包括的なテストスイート。

- **対象**: 全32画面のレスポンシブ対応
- **準拠ドキュメント**: `/home/ktr/mtdev/definitions/mobile/ResponsiveDesignGuideline.md`
- **完了レポート**: `/home/ktr/mtdev/docs/reports/2025-12-09-responsive-completion-report.md`

---

## テストファイル構成

```
mobile/__tests__/responsive/
├── integration.test.ts              # 統合テスト（全画面検証）
├── screen-responsive.test.tsx       # 画面コンポーネントのレスポンシブ対応テスト
└── README.md                        # このファイル

mobile/src/utils/__tests__/
└── responsive.test.ts               # responsive.ts ユーティリティのテスト
```

---

## テストカバレッジ

### 1. ユーティリティ関数テスト (`src/utils/__tests__/responsive.test.ts`)

**テスト対象**: `src/utils/responsive.ts`

- **getDeviceSize()**: デバイスサイズカテゴリ判定（6カテゴリ: xs/sm/md/lg/tablet-sm/tablet）
- **getFontSize()**: フォントサイズスケーリング（大人: 0.80x〜1.15x、子ども: 大人×1.20）
- **getSpacing()**: 余白スケーリング（0.75x〜1.30x、最小50%保証）
- **getBorderRadius()**: 角丸スケーリング（0.80x〜1.15x）
- **getShadow()**: プラットフォーム別シャドウ（iOS: shadow*, Android: elevation）
- **useResponsive()**: カスタムフック（width, height, deviceSize, isPortrait, isLandscape）

**テストケース数**: 50+

**カバレッジ目標**: 100%

### 2. 画面コンポーネントテスト (`__tests__/responsive/screen-responsive.test.tsx`)

**テスト対象**: 実際の画面コンポーネント

- **デバイスサイズ別レンダリング**: 8デバイス × 複数画面
  - Galaxy Fold (280px)
  - iPhone SE 1st (320px)
  - iPhone SE 2nd/3rd (375px)
  - iPhone 12/13/14 (390px)
  - Pixel 7 (412px)
  - iPhone Pro Max (430px)
  - iPad mini (768px)
  - iPad Pro (1024px)

- **画面回転対応**: 縦向き ⇔ 横向き切り替え
- **テーマ別レンダリング**: 大人向け ⇔ 子ども向け切り替え
- **スタイル動的生成**: createStyles(width) パターン、useMemo 最適化
- **極端なデバイスサイズ**: 200px〜1600px
- **パフォーマンス検証**: useMemo による再計算の最適化

**テストケース数**: 30+

### 3. 統合テスト (`__tests__/responsive/integration.test.ts`)

**テスト対象**: プロジェクト全体のレスポンシブ対応完了検証

- **responsive.ts の存在確認**: 必須関数・型定義のエクスポート確認
- **全画面ファイルのレスポンシブ対応確認**:
  - 32画面すべてで createStyles(width) パターン使用
  - useResponsive() インポート
  - getFontSize, getSpacing, getBorderRadius, getShadow 使用
  - 静的 StyleSheet.create の不使用確認
- **ResponsiveDesignGuideline.md の遵守確認**: ブレークポイント、スケーリング係数
- **TypeScript型エラー確認**: `npx tsc --noEmit`
- **インポートパスの正確性確認**: utils/responsive.ts からのインポート（過去のバグ修正検証）
- **完了レポートの存在確認**: docs/reports/ に完了レポート存在

**テストケース数**: 100+ (全32画面 × 複数項目)

---

## テスト実行方法

### 全テスト実行

```bash
cd /home/ktr/mtdev/mobile
npm test
```

### レスポンシブ関連テストのみ実行

```bash
# ユーティリティ関数テスト
npm test -- src/utils/__tests__/responsive.test.ts

# 画面コンポーネントテスト
npm test -- __tests__/responsive/screen-responsive.test.tsx

# 統合テスト
npm test -- __tests__/responsive/integration.test.ts
```

### カバレッジレポート生成

```bash
npm test -- --coverage --collectCoverageFrom='src/utils/responsive.ts'
```

### 特定のテストケースのみ実行

```bash
# getDeviceSize() のテストのみ
npm test -- src/utils/__tests__/responsive.test.ts -t "getDeviceSize"

# デバイスサイズ別レンダリングのみ
npm test -- __tests__/responsive/screen-responsive.test.tsx -t "デバイスサイズ別レンダリング"
```

### ウォッチモード（開発中）

```bash
npm test -- --watch src/utils/__tests__/responsive.test.ts
```

---

## テスト結果の見方

### 成功例

```
 PASS  src/utils/__tests__/responsive.test.ts
  responsive.ts - レスポンシブデザインユーティリティ
    getDeviceSize()
      ✓ 超小型デバイス (〜320px) を正しく判定する (3 ms)
      ✓ 小型デバイス (321px〜374px) を正しく判定する (1 ms)
      ✓ 標準デバイス (375px〜413px) を正しく判定する (1 ms)
    getFontSize()
      大人向けテーマ (adult)
        ✓ 超小型デバイス (xs) で0.80倍にスケールする (1 ms)
        ✓ 標準デバイス (md) でそのまま返す (1 ms)
      子ども向けテーマ (child)
        ✓ 標準デバイスで大人向けの1.20倍になる (1 ms)

Test Suites: 1 passed, 1 total
Tests:       50 passed, 50 total
```

### 失敗例

```
 FAIL  __tests__/responsive/integration.test.ts
  レスポンシブ対応 - 全画面検証
    全画面ファイルのレスポンシブ対応確認
      tasks/TaskListScreen.tsx
        ✕ useResponsive をインポートしている (5 ms)

  ● レスポンシブ対応 - 全画面検証 › 全画面ファイルのレスポンシブ対応確認 › tasks/TaskListScreen.tsx › useResponsive をインポートしている

    expect(received).toMatch(expected)

    Expected pattern: /import.*useResponsive.*from.*responsive/
    Received string: "..."

    → 修正方法: TaskListScreen.tsx に useResponsive のインポートを追加
```

---

## トラブルシューティング

### エラー1: `Cannot find module 'responsive'`

**原因**: インポートパスが間違っている

**解決方法**:
```typescript
// ❌ NG
import { useResponsive } from '../../hooks/useResponsive';

// ✅ OK
import { useResponsive } from '../../utils/responsive';
```

### エラー2: `Type error: Property 'deviceSize' does not exist`

**原因**: useResponsive() の返り値の型定義が古い

**解決方法**:
```typescript
import { useResponsive, type ResponsiveResult } from '../../utils/responsive';

const { width, height, deviceSize, isPortrait, isLandscape } = useResponsive();
```

### エラー3: `TypeError: Cannot read property 'width' of undefined`

**原因**: Dimensions.get のモックが不足

**解決方法**:
```typescript
jest.spyOn(Dimensions, 'get').mockReturnValue({
  width: 390,
  height: 844,
  scale: 3,
  fontScale: 1,
});
```

### エラー4: 統合テストの TypeScript エラー

**原因**: 型エラーが存在する

**解決方法**:
```bash
# 型エラー確認
npx tsc --noEmit

# エラー箇所を修正後、再テスト
npm test -- __tests__/responsive/integration.test.ts
```

---

## CI/CDでの実行

### GitHub Actions

`.github/workflows/mobile-test.yml` に以下を追加:

```yaml
- name: Run responsive tests
  run: |
    cd mobile
    npm test -- __tests__/responsive/
    npm test -- src/utils/__tests__/responsive.test.ts
```

### カバレッジ閾値

```json
{
  "jest": {
    "coverageThreshold": {
      "global": {
        "branches": 80,
        "functions": 80,
        "lines": 80,
        "statements": 80
      },
      "src/utils/responsive.ts": {
        "branches": 100,
        "functions": 100,
        "lines": 100,
        "statements": 100
      }
    }
  }
}
```

---

## 参考ドキュメント

- [ResponsiveDesignGuideline.md](/home/ktr/mtdev/definitions/mobile/ResponsiveDesignGuideline.md) - レスポンシブ設計仕様
- [mobile-rules.md](/home/ktr/mtdev/docs/mobile/mobile-rules.md) - モバイルアプリ開発規則
- [copilot-instructions.md](/home/ktr/mtdev/.github/copilot-instructions.md) - プロジェクト全体の開発規則
- [レスポンシブ対応完了レポート](/home/ktr/mtdev/docs/reports/2025-12-09-responsive-completion-report.md)

---

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-09 | GitHub Copilot | 初版作成: レスポンシブ対応テストスイート |

---

**作成日**: 2025-12-09  
**作成者**: GitHub Copilot  
**テスト対象**: 全32画面のレスポンシブ対応  
**カバレッジ目標**: 80%以上（responsive.ts は100%）
