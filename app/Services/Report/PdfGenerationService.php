<?php

namespace App\Services\Report;

use App\Models\User;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * PDF生成サービス実装
 * Browsershotを使用してPDFを生成（実ブラウザレンダリング）
 */
class PdfGenerationService implements PdfGenerationServiceInterface
{
    /**
     * コンストラクタ
     * 
     * @param MonthlyReportServiceInterface $reportService 月次レポートサービス
     */
    public function __construct(
        protected MonthlyReportServiceInterface $reportService
    ) {}
    
    /**
     * メンバー別概況レポートのPDFを生成
     * 
     * @param User $targetUser 対象ユーザー
     * @param string $yearMonth 対象年月（YYYY-MM形式）
     * @param string $comment AIコメント
     * @param string|null $chartImageBase64 円グラフ画像（Base64）
     * @return string PDFバイナリデータ
     * @throws \RuntimeException PDF生成に失敗した場合
     */
    public function generateMemberSummaryPdf(
        User $targetUser,
        string $yearMonth,
        string $comment,
        ?string $chartImageBase64 = null
    ): string {
        try {
            // PDF用データ生成
            $pdfData = $this->preparePdfData(
                $targetUser,
                $yearMonth,
                $comment,
                $chartImageBase64
            );
            
            // HTMLレンダリング
            $html = view('reports.monthly.member-summary-pdf', $pdfData)->render();
            
            // Browsershotでブラウザレンダリング → PDF生成
            $pdf = Browsershot::html($html)
                ->setChromePath(config('app.browsershot_chrome_path', '/usr/bin/chromium'))
                ->setNodeBinary(config('app.browsershot_node_path', '/usr/bin/node'))
                ->setNpmBinary(config('app.browsershot_npm_path', '/usr/bin/npm'))
                ->noSandbox()  // Docker環境で必須
                ->showBackground()  // 背景色を表示
                ->landscape()  // A4横向き
                ->format('A4')
                ->margins(8, 8, 8, 8)  // 上右下左 (mm)
                ->waitUntilNetworkIdle()  // 画像読み込み完了まで待機
                ->pdf();
            
            Log::info('Browsershot PDF generated successfully', [
                'user_id' => $targetUser->id,
                'year_month' => $yearMonth,
                'pdf_size' => strlen($pdf),
            ]);
            
            return $pdf;
            
        } catch (\Spatie\Browsershot\Exceptions\CouldNotTakeBrowsershot $e) {
            Log::error('Browsershot PDF generation failed', [
                'user_id' => $targetUser->id,
                'year_month' => $yearMonth,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw new \RuntimeException('PDF生成に失敗しました: ' . $e->getMessage(), 0, $e);
            
        } catch (\Exception $e) {
            Log::error('PDF data preparation failed', [
                'user_id' => $targetUser->id,
                'year_month' => $yearMonth,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw new \RuntimeException('PDFデータの準備に失敗しました: ' . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * PDF用データを準備
     * 
     * @param User $targetUser 対象ユーザー
     * @param string $yearMonth 対象年月
     * @param string $comment AIコメント
     * @param string|null $chartImageBase64 円グラフ画像
     * @return array PDF用データ配列
     */
    protected function preparePdfData(
        User $targetUser,
        string $yearMonth,
        string $comment,
        ?string $chartImageBase64
    ): array {
        // グループ取得
        $group = $targetUser->group;
        if (!$group) {
            throw new \RuntimeException('ユーザーのグループが見つかりません。');
        }
        
        // 基本データ生成
        $pdfData = $this->reportService->generateMemberSummaryPdfData(
            $targetUser->id,
            $group->id,
            $yearMonth
        );
        
        // コメント上書き
        $pdfData['comment'] = $comment;
        
        // 円グラフ画像上書き
        if ($chartImageBase64) {
            $pdfData['chartImageBase64'] = $this->sanitizeBase64Image($chartImageBase64);
        }
        
        // 折れ線グラフ生成（サイズを小さく: 350x150）
        $pdfData['trendChartBase64'] = $this->generateTrendChart(
            $pdfData['rewardTrendLabels'],
            $pdfData['rewardTrendData'],
            $yearMonth
        );
        
        // ドーナツグラフ生成（タスク分類用: 250x250）
        $pdfData['donutChartBase64'] = $this->generateDonutChart($pdfData['taskClassification'] ?? []);
        
        // タスク分類データを整形（テキストリスト用 - ドーナツグラフと併用）
        $pdfData['taskCategories'] = $this->formatTaskCategories($pdfData['taskClassification'] ?? []);
        
        // アバター画像取得（今回は使用しないがデータ保持）
        $pdfData['avatarImageBase64'] = $this->getAvatarImageBase64($targetUser);
        
        return $pdfData;
    }
    
    /**
     * Base64画像をサニタイズ（data:プレフィックス除去）
     * 
     * @param string $base64Image Base64画像文字列
     * @return string サニタイズ済みBase64文字列
     */
    protected function sanitizeBase64Image(string $base64Image): string
    {
        return preg_replace('/^data:image\/\w+;base64,/', '', $base64Image);
    }
    
    /**
     * アバター画像をBase64エンコードして取得
     * 
     * @param User $user 対象ユーザー
     * @return string|null Base64エンコードされた画像（なければnull）
     */
    protected function getAvatarImageBase64(User $user): ?string
    {
        if (!$user->teacher_avatar_id) {
            return null;
        }
        
        $avatar = \App\Models\TeacherAvatar::find($user->teacher_avatar_id);
        if (!$avatar) {
            return null;
        }
        
        // バストアップの通常画像を取得
        $avatarImage = \App\Models\AvatarImage::where('teacher_avatar_id', $avatar->id)
            ->where('pose_type', 'bust')
            ->where('expression_type', 'normal')
            ->first();
        
        if (!$avatarImage || !$avatarImage->image_path) {
            return null;
        }
        
        try {
            $imageContent = Storage::disk('s3')->get($avatarImage->image_path);
            if ($imageContent) {
                return base64_encode($imageContent);
            }
        } catch (\Exception $e) {
            Log::warning('アバター画像の取得に失敗しました', [
                'avatar_id' => $avatar->id,
                'image_path' => $avatarImage->image_path,
                'error' => $e->getMessage()
            ]);
        }
        
        return null;
    }
    
    /**
     * 折れ線グラフ画像を生成（報酬推移用）
     * 
     * @param array $labels 月ラベル（6ヶ月分）
     * @param array $data 報酬データ（6ヶ月分）
     * @param string $yearMonth 対象年月（当月のみ報酬額表示）
     * @return string Base64エンコードされた画像
     */
    protected function generateTrendChart(array $labels, array $data, string $yearMonth): string
    {
        try {
            // 当月のインデックスを特定
            $currentMonthLabel = \Carbon\Carbon::createFromFormat('Y-m', $yearMonth)->format('Y年n月');
            $currentMonthIndex = array_search($currentMonthLabel, $labels);
            
            // QuickChart.io API呼び出し（サイズを350x150に縮小）
            $chartConfig = $this->buildChartConfig($labels, $data, $currentMonthIndex);
            $imageContent = $this->fetchChartImage($chartConfig, 350, 150);
            
            return base64_encode($imageContent);
            
        } catch (\Exception $e) {
            Log::error('Trend chart generation failed', [
                'error' => $e->getMessage(),
                'labels' => $labels,
                'data' => $data,
            ]);
            
            // エラー時は透明1x1 PNGを返す
            return $this->getEmptyImageBase64();
        }
    }
    
    /**
     * Chart.js設定を構築
     * 
     * @param array $labels 月ラベル
     * @param array $data 報酬データ
     * @param int|false $currentMonthIndex 当月インデックス
     * @return array Chart.js設定配列
     */
    protected function buildChartConfig(array $labels, array $data, int|false $currentMonthIndex): array
    {
        return [
            'type' => 'line',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => '報酬額',
                    'data' => $data,
                    'borderColor' => '#FFD700',
                    'backgroundColor' => 'rgba(255, 215, 0, 0.1)',
                    'borderWidth' => 3,
                    'tension' => 0.3,
                    'fill' => true,
                    'pointRadius' => 5,
                    'pointBackgroundColor' => '#FFD700',
                    'pointBorderColor' => '#FFFFFF',
                    'pointBorderWidth' => 2,
                ]],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['display' => false],
                    'title' => [
                        'display' => true,
                        'text' => '報酬額の推移（6ヶ月）',
                        'font' => ['size' => 16, 'weight' => 'bold'],
                        'color' => '#1F2937',
                    ],
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                    ],
                ],
            ],
        ];
    }
    
    /**
     * QuickChart.io APIからグラフ画像を取得
     * 
     * @param array $chartConfig Chart.js設定
     * @param int $width 画像幅
     * @param int $height 画像高さ
     * @return string 画像バイナリデータ
     * @throws \RuntimeException API呼び出し失敗時
     */
    protected function fetchChartImage(array $chartConfig, int $width = 600, int $height = 350): string
    {
        $url = 'https://quickchart.io/chart';
        $params = [
            'width' => $width,
            'height' => $height,
            'chart' => json_encode($chartConfig),
            'format' => 'png',
        ];
        
        $queryString = http_build_query($params);
        $imageContent = file_get_contents($url . '?' . $queryString);
        
        if ($imageContent === false) {
            throw new \RuntimeException('折れ線グラフ画像の生成に失敗しました');
        }
        
        return $imageContent;
    }
    
    /**
     * タスク分類データを整形（テキストリスト用）
     * 
     * @param array $taskClassification 分類データ（labels, data, colors）
     * @return array 整形済み分類データ
     */
    protected function formatTaskCategories(array $taskClassification): array
    {
        if (empty($taskClassification['labels']) || empty($taskClassification['data'])) {
            return [];
        }
        
        $labels = $taskClassification['labels'];
        $data = $taskClassification['data'];
        $colors = $taskClassification['colors'] ?? [];
        
        $total = array_sum($data);
        $categories = [];
        
        foreach ($labels as $index => $label) {
            $count = $data[$index] ?? 0;
            $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
            $color = $colors[$index] ?? '#3B82F6';
            
            $categories[] = [
                'name' => $label,
                'count' => $count,
                'percentage' => $percentage,
                'color' => $color,
            ];
        }
        
        // カウントの多い順にソート
        usort($categories, function($a, $b) {
            return $b['count'] <=> $a['count'];
        });
        
        return $categories;
    }
    
    /**
     * ドーナツグラフ画像を生成（タスク分類用）
     * 
     * @param array $taskClassification 分類データ（labels, data, colors）
     * @return string Base64エンコードされた画像
     */
    protected function generateDonutChart(array $taskClassification): string
    {
        try {
            if (empty($taskClassification['labels']) || empty($taskClassification['data'])) {
                return $this->getEmptyImageBase64();
            }
            
            $labels = $taskClassification['labels'];
            $data = $taskClassification['data'];
            $colors = $taskClassification['colors'] ?? ['#3B82F6', '#8B5CF6', '#10B981', '#F59E0B', '#EF4444'];
            
            // QuickChart.io API呼び出し
            $chartConfig = [
                'type' => 'doughnut',
                'data' => [
                    'labels' => $labels,
                    'datasets' => [[
                        'data' => $data,
                        'backgroundColor' => $colors,
                        'borderWidth' => 2,
                        'borderColor' => '#FFFFFF',
                    ]],
                ],
                'options' => [
                    'responsive' => true,
                    'maintainAspectRatio' => true,
                    'plugins' => [
                        'legend' => [
                            'display' => true,
                            'position' => 'bottom',
                            'labels' => [
                                'font' => ['size' => 10],
                                'padding' => 6,
                                'usePointStyle' => true,
                            ],
                        ],
                        'title' => [
                            'display' => false,
                        ],
                        'datalabels' => [
                            'display' => true,
                            'color' => '#FFFFFF',
                            'font' => ['size' => 12, 'weight' => 'bold'],
                            'formatter' => 'function(value, context) { return value + "件"; }',
                        ],
                    ],
                    'cutout' => '65%',
                ],
            ];
            
            $imageContent = $this->fetchChartImage($chartConfig, 250, 250);
            return base64_encode($imageContent);
            
        } catch (\Exception $e) {
            Log::error('Donut chart generation failed', [
                'error' => $e->getMessage(),
                'taskClassification' => $taskClassification,
            ]);
            
            return $this->getEmptyImageBase64();
        }
    }
    
    /**
     * 空の透明画像（1x1 PNG）をBase64で取得
     * 
     * @return string Base64エンコードされた透明PNG
     */
    protected function getEmptyImageBase64(): string
    {
        return 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
    }
}
