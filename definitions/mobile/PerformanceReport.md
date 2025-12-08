# 実績・レポート機能（モバイル版） 要件定義書

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-08 | GitHub Copilot | Phase 2.B-6実装完了: メンバー別概況画面追加、キャッシュ機能、エラーハンドリング強化 |
| 2025-12-08 | GitHub Copilot | 質疑応答結果を反映: グラフ種類明確化、アニメーション方針、PDF生成Phase 2.B-8移動、サブスク制限Phase 2.B-6実装 |
| 2025-12-07 | GitHub Copilot | 初版作成: モバイルアプリ実績・レポート機能（Chart.js移植、PDF生成、共有機能） |

---

## 1. 概要

MyTeacher モバイルアプリにおける実績・レポート機能は、ユーザーのタスク達成状況をグラフで可視化し、月次レポートをPDF生成・共有できる機能です。Web版の `react-native-chart-kit` への移植、PDFレンダリング、ネイティブ共有機能を実装します。

### 1.1 採用技術

**グラフライブラリ**: `react-native-chart-kit` v6.12.0
- Web版Chart.jsをReact Native向けに移植
- SVGベースのレンダリング（iOS/Android対応）
- **グラフ種類**: 積み上げ棒グラフ、折れ線グラフ、ドーナツグラフ
- **アニメーション**: Web版より制限的だが、なめらかな印象を保つ（enter/exit animations、smooth transitions）

**PDF生成**: `react-native-html-to-pdf` または `@react-pdf/renderer`（Phase 2.B-8で実装予定）
- HTMLテンプレートからPDF生成（Web版と同じレイアウト）
- 日本語フォント埋め込み対応
- **Phase 2.B-8（総合テスト）で実装**: Phase 2.B-6では基本機能（グラフ表示、データ表示）のみ実装

**共有機能**: `expo-sharing` v14.0.8（Phase 2.B-8で実装予定）
- ネイティブ共有ダイアログ表示
- メール、クラウドストレージ、メッセージアプリへの共有
- iOS: `UIActivityViewController`
- Android: Intent ACTION_SEND

**色設定**: Tailwind CSSと同じ色を使用
- メインカラー: `#59B9C6`（ティール系、通常タスク）
- アクセント: `#8B5CF6`（パープル系、グループタスク）

### 1.2 対応プラットフォーム

| プラットフォーム | 実装状況 | グラフライブラリ | PDF生成 | 共有機能 |
|----------------|---------|----------------|---------|---------|
| **Web** | ✅ 実装済み | Chart.js | Blade PDF | ブラウザダウンロード |
| **モバイル** | 🎯 Phase 2.B-6実装完了 | react-native-chart-kit | （Phase 2.B-8予定） | （Phase 2.B-8予定） |

**Phase 2.B-6実装内容**:
- ✅ 月次レポート画面（MonthlyReportScreen）
- ✅ メンバー別概況専用画面（MemberSummaryScreen）
- ✅ AsyncStorageキャッシュ機能（対象月別）
- ✅ AIサマリーAPI連携
- ✅ データ検証によるクラッシュ防止
- ✅ 戻るボタン確認ダイアログ
- ⏭️ PDF生成・共有機能（Phase 2.B-8で実装）

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

**概要**: グループメンバーの月次タスク実績を表示する機能。

**Phase 2.B-6実装範囲**:
- ✅ 月次レポートデータ表示（MonthlyReportScreen）
- ✅ メンバー別統計表示（月次レポート画面内）
- ✅ トレンドグラフ表示（メンバー別完了数推移）
- ✅ AI生成サマリー専用画面（MemberSummaryScreen）
  - 円グラフ（タスク分類）
  - 折れ線グラフ（報酬推移）
  - AsyncStorageキャッシュ（対象月別）
  - 戻るボタン確認ダイアログ
- ✅ サブスク制限（無料ユーザーはAIサマリー生成不可）
- ⏭️ PDF生成・共有機能（Phase 2.B-8で実装）

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

## 4. PDF生成機能（Phase 2.B-8で実装予定）

**Phase 2.B-6の実装範囲外**: PDF生成・共有機能はPhase 2.B-8（総合テスト）で実装します。

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

---

## 10. モバイル専用仕様: メンバー別概況画面（Phase 2.B-6実装完了）

### 10.1 概要

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
| PDF生成 | 即時実装済み | 将来実装（ボタンのみ配置、Phase 2.B-8予定） |

### 10.2 画面遷移フロー

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

### 10.3 データフロー

#### 10.3.1 API呼び出しとデータ変換

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

#### 10.3.2 キャッシュ戦略

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

### 10.4 画面実装詳細

#### 10.4.1 MemberSummaryScreen.tsx

**ファイルパス**: `mobile/src/screens/reports/MemberSummaryScreen.tsx`

**主要コンポーネント**:
- **ヘッダー**: `useLayoutEffect`でカスタム戻るボタン設定
- **AIコメントセクション**: アイコン付きカード、複数行テキスト表示
- **タスク分類グラフ**: PieChart（react-native-chart-kit）、凡例付き
- **報酬推移グラフ**: LineChart、ベジェ曲線、Y軸フォーマット
- **トークン消費表示**: 情報アイコン付き、数値フォーマット
- **PDFボタン**: 無効化状態、TODOコメント付き

**テーマ対応**: `useColorScheme()`でダーク/ライトモード自動切替

**実装ファイル**: 377行

#### 10.4.2 戻るボタンの確認ダイアログ

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

#### 10.4.3 グラフ実装

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

### 10.5 エラーハンドリング（アプリクラッシュ対策）

**Option B実装: データ検証 + 画面遷移分離**

#### 10.5.1 MonthlyReportScreen.tsx

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

#### 10.5.2 usePerformance.ts

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

### 10.6 型定義

**MemberSummaryData（画面表示用）**:
```typescript
export interface MemberSummaryData {
  user_id: number;
  user_name: string;  // Service層で追加
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
  generated_at: string;  // Service層で追加
}
```

**MemberSummaryResponse（API生データ）**:
```typescript
export interface MemberSummaryResponse {
  user_id: number;
  group_id: number;
  year_month: string;
  summary: {
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
  };
}
```

**MemberSummaryCacheKey**:
```typescript
export interface MemberSummaryCacheKey {
  prefix: string;  // 'member_summary_'
  user_id: number;
  year_month: string;
}
```

### 10.7 ナビゲーション設定

**AppNavigator.tsx**:
```typescript
import MemberSummaryScreen from '../screens/reports/MemberSummaryScreen';

// Stack.Navigator内
<Stack.Screen
  name="MemberSummary"
  component={MemberSummaryScreen}
  options={{ title: 'メンバー別概況' }}  // ヘッダータイトルはuseLayoutEffectで動的変更
/>
```

**RootStackParamList**:
```typescript
export type RootStackParamList = {
  // ...
  MemberSummary: { data: MemberSummaryData };
};
```

### 10.8 PDF生成機能（将来実装）

**現状**: ボタンのみ配置、無効化状態

**実装予定時の作業**:
```typescript
// TODO: PDF生成機能実装（Phase 2.B-8）
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

### 10.9 テスト要件（Phase 2.B-6実装完了）

**実装済みテスト**:
- ✅ `performance.service.test.ts`: generateMemberSummary()
- ✅ `usePerformance.test.ts`: generateMemberSummary()
- ✅ `MemberSummaryScreen.test.tsx`: 画面表示、グラフ、戻るボタン

**今後の追加テスト**（Phase 2.B-8）:
- PDF生成機能
- オフラインキャッシュ動作
- エラーリカバリー

