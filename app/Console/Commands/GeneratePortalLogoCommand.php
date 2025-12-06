<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Services\AI\OpenAIServiceInterface;
use App\Services\AI\StableDiffusionService;

/**
 * ポータルサイトのロゴ画像を生成するコマンド
 * 
 * DALL-E 3を使用してFamicoポータルのロゴを生成し、
 * public/images/ディレクトリに保存します。
 */
class GeneratePortalLogoCommand extends Command
{
    /**
     * コマンドのシグネチャ
     *
     * @var string
     */
    protected $signature = 'portal:generate-logo {--prompt= : カスタムプロンプト}';

    /**
     * コマンドの説明
     *
     * @var string
     */
    protected $description = 'DALL-E 3を使用してFamicoポータルのロゴを生成';

    /**
     * コマンドを実行
     *
     * @param OpenAIServiceInterface $openAIService
     * @param StableDiffusionService $sdService
     * @return int
     */
    public function handle(OpenAIServiceInterface $openAIService, StableDiffusionService $sdService): int
    {
        $this->info('🎨 Famicoポータルロゴ生成を開始します...');

        // プロンプトの準備（ポータルサイトの配色に合わせて調整）
        $prompt = $this->option('prompt') ?? 
            'minimalist logo design for a web portal named "Famico", symbol icon, ' .
            'combining the shape of a simple House and a Heart, ' .
            'constructed from three overlapping smooth abstract shapes, ' .
            'gradient colors are Turquoise Blue (#59B9C6), Purple (#8b5cf6), and Pink (#ec4899), ' .
            'transparent background, flat vector graphics, modern, clean lines, ' .
            'no text, rounded corners, high quality, icon style';

        $this->info("📝 プロンプト: {$prompt}");

        try {
            // DALL-E 3で画像生成（1024x1024サイズ、標準品質）
            $this->info('⏳ DALL-E 3で画像を生成中...');
            $imageUrl = $openAIService->generateImage($prompt, '1024x1024', 'standard');

            if (!$imageUrl) {
                $this->error('❌ 画像生成に失敗しました（URLが取得できませんでした）');
                return 1;
            }

            $this->info("✅ 画像生成成功: {$imageUrl}");

            // 背景を透過処理
            $this->info('🔄 背景を透過処理中...');
            $transparentResult = $sdService->removeBackground($imageUrl);

            if (!$transparentResult || !isset($transparentResult['url'])) {
                $this->error('❌ 背景透過処理に失敗しました');
                return 1;
            }

            $finalImageUrl = $transparentResult['url'];
            $this->info("✅ 背景透過成功: {$finalImageUrl}");

            // 透過画像をダウンロード
            $this->info('⬇️  透過画像をダウンロード中...');
            $response = Http::timeout(30)->get($finalImageUrl);

            if (!$response->successful()) {
                $this->error('❌ 画像のダウンロードに失敗しました');
                return 1;
            }

            // ファイル名を生成（タイムスタンプ付き）
            $filename = 'famico-logo-' . now()->format('YmdHis') . '.png';
            $publicPath = public_path('images/' . $filename);

            // ディレクトリが存在しない場合は作成
            $directory = public_path('images');
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            // 画像を保存
            file_put_contents($publicPath, $response->body());

            $this->info("💾 画像を保存しました: /images/{$filename}");
            $this->newLine();
            $this->info('✨ ロゴ生成が完了しました！');
            $this->newLine();
            $this->comment('次のステップ:');
            $this->comment("1. ブラウザで http://localhost:8080/images/{$filename} を確認");
            $this->comment('2. 気に入ったら portal.blade.php のロゴを更新してください');

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ エラーが発生しました: ' . $e->getMessage());
            $this->error('スタックトレース: ' . $e->getTraceAsString());
            return 1;
        }
    }
}
