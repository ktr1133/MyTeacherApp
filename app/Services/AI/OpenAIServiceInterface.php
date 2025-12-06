<?php

namespace App\Services\AI;

/**
 * OpenAIサービスインターフェース
 * 
 * OpenAI APIとの通信を担当するサービスのインターフェース
 */
interface OpenAIServiceInterface
{
    /**
     * タスク分解リクエスト
     *
     * @param string $title タスクタイトル
     * @param string $context コンテキスト情報
     * @param bool $isRefinement 細分化フラグ（デフォルト: false）
     * @return array ['response' => string, 'usage' => array, 'model' => string]
     */
    public function requestDecomposition(string $title, string $context = '', bool $isRefinement = false): array;

    /**
     * DALL-E 3で画像生成
     *
     * @param string $prompt プロンプト
     * @param string $size サイズ ('1024x1024', '1792x1024', '1024x1792')
     * @param string $quality 品質 ('standard', 'hd')
     * @return string|null 生成された画像のURL
     */
    public function generateImage(string $prompt, string $size = '1024x1024', string $quality = 'standard'): ?string;

    /**
     * ChatGPT APIでチャット実行
     *
     * @param string $prompt ユーザープロンプト
     * @param string|null $systemPrompt システムプロンプト
     * @param string $model モデル名
     * @return array|null ['content' => string, 'usage' => array, 'model' => string]
     */
    public function chat(string $prompt, ?string $systemPrompt = null, string $model = 'gpt-4'): ?array;

    /**
     * 月次レポート用のコメント生成
     *
     * @param array $reportData レポートデータ
     * @param array|null $avatarPersonality アバターの性格情報
     * @param array $memberChanges メンバー別の変化データ
     * @return array ['content' => string, 'usage' => array, 'model' => string]
     */
    public function generateMonthlyReportComment(array $reportData, ?array $avatarPersonality = null, array $memberChanges = []): array;
}
