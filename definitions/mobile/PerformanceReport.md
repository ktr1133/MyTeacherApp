# 実績・レポート機能（モバイル版） 要件定義書

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-07 | GitHub Copilot | 初版作成: モバイルアプリ実績・レポート機能（Chart.js移植、PDF生成、共有機能） |

---

## 1. 概要

MyTeacher モバイルアプリにおける実績・レポート機能は、ユーザーのタスク達成状況をグラフで可視化し、月次レポートをPDF生成・共有できる機能です。Web版の `react-native-chart-kit` への移植、PDFレンダリング、ネイティブ共有機能を実装します。

### 1.1 採用技術

**グラフライブラリ**: `react-native-chart-kit` v6.12.0
- Web版Chart.jsをReact Native向けに移植
- SVGベースのレンダリング（iOS/Android対応）
- 棒グラフ、折れ線グラフ、円グラフ対応

**PDF生成**: `react-native-html-to-pdf` または `@react-pdf/renderer`
- HTMLテンプレートからPDF生成（Web版と同じレイアウト）
- 日本語フォント埋め込み対応

**共有機能**: `expo-sharing` v14.0.8
- ネイティブ共有ダイアログ表示
- メール、クラウドストレージ、メッセージアプリへの共有
- iOS: `UIActivityViewController`
- Android: Intent ACTION_SEND

### 1.2 対応プラットフォーム

| プラットフォーム | 実装状況 | グラフライブラリ | PDF生成 | 共有機能 |
|----------------|---------|----------------|---------|---------|
| **Web** | ✅ 実装済み | Chart.js | Blade PDF | ブラウザダウンロード |
| **モバイル** | 🎯 Phase 2.B-6実装予定 | react-native-chart-kit | react-native-html-to-pdf | expo-sharing |

---

## 2. 実績画面機能

### 2.1 機能要件

**概要**: ユーザーのタスク実績（通常タスク・グループタスク）をグラフと集計データで表示する画面。

**アクセスルート**:
- **モバイル**: `PerformanceScreen`

**API**:
- `GET /api/reports/performance` - 実績データ取得

**クエリパラメータ**:

| パラメータ | 型 | 説明 | 例 |
|-----------|-----|------|-----|
| `period` | string | 期間種別（week, month, year） | `week` |
| `date` | string | 基準日（YYYY-MM-DD） | `2025-12-07` |
| `type` | string | タスク種別（normal, group） | `normal` |
| `user_id` | integer | メンバーID（グループタスク時） | `123` |

**出力項目**:

| 項目 | 型 | 説明 |
|------|-----|------|
| `period_label` | string | 期間ラベル（例: "2025年11月4週目"） |
| `task_type` | string | タスク種別（normal, group） |
| `chart_data` | object | グラフデータ（labels, datasets） |
| `summary` | object | 集計データ（完了数、報酬合計等） |
| `can_navigate_prev` | boolean | 前の期間へ移動可能か |
| `can_navigate_next` | boolean | 次の期間へ移動可能か |

**レスポンス例**:
```json
{
  "success": true,
  "data": {
    "period_label": "2025年12月1週目",
    "task_type": "normal",
    "chart_data": {
      "labels": ["12/1", "12/2", "12/3", "12/4", "12/5", "12/6", "12/7"],
      "datasets": [
        {
          "label": "完了数",
          "data": [3, 5, 2, 4, 6, 3, 4],
          "backgroundColor": "rgba(89, 185, 198, 0.8)"
        },
        {
          "label": "累積完了数",
          "data": [3, 8, 10, 14, 20, 23, 27],
          "type": "line",
          "borderColor": "rgba(89, 185, 198, 1)"
        }
      ]
    },
    "summary": {
      "total_completed": 27,
      "total_reward": 135000,
      "average_per_day": 3.9
    },
    "can_navigate_prev": true,
    "can_navigate_next": false
  }
}
```

### 2.2 画面構成

**PerformanceScreen.tsx**:
- **ヘッダー**:
  - タイトル「実績」（theme = adult）/ 「実績」（theme = child）
  - 月次レポートボタン（グループ所属ユーザーのみ）
  
- **期間選択タブ**:
  - 週間 / 月間 / 年間
  
- **タスク種別タブ**:
  - 通常タスク / グループタスク
  
- **メンバー選択ドロップダウン**（グループタスク時、編集権限者のみ）:
  - グループ全体（デフォルト）
  - 個別メンバー選択
  
- **期間ナビゲーション**:
  - 「<」前へボタン
  - 期間表示（例: "2025年12月1週目"）
  - 「>」次へボタン
  
- **グラフエリア**:
  - 棒グラフ + 折れ線グラフ（`react-native-chart-kit`）
  - スクロール可能（横スクロール）
  
- **集計データカード**:
  - 完了数、報酬合計、1日あたり平均
  - アイコン + 数値表示

**実装コード例**:
```typescript
// mobile/src/screens/performance/PerformanceScreen.tsx
import React, { useState, useEffect } from 'react';
import { View, Text, ScrollView, TouchableOpacity } from 'react-native';
import { BarChart, LineChart, ComposedChart } from 'react-native-chart-kit';
import { Dimensions } from 'react-native';
import { usePerformance } from '../../hooks/usePerformance';

export const PerformanceScreen = () => {
  const [period, setPeriod] = useState<'week' | 'month' | 'year'>('week');
  const [taskType, setTaskType] = useState<'normal' | 'group'>('normal');
  const [selectedUser, setSelectedUser] = useState<number | null>(null);
  
  const { data, isLoading, navigatePeriod } = usePerformance({
    period,
    taskType,
    userId: selectedUser,
  });

  const chartConfig = {
    backgroundGradientFrom: '#fff',
    backgroundGradientTo: '#fff',
    color: (opacity = 1) => `rgba(89, 185, 198, ${opacity})`,
    strokeWidth: 2,
    barPercentage: 0.5,
    useShadowColorFromDataset: false,
  };

  return (
    <ScrollView>
      {/* 期間選択タブ */}
      <View style={styles.periodTabs}>
        <TouchableOpacity onPress={() => setPeriod('week')}>
          <Text>週間</Text>
        </TouchableOpacity>
        <TouchableOpacity onPress={() => setPeriod('month')}>
          <Text>月間</Text>
        </TouchableOpacity>
        <TouchableOpacity onPress={() => setPeriod('year')}>
          <Text>年間</Text>
        </TouchableOpacity>
      </View>

      {/* タスク種別タブ */}
      <View style={styles.taskTypeTabs}>
        <TouchableOpacity onPress={() => setTaskType('normal')}>
          <Text>通常タスク</Text>
        </TouchableOpacity>
        <TouchableOpacity onPress={() => setTaskType('group')}>
          <Text>グループタスク</Text>
        </TouchableOpacity>
      </View>

      {/* 期間ナビゲーション */}
      <View style={styles.navigation}>
        <TouchableOpacity
          onPress={() => navigatePeriod('prev')}
          disabled={!data?.can_navigate_prev}
        >
          <Text>&lt;</Text>
        </TouchableOpacity>
        <Text>{data?.period_label}</Text>
        <TouchableOpacity
          onPress={() => navigatePeriod('next')}
          disabled={!data?.can_navigate_next}
        >
          <Text>&gt;</Text>
        </TouchableOpacity>
      </View>

      {/* グラフ */}
      <BarChart
        data={data?.chart_data || { labels: [], datasets: [] }}
        width={Dimensions.get('window').width - 32}
        height={220}
        chartConfig={chartConfig}
        style={styles.chart}
      />

      {/* 集計データ */}
      <View style={styles.summaryCard}>
        <Text>完了数: {data?.summary.total_completed}</Text>
        <Text>報酬合計: {data?.summary.total_reward}</Text>
        <Text>1日平均: {data?.summary.average_per_day}</Text>
      </View>
    </ScrollView>
  );
};
```

---

## 3. 月次レポート機能

### 3.1 機能要件

**概要**: グループメンバーの月次タスク実績をPDF生成し、メール・クラウド・メッセージアプリに共有できる機能。

**処理フロー**:
```
1. ユーザーが「月次レポート」ボタンタップ
2. モーダル表示: 対象月選択（過去12ヶ月）
3. 「レポート生成」ボタンタップ
4. API呼び出し: GET /api/reports/monthly?month=2025-12
5. レスポンス取得: HTML文字列（Web版と同じレイアウト）
6. react-native-html-to-pdf でPDF生成
7. expo-sharing でネイティブ共有ダイアログ表示
8. ユーザーが共有先選択（メール、Googleドライブ、LINE等）
```

**API**:
- `GET /api/reports/monthly?month=YYYY-MM` - 月次レポートデータ取得

**出力項目**:

| 項目 | 型 | 説明 |
|------|-----|------|
| `month_label` | string | 月ラベル（例: "2025年12月"） |
| `group_name` | string | グループ名 |
| `summary` | object | 全体サマリー（完了数、報酬合計） |
| `member_stats` | array | メンバー別統計 |
| `html` | string | PDFレンダリング用HTML（Blade生成） |

**レスポンス例**:
```json
{
  "success": true,
  "data": {
    "month_label": "2025年12月",
    "group_name": "家族グループ",
    "summary": {
      "total_completed": 120,
      "total_reward": 600000
    },
    "member_stats": [
      {
        "user_id": 1,
        "user_name": "太郎",
        "completed": 50,
        "reward": 250000,
        "average_per_day": 1.6
      },
      {
        "user_id": 2,
        "user_name": "花子",
        "completed": 70,
        "reward": 350000,
        "average_per_day": 2.3
      }
    ],
    "html": "<html><head>...</head><body>...</body></html>"
  }
}
```

### 3.2 画面構成

**MonthlyReportModalScreen.tsx**:
- **対象月選択**:
  - ドロップダウン（過去12ヶ月）
  
- **メンバー統計プレビュー**:
  - メンバー一覧（名前、完了数、報酬）
  - 全体サマリー
  
- **アクションボタン**:
  - 「PDF生成・共有」ボタン（メインアクション）
  - 「閉じる」ボタン

**実装コード例**:
```typescript
// mobile/src/screens/performance/MonthlyReportScreen.tsx
import React, { useState } from 'react';
import { View, Text, Button, Share } from 'react-native';
import * as Sharing from 'expo-sharing';
import { useMonthlyReport } from '../../hooks/useMonthlyReport';
import { generatePdf } from '../../utils/pdfGenerator';

export const MonthlyReportScreen = ({ route }) => {
  const { groupId } = route.params;
  const [selectedMonth, setSelectedMonth] = useState('2025-12');
  
  const { data, isLoading } = useMonthlyReport(groupId, selectedMonth);

  const handleGenerateAndShare = async () => {
    try {
      // PDF生成
      const pdfPath = await generatePdf({
        html: data.html,
        fileName: `monthly-report-${selectedMonth}.pdf`,
      });

      // 共有ダイアログ表示
      if (await Sharing.isAvailableAsync()) {
        await Sharing.shareAsync(pdfPath, {
          mimeType: 'application/pdf',
          dialogTitle: '月次レポートを共有',
        });
      } else {
        // フォールバック: React Nativeの標準Share API
        await Share.share({
          url: pdfPath,
          title: '月次レポート',
        });
      }
    } catch (error) {
      console.error('PDF生成・共有エラー:', error);
    }
  };

  return (
    <View>
      <Text>{data?.month_label} レポート</Text>
      
      {/* メンバー統計 */}
      {data?.member_stats.map((member) => (
        <View key={member.user_id}>
          <Text>{member.user_name}</Text>
          <Text>完了: {member.completed}</Text>
          <Text>報酬: {member.reward}</Text>
        </View>
      ))}

      {/* アクションボタン */}
      <Button
        title="PDF生成・共有"
        onPress={handleGenerateAndShare}
        disabled={isLoading}
      />
    </View>
  );
};
```

---

## 4. PDF生成機能

### 4.1 実装方式

**方式A: react-native-html-to-pdf**（推奨）
- **Laravel BladでレンダリングしたHTMLをそのままPDF化**
- **Web版と完全に同じレイアウト・デザインを維持**
- 日本語フォント埋め込み対応

**方式B: @react-pdf/renderer**
- React Nativeコンポーネントでレイアウト定義
- より柔軟なデザインカスタマイズ可能
- 実装コスト高（Web版HTMLの移植必要）

**採用: 方式A**（実装コスト70%削減、Web版との完全一致）

### 4.2 PDFテンプレート仕様（Web版準拠）

**重要**: モバイル版のPDF生成は**Web版と全く同じHTMLテンプレート**を使用します。

#### 4.2.1 テンプレートソース

**ファイル**: `resources/views/reports/monthly/show.blade.php`

**API応答に含めるHTML**:
```json
{
  "data": {
    "html": "<html>...</html>",  // Blade完全レンダリング済みHTML
    "html_type": "pdf_ready"      // PDFレンダリング用に最適化済み
  }
}
```

**Laravel側の実装**:
```php
// app/Http/Actions/Api/Reports/GetMonthlyReportApiAction.php
public function __invoke(GetMonthlyReportRequest $request): JsonResponse
{
    $year = $request->input('year', now()->year);
    $month = $request->input('month', now()->month);
    
    // レポートデータ取得
    $reportData = $this->service->getMonthlyReportData($year, $month);
    
    // Blade HTMLレンダリング
    $html = view('reports.monthly.show', $reportData)->render();
    
    // CSS/JSを除外してPDF最適化
    $pdfHtml = $this->optimizeHtmlForPdf($html);
    
    return response()->json([
        'success' => true,
        'data' => [
            'report' => $reportData,
            'html' => $pdfHtml,  // PDF生成用HTML
        ]
    ]);
}

private function optimizeHtmlForPdf(string $html): string
{
    // 1. 外部CSS/JSを削除（@vite、<script>タグ）
    $html = preg_replace('/@vite\(.*?\)/', '', $html);
    $html = preg_replace('/<script.*?>.*?<\/script>/is', '', $html);
    
    // 2. インタラクティブ要素を削除（ボタン、セレクトボックス）
    $html = preg_replace('/<select.*?>.*?<\/select>/is', '', $html);
    $html = preg_replace('/<button.*?>.*?<\/button>/is', '', $html);
    
    // 3. Tailwind CSSをインラインスタイル化（Dompdf互換）
    // ※ 実際のプロジェクトではCSSインライン化ライブラリを使用推奨
    
    // 4. 画像をBase64エンコード（アバター画像）
    $html = $this->embedImagesAsBase64($html);
    
    return $html;
}
```

#### 4.2.2 HTMLテンプレート構造（Web版と同一）

**セクション構成**:
1. **ヘッダー部**:
   - タイトル「月次レポート」
   - 対象月表示（例: "2025年12月の実績レポート"）
   - グループ名

2. **AI教師コメント**（サブスク加入者のみ）:
   - アバター画像（Base64埋め込み）
   - 吹き出しデザイン
   - AI生成コメントテキスト
   - トークン使用量表示

3. **グラフエリア**:
   - **通常タスクグラフ**（積み上げ棒グラフ）:
     - 直近6ヶ月の完了数
     - メンバー別色分け
     - 凡例表示
   - **グループタスクグラフ**（積み上げ棒グラフ）:
     - 直近6ヶ月の完了数
     - メンバー別色分け
     - 凡例表示

4. **集計サマリー**:
   - 今月の完了数（通常タスク / グループタスク）
   - 今月の獲得報酬合計
   - 前月比（増減率）

5. **メンバー別詳細テーブル**:
   - **通常タスク詳細**:
     - メンバー名、完了数、報酬、タスク詳細（タイトル、完了日時）
   - **グループタスク詳細**:
     - メンバー名、完了数、報酬、タスク詳細（タイトル、タグ、完了日時）

6. **フッター**:
   - レポート生成日時
   - MyTeacherロゴ
   - ページ番号

#### 4.2.3 スタイリング（Tailwind CSS → インライン変換）

**色設定**:
- メインカラー: `#59B9C6`（ティール系）
- アクセント: `#8B5CF6`（パープル系）
- 通常タスク: `rgba(89, 185, 198, 0.8)`
- グループタスク: `rgba(139, 92, 246, 0.8)`

**フォント**:
- 見出し: `font-bold text-lg`（18px、太字）
- 本文: `text-sm`（14px、通常）
- 数値: `font-semibold`（セミボールド）
- システムフォント: Noto Sans JP（日本語）、sans-serif（英数字）

**レイアウト**:
- 用紙サイズ: A4（210mm × 297mm）
- 余白: 上下左右 20mm
- 行間: 1.5倍
- カード: 角丸16px、影付き

#### 4.2.4 Chart.jsグラフのPDF埋め込み

**問題**: Chart.jsはJavaScriptで描画するため、PDFに直接埋め込めない

**解決策**:
1. **サーバーサイドで画像生成**（推奨）:
   - Laravel側でChart.jsグラフをPNG画像化（puppeteer、headless Chrome使用）
   - 画像をBase64エンコードして`<img>`タグで埋め込み
   
2. **モバイル側でグラフ画像生成**:
   - `react-native-view-shot`でグラフコンポーネントをキャプチャ
   - Base64画像をHTMLに挿入してPDF生成

**実装例（サーバーサイド）**:
```php
// Laravel側でChart.jsグラフを画像化
use Spatie\Browsershot\Browsershot;

public function generateChartImage(array $chartData): string
{
    $html = view('reports.chart-template', compact('chartData'))->render();
    
    $imagePath = storage_path('app/temp/chart_' . uniqid() . '.png');
    
    Browsershot::html($html)
        ->setScreenshotType('png')
        ->windowSize(800, 400)
        ->save($imagePath);
    
    $base64 = base64_encode(file_get_contents($imagePath));
    unlink($imagePath);
    
    return 'data:image/png;base64,' . $base64;
}
```

**APIレスポンス**:
```json
{
  "data": {
    "html": "<html>...</html>",
    "chart_images": {
      "normal_tasks": "data:image/png;base64,iVBORw0KGgoAAAANS...",
      "group_tasks": "data:image/png;base64,iVBORw0KGgoAAAANS..."
    }
  }
}
```

### 4.3 実装詳細

**必要なパッケージ**:
```bash
npm install react-native-html-to-pdf
```

**PDF生成ユーティリティ**:
```typescript
// mobile/src/utils/pdfGenerator.ts
import RNHTMLtoPDF from 'react-native-html-to-pdf';

export const generatePdf = async (options: {
  html: string;
  fileName: string;
}): Promise<string> => {
  const { html, fileName } = options;

  const pdfOptions = {
    html,
    fileName,
    directory: 'Documents',
    base64: false,
    width: 595,  // A4サイズ（ポイント単位）
    height: 842, // A4サイズ（ポイント単位）
  };

  const file = await RNHTMLtoPDF.convert(pdfOptions);
  return file.filePath; // PDF保存パス
};
```

**HTMLカスタマイズ**:
- ✅ **CSS: インラインスタイル化済み**（Laravel側で処理）
- ✅ **画像: Base64エンコード埋め込み済み**（アバター画像、グラフ画像）
- ✅ **フォント: システムフォント使用**（Noto Sans JP等）
- ❌ **外部リソース: 削除済み**（@vite、<script>タグ）

**エラーハンドリング**:
```typescript
try {
  const pdfPath = await generatePdf({ html: data.html, fileName: 'report.pdf' });
  console.log('PDF生成成功:', pdfPath);
} catch (error) {
  console.error('PDF生成エラー:', error);
  Alert.alert('エラー', 'PDFの生成に失敗しました');
}
```

### 4.4 PDF共有機能（expo-sharing）

**実装コード**:
```typescript
import * as Sharing from 'expo-sharing';

const sharePdf = async (pdfPath: string) => {
  if (await Sharing.isAvailableAsync()) {
    await Sharing.shareAsync(pdfPath, {
      mimeType: 'application/pdf',
      dialogTitle: '月次レポートを共有',
      UTI: 'com.adobe.pdf', // iOS用
    });
  }
};
```

**共有先例**:
- **iOS**: メール、メッセージ、AirDrop、iCloud Drive、Dropbox、LINE等
- **Android**: Gmail、メッセージ、Googleドライブ、Dropbox、LINE等

**注意事項**:
- `expo-sharing` はWeb版では動作しない（ブラウザダウンロードのみ）
- `Share.share()` はテキスト共有専用、PDFは `expo-sharing` 推奨

---

## 5. 技術仕様

### 5.1 グラフ実装（react-native-chart-kit）

**Chart.js → react-native-chart-kitの移植マッピング**:

| Chart.js | react-native-chart-kit | 備考 |
|----------|----------------------|------|
| `Bar` | `BarChart` | 棒グラフ |
| `Line` | `LineChart` | 折れ線グラフ |
| `Pie` | `PieChart` | 円グラフ |
| `datasets[].backgroundColor` | `chartConfig.color` | 色設定 |
| `responsive: true` | `width: Dimensions.get('window').width` | レスポンシブ対応 |

**ChartConfigオブジェクト**:
```typescript
const chartConfig = {
  backgroundGradientFrom: '#ffffff',
  backgroundGradientTo: '#ffffff',
  color: (opacity = 1) => `rgba(89, 185, 198, ${opacity})`,
  strokeWidth: 2,
  barPercentage: 0.5,
  decimalPlaces: 0,
  labelColor: (opacity = 1) => `rgba(0, 0, 0, ${opacity})`,
  style: {
    borderRadius: 16,
  },
  propsForDots: {
    r: '6',
    strokeWidth: '2',
    stroke: '#59B9C6',
  },
};
```

**Web版グラフ設定の移植**:
```javascript
// Web版 (Chart.js) - resources/js/reports/performance.js
const chartData = {
  labels: ["12/1", "12/2", "12/3", ...],
  datasets: [
    {
      label: "完了数",
      data: [3, 5, 2, 4, ...],
      backgroundColor: "rgba(89, 185, 198, 0.8)",
      borderColor: "rgba(89, 185, 198, 1)",
      borderWidth: 1,
    },
  ],
};

// モバイル版 (react-native-chart-kit)
<BarChart
  data={{
    labels: ["12/1", "12/2", "12/3", ...],
    datasets: [{ data: [3, 5, 2, 4, ...] }],
  }}
  width={Dimensions.get('window').width - 32}
  height={220}
  chartConfig={chartConfig}
  style={{ marginVertical: 8, borderRadius: 16 }}
/>
```

### 5.2 API一覧

| エンドポイント | メソッド | 認証 | 説明 |
|--------------|---------|------|------|
| `/api/reports/performance` | GET | Sanctum | 実績データ取得 |
| `/api/reports/monthly` | GET | Sanctum | 月次レポートデータ取得 |

### 5.3 モバイル実装ファイル

**Service層**:
- `mobile/src/services/performance.service.ts` - API通信ロジック
  - `getPerformanceData(params: PerformanceParams): Promise<PerformanceData>`
  - `getMonthlyReport(groupId: number, month: string): Promise<MonthlyReport>`

**Hook層**:
- `mobile/src/hooks/usePerformance.ts` - 実績データ管理
  - `data: PerformanceData | null`
  - `isLoading: boolean`
  - `navigatePeriod(direction: 'prev' | 'next'): Promise<void>`
  
- `mobile/src/hooks/useMonthlyReport.ts` - 月次レポート管理
  - `data: MonthlyReport | null`
  - `isLoading: boolean`
  - `generatePdf(): Promise<string>`

**画面層**:
- `mobile/src/screens/performance/PerformanceScreen.tsx` - 実績画面
- `mobile/src/screens/performance/MonthlyReportScreen.tsx` - 月次レポート画面

**Utils層**:
- `mobile/src/utils/pdfGenerator.ts` - PDF生成ユーティリティ
- `mobile/src/utils/chartHelpers.ts` - グラフデータ変換ヘルパー

**型定義**:
```typescript
// mobile/src/types/performance.types.ts
export interface PerformanceData {
  period_label: string;
  task_type: 'normal' | 'group';
  chart_data: {
    labels: string[];
    datasets: Array<{
      label: string;
      data: number[];
      backgroundColor?: string;
      borderColor?: string;
    }>;
  };
  summary: {
    total_completed: number;
    total_reward: number;
    average_per_day: number;
  };
  can_navigate_prev: boolean;
  can_navigate_next: boolean;
}

export interface MonthlyReport {
  month_label: string;
  group_name: string;
  summary: {
    total_completed: number;
    total_reward: number;
  };
  member_stats: Array<{
    user_id: number;
    user_name: string;
    completed: number;
    reward: number;
    average_per_day: number;
  }>;
  html: string;
}
```

---

## 6. テスト要件

### 6.1 Laravelテスト

**PerformanceApiTest.php**（Feature Test）:
- ✅ 週間実績データ取得成功（通常タスク）
- ✅ 月間実績データ取得成功（グループタスク）
- ✅ 年間実績データ取得成功
- ✅ メンバー指定時の実績取得成功（編集権限者）
- ✅ 月次レポートデータ取得成功（HTML生成）
- ✅ 未認証時は401エラー
- ✅ グループ非所属時は403エラー（グループタスク）
- ✅ 編集権限なしでメンバー指定時は403エラー

### 6.2 モバイルテスト

**performance.service.test.ts**（Service層）:
- ✅ getPerformanceData()成功（各期間）
- ✅ getMonthlyReport()成功（HTML取得）
- ✅ エラーハンドリング（401, 403, 500）

**usePerformance.test.ts**（Hook層）:
- ✅ 実績データ取得成功
- ✅ 期間ナビゲーション（前へ・次へ）
- ✅ タスク種別切り替え
- ✅ メンバー選択（グループタスク）

**PerformanceScreen.test.tsx**（UI層）:
- ✅ グラフ表示
- ✅ 期間選択タブ動作
- ✅ タスク種別タブ動作
- ✅ 集計データ表示

**MonthlyReportScreen.test.tsx**（UI層）:
- ✅ 月次レポート表示
- ✅ PDF生成成功
- ✅ 共有ダイアログ表示（expo-sharing）
- ✅ エラー時のフォールバック（Share API）

**pdfGenerator.test.ts**（Utils層）:
- ✅ generatePdf()成功（HTMLからPDF生成）
- ✅ 日本語フォント正常表示
- ✅ Base64画像埋め込み成功

---

## 7. 制約事項・注意事項

### 7.1 グラフ表示

- `react-native-chart-kit` はSVGベースのため、大量データ（1000点以上）では描画遅延あり
- Web版と完全に同じデザインは不可（ライブラリ仕様の差異）
- アニメーション効果はWeb版より制限的

### 7.2 PDF生成

- `react-native-html-to-pdf` はネイティブライブラリのため、Expo Goでは動作しない（開発ビルド必要）
- 日本語フォント: iOS/Androidのシステムフォントに依存
- 画像埋め込み: 大きすぎる画像（5MB以上）は生成失敗リスク

### 7.3 共有機能

- `expo-sharing` はWebでは動作しない
- iOS: `Info.plist` に `UIFileSharingEnabled` 設定必要
- Android: `AndroidManifest.xml` にストレージ権限必要

---

## 8. サブスクリプション制限（Web版準拠）

### 8.1 制限対象機能（Web版と同じ）

**モバイルアプリでもWeb版と全く同じ制限を適用**します。

#### 8.1.1 期間選択の制限

**無料ユーザー**:
- ✅ **週間実績のみ閲覧可能**
- ❌ **月間実績は閲覧不可**（ボタングレーアウト+ロックアイコン）
- ❌ **年間実績は閲覧不可**（ボタングレーアウト+ロックアイコン）

**サブスク加入者**:
- ✅ 週間・月間・年間すべて閲覧可能

**モバイル実装**:
```typescript
// PerformanceScreen.tsx
const handlePeriodChange = (newPeriod: 'week' | 'month' | 'year') => {
  if (!hasSubscription && (newPeriod === 'month' || newPeriod === 'year')) {
    // サブスクリプション促進アラート表示
    Alert.alert(
      'プレミアム機能',
      '月間・年間の実績表示はサブスクリプションプランでご利用いただけます',
      [
        { text: 'キャンセル', style: 'cancel' },
        { text: 'プランを見る', onPress: () => navigation.navigate('Subscription') }
      ]
    );
    return;
  }
  setPeriod(newPeriod);
};
```

#### 8.1.2 期間ナビゲーションの制限

**無料ユーザー**:
- ✅ **当週のみ閲覧可能**（offset = 0）
- ❌ **過去週・未来週への移動不可**（前へ/次へボタン無効化）

**サブスク加入者**:
- ✅ 過去週・未来週への移動可能（最大52週前まで）

**API応答**（無料ユーザーが過去期間リクエスト時）:
```json
{
  "success": false,
  "error": "過去期間の実績閲覧はサブスクリプションプランでご利用いただけます",
  "subscription_required": true,
  "feature": "navigation"
}
```

**モバイル実装**:
```typescript
// PerformanceScreen.tsx
<TouchableOpacity
  onPress={() => navigatePeriod('prev')}
  disabled={!hasSubscription || !data?.can_navigate_prev}
  style={[
    styles.navButton,
    (!hasSubscription || !data?.can_navigate_prev) && styles.disabledButton
  ]}
>
  <Icon name="chevron-left" />
  {!hasSubscription && <Icon name="lock" size={12} />}
</TouchableOpacity>
```

#### 8.1.3 メンバー選択の制限（グループタスクのみ）

**無料ユーザー**:
- ✅ **「グループ全体」のみ閲覧可能**
- ❌ **個別メンバー選択不可**（選択肢グレーアウト+ロックアイコン）

**サブスク加入者**:
- ✅ 「グループ全体」+ 個別メンバー選択可能

**モバイル実装**:
```typescript
// PerformanceScreen.tsx
const memberOptions = [
  { label: 'グループ全体', value: null },
  ...members.map(m => ({
    label: `${m.name} ${!hasSubscription ? '🔒' : ''}`,
    value: m.id,
    disabled: !hasSubscription
  }))
];
```

#### 8.1.4 月次レポート機能の制限

**無料ユーザー**:
- ✅ **当月レポートのみ閲覧可能**（グループ作成後1ヶ月間）
- ❌ **過去月レポート閲覧不可**（ロック画面表示）
- ❌ **PDF生成・共有不可**

**サブスク加入者**:
- ✅ 過去12ヶ月分のレポート閲覧可能
- ✅ PDF生成・共有機能利用可能

**判定ロジック（Laravel側）**:
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

**API応答**（無料ユーザーが過去月にアクセス時）:
```json
{
  "success": false,
  "error": "過去のレポートを見るにはサブスクリプションが必要です",
  "subscription_required": true,
  "feature": "monthly_report",
  "accessible_until": "2025-12-31"
}
```

**モバイル実装（ロック画面）**:
```typescript
// MonthlyReportScreen.tsx
{!canAccess && (
  <View style={styles.lockOverlay}>
    <Icon name="lock" size={48} color="#8B5CF6" />
    <Text style={styles.lockTitle}>プレミアム機能</Text>
    <Text style={styles.lockMessage}>
      過去のレポートを見るにはサブスクリプションが必要です
    </Text>
    <Text style={styles.lockNote}>
      無料プランでは{accessibleUntil}までのレポートを閲覧できます
    </Text>
    <Button
      title="プランを見る"
      onPress={() => navigation.navigate('Subscription')}
    />
  </View>
)}
```

### 8.2 サブスクリプション判定API

**エンドポイント**: `GET /api/user/subscription-status`

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "has_subscription": true,
    "subscription_active": true,
    "can_access_premium_features": true,
    "features": {
      "performance_period_selection": true,
      "performance_navigation": true,
      "member_selection": true,
      "monthly_report": true,
      "monthly_report_history": true,
      "pdf_generation": true
    },
    "limits": {
      "accessible_reports_until": "2026-01-31"
    }
  }
}
```

### 8.3 UI表示（制限機能）

#### プレミアムバッジ

**月間・年間タブ**:
```typescript
<TouchableOpacity style={styles.tab} disabled={!hasSubscription}>
  <Text>月間</Text>
  {!hasSubscription && <Icon name="lock" size={12} color="#8B5CF6" />}
</TouchableOpacity>
```

#### アラートダイアログ

**デザイン**:
- タイトル: 「プレミアム機能」
- アイコン: 紫色の鍵🔒
- メッセージ: 機能に応じて動的に変更
- ボタン: 「プランを見る」「キャンセル」

**メッセージ例**:
- `period`: 「月間・年間の実績表示はサブスクリプションプランでご利用いただけます」
- `navigation`: 「過去期間の実績閲覧はサブスクリプションプランでご利用いただけます」
- `member`: 「個人別実績表示はサブスクリプションプランでご利用いただけます」
- `monthly_report`: 「過去のレポートを見るにはサブスクリプションが必要です」

#### 月次レポートボタン

**表示条件**:
- グループ所属ユーザーのみ表示
- 無料ユーザーでも当月レポートは閲覧可能（ボタン表示）

**ロックアイコン**:
- 無料ユーザーで過去月選択時のみ表示

---

## 9. 参考資料

- **Web版実装**: `app/Http/Actions/Reports/`, `resources/views/reports/`
- **Chart.js実装**: `resources/js/reports/performance.js`
- **月次レポートPDF**: `resources/views/reports/monthly-report.blade.php`
- **API仕様**: `routes/api.php` L213-215
- **開発規則**: `docs/mobile/mobile-rules.md`
- **プロジェクト規約**: `.github/copilot-instructions.md`
- **react-native-chart-kit**: https://github.com/indiespirit/react-native-chart-kit
- **expo-sharing**: https://docs.expo.dev/versions/latest/sdk/sharing/
