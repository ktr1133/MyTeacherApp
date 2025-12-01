# 実績画面 要件定義書

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|-----|
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

#### 2025-12-01: 初版作成

**作成理由**:
- サブスク制限機能実装に伴い、既存機能と新規機能を統合した要件定義が必要
- 質疑応答で明確化した仕様をドキュメント化

**記載内容**:
- 既存機能の網羅的な要件定義
- サブスク制限機能の詳細仕様
- データフロー・画面レイアウト・技術仕様

---

**ドキュメント終了**
