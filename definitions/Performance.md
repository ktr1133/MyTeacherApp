# 実績画面 要件定義書

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-08 | GitHub Copilot | モバイル仕様追加: メンバー別概況画面（MemberSummaryScreen）実装、キャッシュ機能、戻るボタン確認ダイアログ |
| 2025-12-08 | GitHub Copilot | モバイルAPI実装: 月次レポート利用可能月リスト取得API追加、アバターイベント実装 |
| 2025-12-01 | GitHub Copilot | 月次レポート画面の詳細仕様を追加（セクション13） |
| 2025-12-01 | GitHub Copilot | アバター表示ロジックの詳細を追加（セクション2.5） |
| 2025-12-01 | GitHub Copilot | Phase 1.1.8実装完了: サブスク制限機能・用語統一・デザイン統一 |
| 2025-12-01 | GitHub Copilot | 初版作成: 実績画面の要件定義（既存機能+サブスク制限機能） |

## 1. 概要

### 目的

ユーザーのタスク実績（通常タスク・グループタスク）を視覚的に表示し、達成状況を確認できる画面を提供する。サブスクリプション制度導入に伴い、一部機能を有料化し、マネタイズを実現する。

### 対象ユーザー

- **個人ユーザー**: 自分の通常タスク実績を確認
- **グループ管理者・編集権限者**: グループタスクの全体統計・メンバー別実績を確認
- **グループメンバー**: 自分のグループタスク実績を確認

### 画面パス

- **ルート**: `/reports/performance`
- **アクション**: `App\Http\Actions\Reports\IndexPerformanceAction`
- **ビュー**: `resources/views/reports/performance.blade.php`

---

## 2. 既存機能の要件

### 2.1 画面構成

#### ヘッダー部

**要素**:
- ページタイトル「実績」
- 期間選択ボタン（週間・月間・年間）
- 月次レポートボタン（グループ所属ユーザーのみ）

**テーマ別表示**:
- **大人用**: 「実績」「週間」「月間」「年間」
- **子供用**: 「実績」「Weekly」「Monthly」「Yearly」

#### タブナビゲーション

**タブ種類**:
- **通常タスク**: 個人が作成・割り当てられた通常タスクの実績
- **グループタスク**: グループ内で共有されるタスクの実績

**テーマ別表示**:
- **大人用**: 「通常タスク」「グループタスク」
- **子供用**: 「やること」「クエスト」

**初期値**:
- 大人用テーマ: 通常タスク
- 子供用テーマ: グループタスク

#### メンバー選択（グループタスクタブのみ）

**表示条件**: グループ編集権限がある場合のみ表示

**選択肢**:
- グループ全体（デフォルト）
- 個別メンバー（編集権限者は除外）

**動作**:
- 「グループ全体」選択時: 全メンバーの集計データを表示
- 個別メンバー選択時: 選択したメンバーの個人データを表示
- 編集権限がない場合: 自分自身のデータのみ表示（選択UI非表示）

#### 期間ナビゲーション

**要素**:
- 前へボタン（<）
- 期間表示（例: 2025年11月4週目、2025年11月、2025年）
- 次へボタン（>）

**動作**:
- 前へ: 過去の期間へ移動（最大: 週間52週、月間12ヶ月、年間5年）
- 次へ: 未来の期間へ移動（最大: 当週/当月/当年まで）
- 未来・過去の限界に達した場合: ボタン無効化

#### グラフ表示エリア

**グラフ種類**:
- **週間・月間**: 日別の棒グラフ + 累積折れ線グラフ
- **年間**: 週別の棒グラフ + 累積折れ線グラフ

**表示データ**:
- **通常タスク**: 累積完了数、報酬累積（折れ線）、日別完了数（棒グラフ）
- **グループタスク**: 累積完了数、報酬累積、未完了数、完了数（棒グラフ）

**色分け**:
- 通常タスク: ティール系（#59B9C6）
- グループタスク: パープル系（#8B5CF6）

#### 集計データ表示

**表示項目**:
- **通常タスク**: 完了数、未完了数、獲得報酬
- **グループタスク**: 完了数、未完了数、獲得報酬

**テーマ別表示**:
- **大人用**: 「完了」「未完了」「ごうけい」
- **子供用**: 「DONE」「YET」「コイン」

---

### 2.2 データ取得ロジック

#### 期間別データ取得

**週間データ**:
- 開始: 月曜日 00:00:00
- 終了: 日曜日 23:59:59
- オフセット範囲: -52週 ～ 0週（当週）
- 表示形式: 「YYYY年MM月N週目」

**月間データ**:
- 開始: 月初 00:00:00
- 終了: 月末 23:59:59
- オフセット範囲: -12ヶ月 ～ 0ヶ月（当月）
- 表示形式: 「YYYY年MM月」

**年間データ**:
- 開始: 1月1日 00:00:00
- 終了: 12月31日 23:59:59
- オフセット範囲: -5年 ～ 0年（当年）
- 表示形式: 「YYYY年」
- 集計単位: 週別

#### 通常タスクのデータ取得

**対象**:
- `tasks.user_id = 現在のユーザーID`
- `tasks.group_task_id IS NULL`（通常タスク判定）

**集計項目**:
- 完了数: `is_completed = true` の件数
- 未完了数: `is_completed = false` の件数
- 獲得報酬: `SUM(reward) WHERE is_completed = true`
- 累積データ: 期間開始から各日までの累積完了数・累積報酬

#### グループタスクのデータ取得

**対象（グループ全体選択時）**:
- `tasks.user_id IN (グループメンバーのID一覧)`
- `tasks.group_task_id IS NOT NULL`（グループタスク判定）

**対象（個別メンバー選択時）**:
- `tasks.user_id = 選択されたメンバーのID`
- `tasks.group_task_id IS NOT NULL`

**集計項目**:
- 完了数、未完了数、獲得報酬（通常タスクと同様）
- 累積データ（通常タスクと同様）

---

### 2.3 権限管理

#### 通常タスク

**閲覧権限**: 全ユーザー（自分自身のデータのみ）

**データ範囲**: 自分が作成・割り当てられたタスクのみ

#### グループタスク

**閲覧権限**:
- グループ編集権限あり: グループ全体 + 個別メンバー選択可能
- グループ編集権限なし: 自分自身のデータのみ

**メンバー選択制限**:
- 編集権限者は選択肢に表示されない（子供アカウントのみ選択可能）
- 無効な選択（他グループメンバー、編集権限者）の場合は「グループ全体」に強制リセット

---

### 2.4 レスポンシブデザイン

#### デスクトップ（1024px以上）

**レイアウト**:
- サイドバー表示
- グラフ表示エリア: 画面高さの60%程度
- 集計データ: グラフ下部に横並び表示

#### タブレット（768px - 1023px）

**レイアウト**:
- サイドバー折りたたみ
- グラフ表示エリア: 画面高さの50%程度
- 集計データ: グラフ下部に横並び表示（縮小）

#### スマートフォン（767px以下）

**レイアウト**:
- サイドバー非表示（ハンバーガーメニュー）
- 期間選択ボタン: アイコン+テキスト縮小
- グラフ表示エリア: 画面高さの40%程度
- 集計データ: 2列表示

#### 子供用テーマ（iPhone SE等）

**特別対応**:
- グラフ高さ: 60vh（画面高さの60%）
- ラベルフォントサイズ: 0.65rem（通常の約半分）
- 値フォントサイズ: 0.9rem
- スクロール無効化（`overflow-y: hidden`）

---

### 2.5 アバター表示機能

#### 概要

実績画面にアクセスした際、サブスクリプション加入者限定でアバターキャラクターが実績コメントを表示する。通常のアバターイベント機能とは異なる、実績画面専用の表示ロジックを使用する。

#### 表示条件

**必須条件**:
1. サブスクリプション加入者であること（`$hasSubscription = true`）
2. 他ページから実績画面へ遷移した場合（リファラーチェック）
3. 直接アクセス（URL直接入力・ブックマーク）の場合も表示

**表示しない条件**:
- サブスクリプション未加入者（無料ユーザー）
- 実績画面内でのタブ切り替え・期間変更（内部ナビゲーション）

#### 表示タイミング

**トリガー**: 
- `DOMContentLoaded` イベント発火後
- `window.location.pathname === '/reports/performance'` の場合のみ
- リファラーが空または実績画面以外のURL

**実装場所**: 
- `resources/js/reports/performance.js`
- `showPerformanceAvatarOnLoad()` 関数

#### コメント内容

**大人向けテーマ**:
```
今週は{完了件数}件完了しました。
お疲れ様です。
```
- データソース: `normalData.nDone`（今週の完了タスク配列）
- 集計方法: 配列の合計値を算出

**子供向けテーマ**:
```
今月は{報酬累計}コインゲット！
がんばったね！
```
- データソース: `groupData.gRewardCum`（報酬累計配列）
- 取得方法: 配列の最後の要素（当月累計値）

#### データフロー

1. **サーバーサイド（IndexPerformanceAction）**:
   ```php
   return view('reports.performance', compact(
       'normalData',  // 今週の通常タスクデータ
       'groupData',   // 今月のグループタスクデータ
       'hasSubscription'  // サブスク状態
   ));
   ```

2. **ビュー（performance.blade.php）**:
   ```javascript
   window.performanceData = {
       normalData: @json($normalData),
       groupData: @json($groupData),
       hasSubscription: @json($hasSubscription)
   };
   ```

3. **JavaScript（performance.js）**:
   ```javascript
   function showPerformanceAvatarOnLoad() {
       const { normalData, groupData, hasSubscription } = window.performanceData;
       
       // サブスク未加入の場合はスキップ
       if (!hasSubscription) {
           console.log('[Performance Avatar] Subscription required - avatar display skipped');
           return;
       }
       
       // テーマに応じてコメント生成
       const isChildTheme = document.documentElement.classList.contains('child-theme');
       let comment, value;
       
       if (isChildTheme) {
           const rewardCumulative = groupData.gRewardCum || [];
           value = rewardCumulative[rewardCumulative.length - 1] || 0;
           comment = `今月は${value.toLocaleString()}コインゲット！<br>がんばったね！`;
       } else {
           const completedCount = (normalData.nDone || []).reduce((sum, n) => sum + n, 0);
           value = completedCount;
           comment = `今週は${value}件完了しました。<br>お疲れ様です。`;
       }
       
       showPerformanceAvatar({ comment, imageUrl: null, animation: 'avatar-cheer', isChildTheme });
   }
   ```

#### アバター表示UI

**表示形式**: フルスクリーンオーバーレイ

**構成要素**:
- 半透明背景オーバーレイ（クリックで閉じる）
- アバター画像（`happy`表情、アニメーション付き）
- 吹き出し（コメント表示）
- 閉じるボタン（右上）

**自動消去**: 20秒後に自動的に非表示

**子供向け特別エフェクト**:
- 紙吹雪エフェクト（`confetti.js`）3回連続発火
- 浮遊パーティクル（⭐💖✨🌟💫）20個生成

#### 技術仕様

**使用データ構造**:
```javascript
// normalData（週間データ）
{
    labels: ['月', '火', '水', '木', '金', '土', '日'],
    nDone: [2, 3, 1, 0, 2, 4, 1],  // 日別完了数
    nTodo: [1, 0, 2, 3, 1, 0, 2],  // 日別未完了数
    nCum: [2, 5, 6, 6, 8, 12, 13]  // 累積完了数
}

// groupData（月間データ）
{
    labels: ['第1週', '第2週', '第3週', '第4週'],
    gDone: [12, 15, 10, 8],           // 週別完了数
    gTodo: [3, 2, 5, 7],              // 週別未完了数
    gCum: [12, 27, 37, 45],           // 累積完了数
    gRewardCum: [120, 350, 520, 680]  // 累積報酬
}
```

**スタイル定義**: `resources/css/reports/performance.css`

**主要クラス**:
- `.performance-avatar-overlay`: オーバーレイ背景
- `.performance-avatar-container`: アバターコンテナ
- `.performance-avatar-bubble`: 吹き出し
- `.performance-avatar-image`: アバター画像
- `.performance-celebration-bg`: 子供向け背景エフェクト
- `.floating-particle`: 浮遊パーティクル

#### ログ出力

デバッグ用に以下のログを出力:

```javascript
console.log('[Performance Avatar] showPerformanceAvatarOnLoad called');
console.log('[Performance Avatar] Initial check', {
    hasPerformanceData: !!window.performanceData,
    hasNormalData: !!normalData,
    hasGroupData: !!groupData,
    hasSubscription: hasSubscription,
    isChildTheme: isChildTheme,
});
console.log('[Performance Avatar] Subscription required - avatar display skipped'); // 無料ユーザー
console.log('[Performance Avatar] Subscription active - showing avatar'); // サブスク加入者
```

#### 注意事項

**固定期間データの使用**:
- コメントに「今週」「今月」という文言が含まれるため、**常に当週/当月のデータ**を使用
- ユーザーが過去期間（例: 先週）を表示中でも、アバターコメントは「今週のデータ」を参照
- `normalData` は常に `offset=0`（今週）、`groupData` は常に `offset=0`（今月）を想定

**他のアバターイベントとの違い**:
- 通常のアバターイベント: `/avatars/comment/{eventType}` API経由、`TeacherAvatarService`でサブスクチェック
- 実績画面アバター: JavaScript側で直接表示、`window.performanceData.hasSubscription`でチェック

**サブスク制限の一貫性**:
- `TeacherAvatarService::shouldSkipAvatarEvent()` で `performance_personal_viewed` / `performance_group_viewed` イベントを制限
- `performance.js` の `showPerformanceAvatarOnLoad()` でも同様に `hasSubscription` チェックを実施
- これにより、無料ユーザーには実績画面でアバターが一切表示されない

#### モバイル版実装（Phase 2.B-6）

**実装場所**: `mobile/src/screens/reports/PerformanceScreen.tsx`

**表示タイミング**: 
- 画面初回マウント時のみ（`useEffect` + `useRef`でフラグ管理）
- サブスクリプション加入者のみ（`data.has_subscription === true`）

**イベントタイプ選択**:
```typescript
// テーマに応じてAPIイベントを選択
const eventType = theme === 'child' 
  ? 'performance_group_viewed'  // 子ども向け: 今月のグループタスク報酬累計
  : 'performance_personal_viewed'; // 大人向け: 今週の通常タスク完了件数
```

**APIエンドポイント**: 
- `GET /api/avatar/comment/{eventType}`
- 実装: `App\Http\Actions\Api\Avatar\GetAvatarCommentApiAction`

**アバター表示処理**:
```typescript
useEffect(() => {
  if (hasShownAvatar.current) return;
  
  if (data && data.has_subscription) {
    hasShownAvatar.current = true;
    const eventType = theme === 'child' 
      ? 'performance_group_viewed' 
      : 'performance_personal_viewed';
    dispatchAvatarEvent(eventType);
  }
}, [data, theme, dispatchAvatarEvent]);
```

**Web版との違い**:
- Web版: `performance.js`で直接コメント生成、`showPerformanceAvatar()`関数で表示
- モバイル版: `GetAvatarCommentApiAction`経由でバックエンドからコメント取得、`AvatarContext`で表示

**データフロー**:
1. PerformanceScreen初回マウント
2. `data.has_subscription`チェック（`IndexPerformanceApiAction`から取得済み）
3. テーマに応じて`performance_group_viewed`または`performance_personal_viewed`を選択
4. `dispatchAvatarEvent(eventType)` → APIコール → アバター表示

---

## 3. サブスクリプション制限機能の要件（Phase 1.1.8）

### 3.1 制限対象機能

#### 3.1.1 期間選択の制限

**無料ユーザー**:
- ✅ 週間実績のみ閲覧可能
- ❌ 月間実績は閲覧不可（ボタングレーアウト）
- ❌ 年間実績は閲覧不可（ボタングレーアウト）

**サブスク加入者**:
- ✅ 週間・月間・年間すべて閲覧可能

**実装方法**:
- 月間・年間ボタンを `opacity-50 cursor-not-allowed disabled` クラスでグレーアウト
- クリック時: `showSubscriptionAlert()` 関数を呼び出し、アラートモーダル表示

#### 3.1.2 期間ナビゲーションの制限

**無料ユーザー**:
- ✅ 当週のみ閲覧可能（offset = 0）
- ❌ 過去週・未来週への移動不可（前へ/次へボタン制限）

**サブスク加入者**:
- ✅ 過去週・未来週への移動可能（最大52週前まで）

**実装方法**:
- `offset !== 0` の場合、無料ユーザーは強制的に `offset = 0` にリダイレクト
- 前へ/次へボタンに `disabled` クラス追加
- クリック時: `showSubscriptionAlert()` 関数を呼び出し

#### 3.1.3 メンバー選択の制限（グループタスクのみ）

**無料ユーザー**:
- ✅ 「グループ全体」のみ閲覧可能
- ❌ 個別メンバー選択不可（個人名グレーアウト）

**サブスク加入者**:
- ✅ 「グループ全体」+ 個別メンバー選択可能

**実装方法**:
- `<select>` 要素の個人名 `<option>` に `disabled class="text-gray-400"` 属性追加
- 個人名の右側に 🔒 アイコン表示
- 選択時: `handleMemberSelect()` 関数で判定し、無料ユーザーは選択をリセット+アラート表示

#### 3.1.4 アバターイベントの制限

**無料ユーザー**:
- ❌ アバターイベント非表示（週間実績閲覧時も）

**サブスク加入者**:
- ✅ アバターイベント表示（タスク完了時など）

**実装方法**:
- `TeacherAvatarService::shouldSkipAvatarEvent()` で実績関連イベント判定
- `performance_*` イベントの場合、無料ユーザーはイベントをスキップ
- サブスク加入者のみ実績画面でアバターイベント表示

---

### 3.2 サブスクリプション判定ロジック

#### 判定基準

**グループ管理者のサブスク加入状況で判定**:
- ユーザー → グループ → グループマスター（`groups.master_user_id`）のサブスク状態をチェック
- `groups.subscription_active = true` ならサブスク加入中

#### 判定サービス

**サービス**: `App\Services\Subscription\SubscriptionService`

**メソッド**:
```php
// グループがサブスク加入済みか
public function isGroupSubscribed(Group $group): bool

// サブスクリプション限定機能にアクセスできるか（有料機能）
public function canAccessSubscriptionFeatures(Group $group): bool

// 期間選択可否（無料: 週間のみ / サブスク: 全期間）
public function canSelectPeriod(Group $group, string $period): bool

// メンバー選択可否（無料: グループ全体のみ / サブスク: 個人別選択可能）
public function canSelectMember(Group $group, bool $individualSelection): bool

// 期間ナビゲーション可否（無料: 当週のみ / サブスク: 過去期間閲覧可能）
public function canNavigateToPeriod(Group $group, Carbon $targetPeriod): bool

// サブスク促進アラート表示要否
public function shouldShowSubscriptionAlert(Group $group, string $feature): bool
```

**ビューへの変数**:
- `$hasSubscription`: サブスク加入状況（boolean）
- `$canSelectPeriod`: 期間選択可否（boolean）
- `$canSelectMember`: メンバー選択可否（boolean）
- `$canNavigate`: 期間ナビゲーション可否（boolean）
- `$subscriptionAlertFeature`: アラート表示対象機能（'period', 'member', 'navigation'）

### 3.3 フロントエンド実装

#### モーダルコンポーネント

**ファイル**: `resources/views/components/subscription-alert-modal.blade.php`

**実装方式**: Vanilla JavaScript（Alpine.js不使用）

**特徴**:
- グローバル`SubscriptionAlertModal`オブジェクトで一元管理
- `show(feature)` メソッドで機能別メッセージ表示
- ESCキー、オーバーレイクリック、閉じるボタンで閉じる
- CSSトランジション（フェードイン/アウト）
- confirm-dialog.blade.phpのデザインを踏襲

**機能別メッセージ**:
- `period`: 「月間・年間の実績表示はサブスクリプションプランでご利用いただけます」
- `member`: 「個人別実績表示はサブスクリプションプランでご利用いただけます」
- `navigation`: 「過去期間の実績閲覧はサブスクリプションプランでご利用いただけます」

#### イベントハンドラー

**ファイル**: `resources/views/reports/performance.blade.php`

**実装箇所**:
```html
<script>
document.addEventListener('DOMContentLoaded', function() {
    const alertButtons = document.querySelectorAll('.show-subscription-alert');
    alertButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const feature = this.dataset.feature || '';
            if (typeof SubscriptionAlertModal !== 'undefined') {
                SubscriptionAlertModal.show(feature);
            }
        });
    });
});
</script>
```

**トリガー要素**:
- 期間選択ボタン（月間・年間）: `data-feature="period"`
- メンバー選択ボタン: `data-feature="member"`
- 期間ナビゲーション（前へボタン）: `data-feature="navigation"`

---

### 3.3 サブスクリプションアラートモーダル

#### 表示タイミング

- 無料ユーザーが月間・年間ボタンをクリック
- 無料ユーザーが前へ/次へボタンをクリック（`offset !== 0`）
- 無料ユーザーが個別メンバーを選択

#### モーダル構成

**要素**:
- 紫色のロックアイコン（プレミアム機能を示す）
- タイトル「プレミアム機能」
- メッセージ（動的に変更可能）
- 「プランを見る」ボタン（サブスクリプション購入画面へリンク）
- 「閉じる」ボタン

**デフォルトメッセージ**:
「この機能はサブスク加入者のみ利用できます」

**メッセージ例**:
- 「月間実績を見るにはサブスク加入が必要です」
- 「過去の期間を見るにはサブスク加入が必要です」
- 「メンバー別の実績を見るにはサブスク加入が必要です」

#### 実装

**コンポーネント**: `resources/views/components/subscription-alert-modal.blade.php`

**JavaScript関数**:
```javascript
function showSubscriptionAlert(message)  // モーダル表示
function hideSubscriptionAlert()         // モーダル非表示
function handleMemberSelect(userId, hasSubscription)  // メンバー選択処理
```

---

### 3.4 月次レポートボタン

#### 表示条件

- グループ所属ユーザーのみ表示
- グループが存在しない場合は非表示

#### ボタン配置

- 実績画面ヘッダー部の期間選択ボタンの右側
- レスポンシブ: 画面幅が狭い場合はアイコンのみ表示

#### リンク先

- `/reports/monthly`（月次レポート一覧画面）

#### デザイン

- 背景色: 紫色（`bg-purple-600 hover:bg-purple-700`）
- アイコン: ドキュメントアイコン（レポートを象徴）
- テキスト: 「月次レポート」（`hidden md:inline` でレスポンシブ対応）

---

## 4. データフロー

### 4.1 リクエストフロー

```
ユーザー（ブラウザ）
    ↓ GET /reports/performance?tab=group&period=week&offset=-1&user_id=5
IndexPerformanceAction
    ↓ パラメータ検証・サブスクチェック
PerformanceService
    ↓ データ集計
ReportRepository
    ↓ DB Query
Database (tasks テーブル)
    ↓ 結果返却
PerformanceService（データ整形）
    ↓ 配列返却
IndexPerformanceAction
    ↓ ビュー変数準備
performance.blade.php（レンダリング）
    ↓ HTML + JavaScript (Chart.js)
ユーザー（ブラウザ）にグラフ表示
```

### 4.2 サブスク制限フロー

```
ユーザー操作（月間ボタンクリック）
    ↓
JavaScript: showSubscriptionAlert()
    ↓
モーダル表示
    ↓ 「プランを見る」クリック
/subscriptions（サブスク購入画面）
    ↓ 購入完了
groups.subscription_active = true
    ↓
実績画面再アクセス
    ↓
hasSubscription = true → 全機能利用可能
```

---

## 5. 画面レイアウト仕様

### 5.1 大人用テーマ

#### ヘッダー部

```
┌─────────────────────────────────────────────────────────────┐
│ ≡  📊 実績                    Weekly Monthly Yearly  📄月次 │
├─────────────────────────────────────────────────────────────┤
│ 通常タスク │ グループタスク                                  │
└─────────────────────────────────────────────────────────────┘
```

#### メインコンテンツ（グループタスク、グループ全体）

```
┌─────────────────────────────────────────────────────────────┐
│ 👥 メンバー選択: [グループ全体 ▼]                          │
├─────────────────────────────────────────────────────────────┤
│ < 前へ    📅 2025年11月4週目    次へ >                     │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  グループタスク - 2025年11月4週目                           │
│  ┌───────────────────────────────────────────────────────┐  │
│  │                                                       │  │
│  │              [Chart.js グラフ表示エリア]              │  │
│  │                                                       │  │
│  └───────────────────────────────────────────────────────┘  │
│                                                             │
│  完了: 6  未完了: 15  報酬累計: 6  獲得報酬: 320 円         │
└─────────────────────────────────────────────────────────────┘
```

### 5.2 子供用テーマ

#### ヘッダー部

```
┌─────────────────────────────────────────────────────────────┐
│ ≡  📊 実績                    Weekly Monthly Yearly  📄    │
├─────────────────────────────────────────────────────────────┤
│ やること │ クエスト                                          │
└─────────────────────────────────────────────────────────────┘
```

#### メインコンテンツ（クエスト、グループ全体）

```
┌─────────────────────────────────────────────────────────────┐
│ 👥 だれのグラフをみる？: [みんな ▼]                        │
├─────────────────────────────────────────────────────────────┤
│ < まえ    📅 2025年11月    つぎ >                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  クエスト - 2025年11月                                      │
│  ┌───────────────────────────────────────────────────────┐  │
│  │                                                       │  │
│  │              [Chart.js グラフ表示エリア]              │  │
│  │         （縮小表示: 60vh + 小さいラベル）              │  │
│  │                                                       │  │
│  └───────────────────────────────────────────────────────┘  │
│                                                             │
│  DONE: 5  YET: 5  ごうけい: 5  コイン: 290                │
│  （縮小表示: 0.65rem ラベル + 0.9rem 値）                  │
└─────────────────────────────────────────────────────────────┘
```

### 5.3 サブスク制限時の表示

#### 無料ユーザー（週間のみ）

```
┌─────────────────────────────────────────────────────────────┐
│ ≡  📊 実績                    Weekly [Monthly] [Yearly] 📄 │
│                                      ↑ グレーアウト          │
├─────────────────────────────────────────────────────────────┤
│ 通常タスク │ グループタスク                                  │
└─────────────────────────────────────────────────────────────┘

クリック時:
┌─────────────────────────────────────────────────────────────┐
│                    💬 モーダル表示                          │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ 🔒 プレミアム機能                                    │   │
│  │                                                     │   │
│  │ この機能はサブスク加入者のみ利用できます             │   │
│  │                                                     │   │
│  │ [プランを見る]  [閉じる]                            │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

#### 無料ユーザー（メンバー選択制限）

```
┌─────────────────────────────────────────────────────────────┐
│ 👥 メンバー選択:                                            │
│  ┌─────────────────────────────────────┐                   │
│  │ グループ全体                        │  ← 選択可能      │
│  │ 太郎 🔒                              │  ← グレーアウト  │
│  │ 花子 🔒                              │  ← グレーアウト  │
│  └─────────────────────────────────────┘                   │
└─────────────────────────────────────────────────────────────┘
```

---

## 6. 技術仕様

### 6.1 使用技術

**バックエンド**:
- Laravel 12
- PHP 8.3
- Action-Service-Repositoryパターン

**フロントエンド**:
- Blade Template
- Vanilla JavaScript（Alpine.js不使用）
- Chart.js（グラフ描画）
- Tailwind CSS 3

**データベース**:
- PostgreSQL 16（本番）
- SQLite（テスト）

### 6.2 主要クラス

**アクション**:
- `App\Http\Actions\Reports\IndexPerformanceAction`

**サービス**:
- `App\Services\Report\PerformanceService`
- `App\Services\Report\PerformanceServiceInterface`
- `App\Services\Subscription\SubscriptionCheckService`（Phase 1.1.8で追加）
- `App\Services\Subscription\SubscriptionCheckServiceInterface`（Phase 1.1.8で追加）

**リポジトリ**:
- `App\Repositories\Report\ReportRepository`
- `App\Repositories\Report\ReportRepositoryInterface`

**モデル**:
- `App\Models\User`
- `App\Models\Task`
- `App\Models\Group`

### 6.3 データベーススキーマ

#### tasks テーブル

```sql
id BIGINT PRIMARY KEY
user_id BIGINT NOT NULL                    -- タスク所有者
title VARCHAR(255) NOT NULL                -- タイトル
group_task_id UUID NULL                    -- グループタスクID（NULL=通常タスク）
is_completed BOOLEAN DEFAULT false         -- 完了フラグ
completed_at TIMESTAMP NULL                -- 完了日時
reward INT DEFAULT 0                       -- 報酬額
created_at TIMESTAMP
updated_at TIMESTAMP

INDEX (user_id, created_at)
INDEX (user_id, is_completed)
INDEX (group_task_id)
```

#### users テーブル

```sql
id BIGINT PRIMARY KEY
username VARCHAR(255) NOT NULL
group_id BIGINT NULL                       -- 所属グループ
group_edit_flg BOOLEAN DEFAULT false       -- グループ編集権限
theme VARCHAR(50) DEFAULT 'adult'          -- テーマ (adult | child)
```

#### groups テーブル

```sql
id BIGINT PRIMARY KEY
name VARCHAR(255)
master_user_id BIGINT NULL                 -- グループマスター
subscription_active BOOLEAN DEFAULT false  -- サブスク加入フラグ
created_at TIMESTAMP                       -- グループ作成日時
```

### 6.4 URLパラメータ

| パラメータ | 型 | デフォルト値 | 説明 |
|-----------|-----|-------------|------|
| `tab` | string | 大人: `normal`<br>子供: `group` | タブ種別（normal/group） |
| `period` | string | 大人: `week`<br>子供: `month` | 期間種別（week/month/year） |
| `offset` | int | `0` | 期間オフセット（0=当週/当月/当年） |
| `user_id` | int | `0` | メンバーID（0=グループ全体） |

**例**:
```
/reports/performance?tab=group&period=week&offset=-1&user_id=5
→ グループタスク、先週、メンバーID=5の個人データ
```

---

## 7. パフォーマンス要件

### 7.1 レスポンス時間

- **初回ロード**: 2秒以内
- **期間切替**: 1秒以内
- **メンバー切替**: 1秒以内

### 7.2 最適化手法

**データベースクエリ**:
- インデックス活用（`user_id`, `created_at`, `is_completed`）
- 必要なカラムのみ取得（`SELECT` 句最適化）
- 期間フィルタリング（`WHERE` 句で日時範囲指定）

**フロントエンド**:
- Chart.js軽量化（必要なチャートタイプのみ読み込み）
- 画像遅延読み込み（アバター画像）
- CSS/JSのminify化（Vite）

---

## 8. セキュリティ要件

### 8.1 認証・認可

**認証**:
- Laravel標準認証（`auth` ミドルウェア）
- ログインユーザーのみアクセス可能

**認可**:
- 自分自身のデータのみ閲覧可能（通常タスク）
- グループメンバーのデータは編集権限者のみ閲覧可能（グループタスク）
- 他グループのデータは閲覧不可

### 8.2 入力バリデーション

**パラメータ検証**:
- `tab`: `normal` または `group` のみ許可
- `period`: `week`, `month`, `year` のみ許可
- `offset`: 数値型、範囲チェック（-52 ～ 0 など）
- `user_id`: 数値型、同一グループメンバーのみ許可

**SQLインジェクション対策**:
- Eloquent ORMのパラメータバインディング使用
- ユーザー入力を直接SQLに埋め込まない

### 8.3 CSRF対策

- Laravel標準のCSRF保護（`@csrf` トークン）
- 全POSTリクエストでトークン検証

---

## 9. エラーハンドリング

### 9.1 エラーケース

| エラー | 原因 | 対処 |
|--------|------|------|
| 404 | グループ未所属 | エラーページ表示 |
| 403 | 他グループのデータアクセス | エラーページ表示 |
| 400 | 無効なパラメータ | デフォルト値にフォールバック |
| 500 | DB接続エラー | エラーページ + ログ記録 |

### 9.2 フォールバック処理

**無効なパラメータ**:
- `tab` が不正 → デフォルト値（大人: `normal`, 子供: `group`）
- `period` が不正 → デフォルト値（大人: `week`, 子供: `month`）
- `offset` が範囲外 → 境界値にクランプ（-52 ～ 0）
- `user_id` が無効 → 0（グループ全体）にリセット

**データ取得失敗**:
- 空のデータセット返却
- グラフに「データがありません」メッセージ表示

---

## 10. テスト要件

### 10.1 単体テスト

**対象クラス**:
- `PerformanceService`: データ集計ロジック
- `SubscriptionCheckService`: サブスク判定ロジック
- `ReportRepository`: データ取得クエリ

**テストケース**:
- 週間・月間・年間データ取得
- オフセット計算
- グループ全体・個別メンバーのデータ取得
- サブスク判定（加入済み/未加入/初月無料）

### 10.2 統合テスト

**テストシナリオ**:
- 通常タスクの週間実績表示
- グループタスクの月間実績表示（グループ全体）
- グループタスクの個別メンバー実績表示
- 期間ナビゲーション（前へ/次へ）
- サブスク制限（無料ユーザーの月間アクセス拒否）
- サブスク制限（無料ユーザーのメンバー選択制限）

### 10.3 E2Eテスト

**テストフロー**:
1. ログイン
2. 実績画面アクセス
3. タブ切替（通常タスク ⇔ グループタスク）
4. 期間切替（週間 → 月間 → 年間）
5. 期間ナビゲーション（前へ/次へ）
6. メンバー選択（グループ全体 → 個別メンバー）
7. グラフ表示確認
8. 集計データ確認

---

## 11. 運用要件

### 11.1 モニタリング

**監視項目**:
- ページロード時間
- データ取得クエリ実行時間
- エラー発生率
- ユーザーアクセス数（期間別・タブ別）

**ログ出力**:
```php
Log::info('Performance data fetched', [
    'user_id' => $user->id,
    'tab' => $tab,
    'period' => $period,
    'offset' => $offset,
    'query_time' => $queryTime,
]);
```

### 11.2 スケーラビリティ

**想定負荷**:
- 同時アクセスユーザー数: 100人
- 1ユーザーあたりのタスク数: 最大1,000件/年

**スケールアウト対策**:
- データベース読み取りレプリカ
- キャッシュ活用（Redis）
- CDN活用（静的ファイル）

---

## 12. 今後の拡張予定

### 12.1 Phase 1.1.8（実装中）

**実装内容**:
- サブスク制限機能（期間選択・メンバー選択・期間ナビゲーション）
- サブスクリプションアラートモーダル
- 月次レポートボタン追加
- 子供用テーマのレイアウト改善

**想定期間**: 5-7日

### 12.2 Phase 1.1.9（未着手）

**実装内容**:
- 包括的テスト作成（単体・統合・E2E）
- CI/CD統合
- カバレッジ80%以上達成

**想定期間**: 2-3日

### 12.3 将来的な拡張機能

**検討中の機能**:
- CSV/PDFエクスポート機能
- グラフのカスタマイズ（色・種類変更）
- 比較機能（今週 vs 先週、今月 vs 先月）
- 目標設定機能（週間目標タスク数など）
- 通知機能（目標達成時、実績更新時）

---

## 付録

### A. 用語集

| 用語 | 説明 |
|------|------|
| **通常タスク** | 個人が作成・管理するタスク。`group_task_id` が NULL。 |
| **グループタスク** | グループ内で共有されるタスク。`group_task_id` が存在。 |
| **グループ全体** | グループメンバー全員の集計データ。 |
| **編集権限** | グループ管理者・編集権限者のみ持つ権限。`group_edit_flg = true`。 |
| **オフセット** | 期間のずれ。0が当週/当月/当年、-1が前週/前月/前年。 |
| **累積データ** | 期間開始から各日までの合計値（完了数、報酬）。 |
| **サブスク加入者** | サブスクリプションに加入済みのグループに所属するユーザー。 |

### B. 参考資料

- **Laravelドキュメント**: https://laravel.com/docs/12.x
- **Chart.jsドキュメント**: https://www.chartjs.org/docs/
- **Tailwind CSSドキュメント**: https://tailwindcss.com/docs
- **関連要件定義書**:
  - `definitions/GroupTask.md`（グループタスク機能）
  - `definitions/Avatar.md`（アバター機能）
  - `docs/plans/phase1-1-stripe-subscription-plan.md`（サブスク機能）

### C. 変更履歴の詳細

#### 2025-12-01: 月次レポート画面の詳細仕様追加

**追加理由**:
- Phase 1.1.8-2実装における仕様の齟齬を解消
- 質疑応答で明確化した月次レポート画面の詳細仕様をドキュメント化

**追加内容**:
- セクション13: 月次レポート画面の完全な要件定義
- 画面構成（年月選択、グラフ、AIコメント、詳細テーブル）
- データ構造（member_task_summary、group_task_details）
- AIコメント生成仕様（OpenAI API統合、アバター性格反映）
- アクセス制御（サブスクリプション制限）

#### 2025-12-01: 初版作成

**作成理由**:
- サブスク制限機能実装に伴い、既存機能と新規機能を統合した要件定義が必要
- 質疑応答で明確化した仕様をドキュメント化

**記載内容**:
- 既存機能の網羅的な要件定義
- サブスク制限機能の詳細仕様
- データフロー・画面レイアウト・技術仕様

---

## 13. 月次レポート画面

### 13.1 概要

#### 目的

グループの月次実績を可視化し、メンバー別の達成状況、グラフによる推移、AI生成コメントを統合的に表示する。サブスクリプション加入者のみが過去レポートを閲覧可能とし、プレミアム機能としての価値を提供する。

#### 対象ユーザー

- **グループメンバー全員**: 自グループの月次レポート閲覧
- **サブスク加入者**: 過去12ヶ月分のレポート閲覧可能
- **無料ユーザー**: 当月（グループ作成後1ヶ月）のみ閲覧可能

#### 画面パス

- **ルート**: `/reports/monthly/{year}/{month}` または `/reports/monthly`（デフォルト: 前月）
- **アクション**: `App\Http\Actions\Reports\ShowMonthlyReportAction`
- **ビュー**: `resources/views/reports/monthly/show.blade.php`

---

### 13.2 画面構成

#### 13.2.1 ヘッダー部

**要素**:
- ページタイトル「月次レポート」
- 年月選択UI（スマホ対応）
- 実績画面に戻るボタン

**年月選択UI**:
- **デスクトップ**: ドロップダウン形式（年・月別々）
- **スマホ**: ネイティブ月選択ピッカー（`<input type="month">`）
- **選択可能範囲**: 過去12ヶ月（グループ作成日以降）
- **デフォルト**: 前月（`now()->subMonth()->format('Y-m')`）

**レスポンシブ対応**:
```html
<!-- デスクトップ（768px以上） -->
<select class="hidden md:block">
  <option value="2025-11">2025年11月</option>
</select>

<!-- スマホ（767px以下） -->
<input type="month" class="md:hidden" value="2025-11" min="2024-12" max="2025-11">
```

#### 13.2.2 サマリー統計

**表示項目**:
1. **通常タスク完了数**
   - 当月の完了件数
   - 前月比（例: 前月比 +15%）
   - 色分け: 増加=緑、減少=赤

2. **グループタスク完了数**
   - 当月の完了件数
   - 前月比
   - 色分け: 増加=緑、減少=赤

3. **獲得報酬合計**
   - 当月の報酬合計（円）
   - 前月比
   - 色分け: 増加=緑、減少=赤

**レイアウト**:
- 3カラムグリッド（デスクトップ）
- 1カラムスタック（スマホ）
- カード形式、アイコン付き

#### 13.2.3 グラフ表示

**グラフ1: 通常タスク月別推移（メンバー別内訳）**

**仕様**:
- **種類**: 積み上げ棒グラフ（Chart.js `bar` type, `stacked: true`）
- **データ範囲**: 直近6ヶ月
- **X軸**: 月（例: 2025-06, 2025-07, ..., 2025-11）
- **Y軸**: 完了タスク数
- **色分け**: メンバーごとに異なる色
- **凡例**: メンバー名リスト（クリックで表示/非表示切替）

**データ構造**:
```javascript
{
  labels: ['2025-06', '2025-07', '2025-08', '2025-09', '2025-10', '2025-11'],
  datasets: [
    {
      label: '太郎',
      data: [5, 7, 3, 8, 6, 10],
      backgroundColor: '#59B9C6',
      stack: 'Stack 0'
    },
    {
      label: '花子',
      data: [3, 5, 6, 4, 7, 8],
      backgroundColor: '#8B5CF6',
      stack: 'Stack 0'
    }
  ]
}
```

**グラフ2: グループタスク月別推移（メンバー別内訳）**

**仕様**:
- 通常タスクと同様の積み上げ棒グラフ
- グループタスク専用データセット
- 色分けは通常タスクと同じメンバー色を使用

#### 13.2.4 AIコメント表示

**アバターあり（グループ管理者がアバター登録済み）**:

**レイアウト**:
```
┌─────────────────────────────────────────┐
│  [アバター画像]  [吹き出し]             │
│  （happy表情）   「今月はみんながんばっ│
│                  たね！太郎くんは...」  │
└─────────────────────────────────────────┘
```

**実装**:
- アバター画像: `teacher_avatars.avatar_images` から `happy_bust` 取得
- 吹き出し: CSS `border-radius`, `::before` で三角形
- コメント: `monthly_reports.ai_comment` から取得

**アバターなし（グループ管理者がアバター未登録）**:

**レイアウト**:
```
┌─────────────────────────────────────────┐
│  📊 今月の実績概況                      │
│  ・太郎: 通常タスク10件、グループタスク│
│    8件完了（前月比+15%）               │
│  ・花子: 通常タスク8件、グループタスク │
│    6件完了（前月比+20%）               │
└─────────────────────────────────────────┘
```

**実装**:
- カード形式、アイコン付き
- テキストのみ、箇条書き
- コメント: `monthly_reports.ai_comment` から取得

**AIコメント生成仕様**:

**アバター性格パラメータ**:
```php
$personality = [
    'sex' => 'male',          // 性別（male/female）
    'tone' => 'friendly',     // 口調（formal/friendly/casual）
    'enthusiasm' => 'high',   // テンション（low/medium/high）
    'formality' => 'medium',  // 丁寧さ（low/medium/high）
    'humor' => 'medium'       // ユーモア（low/medium/high）
];
```

**プロンプト構造**:
```
あなたは以下の性格を持つ教師アバターです：
- 性別: {sex}
- 口調: {tone}
- テンション: {enthusiasm}
- 丁寧さ: {formality}
- ユーモア: {humor}

以下のグループメンバーの月次実績を、あなたの性格に合わせたしゃべり口調でコメントしてください：

【メンバー別実績】
- 太郎: 通常タスク10件、グループタスク8件完了（前月比+15%）、報酬320円
  完了した主なグループタスク: 「宿題を終わらせる」「部屋の掃除」
- 花子: 通常タスク8件、グループタスク6件完了（前月比+20%）、報酬240円
  完了した主なグループタスク: 「ピアノ練習」「お手伝い」

コメントは3-5文程度で、メンバーの頑張りを称賛し、次月への励ましを含めてください。
```

**生成タイミング**:
- `GenerateMonthlyReports` コマンド実行時
- `MonthlyReportService::generateMonthlyReport()` 内で生成
- `gpt-4o-mini` モデル使用
- トークン消費記録（個人残高には影響なし、管理者統計のみ）

#### 13.2.5 詳細データテーブル

**メンバー選択**:
- セレクトボックスで切り替え
- デフォルト: 「全メンバー」
- 選択肢: 全メンバー、太郎、花子、...

**テーブルカラム**:

| カラム | 説明 | 例 |
|--------|------|-----|
| 日時 | 完了日時 | 2025-11-15 10:30 |
| タイトル | タスク名 | 宿題を終わらせる |
| 種別 | 通常/グループ | グループ |
| 報酬 | 獲得報酬額 | 50円 |
| タグ | タスクタグ | 学習, 宿題 |

**データソース**:
- `member_task_summary`: 通常タスクデータ
- `group_task_details`: グループタスクデータ
- 両方をマージして日時順ソート

**ページング**:
- 1ページあたり20件
- ページネーションUI（Laravel標準）
- スマホ対応（簡略版ページャー）

**レスポンシブ対応**:
- デスクトップ: 全カラム表示
- スマホ: タイトル+種別+報酬のみ表示（タグは省略）

---

### 13.3 PDF生成機能

#### 13.3.1 概要

月次レポート画面および個人概況モーダルから、実績データをPDF形式でダウンロードできる機能。親が子どもの実績を確認し、SNSにシェアできるデザインを提供する。

#### 13.3.2 PDF生成の2つの用途

**全体版PDF（月次レポート詳細画面用）**

**用途**:
- グループ全体の月次実績を包括的に記録
- 親が月末に子どもたちの活動全体を振り返る資料
- SNSシェア、家族での共有

**生成元**:
- `/reports/monthly/{year}/{month}` 画面
- 画面に表示されている内容をそのままPDF化

**データ範囲**:
- 集計データのみ（各メンバー個別の並列表示なし）
- 月次レポート画面の折れ線グラフ + 棒グラフ（直近6ヶ月分）
- AIコメント（月次レポートトップのコメントと同一）
- グループ全体のサマリー統計（通常タスク完了数、グループタスク完了数、獲得報酬合計）

**個人版PDF（個人概況モーダル用）**

**用途**:
- 個別メンバーの詳細実績を記録
- 子ども一人ひとりの頑張りを称賛・共有する資料
- SNSシェア（子ども個人のハイライト）

**生成元**:
- `/reports/monthly/show` 画面の「概況レポート生成」ボタン → モーダル表示 → PDFダウンロード
- モーダルに表示された結果をPDF化

**データ範囲**:
- 個人の統計データ（通常タスク、グループタスク、報酬）
- タスク分類（最も取り組んだカテゴリ）
- 報酬推移（直近3ヶ月）
- AIコメント（個人向けカスタマイズコメント）

#### 13.3.3 掲載項目の優先順位

PDF内の情報配置は以下の優先順位に従う：

| 順位 | 項目 | 全体版 | 個人版 | 重要度 |
|------|------|--------|--------|--------|
| 1 | ヘッダー | ✅ | ✅ | 必須 |
| 2 | 報酬推移 | ✅ | ✅ | **最重要** |
| 3 | 統計サマリー | ✅ | ✅ | 高 |
| 4 | タスク傾向 | ✅ | ✅ | 高 |
| 5 | タスク内訳 | ✅ | ✅ | 中 |
| 6 | AIコメント | ✅ | ✅ | **重要** |
| 7 | フッター | ✅ | ✅ | 必須 |

**補足**:
- **報酬推移**: 子どもにとって次のお小遣いに直結する最重要データ。大きなフォントで目立たせる。
- **AIコメント**: 子どもへの励まし・称賛の言葉。アバターと一緒に表示。

#### 13.3.4 デザイン要件

**デザインコンセプト**:
- **インフォグラフィック重視**: データビジュアライゼーション中心
- **多様な色彩**: 単調にならない多色使用（添付画像参照）
- **強弱のある弁当レイアウト**: 優先順位に基づいてサイズ・配置に強弱をつける
- **子ども向け**: 集中力のない子どもでもパッと見で理解できる
- **SNS映え**: 親がSNSにシェアしたくなるデザイン

**具体的表現**:
- 報酬額: **超特大フォント**（96pt以上）、金色/黄色のグラデーション
- 完了タスク数: 大きな数字（48pt以上）、アイコン+色分け
- グラフ: カラフルな配色、グラデーション使用
- アバター: happy表情、吹き出し付き
- 背景: 白ベース、部分的にガラスモーフィズム（透過+blur）使用

**配色**:
- メインカラー: #59B9C6（ティール）
- アクセントカラー: メインカラーの反対色（#C6595B - コーラルレッド系）
- 背景: 白ベース（`#FFFFFF`）
- カード: ガラスモーフィズム（`rgba(255, 255, 255, 0.7)` + `backdrop-filter: blur(10px)`）
  - **注意**: dompdfは`backdrop-filter`非対応のため、代替として白背景+影で表現

#### 13.3.5 レイアウト詳細

**全体版PDF（複数ページ可）**

**ページ1: サマリー**
```
┌─────────────────────────────────────────────────────────────┐
│ [ヘッダー]                                                  │
│ グループ名：〇〇家  対象月：2025年11月                     │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  [報酬推移グラフ - 大きく表示]                              │
│  直近6ヶ月の折れ線グラフ + 棒グラフ                         │
│  カラフル、グラデーション使用                               │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│ [統計サマリー - 3カラム]                                    │
│  通常タスク: 25件 ↑15%   グループタスク: 18件 ↑20%        │
│  獲得報酬: 560円 ↑25%                                       │
├─────────────────────────────────────────────────────────────┤
│ [AIコメント - アバター付き]                                 │
│  [アバター画像] 「今月はみんながんばったね！...」          │
├─────────────────────────────────────────────────────────────┤
│ [フッター]  生成日時: 2025-12-01 10:30                     │
└─────────────────────────────────────────────────────────────┘
```

**ページ2: 詳細データ（必要に応じて）**
```
┌─────────────────────────────────────────────────────────────┐
│ [タスク内訳テーブル]                                        │
│ 日時       │ タイトル           │ 種別     │ 報酬 │ タグ    │
│ 11/01 10:30│ 宿題を終わらせる    │ グループ │ 50円 │ 学習    │
│ ...                                                         │
└─────────────────────────────────────────────────────────────┘
```

**個人版PDF（複数ページ可）**

**ページ1: 個人サマリー**
```
┌─────────────────────────────────────────────────────────────┐
│ [ヘッダー]                                                  │
│ 太郎くんの実績レポート  対象月：2025年11月                 │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  [報酬額 - 超特大表示]                                      │
│            💰 320円                                         │
│       （96pt以上、金色グラデーション）                      │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│ [報酬推移グラフ - 3ヶ月分]                                  │
│  折れ線グラフ、カラフル表現                                 │
├─────────────────────────────────────────────────────────────┤
│ [統計サマリー - 2カラム]                                    │
│  通常タスク: 10件 ↑15%   グループタスク: 8件 ↑20%         │
├─────────────────────────────────────────────────────────────┤
│ [タスク分類]                                                │
│  最も取り組んだカテゴリ: 学習（6件）                        │
│  円グラフまたはアイコン表示                                 │
├─────────────────────────────────────────────────────────────┤
│ [AIコメント - アバター付き]                                 │
│  [アバター画像] 「今月は320円もがんばったね！...」         │
├─────────────────────────────────────────────────────────────┤
│ [フッター]  生成日時: 2025-12-01 10:30                     │
└─────────────────────────────────────────────────────────────┘
```

#### 13.3.6 技術仕様

**ライブラリ**:
- `barryvdh/laravel-dompdf` v3.1.1
- dompdf v3.1.4（HTML/CSSレンダリングエンジン）

**PDF設定**:
- サイズ: A4（210mm × 297mm）
- 向き: Portrait（縦）
- フォント: DejaVu Sans（日本語対応）
- 画像埋め込み: Base64エンコード

**制約事項**:
- `backdrop-filter`非対応 → 白背景+影で代替
- CSS Grid/Flexboxは限定的にサポート → テーブルレイアウト併用
- 印刷前提ではない（画面表示用）
- ファイルサイズは大きくなっても許容

**生成フロー（全体版）**:
```
ユーザー: 月次レポート画面でPDFダウンロードボタンクリック
    ↓
DownloadMonthlyReportPdfAction: リクエスト受信
    ↓
MonthlyReportService::generateMonthlyReportPdfData(): データ生成
    ↓ 月次レポート集計データ取得
    ↓ グラフ画像（Base64）取得（フロントエンドから）
    ↓ AIコメント取得
Pdf::loadView('reports.monthly.monthly-report-pdf', $data): PDF生成
    ↓
return response()->download(): ダウンロードレスポンス
```

**生成フロー（個人版）**:
```
ユーザー: 概況レポート生成 → モーダル表示 → PDFダウンロードボタンクリック
    ↓
DownloadMemberSummaryPdfAction: リクエスト受信
    ↓
MonthlyReportService::generateMemberSummaryPdfData(): データ生成
    ↓ 個人統計データ取得
    ↓ タスク分類データ取得
    ↓ 報酬推移（3ヶ月）取得
    ↓ AIコメント取得
Pdf::loadView('reports.monthly.member-summary-pdf', $data): PDF生成
    ↓
return response()->download(): ダウンロードレスポンス
```

#### 13.3.7 AIコメント生成ロジック（個人版）

**優先順位に沿ったコメント構成**:

個人概況用のAIコメントは、掲載項目の優先順位（報酬 → 統計サマリー → タスク傾向 → ...）に従って構成する。

**修正後のプロンプト構造**:
```
あなたは子どもの学習・生活習慣を支援する教師アバターです。

以下の{メンバー名}の月次実績データに基づいて、優先順位の高い情報から順にコメントしてください：

【最優先: 報酬情報】
- 獲得報酬: {reward}円（前月比{change_percentage}%）
- コメント例: 「今月は{reward}円もがんばったね！」

【次点: タスク完了状況】
- 通常タスク: {normalTaskCount}件完了
- グループタスク: {groupTaskCount}件完了
- コメント例: 「通常タスクを{normalTaskCount}件、グループタスクを{groupTaskCount}件も達成したね！」

【その他: タスク傾向】
- 最も取り組んだカテゴリ: {topCategory}（{分類データ}）
- コメント例: 「特に{topCategory}をがんばっていたね」

コメントは3-5文程度で、報酬を最初に称賛し、次にタスク完了状況、最後にタスク傾向の順で記述してください。
子どもが喜ぶ、励まされるトーンでお願いします。
```

**実装箇所**:
- `app/Services/Report/MonthlyReportService.php`
- `generateMemberSummaryComment()` メソッド

**修正内容**:
- プロンプト内のデータ提示順序を「報酬 → タスク完了 → タスク傾向」に変更
- 「優先順位に従ってコメントしてください」という指示を追加

#### 13.3.8 今後の実装計画

**Phase 1: 要件定義（本セクション）** ✅
- PDF生成機能の用途・デザイン要件の明確化
- 全体版・個人版の差異の定義
- Performance.mdへの要件記載

**Phase 2: コメント生成ロジック修正**
- `MonthlyReportService::generateMemberSummaryComment()` のプロンプト修正
- 報酬情報を最初に配置する構成に変更

**Phase 3: デザイン提案作成**
- インフォグラフィック重視のPDFテンプレートデザイン提案
- 弁当レイアウト案、配色案、フォントサイズ案を複数パターン提示

**Phase 4: 個人版PDF再設計**
- 既存テンプレート（member-summary-pdf.blade.php）の再構成
- 報酬を超特大表示、優先順位に基づく強弱配置

**Phase 5: 全体版PDF実装**
- 新規テンプレート（monthly-report-pdf.blade.php）作成
- 月次レポート詳細画面からのPDF生成機能実装

**Phase 6: テスト・調整**
- 実際のデータでPDF生成テスト
- デザイン調整、レイアウト最適化

---

### 13.4 データ構造

#### 13.4.1 monthly_reports テーブル

**スキーマ**:
```sql
CREATE TABLE monthly_reports (
    id BIGINT PRIMARY KEY,
    group_id BIGINT NOT NULL,
    report_month DATE NOT NULL COMMENT 'YYYY-MM-01',
    generated_at TIMESTAMP NULL,
    
    -- メンバー別通常タスク集計
    member_task_summary JSON NULL COMMENT '{user_id: {name, completed_count, reward, tasks: [{title, completed_at}]}}',
    
    -- グループタスク集計
    group_task_summary JSON NULL COMMENT '{user_id: {name, completed_count, reward, tasks: [{title, reward, completed_at, tags}]}}',
    group_task_completed_count INT DEFAULT 0,
    group_task_total_reward INT DEFAULT 0,
    
    -- 前月比データ
    normal_task_count_previous_month INT DEFAULT 0,
    group_task_count_previous_month INT DEFAULT 0,
    reward_previous_month INT DEFAULT 0,
    
    -- AIコメント
    ai_comment TEXT NULL COMMENT 'OpenAI生成コメント',
    ai_comment_tokens_used INT DEFAULT 0 COMMENT '消費トークン数',
    
    -- PDFファイル
    pdf_path VARCHAR(255) NULL,
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    UNIQUE (group_id, report_month),
    INDEX (report_month),
    INDEX (generated_at)
);
```

#### 13.4.2 member_task_summary 構造

```json
{
  "14": {
    "name": "太郎",
    "completed_count": 10,
    "reward": 320,
    "tasks": [
      {
        "title": "宿題を終わらせる",
        "completed_at": "2025-11-15 10:30:00"
      },
      {
        "title": "部屋の掃除",
        "completed_at": "2025-11-16 14:20:00"
      }
    ]
  },
  "15": {
    "name": "花子",
    "completed_count": 8,
    "reward": 240,
    "tasks": [...]
  }
}
```

#### 13.4.3 group_task_summary 構造

```json
{
  "14": {
    "name": "太郎",
    "completed_count": 8,
    "reward": 400,
    "tasks": [
      {
        "title": "ピアノ練習",
        "reward": 50,
        "completed_at": "2025-11-17 16:00:00",
        "tags": ["音楽", "練習"]
      }
    ]
  },
  "15": {
    "name": "花子",
    "completed_count": 6,
    "reward": 300,
    "tasks": [...]
  }
}
```

---

### 13.5 アクセス制御

#### サブスクリプション制限

**無料ユーザー**:
- ✅ 当月レポート閲覧可能（グループ作成後1ヶ月間）
- ❌ 過去月レポート閲覧不可（ロック画面表示）

**サブスク加入者**:
- ✅ 過去12ヶ月分のレポート閲覧可能
- ✅ 全機能利用可能

**判定ロジック**:
```php
public function canAccessReport(Group $group, string $yearMonth): bool
{
    // サブスク加入者は全期間アクセス可能
    if ($group->subscription_active === true) {
        return true;
    }
    
    // 無料ユーザーは初月のみ
    $groupCreatedAt = Carbon::parse($group->created_at);
    $firstMonthEnd = $groupCreatedAt->copy()->addMonth()->endOfMonth();
    $targetMonth = Carbon::createFromFormat('Y-m', $yearMonth);
    
    return $targetMonth->lte($firstMonthEnd);
}
```

**ロック画面**:
- 半透明オーバーレイ
- 🔒 アイコン + メッセージ「過去のレポートを見るにはサブスクリプションが必要です」
- 「プランを見る」ボタン → `/subscriptions`

---

### 13.6 技術仕様

#### 使用ライブラリ

- **Chart.js**: グラフ描画（v4.x）
- **OpenAI PHP SDK**: AIコメント生成
- **Tailwind CSS**: スタイリング
- **Vanilla JavaScript**: インタラクティブ機能

#### パフォーマンス

**最適化手法**:
- レポートデータはJSON形式で事前集計済み
- グラフデータは最大6ヶ月分のみ
- 詳細テーブルはページングで20件ずつ表示
- 画像遅延読み込み（アバター）

**キャッシュ戦略**:
- レポートデータは月次で静的（更新頻度低い）
- `Cache::remember()` で1日キャッシュ
- 手動再生成時はキャッシュクリア

---

### 13.7 エラーハンドリング

#### レポート未生成の場合

**表示内容**:
```
┌─────────────────────────────────────────┐
│  📊 レポートが存在しません              │
│                                         │
│  選択された月のレポートはまだ生成され  │
│  ていません。レポートは毎月1日の午前2  │
│  時に自動生成されます。                │
│                                         │
│  [実績画面に戻る]                       │
└─────────────────────────────────────────┘
```

#### アクセス権限エラー

**表示内容**:
- サブスク未加入者が過去月にアクセス → ロック画面表示
- 他グループのレポートにアクセス → 404エラー

---

### 13.8 実装優先順位

#### Phase 1.1.8-2（現在実装中）

1. **データベースマイグレーション修正**
   - `ai_comment` カラム追加
   - `ai_comment_tokens_used` カラム追加
   - `group_task_summary` カラム追加（`group_task_details` から変更）

2. **一覧画面を詳細画面に変更**
   - `IndexMonthlyReportAction` → `ShowMonthlyReportAction`
   - ルート変更: `/reports/monthly/{year}/{month}`
   - 年月選択UI実装

3. **グラフ表示実装**
   - Chart.js統合
   - 通常タスク積み上げ棒グラフ
   - グループタスク積み上げ棒グラフ
   - 直近6ヶ月データ取得ロジック

4. **AIコメント表示エリア**
   - アバター画像+吹き出し
   - アバターなしの場合のテキスト表示

5. **詳細データテーブル**
   - メンバー選択セレクトボックス
   - ページング機能
   - タグ表示

6. **AIコメント生成機能**
   - `MonthlyReportService::generateAIComment()`
   - OpenAI API統合
   - アバター性格パラメータ取得
   - トークン消費記録

#### Phase 1.1.8-3（次フェーズ）

1. **PDF出力機能**
   - Dompdf統合
   - レポートPDF生成
   - S3アップロード
   - ダウンロードアクション

---

### 13.9 テスト要件

#### 単体テスト

**対象**:
- `MonthlyReportService::generateMonthlyReport()`
- `MonthlyReportService::generateAIComment()`
- `MonthlyReportService::canAccessReport()`

**テストケース**:
- メンバー別データ集計
- 前月比計算
- AIコメント生成（モック使用）
- アクセス権限判定（サブスク/無料）

#### 統合テスト

**シナリオ**:
- レポート生成コマンド実行
- 詳細画面表示（サブスク加入者）
- ロック画面表示（無料ユーザー）
- 年月選択・切り替え
- メンバー別データ表示

---

## 14. モバイルAPI仕様（Phase 2.B-6）

### 14.1 実績データ取得API

**エンドポイント**: `GET /api/reports/performance`

**実装**: `App\Http\Actions\Api\Report\IndexPerformanceApiAction`

**パラメータ**:
- `period`: `week` | `month` | `year`（デフォルト: `week`）
- `tab`: `normal` | `group`（デフォルト: `normal`）
- `offset`: 整数（0=今週/今月/今年、-1=先週/先月/昨年）
- `user_id`: 整数（グループタスクのみ、0=全体）

**レスポンス**: Web版と同じ構造（`IndexPerformanceAction`と共通）

---

### 14.2 月次レポート詳細取得API

**エンドポイント**: `GET /api/reports/monthly/{year}/{month}`

**実装**: `App\Http\Actions\Api\Report\ShowMonthlyReportApiAction`

**パラメータ**:
- `year`: 年（YYYY形式、オプション、デフォルト: 前月の年）
- `month`: 月（MM形式、オプション、デフォルト: 前月の月）

**レスポンス**:
```json
{
  "message": "月次レポートを取得しました。",
  "data": {
    "report": { /* MonthlyReportモデル */ },
    "formatted": { /* formatReportForDisplay()の結果 */ },
    "target_month": "2025-11-01",
    "year_month": "2025-11",
    "year": "2025",
    "month": "11",
    "available_months": [ /* 利用可能な月リスト */ ],
    "trend_data": { /* 直近6ヶ月のグラフデータ */ }
  }
}
```

**エラーレスポンス**:
- **404 Not Found** (レポート未生成): 
  ```json
  {
    "message": "レポートが見つかりません。",
    "year_month": "2025-11",
    "not_generated": true
  }
  ```
- **403 Forbidden** (サブスク制限):
  ```json
  {
    "message": "このレポートへのアクセス権限がありません。サブスクリプションが必要です。",
    "locked": true,
    "year_month": "2025-11",
    "subscription_required": true
  }
  ```

**Web版との差異**: なし（同じ実装ロジック）

---

### 14.3 利用可能月リスト取得API（新規追加）

**エンドポイント**: `GET /api/reports/monthly/available-months`

**実装**: `App\Http\Actions\Api\Report\GetAvailableMonthsApiAction`

**目的**: 
- 実際に生成済みのレポート月のみを返却
- クライアント側で存在しない月を選択してしまう404エラーを防止
- Web版の`MonthlyReportService::getAvailableMonths()`と同じロジック

**パラメータ**: なし（認証済みユーザーのグループに基づいて取得）

**レスポンス**:
```json
{
  "message": "利用可能な月リストを取得しました。",
  "data": [
    {
      "year": "2025",
      "month": "11",
      "label": "2025年11月"
    },
    {
      "year": "2025",
      "month": "10",
      "label": "2025年10月"
    }
  ]
}
```

**返却順序**: 新しい順（直近の月が配列の先頭）

**モバイル側の利用**:
```typescript
// mobile/src/services/performance.service.ts
export const getAvailableMonths = async (): Promise<AvailableMonth[]> => {
  const response = await api.get<ApiResponse<AvailableMonth[]>>(
    '/reports/monthly/available-months'
  );
  return response.data.data;
};

// mobile/src/hooks/usePerformance.ts
const fetchAvailableMonths = useCallback(async () => {
  const months = await performanceService.getAvailableMonths();
  setAvailableMonths(months);
  
  // 最新の利用可能月を初期選択（配列の最初の要素）
  if (months.length > 0 && !selectedYear && !selectedMonth) {
    const latestMonth = months[0];
    setSelectedYear(latestMonth.year);
    setSelectedMonth(latestMonth.month);
  }
}, [selectedYear, selectedMonth]);
```

**Web版との違い**:
- Web版: ビュー内で`MonthlyReportService::getAvailableMonths()`を直接呼び出し
- モバイル版: 専用APIエンドポイントを経由して取得

---

**ドキュメント終了**

---

## 15. モバイル専用仕様: メンバー別概況画面

### 15.1 概要

**目的**: 
- Web版のモーダル表示をモバイルでは専用画面として実装
- トークン消費による生成結果を確実に表示し、アプリクラッシュを防止
- AsyncStorageによるキャッシュ機能で対象月別にデータを保持

**Web版との違い**:
| 項目 | Web版 | モバイル版 |
|------|-------|-----------|
| 表示方式 | モーダル | 専用画面（スタックナビゲーション） |
| 閉じる時の警告 | モーダルの×ボタン・オーバーレイクリック | 戻るボタン（ハードウェア含む） |
| データ保持 | セッション（モーダル閉じると破棄） | AsyncStorageキャッシュ（対象月別） |
| グラフライブラリ | Chart.js | react-native-chart-kit |
| PDF生成 | 即時実装 | 将来実装（ボタンのみ配置） |

### 15.2 画面遷移フロー

```
MonthlyReportScreen
  ↓ [メンバー選択 → AIサマリーボタン押下]
  ↓ [トークン消費確認ダイアログ]
  ↓ [API呼び出し + データ検証]
  ↓ [AsyncStorageキャッシュチェック]
  ↓ [成功時]
  ↓
MemberSummaryScreen
  ├─ ヘッダー: カスタム戻るボタン（確認ダイアログ付き）
  ├─ AIコメント表示エリア
  ├─ タスク分類円グラフ (PieChart)
  ├─ 報酬推移折れ線グラフ (LineChart)
  ├─ トークン消費量表示
  ├─ PDFダウンロードボタン（無効化・TODO付き）
  └─ 生成日時フッター
  
  [戻るボタン押下]
  ↓ [確認ダイアログ表示]
  ↓ [「戻る」選択]
  ↓
MonthlyReportScreen（元の画面に戻る）
```

### 15.3 データフロー

#### 15.3.1 API呼び出しとデータ変換

**Service層** (`mobile/src/services/performance.service.ts`):
```typescript
export const generateMemberSummary = async (
  request: GenerateMemberSummaryRequest,
  userName: string
): Promise<MemberSummaryData> => {
  // キャッシュキー: member_summary_{user_id}_{year_month}
  const cacheKey = `${MEMBER_SUMMARY_CACHE_KEY_PREFIX}${request.user_id}_${request.year_month}`;
  
  // キャッシュチェック
  const cached = await AsyncStorage.getItem(cacheKey);
  if (cached) {
    return JSON.parse(cached); // キャッシュヒット
  }
  
  // API呼び出し
  const response = await api.post<ApiResponse<MemberSummaryResponse>>(
    '/reports/monthly/member-summary',
    request
  );
  
  // 生データ → 画面表示用データ変換
  const summaryData: MemberSummaryData = {
    user_id: apiData.user_id,
    user_name: userName,
    year_month: apiData.year_month,
    comment: apiData.summary.comment,
    task_classification: apiData.summary.task_classification,
    reward_trend: apiData.summary.reward_trend,
    tokens_used: apiData.summary.tokens_used,
    generated_at: new Date().toISOString(),
  };
  
  // キャッシュ保存
  await AsyncStorage.setItem(cacheKey, JSON.stringify(summaryData));
  
  return summaryData;
};
```

**Hook層** (`mobile/src/hooks/usePerformance.ts`):
```typescript
const generateMemberSummary = useCallback(
  async (userId: number, userName: string): Promise<MemberSummaryData | null> => {
    // データ検証
    if (!selectedYear || !selectedMonth || !user?.group_id) {
      throw new Error('必要なデータが不足しています');
    }
    
    const yearMonth = `${selectedYear}-${selectedMonth}`;
    
    // Service層でキャッシュチェック + API呼び出し + データ変換
    const result = await performanceService.generateMemberSummary(
      { user_id: userId, group_id: user.group_id, year_month: yearMonth },
      userName
    );
    
    // レスポンス検証
    if (!result.comment || !result.task_classification || !result.reward_trend) {
      throw new Error('サマリーデータの形式が不正です');
    }
    
    return result;
  },
  [selectedYear, selectedMonth, user]
);
```

#### 15.3.2 キャッシュ戦略

**キャッシュキー形式**: `member_summary_{user_id}_{year_month}`

**対象月別キャッシュの動作**:
```
例1: 2025-11のサマリー生成
  → キャッシュキー: member_summary_2_2025-11
  → 次回2025-11のサマリー表示時はキャッシュヒット（API呼び出しなし）

例2: 2025-12に月を変更してサマリー生成
  → キャッシュキー: member_summary_2_2025-12（別キー）
  → キャッシュミス → API呼び出し → 新規キャッシュ保存
```

**キャッシュ無効化**: 対象月が異なれば自動的に別キーとなり、古いキャッシュは参照されない

**メリット**:
- トークン節約: 同じ月のサマリーを再表示する際はAPIコールなし
- オフライン対応: 一度生成したサマリーはオフラインでも閲覧可能
- パフォーマンス向上: 即座にデータ表示

### 15.4 画面実装詳細

#### 15.4.1 MemberSummaryScreen.tsx

**ファイルパス**: `mobile/src/screens/reports/MemberSummaryScreen.tsx`

**主要コンポーネント**:
- **ヘッダー**: `useLayoutEffect`でカスタム戻るボタン設定
- **AIコメントセクション**: アイコン付きカード、複数行テキスト表示
- **タスク分類グラフ**: PieChart（react-native-chart-kit）、凡例付き
- **報酬推移グラフ**: LineChart、ベジェ曲線、Y軸フォーマット
- **トークン消費表示**: 情報アイコン付き、数値フォーマット
- **PDFボタン**: 無効化状態、TODOコメント付き

**テーマ対応**: `useColorScheme()`でダーク/ライトモード自動切替

#### 15.4.2 戻るボタンの確認ダイアログ

**実装箇所**: `MemberSummaryScreen.tsx`の`handleBackPress()`

**ダイアログ内容**:
```javascript
Alert.alert(
  'レポートを閉じますか？',
  'このレポートはトークンを消費して生成されています。\n戻ると生成結果が破棄されます。\n\n本当に戻ってもよろしいですか？',
  [
    { text: 'キャンセル', style: 'cancel' },
    { text: '戻る', style: 'destructive', onPress: () => navigation.goBack() }
  ]
);
```

**発動タイミング**:
- ヘッダーの戻るボタン（←）タップ
- Androidのハードウェア戻るボタン（`useLayoutEffect`でインターセプト）

**Web版との文言統一**:
- Web版: "このレポートはトークンを消費して生成されています。\n閉じると生成結果が破棄されます。\n\n本当に閉じてもよろしいですか？"
- モバイル版: "戻ると" に変更（画面遷移の文脈に合わせる）

#### 15.4.3 グラフ実装

**タスク分類円グラフ** (PieChart):
```typescript
const getPieChartData = () => {
  const colors = [
    'rgba(59, 130, 246, 0.9)',   // blue
    'rgba(168, 85, 247, 0.9)',   // purple
    'rgba(236, 72, 153, 0.9)',   // pink
    'rgba(16, 185, 129, 0.9)',   // green
    'rgba(251, 146, 60, 0.9)',   // orange
    'rgba(250, 204, 21, 0.9)',   // yellow
  ];

  return data.task_classification.labels.map((label, index) => ({
    name: label,
    population: data.task_classification.data[index],
    color: colors[index % colors.length],
    legendFontColor: isDark ? '#e5e7eb' : '#374151',
    legendFontSize: 12,
  }));
};
```

**報酬推移折れ線グラフ** (LineChart):
```typescript
const getLineChartData = () => {
  return {
    labels: data.reward_trend.labels,
    datasets: [{
      data: data.reward_trend.data,
      color: (opacity = 1) => `rgba(251, 146, 60, ${opacity})`,
      strokeWidth: 3,
    }],
  };
};

// Y軸フォーマット
formatYLabel={(value) => `${parseInt(value).toLocaleString()}円`}
```

### 15.5 エラーハンドリング（アプリクラッシュ対策）

**Option B実装: データ検証 + 画面遷移分離**

#### 15.5.1 MonthlyReportScreen.tsx

```typescript
const handleGenerateSummary = async (userId: number, userName: string) => {
  // サブスクチェック
  if (!report?.has_subscription) {
    Alert.alert('プレミアム機能', 'サブスクリプションが必要です');
    return;
  }

  Alert.alert(
    'AI生成サマリー',
    `${userName}さんの月次サマリーを生成しますか？\n（トークンを消費します）`,
    [
      { text: 'キャンセル', style: 'cancel' },
      {
        text: '生成',
        onPress: async () => {
          setGeneratingSummary(userId);
          try {
            // ✅ データ検証済みのサマリーデータを取得
            const summaryData = await generateMemberSummary(userId, userName);
            
            if (summaryData) {
              // ✅ 検証済みデータを持って専用画面に遷移
              navigation.navigate('MemberSummary', { data: summaryData });
            } else {
              throw new Error('サマリーデータの取得に失敗しました');
            }
          } catch (error: any) {
            console.error('[MonthlyReportScreen] サマリー生成エラー:', error);
            Alert.alert('エラー', error.message || 'サマリーの生成に失敗しました');
          } finally {
            setGeneratingSummary(null);
          }
        },
      },
    ]
  );
};
```

**重要ポイント**:
1. **画面遷移前にデータ検証**: `generateMemberSummary()`内で構造チェック
2. **try-catchで確実にエラー捕捉**: アプリクラッシュを防止
3. **検証済みデータのみ渡す**: `navigation.navigate('MemberSummary', { data })`

#### 15.5.2 usePerformance.ts

```typescript
const generateMemberSummary = useCallback(
  async (userId: number, userName: string): Promise<MemberSummaryData | null> => {
    // パラメータ検証
    if (!selectedYear || !selectedMonth) {
      throw new Error('年月が選択されていません');
    }
    if (!user?.group_id) {
      throw new Error('グループIDが取得できません');
    }

    try {
      const yearMonth = `${selectedYear}-${selectedMonth}`;
      
      // Service層でキャッシュチェック + API呼び出し + データ変換
      const result = await performanceService.generateMemberSummary(
        { user_id: userId, group_id: user.group_id, year_month: yearMonth },
        userName
      );
      
      // ✅ データ検証
      if (!result.comment || !result.task_classification || !result.reward_trend) {
        console.error('[useMonthlyReport] 不正なレスポンス構造:', result);
        throw new Error('サマリーデータの形式が不正です');
      }
      
      return result;
    } catch (err: any) {
      console.error('[useMonthlyReport] メンバーサマリー生成エラー:', err);
      throw new Error(err.response?.data?.message || 'サマリーの生成に失敗しました');
    }
  },
  [selectedYear, selectedMonth, user]
);
```

**エラーハンドリングの階層**:
1. **Service層**: キャッシュエラー、API通信エラー
2. **Hook層**: パラメータ不足、レスポンス構造不正
3. **Screen層**: UI操作エラー、ナビゲーションエラー

### 15.6 PDF生成機能（将来実装）

**現状**: ボタンのみ配置、無効化状態

**実装予定時の作業**:
```typescript
// TODO: PDF生成機能実装
// - React Native Blob Util等でPDFダウンロード
// - バックエンドAPI: POST /reports/monthly/member-summary/pdf
// - リクエストボディ: { user_id, year_month, comment, chart_image }
```

**ボタン実装**:
```tsx
<TouchableOpacity
  style={[styles.pdfButton, styles.pdfButtonDisabled]}
  disabled={true}
>
  <Ionicons name="download-outline" size={20} color="#9ca3af" />
  <Text style={styles.pdfButtonTextDisabled}>
    PDFダウンロード（準備中）
  </Text>
</TouchableOpacity>
```

### 15.7 型定義

**MemberSummaryData** (`mobile/src/types/performance.types.ts`):
```typescript
export interface MemberSummaryData {
  user_id: number;
  user_name: string;
  year_month: string;
  comment: string;
  task_classification: {
    labels: string[];
    data: number[];
  };
  reward_trend: {
    labels: string[];
    data: number[];
  };
  tokens_used: number;
  generated_at: string;
}

export interface MemberSummaryCacheKey {
  user_id: number;
  year_month: string;
}
```

### 15.8 ナビゲーション設定

**AppNavigator.tsx**:
```tsx
import MemberSummaryScreen from '../screens/reports/MemberSummaryScreen';

// ...

<Stack.Screen
  name="MemberSummary"
  component={MemberSummaryScreen}
  options={{
    title: 'メンバー別概況',
  }}
/>
```

---

**ドキュメント終了**
