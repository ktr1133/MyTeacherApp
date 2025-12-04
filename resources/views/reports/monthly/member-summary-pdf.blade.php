<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <title>{{ $userName }}'s Report - {{ $yearMonth }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=M+PLUS+Rounded+1c:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @page {
            size: A4 landscape;
            margin: 0;
        }
        
        body {
            font-family: 'M PLUS Rounded 1c', 'Hiragino Kaku Gothic ProN', 'Yu Gothic', 'Meiryo', sans-serif;
            background: linear-gradient(135deg, #FFE4D6 0%, #FFEEE8 50%, #FFE4D6 100%);
            padding: 5mm 6mm;
            color: #333333;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        
        .container {
            background: #FFFFFF;
            border-radius: 16px;
            padding: 5mm 6mm;
            box-shadow: 0 6px 20px rgba(255, 140, 0, 0.2), 0 0 0 3px #FF8C00;
            border: 3px solid #FF8C00;
            max-width: 280mm;
            margin: 0 auto;
            position: relative;
        }
        
        .top-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 3mm;
            gap: 5mm;
        }
        
        .header {
            flex: 0 0 auto;
            text-align: left;
        }
        
        .header h1 {
            font-size: 16pt;
            font-weight: 900;
            margin-bottom: 0.5mm;
            background: linear-gradient(135deg, #FF6B00 0%, #FF1493 50%, #FF6B00 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: 0.3mm;
        }
        
        .header .user-name {
            font-size: 18pt;
            font-weight: 900;
            color: #333333;
            display: inline-block;
        }
        
        .header .date {
            font-size: 7pt;
            color: #999999;
            margin-top: 0.5mm;
        }
        
        .reward-hero {
            background: linear-gradient(135deg, #FF6B00 0%, #FF8C00 50%, #FFA500 100%);
            border-radius: 16px;
            padding: 4mm 6mm;
            text-align: center;
            box-shadow: 0 6px 16px rgba(255, 107, 0, 0.5), 0 0 0 2px rgba(255, 255, 255, 0.6) inset;
            position: relative;
            overflow: hidden;
            flex: 1;
            min-width: 120mm;
        }
        
        .reward-hero::before {
            content: '⭐';
            position: absolute;
            font-size: 50pt;
            opacity: 0.12;
            top: -8mm;
            right: -3mm;
            transform: rotate(15deg);
        }
        
        .reward-hero::after {
            content: '🎉';
            position: absolute;
            font-size: 35pt;
            opacity: 0.15;
            bottom: -4mm;
            left: -3mm;
            transform: rotate(-20deg);
        }
        
        .reward-hero .amount {
            font-size: 42pt;
            font-weight: 900;
            color: #FFFFFF;
            line-height: 1;
            text-shadow: 3px 3px 8px rgba(0,0,0,0.4);
            position: relative;
            z-index: 1;
        }
        
        .reward-hero .unit {
            font-size: 22pt;
            font-weight: 700;
        }
        
        .reward-hero .label {
            font-size: 10pt;
            color: #FFFFFF;
            margin-top: 1mm;
            font-weight: 700;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
            position: relative;
            z-index: 1;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 60% 40%;
            gap: 3mm;
            margin-bottom: 0;
        }
        
        .card {
            background: #FFFFFF;
            border-radius: 12px;
            padding: 3mm;
            border: 3px solid #FF1493;
            box-shadow: 0 4px 12px rgba(255, 20, 147, 0.25);
            position: relative;
        }
        
        .card-title {
            font-size: 10pt;
            font-weight: 900;
            color: #FF1493;
            margin-bottom: 2mm;
            padding: 1.5mm;
            background: linear-gradient(135deg, #FFE4F0, #FFB6D9);
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(255, 20, 147, 0.15);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2mm;
            margin-top: 2mm;
        }
        
        .stat-box {
            background: linear-gradient(135deg, #FFFFFF, #FFF5F5);
            border-radius: 10px;
            padding: 3mm 2mm;
            text-align: center;
            border: 2px solid #FF1493;
            box-shadow: 0 2px 6px rgba(255, 20, 147, 0.2);
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 20mm;
        }
        
        .stat-box .icon {
            display: none;
        }
        
        .stat-box .label {
            font-size: 8pt;
            color: #666666;
            margin-bottom: 1mm;
            font-weight: 700;
        }
        
        .stat-box .value {
            font-size: 24pt;
            font-weight: 900;
            color: #0066FF;
            line-height: 1;
        }
        
        .stat-box.task {
            border-color: #0066FF;
        }
        
        .stat-box.task .value {
            color: #0066FF;
        }
        
        .stat-box.group {
            border-color: #9933FF;
        }
        
        .stat-box.group .value {
            color: #9933FF;
        }
        
        .stat-box.trend {
            border-color: #FF0066;
        }
        
        .stat-box.trend .value {
            color: #FF0066;
        }
        
        .stat-box.trend.positive {
            border-color: #FF6B00;
        }
        
        .stat-box.trend.positive .value {
            color: #FF6B00;
        }
        
        .chart-container {
            background: linear-gradient(135deg, #FFF5E6, #FFECD6);
            border-radius: 10px;
            padding: 2mm;
            text-align: center;
            margin-top: 2mm;
            box-shadow: 0 2px 6px rgba(255, 140, 0, 0.1) inset;
        }
        
        .chart-container img {
            max-width: 100%;
            height: auto;
            max-height: 130mm;
            border-radius: 6px;
        }
        
        .comment-card {
            background: #FFE4D6;
            border-radius: 12px;
            padding: 3mm;
            margin-bottom: 3mm;
            position: relative;
            box-shadow: 0 3px 10px rgba(255, 140, 0, 0.15);
        }
        
        .comment-card::before {
            content: '';
            position: absolute;
            left: 6mm;
            top: -2.5mm;
            width: 0;
            height: 0;
            border-left: 7px solid transparent;
            border-right: 7px solid transparent;
            border-bottom: 9px solid #FFE4D6;
            filter: drop-shadow(0 -2px 3px rgba(255, 140, 0, 0.1));
        }
        
        .comment-card .title {
            font-size: 8pt;
            font-weight: 700;
            color: #FF6B00;
            margin-bottom: 1.5mm;
            display: flex;
            align-items: center;
            gap: 2mm;
        }
        
        .comment-card .teacher-icon {
            width: 7mm;
            height: 7mm;
            background: linear-gradient(135deg, #FF6B00, #FF8C00);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #FFFFFF;
            font-size: 9pt;
            font-weight: 700;
            box-shadow: 0 2px 6px rgba(255, 107, 0, 0.4);
            flex-shrink: 0;
        }
        
        .comment-card .text {
            font-size: 8pt;
            line-height: 1.5;
            color: #333333;
            background: #FFFFFF;
            padding: 2.5mm;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(255, 140, 0, 0.1) inset;
        }
        
        .donut-chart-section {
            text-align: center;
            margin-top: 2mm;
            background: linear-gradient(135deg, #FFEBF5, #FFD6EB);
            border-radius: 10px;
            padding: 2mm;
            box-shadow: 0 2px 6px rgba(255, 20, 147, 0.1) inset;
        }
        
        .donut-chart-section img {
            max-width: 100%;
            height: auto;
            max-height: 140mm;
            border-radius: 6px;
        }
        
        .copyright {
            position: absolute;
            bottom: 4mm;
            right: 5mm;
            font-size: 7pt;
            color: #999999;
            font-weight: 700;
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
        {{-- タイトルと報酬の横並びセクション --}}
        <div class="top-section">
            {{-- ヘッダー --}}
            <div class="header">
                <h1><span class="user-name">{{ $userName }}</span>'s Report</h1>
                <div class="date">{{ $yearMonth }} | Generated: {{ now()->format('Y-m-d H:i') }}</div>
            </div>
            
            {{-- 報酬ヒーローセクション --}}
            <div class="reward-hero">
                <div class="amount">
                    {{ number_format($totalReward) }}<span class="unit">円</span>
                </div>
                <div class="label">⭐ 今月の報酬 ⭐</div>
            </div>
        </div>
        
        {{-- コメントカード --}}
        <div class="comment-card">
            <div class="title">
                <div class="teacher-icon">◉</div>
                <span>アバターからのメッセージ</span>
            </div>
            <div class="text">{{ $comment }}</div>
        </div>
        
        {{-- コンテンツグリッド --}}
        <div class="content-grid">
            {{-- 左: 統計カード --}}
            <div class="card">
                <div class="card-title">★ がんばりポイント ★</div>
                
                {{-- 折れ線グラフ --}}
                @if(!empty($trendChartBase64))
                <div class="chart-container">
                    <img src="data:image/png;base64,{{ $trendChartBase64 }}" alt="報酬推移グラフ" />
                </div>
                @endif
                
                {{-- 統計ボックス --}}
                <div class="stats-grid">
                    <div class="stat-box task">
                        <div class="label">ToDo</div>
                        <div class="value">{{ $normalTaskCount }}</div>
                    </div>
                    <div class="stat-box group">
                        <div class="label">クエスト</div>
                        <div class="value">{{ $groupTaskCount }}</div>
                    </div>
                    <div class="stat-box trend {{ $changePercentage >= 0 ? 'positive' : '' }}">
                        <div class="label">前月比</div>
                        <div class="value">{{ $changePercentage >= 0 ? '+' : '' }}{{ $changePercentage }}%</div>
                    </div>
                </div>
            </div>
            
            {{-- 右: ドーナツグラフカード --}}
            <div class="card">
                <div class="card-title">■ タスクの内わけ ■</div>
                
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
        
        {{-- 著作権表示 --}}
        <div class="copyright">
            MyTeacher &copy; 2025
        </div>
    </div>
</body>
</html>

