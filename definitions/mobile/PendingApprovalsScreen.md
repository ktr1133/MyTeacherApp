# 承認待ち一覧画面（モバイル版） 要件定義書

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-09 | GitHub Copilot | 初版作成: 承認待ち一覧画面の要件定義 |
| 2025-12-10 | GitHub Copilot | タブ切り替えを廃止、Web版と同様に日付順統合表示に変更 |

---

## 1. 概要

### 1.1 目的

親ユーザー（グループ管理者）が子どもからのタスク承認申請およびトークン購入申請を確認・承認・却下する機能を提供する。

### 1.2 対象フェーズ

- **Phase 2.B-8**: Web版スタイル統一・画面追加

### 1.3 対象ユーザー

- **親ユーザー（グループ管理者）専用**
- 表示条件: `user.isParent() === true` または `user.canEditGroup() === true`

### 1.4 Web版との対応

- **Web版**: `/home/ktr/mtdev/resources/views/tasks/pending-approvals.blade.php`
- **API**: `GET /api/tasks/approvals/pending` (統合API)
- **機能**: タスク承認とトークン購入申請を1つの画面で統合表示

---

## 2. 画面仕様

### 2.1 画面名称と配置

**画面名**: `PendingApprovalsScreen.tsx`

**配置先**: `/home/ktr/mtdev/mobile/src/screens/approvals/PendingApprovalsScreen.tsx`

**理由**: 
- タスク専用ではなく、複数種類の承認を統合表示するため独立ディレクトリに配置
- 将来的に承認種別が増える可能性を考慮

### 2.2 画面構成

```
┌─────────────────────────────────────┐
│ ヘッダー                              │
│ [≡] 承認待ち一覧          [🔔]      │
├─────────────────────────────────────┤
│ 承認待ち一覧（スクロール可能）        │
│ ※申請日時の古い順に表示              │
│                                      │
│ ┌───────────────────────────────┐   │
│ │ [タスク] タスクタイトル          │   │
│ │ 申請者: 太郎                     │   │
│ │ 期限: 2025/12/10                 │   │
│ │ 報酬: 1,000円                    │   │
│ │ [承認する] [却下する]            │   │
│ └───────────────────────────────┘   │
│ ┌───────────────────────────────┐   │
│ │ [トークン] スタンダードパック     │   │
│ │ 申請者: 花子                     │   │
│ │ 金額: 500円 / 10,000トークン    │   │
│ │ [承認する] [却下する]            │   │
│ └───────────────────────────────┘   │
│ ┌───────────────────────────────┐   │
│ │ [タスク] 別のタスク              │   │
│ │ 申請者: 次郎                     │   │
│ │ [承認する] [却下する]            │   │
│ └───────────────────────────────┘   │
│                                      │
│ （空状態）                            │
│ ┌───────────────────────────────┐   │
│ │      ✓                           │   │
│ │  承認待ちの項目がありません      │   │
│ │  すべての申請を処理しました      │   │
│ └───────────────────────────────┘   │
└─────────────────────────────────────┘

【却下理由入力モーダル】
┌─────────────────────────────────────┐
│ 却下理由の入力                       │
│                                      │
│ 「タスクタイトル」を却下します       │
│                                      │
│ ┌─────────────────────────────┐     │
│ │ 却下理由を入力してください... │     │
│ │ （任意）                      │     │
│ └─────────────────────────────┘     │
│                                      │
│ [キャンセル]        [却下する]       │
└─────────────────────────────────────┘
```

---

## 3. 機能要件

### 3.1 データ取得

#### 統合API使用

**エンドポイント**: `GET /api/tasks/approvals/pending`

**レスポンス形式**:
```json
{
  "success": true,
  "data": {
    "approvals": [
      {
        "id": 1,
        "type": "task",
        "title": "タスクタイトル",
        "requester_name": "太郎",
        "requester_id": 10,
        "requested_at": "2025-12-09T10:00:00Z",
        "description": "タスクの説明...",
        "reward": 1000,
        "has_images": true,
        "images_count": 2,
        "due_date": "2025-12-10T23:59:59Z",
        "model": {
          "id": 123,
          "title": "タスクタイトル",
          "description": "...",
          "reward": 1000
        }
      },
      {
        "id": 2,
        "type": "token",
        "package_name": "スタンダードパック",
        "requester_name": "花子",
        "requester_id": 11,
        "requested_at": "2025-12-09T11:00:00Z",
        "token_amount": 10000,
        "price": 500,
        "model": {
          "id": 456,
          "package_id": 2,
          "status": "pending"
        }
      }
    ],
    "total": 2,
    "page": 1,
    "per_page": 15
  }
}
```

**ページネーション**:
- 1ページあたり15件
- プルダウンリフレッシュで再取得
- 無限スクロール対応（`onEndReached`で次ページ読み込み）

**ソート順**:
- 申請日時（`requested_at`）の古い順（昇順）
- Web版と同じ表示順序

### 3.2 承認カード表示（統合表示）

**表示ルール**:
- タスク承認（`type === 'task'`）とトークン購入申請（`type === 'token'`）を混在表示
- 申請日時の古い順に並べる（サーバー側でソート済み）
- カード種別は `type` フィールドで判別し、適切なカードコンポーネントを表示

#### タスク承認カード

**表示要素**:
- タイプバッジ: 「タスク」（紫色）
- タスクタイトル（太字、大きめフォント）
- 申請者名（アイコン付き）
- 申請日時（カレンダーアイコン付き）
- 期限（時計アイコン付き）
- 報酬（コインアイコン付き、あれば表示）
- タグ一覧（あれば表示）
- 添付画像表示（「添付画像 N枚」、あれば表示）
- 説明文（折りたたみ可能、3行まで表示）
- [承認する] ボタン（緑色）
- [却下する] ボタン（赤色）

**スタイル**:
```typescript
{
  backgroundColor: '#FFFFFF',
  borderRadius: 16,
  padding: 16,
  marginBottom: 12,
  shadowColor: '#000',
  shadowOpacity: 0.1,
  shadowRadius: 8,
  elevation: 3,
}
```

#### トークン購入申請カード

**表示要素**:
- タイプバッジ: 「トークン購入」（オレンジ色）
- パッケージ名（太字、大きめフォント）
- 申請者名（アイコン付き）
- 申請日時（カレンダーアイコン付き）
- トークン数量（コインアイコン付き）
- 金額（円マーク付き）
- [承認する] ボタン（緑色）
- [却下する] ボタン（赤色）

**スタイル**: タスク承認カードと同様

### 3.4 承認処理

#### タスク承認

**API**: `POST /api/tasks/{id}/approve`

**リクエスト**: なし（ボディ不要）

**レスポンス**:
```json
{
  "success": true,
  "message": "タスクを承認しました",
  "data": {
    "task": {
      "id": 123,
      "status": "approved",
      "approved_at": "2025-12-09T12:00:00Z"
    }
  }
}
```

**処理フロー**:
1. 確認ダイアログ表示: 「このタスクを承認しますか?」
2. OKタップ → API呼び出し
3. 成功 → トースト表示「タスクを承認しました」、一覧から削除
4. 失敗 → エラーメッセージ表示

#### トークン購入申請承認

**API**: `PUT /api/tokens/purchase-requests/{id}/approve`

**リクエスト**: なし（ボディ不要）

**レスポンス**:
```json
{
  "success": true,
  "message": "購入リクエストを承認しました",
  "data": {
    "request": {
      "id": 456,
      "status": "approved",
      "approved_at": "2025-12-09T12:00:00Z"
    }
  }
}
```

**処理フロー**: タスク承認と同様

### 3.5 却下処理

#### 却下理由入力モーダル

**表示条件**: [却下する] ボタンタップ時

**表示要素**:
- モーダルタイトル: 「却下理由の入力」
- 対象名表示: 「〇〇を却下します」
- 却下理由入力フィールド（TextInput、複数行、任意）
  - プレースホルダー: 「却下理由を入力してください（任意）」
  - 最大文字数: 500文字
- [キャンセル] ボタン: モーダルを閉じる
- [却下する] ボタン: 却下API呼び出し

**実装**:
```typescript
const [showRejectModal, setShowRejectModal] = useState(false);
const [rejectTarget, setRejectTarget] = useState<ApprovalItem | null>(null);
const [rejectReason, setRejectReason] = useState('');

const openRejectModal = (approval: ApprovalItem) => {
  setRejectTarget(approval);
  setRejectReason('');
  setShowRejectModal(true);
};

const closeRejectModal = () => {
  setShowRejectModal(false);
  setRejectTarget(null);
  setRejectReason('');
};
```

#### タスク却下

**API**: `POST /api/tasks/{id}/reject`

**リクエスト**:
```json
{
  "reason": "却下理由（任意）"
}
```

**レスポンス**:
```json
{
  "success": true,
  "message": "タスクを却下しました",
  "data": {
    "task": {
      "id": 123,
      "status": "rejected",
      "rejected_at": "2025-12-09T12:00:00Z",
      "rejection_reason": "却下理由..."
    }
  }
}
```

#### トークン購入申請却下

**API**: `PUT /api/tokens/purchase-requests/{id}/reject`

**リクエスト**: タスク却下と同様

**レスポンス**: タスク却下と同様（`request` に変更）

**処理フロー**:
1. [却下する] ボタンタップ → 却下理由入力モーダル表示
2. 理由入力（任意） → [却下する] ボタンタップ
3. API呼び出し
4. 成功 → トースト表示「却下しました」、一覧から削除、モーダル閉じる
5. 失敗 → エラーメッセージ表示、モーダルは開いたまま

---

## 4. UI/UXデザイン

### 4.1 レスポンシブ対応

**ブレークポイント**: `/home/ktr/mtdev/definitions/mobile/ResponsiveDesignGuideline.md` に準拠

```typescript
import { useResponsive } from '@/utils/responsive';
import { getAdultFontSize, getChildFontSize, getSpacing, getBorderRadius } from '@/utils/responsive';

const { width, deviceSize, isTablet } = useResponsive();
const theme = user.theme; // 'adult' or 'child'

const styles = StyleSheet.create({
  // カードスタイル
  card: {
    backgroundColor: '#FFFFFF',
    borderRadius: getBorderRadius(16, width),
    padding: getSpacing(16, width),
    marginBottom: getSpacing(12, width),
    shadowColor: '#000',
    shadowOpacity: 0.1,
    shadowRadius: 8,
    elevation: 3,
  },
  
  // タイトルフォント
  title: {
    fontSize: theme === 'adult' 
      ? getAdultFontSize(18, width) 
      : getChildFontSize(18, width),
    fontWeight: '700',
    color: '#111827',
  },
  
  // 申請者名フォント
  requesterName: {
    fontSize: theme === 'adult' 
      ? getAdultFontSize(14, width) 
      : getChildFontSize(14, width),
    color: '#4B5563',
  },
});
```

### 4.2 カラーパレット

**Web版Tailwind CSS → React Native対応**:

| 用途 | Web版 | React Native |
|------|-------|-------------|
| プライマリ（承認ボタン） | `bg-green-600` | `#10B981` |
| 危険（却下ボタン） | `bg-red-600` | `#EF4444` |
| カード背景 | `bg-white` | `#FFFFFF` |
| テキスト（プライマリ） | `text-gray-900` | `#111827` |
| テキスト（セカンダリ） | `text-gray-600` | `#4B5563` |
| タイプバッジ（タスク） | `bg-purple-600` | `#9333EA` |
| タイプバッジ（トークン） | `bg-amber-600` | `#D97706` |

### 4.3 子ども向けテーマ対応

**表示テキスト**:

| 要素 | 大人向け | 子ども向け |
|------|---------|----------|
| 画面タイトル | 承認待ち一覧 | しょうにんまち |
| タイプバッジ（タスク） | タスク | おてつだい |
| タイプバッジ（トークン） | トークン購入 | コインかいたい |
| 承認ボタン | 承認する | OK! |
| 却下ボタン | 却下する | やりなおし |
| 却下理由 | 却下理由を入力してください（任意） | どうしてダメなのかおしえてね（かかなくてもいいよ） |
| 空状態 | 承認待ちの項目がありません | ぜんぶおわったよ！ |

**フォントサイズ拡大**:
```typescript
const getFontSize = (baseSize: number, width: number, theme: 'adult' | 'child') => {
  return theme === 'adult' 
    ? getAdultFontSize(baseSize, width) 
    : getChildFontSize(baseSize, width); // 子ども向けは1.2倍
};
```

### 4.4 アニメーション

**カード表示アニメーション**:
```typescript
import { useEffect } from 'react';
import { Animated } from 'react-native';

const fadeAnim = useRef(new Animated.Value(0)).current;

useEffect(() => {
  Animated.timing(fadeAnim, {
    toValue: 1,
    duration: 300,
    useNativeDriver: true,
  }).start();
}, []);

<Animated.View style={{ opacity: fadeAnim }}>
  {/* カード内容 */}
</Animated.View>
```

**ボタンタップフィードバック**:
```typescript
<TouchableOpacity
  activeOpacity={0.7}
  style={styles.approveButton}
  onPress={handleApprove}
>
  <Text style={styles.approveButtonText}>承認する</Text>
</TouchableOpacity>
```

---

## 5. ナビゲーション

### 5.1 遷移元

**ハンバーガーメニュー（ドロワー）**:
- メニュー項目: 「承認待ち」（時計アイコン）
- バッジ表示: 承認待ち件数（例: `(3)`）
- 表示条件: `user.isParent() === true`

**実装**:
```typescript
// DrawerNavigator.tsx
{user.isParent() && (
  <DrawerItem
    icon="clock"
    label="承認待ち"
    badge={pendingCount > 0 ? pendingCount : undefined}
    onPress={() => navigation.navigate('PendingApprovals')}
  />
)}
```

### 5.2 遷移先

**タスク詳細画面への遷移**:
- タスク承認カード全体をタップ可能（Web版では不可、モバイル版で強化）
- タスク詳細画面へ遷移: `navigation.navigate('TaskDetail', { taskId })`

**トークン購入申請の詳細表示**:
- カード内にすべての情報を表示（詳細画面なし）

---

## 6. エラーハンドリング

### 6.1 ネットワークエラー

```typescript
try {
  const response = await getPendingApprovals();
  setApprovals(response.data.approvals);
} catch (error) {
  if (error.message === 'Network Error') {
    Alert.alert(
      theme === 'child' ? 'エラー' : 'ネットワークエラー',
      theme === 'child' 
        ? 'インターネットにつながっていないよ' 
        : 'ネットワーク接続を確認してください'
    );
  } else {
    Alert.alert('エラー', error.message);
  }
}
```

### 6.2 権限エラー

**403エラー**:
```typescript
if (error.response?.status === 403) {
  Alert.alert(
    theme === 'child' ? 'ダメだよ' : '権限エラー',
    theme === 'child' 
      ? 'おとなしかみれないよ' 
      : '親ユーザーのみアクセスできます'
  );
  navigation.goBack();
}
```

### 6.3 承認・却下失敗

```typescript
try {
  await approveTask(taskId);
  // 成功処理
} catch (error) {
  Alert.alert(
    theme === 'child' ? 'エラー' : '承認失敗',
    error.response?.data?.message || '承認に失敗しました。もう一度お試しください。'
  );
}
```

---

## 7. テスト要件

### 7.1 単体テスト

**テストファイル**: `/home/ktr/mtdev/mobile/src/screens/approvals/__tests__/PendingApprovalsScreen.test.tsx`

**テストケース**:
```typescript
describe('PendingApprovalsScreen', () => {
  it('親ユーザーで承認待ち一覧を表示できる', async () => {
    // Arrange
    const mockUser = { id: 1, isParent: () => true, theme: 'adult' };
    const mockApprovals = [
      { id: 1, type: 'task', title: 'テストタスク', requester_name: '太郎', requested_at: '2025-12-09T10:00:00Z' },
      { id: 2, type: 'token', package_name: 'スタンダード', requester_name: '花子', requested_at: '2025-12-09T11:00:00Z' },
    ];
    
    // Act
    render(<PendingApprovalsScreen />);
    
    // Assert
    expect(screen.getByText('承認待ち一覧')).toBeTruthy();
    expect(screen.getByText('テストタスク')).toBeTruthy();
    expect(screen.getByText('スタンダード')).toBeTruthy();
  });

  it('タスクとトークン購入申請が混在表示される', () => {
    // Arrange
    const mockApprovals = [
      { id: 1, type: 'task', title: 'タスクA', requested_at: '2025-12-09T10:00:00Z' },
      { id: 2, type: 'token', package_name: 'パッケージB', requested_at: '2025-12-09T11:00:00Z' },
      { id: 3, type: 'task', title: 'タスクC', requested_at: '2025-12-09T12:00:00Z' },
    ];
    render(<PendingApprovalsScreen />);
    
    // Assert
    expect(screen.getByText('タスクA')).toBeTruthy();
    expect(screen.getByText('パッケージB')).toBeTruthy();
    expect(screen.getByText('タスクC')).toBeTruthy();
  });

  it('承認ボタンタップで確認ダイアログが表示される', () => {
    // Arrange
    render(<PendingApprovalsScreen />);
    
    // Act
    fireEvent.press(screen.getByText('承認する'));
    
    // Assert
    expect(Alert.alert).toHaveBeenCalledWith(
      '確認',
      'このタスクを承認しますか?',
      expect.any(Array)
    );
  });

  it('却下ボタンタップで却下理由入力モーダルが表示される', () => {
    // Arrange
    render(<PendingApprovalsScreen />);
    
    // Act
    fireEvent.press(screen.getByText('却下する'));
    
    // Assert
    expect(screen.getByText('却下理由の入力')).toBeTruthy();
  });

  it('空状態が正しく表示される', () => {
    // Arrange
    const mockEmptyApprovals = [];
    
    // Act
    render(<PendingApprovalsScreen />);
    
    // Assert
    expect(screen.getByText('承認待ちの項目がありません')).toBeTruthy();
  });
});
```

### 7.2 統合テスト

**テストファイル**: `/home/ktr/mtdev/mobile/src/screens/approvals/__tests__/PendingApprovalsScreen.integration.test.tsx`

**テストケース**:
```typescript
describe('PendingApprovalsScreen - 統合テスト', () => {
  it('承認処理が完了し一覧から削除される', async () => {
    // Arrange
    const mockApi = jest.spyOn(TaskService, 'approveTask').mockResolvedValue({ success: true });
    render(<PendingApprovalsScreen />);
    
    // Act
    fireEvent.press(screen.getByText('承認する'));
    fireEvent.press(screen.getByText('OK')); // 確認ダイアログ
    
    // Assert
    await waitFor(() => {
      expect(mockApi).toHaveBeenCalledWith(123);
      expect(screen.queryByText('テストタスク')).toBeNull();
    });
  });

  it('却下処理が完了し一覧から削除される', async () => {
    // Arrange
    const mockApi = jest.spyOn(TaskService, 'rejectTask').mockResolvedValue({ success: true });
    render(<PendingApprovalsScreen />);
    
    // Act
    fireEvent.press(screen.getByText('却下する'));
    fireEvent.changeText(screen.getByPlaceholderText('却下理由を入力...'), '理由');
    fireEvent.press(screen.getByText('却下する')); // モーダル内ボタン
    
    // Assert
    await waitFor(() => {
      expect(mockApi).toHaveBeenCalledWith(123, '理由');
      expect(screen.queryByText('テストタスク')).toBeNull();
    });
  });
});
```

---

## 8. 実装ファイル

### 8.1 画面コンポーネント

```
/home/ktr/mtdev/mobile/src/screens/approvals/
├── PendingApprovalsScreen.tsx          # メイン画面
└── __tests__/
    ├── PendingApprovalsScreen.test.tsx
    └── PendingApprovalsScreen.integration.test.tsx
```

### 8.2 コンポーネント

```
/home/ktr/mtdev/mobile/src/components/approvals/
├── ApprovalCard.tsx                    # 承認カード（共通）
├── TaskApprovalCard.tsx                # タスク承認カード
├── TokenApprovalCard.tsx               # トークン購入申請カード
├── RejectReasonModal.tsx               # 却下理由入力モーダル
└── __tests__/
    ├── ApprovalCard.test.tsx
    ├── TaskApprovalCard.test.tsx
    ├── TokenApprovalCard.test.tsx
    └── RejectReasonModal.test.tsx
```

### 8.3 Hook

```
/home/ktr/mtdev/mobile/src/hooks/
└── usePendingApprovals.ts              # 承認待ち一覧Hook
```

### 8.4 Service

```
/home/ktr/mtdev/mobile/src/services/
└── approval.service.ts                  # 承認API呼び出し
```

### 8.5 型定義

```
/home/ktr/mtdev/mobile/src/types/
└── approval.types.ts                    # 承認関連型定義
```

---

## 9. API仕様（OpenAPI更新）

### 9.1 統合API追加

**エンドポイント**: `GET /api/tasks/approvals/pending`

**追加箇所**: `/home/ktr/mtdev/docs/api/openapi.yaml` の `Tasks` セクション

```yaml
/tasks/approvals/pending:
  get:
    summary: 承認待ち一覧取得（統合API）
    description: |
      タスク承認とトークン購入申請の承認待ち一覧を統合して取得します。
      親ユーザー（グループ管理者）専用APIです。
    tags: [Tasks]
    security:
      - SanctumAuth: []
    parameters:
      - name: page
        in: query
        schema:
          type: integer
          default: 1
        description: ページ番号
      - name: per_page
        in: query
        schema:
          type: integer
          default: 15
        description: 1ページあたりの件数
    responses:
      '200':
        description: 成功
        content:
          application/json:
            schema:
              type: object
              properties:
                success:
                  type: boolean
                  example: true
                data:
                  type: object
                  properties:
                    approvals:
                      type: array
                      items:
                        oneOf:
                          - $ref: '#/components/schemas/TaskApprovalItem'
                          - $ref: '#/components/schemas/TokenApprovalItem'
                    total:
                      type: integer
                      example: 25
                    page:
                      type: integer
                      example: 1
                    per_page:
                      type: integer
                      example: 15
      '403':
        description: 権限エラー（親ユーザーのみアクセス可能）
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/ErrorResponse'
```

### 9.2 スキーマ定義追加

```yaml
components:
  schemas:
    TaskApprovalItem:
      type: object
      properties:
        id:
          type: integer
          example: 1
        type:
          type: string
          enum: [task]
          example: task
        title:
          type: string
          example: "部屋の掃除"
        requester_name:
          type: string
          example: "太郎"
        requester_id:
          type: integer
          example: 10
        requested_at:
          type: string
          format: date-time
          example: "2025-12-09T10:00:00Z"
        description:
          type: string
          nullable: true
          example: "リビングと自分の部屋を掃除する"
        reward:
          type: integer
          nullable: true
          example: 1000
        has_images:
          type: boolean
          example: true
        images_count:
          type: integer
          example: 2
        due_date:
          type: string
          format: date-time
          nullable: true
          example: "2025-12-10T23:59:59Z"
        model:
          type: object
          description: 元のタスクオブジェクト
          properties:
            id:
              type: integer
            title:
              type: string
            description:
              type: string
            reward:
              type: integer
    
    TokenApprovalItem:
      type: object
      properties:
        id:
          type: integer
          example: 2
        type:
          type: string
          enum: [token]
          example: token
        package_name:
          type: string
          example: "スタンダードパック"
        requester_name:
          type: string
          example: "花子"
        requester_id:
          type: integer
          example: 11
        requested_at:
          type: string
          format: date-time
          example: "2025-12-09T11:00:00Z"
        token_amount:
          type: integer
          example: 10000
        price:
          type: integer
          example: 500
        model:
          type: object
          description: 元のトークン購入リクエストオブジェクト
          properties:
            id:
              type: integer
            package_id:
              type: integer
            status:
              type: string
              enum: [pending, approved, rejected]
```

---

## 10. 参考資料

### 10.1 関連ドキュメント

| ドキュメント | パス | 用途 |
|------------|------|------|
| モバイル開発規則 | `/home/ktr/mtdev/docs/mobile/mobile-rules.md` | 開発規約 |
| レスポンシブ設計 | `/home/ktr/mtdev/definitions/mobile/ResponsiveDesignGuideline.md` | UI/UX設計 |
| 画面遷移フロー | `/home/ktr/mtdev/definitions/mobile/NavigationFlow.md` | ナビゲーション |
| Phase 2実装計画 | `/home/ktr/mtdev/docs/plans/phase2-mobile-app-implementation-plan.md` | 全体計画 |
| OpenAPI仕様 | `/home/ktr/mtdev/docs/api/openapi.yaml` | API仕様 |

### 10.2 Web版実装

| ファイル | パス |
|---------|------|
| Bladeテンプレート | `/home/ktr/mtdev/resources/views/tasks/pending-approvals.blade.php` |
| CSS | `/home/ktr/mtdev/resources/css/tasks/pending-approvals.css` |
| JavaScript | `/home/ktr/mtdev/resources/js/tasks/pending-approvals.js` |
| Action | `/home/ktr/mtdev/app/Http/Actions/Task/ListPendingApprovalsAction.php` |
| Service | `/home/ktr/mtdev/app/Services/Approval/ApprovalMergeService.php` |

### 10.3 既存モバイル実装

| ファイル | パス | 参照箇所 |
|---------|------|---------|
| TaskDetailScreen | `/home/ktr/mtdev/mobile/src/screens/tasks/TaskDetailScreen.tsx` | 承認・却下UI参考 |
| useTasks Hook | `/home/ktr/mtdev/mobile/src/hooks/useTasks.ts` | approveTask, rejectTask メソッド |
| Task Service | `/home/ktr/mtdev/mobile/src/services/task.service.ts` | API呼び出し |

---

## 11. 実装チェックリスト

### 11.1 Phase 1: 画面・コンポーネント作成

- [ ] `PendingApprovalsScreen.tsx` 作成
- [ ] `ApprovalCard.tsx` 作成（共通カードコンポーネント）
- [ ] `TaskApprovalCard.tsx` 作成（タスク専用カード）
- [ ] `TokenApprovalCard.tsx` 作成（トークン専用カード）
- [ ] `RejectReasonModal.tsx` 作成（却下理由入力モーダル）
- [ ] レスポンシブ対応（Dimensions API使用）
- [ ] 子ども向けテーマ対応

### 11.2 Phase 2: Hook・Service実装

- [ ] `usePendingApprovals.ts` 作成
- [ ] `approval.service.ts` 作成
- [ ] `approval.types.ts` 作成（型定義）
- [ ] API統合エンドポイント呼び出し実装

### 11.3 Phase 3: ナビゲーション統合

- [ ] `DrawerNavigator.tsx` にメニュー項目追加
- [ ] バッジ表示実装（承認待ち件数）
- [ ] 親ユーザー判定による表示制御

### 11.4 Phase 4: テスト実装

- [ ] 単体テスト作成（全コンポーネント）
- [ ] 統合テスト作成（承認・却下フロー）
- [ ] エラーハンドリングテスト
- [ ] 権限チェックテスト

### 11.5 Phase 5: API・バックエンド連携

- [ ] OpenAPI仕様更新（`/tasks/approvals/pending`）
- [ ] スキーマ定義追加（`TaskApprovalItem`, `TokenApprovalItem`）
- [ ] バックエンドの既存実装確認（`ListPendingApprovalsApiAction`）

### 11.6 Phase 6: 最終確認

- [ ] Web版との動作整合性確認
- [ ] レスポンシブ動作確認（全デバイスサイズ）
- [ ] 子ども向けテーマ動作確認
- [ ] エラーハンドリング動作確認
- [ ] パフォーマンステスト（無限スクロール、画像読み込み）
- [ ] ドキュメント更新（`NavigationFlow.md` 更新）

---

## 12. 備考

### 12.1 Web版との差異

| 項目 | Web版 | モバイル版 | 理由 |
|-----|-------|----------|------|
| 表示形式 | 統合表示（日付順） | 統合表示（日付順） | 同じ |
| タスクカードタップ | モーダル表示 | タスク詳細画面へ遷移 | モバイルでは全画面表示が望ましい |
| ページネーション | 下部に表示 | 無限スクロール | モバイルUX向上 |
| 却下理由入力 | モーダル | モーダル | 同じ |

### 12.2 将来対応

- グループタスクの詳細情報表示強化（メンバー一覧、進捗状況）
- Push通知との連携（承認待ち増加時に通知）
- 一括承認・却下機能
- フィルタリング機能（申請者別、日付範囲指定）

---

以上
