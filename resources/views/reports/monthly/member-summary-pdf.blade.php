<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <title>{{ $userName }}'s Report - {{ $yearMonth }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Noto Sans JP', 'Yu Gothic', 'Meiryo', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 5mm;
            color: #1F2937;
        }
        
        .container {
            background: #FFFFFF;
            border-radius: 10px;
            padding: 4mm;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .header {
            text-align: center;
            padding-bottom: 2mm;
            border-bottom: 2px solid #667eea;
            margin-bottom: 3mm;
        }
        
        .header h1 {
            font-size: 14pt;
            color: #667eea;
            font-weight: bold;
            margin-bottom: 0.5mm;
        }
        
        .header .date {
            font-size: 6pt;
            color: #9CA3AF;
        }
        
        .reward-hero {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            border-radius: 10px;
            padding: 5mm;
            text-align: center;
            margin-bottom: 3mm;
            box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
        }
        
        .reward-hero .amount {
            font-size: 32pt;
            font-weight: bold;
            color: #FFFFFF;
            line-height: 1;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .reward-hero .unit {
            font-size: 16pt;
        }
        
        .reward-hero .label {
            font-size: 9pt;
            color: #FFFFFF;
            margin-top: 1mm;
            font-weight: bold;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3mm;
            margin-bottom: 2mm;
        }
        
        .card {
            background: #F9FAFB;
            border-radius: 8px;
            padding: 3mm;
            border: 2px solid #E5E7EB;
        }
        
        .card-title {
            font-size: 9pt;
            font-weight: bold;
            color: #1F2937;
            margin-bottom: 2mm;
            padding-bottom: 1mm;
            border-bottom: 2px solid #667eea;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5mm;
            margin-top: 2mm;
        }
        
        .stat-box {
            background: #FFFFFF;
            border-radius: 6px;
            padding: 2mm;
            text-align: center;
            border: 1px solid #E5E7EB;
        }
        
        .stat-box .icon {
            font-size: 12pt;
            margin-bottom: 0.5mm;
        }
        
        .stat-box .label {
            font-size: 6pt;
            color: #6B7280;
            margin-bottom: 0.5mm;
        }
        
        .stat-box .value {
            font-size: 13pt;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat-box.green .value {
            color: #10B981;
        }
        
        .stat-box.red .value {
            color: #EF4444;
        }
        
        .chart-container {
            background: #FFFFFF;
            border-radius: 6px;
            padding: 2mm;
            text-align: center;
            margin-top: 2mm;
        }
        
        .chart-container img {
            max-width: 100%;
            height: auto;
            max-height: 28mm;
        }
        
        .comment-card {
            background: linear-gradient(135deg, #F5F3FF 0%, #EDE9FE 100%);
            border-radius: 8px;
            padding: 3mm;
            border-left: 3px solid #8B5CF6;
            margin-bottom: 3mm;
        }
        
        .comment-card .title {
            font-size: 8pt;
            font-weight: bold;
            color: #8B5CF6;
            margin-bottom: 1mm;
        }
        
        .comment-card .text {
            font-size: 7pt;
            line-height: 1.5;
            color: #374151;
            background: #FFFFFF;
            padding: 2mm;
            border-radius: 4px;
        }
        
        .donut-chart-section {
            text-align: center;
            margin-top: 2mm;
        }
        
        .donut-chart-section img {
            max-width: 100%;
            height: auto;
            max-height: 40mm;
        }
        
        .footer {
            text-align: center;
            margin-top: 2mm;
            padding-top: 1mm;
            border-top: 1px solid #E5E7EB;
        }
        
        .footer .text {
            font-size: 6pt;
            color: #9CA3AF;
        }
        
        /* アイコン用スタイル（絵文字の代わりにテキスト使用） */
        .icon-task::before { content: "▶"; color: #3B82F6; }
        .icon-group::before { content: "★"; color: #8B5CF6; }
        .icon-trend::before { content: "▲"; color: #10B981; }
        .icon-chat::before { content: "●"; color: #8B5CF6; }
    </style>
</head>
<body>
    <div class="container">
        {{-- ヘッダー --}}
        <div class="header">
            <h1>{{ $userName }}'s Report</h1>
            <div class="date">{{ $yearMonth }} | Generated: {{ now()->format('Y-m-d H:i') }}</div>
        </div>
        
        {{-- 報酬ヒーローセクション --}}
        <div class="reward-hero">
            <div class="amount">
                {{ number_format($totalReward) }}<span class="unit">円</span>
            </div>
            <div class="label">今月の報酬 🎉</div>
        </div>
        
        {{-- コメントカード --}}
        <div class="comment-card">
            <div class="title"><span class="icon-chat"></span> せんせいからのメッセージ</div>
            <div class="text">{{ $comment }}</div>
        </div>
        
        {{-- コンテンツグリッド --}}
        <div class="content-grid">
            {{-- 左: 統計カード --}}
            <div class="card">
                <div class="card-title">がんばりポイント ✨</div>
                
                {{-- 折れ線グラフ --}}
                @if(!empty($trendChartBase64))
                <div class="chart-container">
                    <img src="data:image/png;base64,{{ $trendChartBase64 }}" alt="報酬推移グラフ" />
                </div>
                @endif
                
                {{-- 統計ボックス --}}
                <div class="stats-grid">
                    <div class="stat-box">
                        <div class="icon icon-task"></div>
                        <div class="label">通常タスク</div>
                        <div class="value">{{ $normalTaskCount }}</div>
                    </div>
                    <div class="stat-box">
                        <div class="icon icon-group"></div>
                        <div class="label">グループ</div>
                        <div class="value">{{ $groupTaskCount }}</div>
                    </div>
                    <div class="stat-box {{ $changePercentage >= 0 ? 'green' : 'red' }}">
                        <div class="icon icon-trend"></div>
                        <div class="label">前月比</div>
                        <div class="value">{{ $changePercentage >= 0 ? '+' : '' }}{{ $changePercentage }}%</div>
                    </div>
                </div>
            </div>
            
            {{-- 右: ドーナツグラフカード --}}
            <div class="card">
                <div class="card-title">タスクの内わけ 📊</div>
                
                {{-- ドーナツグラフ --}}
                @if(!empty($donutChartBase64))
                <div class="donut-chart-section">
                    <img src="data:image/png;base64,{{ $donutChartBase64 }}" alt="タスク分類グラフ" />
                </div>
                @else
                <div style="text-align: center; padding: 10mm; color: #9CA3AF; font-size: 8pt;">
                    データがありません
                </div>
                @endif
            </div>
        </div>
        
        {{-- フッター --}}
        <div class="footer">
            <div class="text">MyTeacher &copy; 2025 | がんばったね！この調子でつづけよう！ 💪</div>
        </div>
    </div>
</body>
</html>

