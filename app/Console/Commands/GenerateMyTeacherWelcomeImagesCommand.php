<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Services\AI\OpenAIServiceInterface;
use App\Services\AI\StableDiffusionService;

/**
 * MyTeacherウェルカムページの画像を生成するコマンド
 * 
 * DALL-E 3を使用してヒーローセクション用とアバター応援セクション用の
 * ちびキャラスタイル画像を生成し、背景を透過処理してpublic/images/に保存します。
 */
class GenerateMyTeacherWelcomeImagesCommand extends Command
{
    /**
     * コマンドのシグネチャ
     *
     * @var string
     */
    protected $signature = 'myteacher-welcome:generate-images 
                            {--hero-only : ヒーローセクション画像のみ生成}
                            {--avatar-only : アバター応援画像のみ生成}';

    /**
     * コマンドの説明
     *
     * @var string
     */
    protected $description = 'MyTeacherウェルカムページ用のちびキャラ画像を生成（DALL-E 3 + 背景透過）';

    /**
     * コマンドを実行
     *
     * @param OpenAIServiceInterface $openAIService
     * @param StableDiffusionService $sdService
     * @return int
     */
    public function handle(OpenAIServiceInterface $openAIService, StableDiffusionService $sdService): int
    {
        $this->info('🎨 MyTeacherウェルカムページ画像生成を開始します...');
        $this->newLine();

        $heroOnly = $this->option('hero-only');
        $avatarOnly = $this->option('avatar-only');

        // ヒーローセクション画像の生成
        if (!$avatarOnly) {
            $this->info('📸 [1/2] ヒーローセクション画像を生成中...');
            $heroResult = $this->generateHeroImage($openAIService, $sdService);
            
            if ($heroResult) {
                $this->info("✅ ヒーローセクション画像: /images/{$heroResult}");
            } else {
                $this->error('❌ ヒーローセクション画像の生成に失敗しました');
                if ($heroOnly) {
                    return 1;
                }
            }
            $this->newLine();
        }

        // アバター応援画像の生成
        if (!$heroOnly) {
            $this->info('📸 [2/2] アバター応援画像を生成中...');
            $avatarResult = $this->generateAvatarCelebrationImage($openAIService, $sdService);
            
            if ($avatarResult) {
                $this->info("✅ アバター応援画像: /images/{$avatarResult}");
            } else {
                $this->error('❌ アバター応援画像の生成に失敗しました');
                if ($avatarOnly) {
                    return 1;
                }
            }
            $this->newLine();
        }

        $this->info('✨ 画像生成が完了しました！');
        $this->newLine();
        $this->comment('次のステップ:');
        $this->comment('1. ブラウザで http://localhost:8080/images/welcome-hero.png を確認');
        $this->comment('2. ブラウザで http://localhost:8080/images/avatar-celebration.png を確認');
        $this->comment('3. welcome.blade.php を更新して画像を適用');

        return 0;
    }

    /**
     * ヒーローセクション画像を生成（お手伝いする子ども＋見守る家族＋応援するアバター）
     *
     * @param OpenAIServiceInterface $openAIService
     * @param StableDiffusionService $sdService
     * @return string|null ファイル名
     */
    private function generateHeroImage(OpenAIServiceInterface $openAIService, StableDiffusionService $sdService): ?string
    {
        $prompt = 
            'anime style illustration, overhead bird\'s eye view composition, ' .
            'four characters: father, mother, elementary school child, and female teacher avatar, ' .
            'all characters in chibi design with super deformed proportions (big heads, small bodies), ' .
            'center: one chibi elementary school child with happy smile doing victory pose (guts pose with raised fist), ' .
            'distance behind child: chibi father and chibi mother standing slightly apart from child, looking at child with joyful happy expressions, ' .
            'beside child: one female chibi teacher avatar character wearing cheerleading uniform, ' .
            'teacher holding cheerleading megaphone and cheering for child with sweat drops showing effort, ' .
            'bright solid blue background (#0000FF blue screen), ' .
            'no shadows on background, clean flat design, kawaii anime style';

        try {
            // DALL-E 3で画像生成
            $this->line('   ⏳ DALL-E 3で画像を生成中...');
            $imageUrl = $openAIService->generateImage($prompt, '1024x1024', 'standard');

            if (!$imageUrl) {
                $this->error('   ❌ 画像生成に失敗しました（URLが取得できませんでした）');
                return null;
            }

            $this->line('   ✅ 画像生成成功');

            // 画像をダウンロード
            $this->line('   ⬇️  画像をダウンロード中...');
            $response = Http::timeout(30)->get($imageUrl);

            if (!$response->successful()) {
                $this->error('   ❌ 画像のダウンロードに失敗しました');
                return null;
            }

            // ファイル名（固定名で上書き）
            $filename = 'welcome-hero.png';
            $publicPath = public_path('images/' . $filename);

            // ディレクトリが存在しない場合は作成
            $directory = public_path('images');
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            // 画像を保存
            file_put_contents($publicPath, $response->body());

            return $filename;

        } catch (\Exception $e) {
            $this->error('   ❌ エラーが発生しました: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * アバター応援画像を生成（喜びのアバター単体・バスト）
     *
     * @param OpenAIServiceInterface $openAIService
     * @param StableDiffusionService $sdService
     * @return string|null ファイル名
     */
    private function generateAvatarCelebrationImage(OpenAIServiceInterface $openAIService, StableDiffusionService $sdService): ?string
    {
        $prompt = 
            'chibi style teacher character portrait, bust shot (upper body only), ' .
            'extremely happy expression with big bright smile and sparkling eyes, ' .
            'celebrating with both hands raised in victory or applause gesture, ' .
            'wearing graduation cap and round glasses, ' .
            'super deformed proportions (very big head, cute small body), ' .
            'cheerful and energetic mood, game character style, ' .
            'gradient colors turquoise blue and purple, ' .
            'solid plain light green background (easy to remove with chroma key), no text, kawaii anime style, ' .
            'digital illustration, clean and simple design, joyful atmosphere, ' .
            'no shadows, even lighting, flat background';

        try {
            // DALL-E 3で画像生成
            $this->line('   ⏳ DALL-E 3で画像を生成中...');
            $imageUrl = $openAIService->generateImage($prompt, '1024x1024', 'standard');

            if (!$imageUrl) {
                $this->error('   ❌ 画像生成に失敗しました（URLが取得できませんでした）');
                return null;
            }

            $this->line('   ✅ 画像生成成功');

            // 画像をダウンロード
            $this->line('   ⬇️  画像をダウンロード中...');
            $response = Http::timeout(30)->get($imageUrl);

            if (!$response->successful()) {
                $this->error('   ❌ 画像のダウンロードに失敗しました');
                return null;
            }

            // ファイル名（固定名で上書き）
            $filename = 'avatar-celebration.png';
            $publicPath = public_path('images/' . $filename);

            // ディレクトリが存在しない場合は作成
            $directory = public_path('images');
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            // 画像を保存
            file_put_contents($publicPath, $response->body());

            return $filename;

        } catch (\Exception $e) {
            $this->error('   ❌ エラーが発生しました: ' . $e->getMessage());
            return null;
        }
    }
}
