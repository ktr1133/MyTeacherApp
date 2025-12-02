/**
 * 月次レポート詳細ページのJavaScript
 * 
 * 責務:
 * - 年月選択による画面遷移制御
 * - Chart.jsによるグラフ描画（合計、報酬、詳細）
 * - 詳細グラフの折りたたみ制御とアニメーション
 * - レスポンシブ対応（デスクトップ/モバイル）
 */

document.addEventListener('DOMContentLoaded', function() {
    // ========================================
    // 1. 年月選択による画面遷移
    // ========================================
    
    // デスクトップ: 年月プルダウン
    const yearSelect = document.getElementById('year-select');
    const monthSelect = document.getElementById('month-select');
    
    if (yearSelect && monthSelect) {
        const handleNavigation = () => {
            const year = yearSelect.value;
            const month = monthSelect.value.padStart(2, '0');
            const baseUrl = yearSelect.dataset.routeBase || '/reports/monthly';
            window.location.href = `${baseUrl}/${year}/${month}`;
        };
        
        yearSelect.addEventListener('change', handleNavigation);
        monthSelect.addEventListener('change', handleNavigation);
    }
    
    // モバイル: input[type=month]
    const monthPicker = document.getElementById('month-picker');
    if (monthPicker) {
        monthPicker.addEventListener('change', function() {
            const [year, month] = this.value.split('-');
            const baseUrl = this.dataset.routeBase || '/reports/monthly';
            window.location.href = `${baseUrl}/${year}/${month}`;
        });
    }
    
    // ========================================
    // 2. Chart.js グラフ描画
    // ========================================
    
    // データ取得（Bladeから渡される）
    const trendDataElement = document.getElementById('trend-data');
    if (!trendDataElement) {
        console.warn('Trend data element not found');
        return;
    }
    
    const trendData = JSON.parse(trendDataElement.textContent);
    
    if (!trendData || !trendData.total?.datasets?.length) {
        console.warn('No trend data available');
        return;
    }
    
    console.log('Trend data loaded:', {
        totalDatasetCount: trendData.total?.datasets?.length || 0,
        normalDatasetCount: trendData.normal?.datasets?.length || 0,
        groupDatasetCount: trendData.group?.datasets?.length || 0,
        rewardDatasetCount: trendData.reward?.datasets?.length || 0,
        members: trendData.members
    });
    
    // ダークモード判定
    const isDarkMode = document.documentElement.classList.contains('dark');
    
    // 共通のChartオプション（折れ線グラフ用）
    const lineOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
                align: 'start',
                labels: {
                    color: isDarkMode ? '#e5e7eb' : '#374151',
                    font: {
                        size: 12,
                        weight: '500'
                    },
                    boxWidth: 20,
                    boxHeight: 12,
                    padding: 12,
                    usePointStyle: true,
                    pointStyle: 'circle',
                },
                maxHeight: 80,
            },
            title: {
                display: false
            },
            tooltip: {
                mode: 'index',
                intersect: false,
                backgroundColor: isDarkMode ? 'rgba(31, 41, 55, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                titleColor: isDarkMode ? '#f3f4f6' : '#111827',
                bodyColor: isDarkMode ? '#e5e7eb' : '#374151',
                borderColor: isDarkMode ? '#4b5563' : '#d1d5db',
                borderWidth: 1,
                padding: 12,
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ' + context.parsed.y + '件';
                    }
                }
            }
        },
        scales: {
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    color: isDarkMode ? '#9ca3af' : '#6b7280',
                    font: {
                        size: 11
                    }
                }
            },
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 2,
                    color: isDarkMode ? '#9ca3af' : '#6b7280',
                    font: {
                        size: 11
                    }
                },
                grid: {
                    color: isDarkMode ? '#374151' : '#e5e7eb'
                }
            }
        },
        interaction: {
            mode: 'nearest',
            axis: 'x',
            intersect: false
        }
    };
    
    // 積み上げ棒グラフ用オプション
    const barOptions = JSON.parse(JSON.stringify(lineOptions));
    barOptions.scales.x.stacked = true;
    barOptions.scales.y.stacked = true;
    barOptions.scales.y.ticks.stepSize = 1;
    barOptions.plugins.legend.labels.pointStyle = 'rectRounded';
    
    // 合計タスクグラフ（折れ線グラフ）
    const totalCtx = document.getElementById('total-trend-chart');
    if (totalCtx && trendData.total?.datasets?.length > 0) {
        new Chart(totalCtx, {
            type: 'line',
            data: {
                labels: trendData.total.labels,
                datasets: trendData.total.datasets
            },
            options: lineOptions
        });
    }
    
    // 報酬獲得の推移グラフ（折れ線グラフ）
    const rewardCtx = document.getElementById('reward-trend-chart');
    if (rewardCtx && trendData.reward?.datasets?.length > 0) {
        // 報酬用にツールチップをカスタマイズ（数値のみ表示）
        const rewardOptions = JSON.parse(JSON.stringify(lineOptions));
        rewardOptions.plugins.tooltip.callbacks = {
            label: function(context) {
                let label = context.dataset.label || '';
                if (label) {
                    label += ': ';
                }
                if (context.parsed.y !== null) {
                    label += context.parsed.y.toLocaleString();
                }
                return label;
            }
        };
        
        new Chart(rewardCtx, {
            type: 'line',
            data: {
                labels: trendData.reward.labels,
                datasets: trendData.reward.datasets
            },
            options: rewardOptions
        });
    }
    
    // ========================================
    // 3. 詳細グラフの遅延初期化 & トグル制御
    // ========================================
    
    let normalChart = null;
    let groupChart = null;
    let detailChartsInitialized = false;
    
    /**
     * 詳細グラフを初期化（遅延実行）
     * Canvas要素が非表示の状態でChart.jsを初期化すると正しく描画されないため、
     * トグルで表示された後に初期化を実行する
     */
    function initializeDetailCharts() {
        if (detailChartsInitialized) return;
        
        // 通常タスクグラフ（積み上げ棒グラフ）
        const normalCtx = document.getElementById('normal-trend-chart');
        if (normalCtx && trendData.normal?.datasets?.length > 0 && !normalChart) {
            normalChart = new Chart(normalCtx, {
                type: 'bar',
                data: {
                    labels: trendData.normal.labels,
                    datasets: trendData.normal.datasets
                },
                options: barOptions
            });
        }
        
        // グループタスクグラフ（積み上げ棒グラフ）
        const groupCtx = document.getElementById('group-trend-chart');
        if (groupCtx && trendData.group?.datasets?.length > 0 && !groupChart) {
            groupChart = new Chart(groupCtx, {
                type: 'bar',
                data: {
                    labels: trendData.group.labels,
                    datasets: trendData.group.datasets
                },
                options: barOptions
            });
        }
        
        detailChartsInitialized = true;
    }
    
    /**
     * 詳細グラフのトグル制御（スムーズなアニメーション付き）
     */
    const toggleButton = document.getElementById('toggle-detail-charts');
    const detailCharts = document.getElementById('detail-charts');
    const toggleIcon = document.getElementById('toggle-icon');
    
    if (toggleButton && detailCharts && toggleIcon) {
        toggleButton.addEventListener('click', function() {
            const isOpen = detailCharts.style.maxHeight && detailCharts.style.maxHeight !== '0px';
            
            if (!isOpen) {
                // 開く処理
                toggleIcon.classList.add('rotate-180');
                
                // スクロール高さを取得するために一時的にmax-heightを解除
                detailCharts.style.maxHeight = 'none';
                const height = detailCharts.scrollHeight;
                detailCharts.style.maxHeight = '0';
                
                // 次のフレームでアニメーション開始
                requestAnimationFrame(() => {
                    detailCharts.style.maxHeight = height + 'px';
                    detailCharts.style.opacity = '1';
                });
                
                // グラフ初期化（アニメーション開始後に実行）
                if (!detailChartsInitialized) {
                    setTimeout(() => {
                        initializeDetailCharts();
                        
                        // グラフ描画完了後に高さを再調整
                        setTimeout(() => {
                            detailCharts.style.maxHeight = detailCharts.scrollHeight + 'px';
                        }, 100);
                    }, 50);
                }
            } else {
                // 閉じる処理
                toggleIcon.classList.remove('rotate-180');
                detailCharts.style.maxHeight = '0';
                detailCharts.style.opacity = '0';
            }
        });
    }
    
    // ========================================
    // 4. メンバー別概況レポート生成機能
    // ========================================
    
    const memberFilter = document.getElementById('member-filter');
    const generateBtn = document.getElementById('generate-member-summary-btn');
    const generatingModal = document.getElementById('member-summary-generating-modal');
    const resultModal = document.getElementById('member-summary-result-modal');
    const resultOverlay = document.getElementById('member-summary-result-overlay');
    const resultCloseBtn1 = document.getElementById('member-summary-result-close-btn');
    const resultCloseBtn2 = document.getElementById('member-summary-result-close-btn-2');
    
    let memberTaskChart = null;
    let memberRewardChart = null;
    
    // メンバー選択時にボタンを有効化
    if (memberFilter && generateBtn) {
        memberFilter.addEventListener('change', function() {
            const selectedUserId = this.value;
            generateBtn.disabled = !selectedUserId; // ユーザーが選択されていればボタン有効化
        });
        
        // ボタンクリック時の処理
        generateBtn.addEventListener('click', async function() {
            const selectedUserId = memberFilter.value;
            if (!selectedUserId) {
                return;
            }
            
            const selectedOption = memberFilter.options[memberFilter.selectedIndex];
            const userName = selectedOption.dataset.name || '選択されたメンバー';
            const groupId = this.dataset.groupId;
            const yearMonth = this.dataset.yearMonth;
            const apiUrl = this.dataset.apiUrl;
            
            // トークン消費警告をconfirm-dialogで表示
            window.showConfirmDialog(
                `${userName}さんの概況レポートを生成します。\n\nこの操作にはトークンを消費します。\n実行してもよろしいですか？`,
                async () => {
                    // 確認が取れたら生成処理を開始
                    try {
                        // 生成中モーダルを表示
                        showGeneratingModal();
                        
                        // API呼び出し
                        const response = await fetch(apiUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                user_id: parseInt(selectedUserId),
                                year_month: yearMonth
                            })
                        });
                        
                        const result = await response.json();
                        
                        // 生成中モーダルを非表示
                        hideGeneratingModal();
                        
                        if (response.ok && result.success) {
                            // 結果モーダルに データを表示（userIdとyearMonthを渡す）
                            displayMemberSummaryResult(result.data, userName, selectedUserId, yearMonth);
                        } else {
                            // エラー処理
                            alert(result.message || 'レポート生成に失敗しました。');
                        }
                    } catch (error) {
                        hideGeneratingModal();
                        console.error('Error generating member summary:', error);
                        alert('レポート生成中にエラーが発生しました。');
                    }
                },
                () => {
                    // キャンセル時の処理（何もしない）
                }
            );
        });
    }
    
    /**
     * 生成中モーダルを表示
     */
    function showGeneratingModal() {
        if (generatingModal) {
            generatingModal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }
    }
    
    /**
     * 生成中モーダルを非表示
     */
    function hideGeneratingModal() {
        if (generatingModal) {
            generatingModal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
    }
    
    /**
     * 結果モーダルを表示してデータを反映
     */
    function displayMemberSummaryResult(data, userName, userId, yearMonth) {
        if (!resultModal) return;
        
        // タイトル設定
        const titleEl = document.getElementById('member-summary-result-title');
        if (titleEl) {
            titleEl.textContent = `${userName}さんの概況レポート`;
        }
        
        // AIコメント表示
        const commentEl = document.getElementById('member-summary-comment');
        if (commentEl && data.comment) {
            commentEl.textContent = data.comment;
        }
        
        // トークン消費量表示
        const tokensUsedEl = document.getElementById('member-summary-tokens-used');
        if (tokensUsedEl && data.tokens_used) {
            tokensUsedEl.textContent = data.tokens_used.toLocaleString();
        }
        
        // 隠しフィールドに値を設定（PDF生成用）
        const userIdField = document.getElementById('member-summary-result-user-id');
        const yearMonthField = document.getElementById('member-summary-result-year-month');
        const commentField = document.getElementById('member-summary-result-comment');
        
        if (userIdField) userIdField.value = userId;
        if (yearMonthField) yearMonthField.value = yearMonth;
        if (commentField && data.comment) commentField.value = data.comment;
        
        // タスク分類円グラフ描画
        if (data.task_classification) {
            drawTaskClassificationChart(data.task_classification);
        }
        
        // 報酬推移折れ線グラフ描画
        if (data.reward_trend) {
            drawRewardTrendChart(data.reward_trend);
        }
        
        // モーダル表示
        resultModal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }
    
    /**
     * タスク分類円グラフを描画
     */
    function drawTaskClassificationChart(classificationData) {
        const canvas = document.getElementById('member-task-classification-chart');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        
        // 既存のグラフを破棄
        if (memberTaskChart) {
            memberTaskChart.destroy();
        }
        
        // 円グラフ用の色設定（グラデーション風）
        const colors = [
            'rgba(59, 130, 246, 0.9)',   // blue
            'rgba(168, 85, 247, 0.9)',   // purple
            'rgba(236, 72, 153, 0.9)',   // pink
            'rgba(16, 185, 129, 0.9)',   // green
            'rgba(251, 146, 60, 0.9)',   // orange
            'rgba(250, 204, 21, 0.9)',   // yellow
        ];
        
        const borderColors = colors.map(c => c.replace('0.9', '1'));
        
        memberTaskChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: classificationData.labels || [],
                datasets: [{
                    data: classificationData.data || [],
                    backgroundColor: colors,
                    borderColor: isDarkMode ? '#1f2937' : '#ffffff',
                    borderWidth: 3,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '50%',
                plugins: {
                    legend: {
                        position: 'right',
                        align: 'center',
                        labels: {
                            color: isDarkMode ? '#e5e7eb' : '#374151',
                            font: {
                                size: 12,
                                weight: '500'
                            },
                            padding: 12,
                            boxWidth: 16,
                            boxHeight: 16,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: isDarkMode ? 'rgba(31, 41, 55, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                        titleColor: isDarkMode ? '#f3f4f6' : '#111827',
                        bodyColor: isDarkMode ? '#d1d5db' : '#4b5563',
                        borderColor: isDarkMode ? '#4b5563' : '#e5e7eb',
                        borderWidth: 1,
                        padding: 12,
                        boxPadding: 6,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return `${label}: ${value}件 (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    /**
     * 報酬推移折れ線グラフを描画
     */
    function drawRewardTrendChart(rewardData) {
        const canvas = document.getElementById('member-reward-trend-chart');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        
        // 既存のグラフを破棄
        if (memberRewardChart) {
            memberRewardChart.destroy();
        }
        
        memberRewardChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: rewardData.labels || [],
                datasets: [{
                    label: '報酬',
                    data: rewardData.data || [],
                    backgroundColor: function(context) {
                        const ctx = context.chart.ctx;
                        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                        gradient.addColorStop(0, 'rgba(251, 146, 60, 0.3)');
                        gradient.addColorStop(1, 'rgba(251, 146, 60, 0.0)');
                        return gradient;
                    },
                    borderColor: 'rgba(251, 146, 60, 1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    pointBackgroundColor: 'rgba(251, 146, 60, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 3,
                    pointHoverBorderWidth: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: isDarkMode ? 'rgba(31, 41, 55, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                        titleColor: isDarkMode ? '#f3f4f6' : '#111827',
                        bodyColor: isDarkMode ? '#d1d5db' : '#4b5563',
                        borderColor: isDarkMode ? '#4b5563' : '#e5e7eb',
                        borderWidth: 1,
                        padding: 12,
                        boxPadding: 6,
                        callbacks: {
                            label: function(context) {
                                return `報酬: ${context.parsed.y.toLocaleString()}円`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: isDarkMode ? 'rgba(75, 85, 99, 0.3)' : 'rgba(229, 231, 235, 0.8)'
                        },
                        ticks: {
                            color: isDarkMode ? '#9ca3af' : '#6b7280',
                            font: {
                                size: 11
                            },
                            callback: function(value) {
                                return value.toLocaleString() + '円';
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: isDarkMode ? '#9ca3af' : '#6b7280',
                            font: {
                                size: 11
                            }
                        }
                    }
                }
            }
        });
    }
    
    /**
     * 結果モーダルを閉じる（確認付き）
     */
    function confirmAndCloseResultModal() {
        window.showConfirmDialog(
            'このレポートはトークンを消費して生成されています。\n閉じると生成結果が破棄されます。\n\n本当に閉じてもよろしいですか？',
            () => {
                // 確認後に閉じる
                closeResultModal();
            },
            () => {
                // キャンセル時は何もしない
            }
        );
    }
    
    /**
     * 結果モーダルを閉じる（実際の処理）
     */
    function closeResultModal() {
        if (resultModal) {
            resultModal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            
            // グラフを破棄してメモリ解放
            if (memberTaskChart) {
                memberTaskChart.destroy();
                memberTaskChart = null;
            }
            if (memberRewardChart) {
                memberRewardChart.destroy();
                memberRewardChart = null;
            }
        }
    }
    
    // 閉じるボタンのイベント登録（確認付き）
    if (resultCloseBtn1) {
        resultCloseBtn1.addEventListener('click', confirmAndCloseResultModal);
    }
    if (resultCloseBtn2) {
        resultCloseBtn2.addEventListener('click', confirmAndCloseResultModal);
    }
    
    // オーバーレイクリックで閉じる（確認付き）
    if (resultOverlay) {
        resultOverlay.addEventListener('click', confirmAndCloseResultModal);
    }
    
    // ========================================
    // 7. PDFダウンロード機能
    // ========================================
    
    const downloadPdfBtn = document.getElementById('download-member-summary-pdf-btn');
    
    if (downloadPdfBtn) {
        downloadPdfBtn.addEventListener('click', async function() {
            // ボタン無効化（元のHTMLを保存）
            downloadPdfBtn.disabled = true;
            const originalHTML = downloadPdfBtn.innerHTML;
            downloadPdfBtn.innerHTML = `
                <svg class="w-5 h-5 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                PDF生成中...
            `;
            
            try {
                
                // 現在表示中のコメントとデータを取得（hiddenフィールドから）
                const commentText = document.getElementById('member-summary-result-comment')?.value || '';
                const userId = document.getElementById('member-summary-result-user-id')?.value || '';
                const yearMonth = document.getElementById('member-summary-result-year-month')?.value || '';
                
                // バリデーション
                if (!commentText) {
                    showFlashMessage('error', 'コメントが生成されていません。概況レポートを生成してください。');
                    return;
                }
                if (!userId || !yearMonth) {
                    showFlashMessage('error', '必要なデータが不足しています。');
                    return;
                }
                
                // Chart.jsのグラフをBase64画像に変換
                let chartImageBase64 = null;
                if (memberTaskChart) {
                    chartImageBase64 = memberTaskChart.toBase64Image();
                }
                
                // PDFダウンロードリクエスト
                const response = await fetch('/reports/monthly/member-summary/pdf', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        year_month: yearMonth,
                        comment: commentText,
                        chart_image: chartImageBase64,
                    }),
                });
                
                if (!response.ok) {
                    // エラーレスポンスを取得
                    let errorMessage = 'PDF生成に失敗しました';
                    
                    try {
                        // JSON形式のエラーレスポンスを試みる
                        const errorData = await response.json();
                        errorMessage = errorData.error || errorMessage;
                        
                        // バリデーションエラーの詳細を表示
                        if (errorData.errors) {
                            const errorDetails = Object.values(errorData.errors).flat().join(', ');
                            errorMessage = `${errorMessage} (${errorDetails})`;
                        }
                    } catch (jsonError) {
                        // JSON解析失敗の場合はテキストとして取得
                        try {
                            const textError = await response.text();
                            if (textError) {
                                errorMessage = `${errorMessage} (ステータス: ${response.status})`;
                                console.error('Error response:', textError);
                            }
                        } catch (textError) {
                            errorMessage = `${errorMessage} (ステータス: ${response.status})`;
                        }
                    }
                    
                    throw new Error(errorMessage);
                }
                
                // PDFをダウンロード
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                
                // ファイル名を取得（Content-Dispositionヘッダーから）
                const contentDisposition = response.headers.get('content-disposition');
                let fileName = `member-summary-${yearMonth}.pdf`;
                if (contentDisposition) {
                    const fileNameMatch = contentDisposition.match(/filename="?(.+)"?/);
                    if (fileNameMatch && fileNameMatch.length === 2) {
                        fileName = fileNameMatch[1];
                    }
                }
                
                a.download = fileName;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                
                // 成功メッセージ表示
                showFlashMessage('success', 'PDFをダウンロードしました');
                
            } catch (error) {
                console.error('PDF download error:', error);
                showFlashMessage('error', 'PDF生成に失敗しました: ' + error.message);
            } finally {
                // ボタン有効化（元のHTMLに復元）- 必ず実行
                downloadPdfBtn.disabled = false;
                downloadPdfBtn.innerHTML = originalHTML;
            }
        });
    }
    
    /**
     * フラッシュメッセージ表示
     */
    function showFlashMessage(type, message) {
        const colors = {
            success: { bg: 'bg-green-50', border: 'border-green-500', text: 'text-green-800' },
            error: { bg: 'bg-red-50', border: 'border-red-500', text: 'text-red-800' },
            warning: { bg: 'bg-yellow-50', border: 'border-yellow-500', text: 'text-yellow-800' },
            info: { bg: 'bg-blue-50', border: 'border-blue-500', text: 'text-blue-800' },
        };
        
        const color = colors[type] || colors.info;
        
        const flashDiv = document.createElement('div');
        flashDiv.className = `fixed top-4 right-4 z-[9999] max-w-sm w-full ${color.bg} ${color.border} ${color.text} border-l-4 p-4 rounded-lg shadow-lg`;
        flashDiv.innerHTML = `
            <div class="flex items-start">
                <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium">${message}</p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 flex-shrink-0">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        `;
        
        document.body.appendChild(flashDiv);
        
        // 5秒後に自動削除
        setTimeout(() => flashDiv.remove(), 5000);
    }
});
