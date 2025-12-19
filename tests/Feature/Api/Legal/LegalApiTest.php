<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Legal;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 法的文書API機能テスト
 */
class LegalApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * プライバシーポリシーAPIが正しいコンテンツを返すことを確認
     */
    public function test_プライバシーポリシーAPIが正しいコンテンツを返す(): void
    {
        $response = $this->getJson('/api/legal/privacy-policy');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'type',
                    'html',
                    'version',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $data = $response->json('data');
        $this->assertEquals('privacy-policy', $data['type']);
        $this->assertNotEmpty($data['html']);
        $this->assertStringContainsString('プライバシーポリシー', $data['html']);
        $this->assertStringContainsString('個人情報', $data['html']);
        
        // HTML形式で返されることを確認
        $this->assertStringContainsString('<', $data['html']);
        $this->assertStringContainsString('>', $data['html']);
    }

    /**
     * 利用規約APIが正しいコンテンツを返すことを確認
     */
    public function test_利用規約APIが正しいコンテンツを返す(): void
    {
        $response = $this->getJson('/api/legal/terms-of-service');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'type',
                    'html',
                    'version',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $data = $response->json('data');
        $this->assertEquals('terms-of-service', $data['type']);
        $this->assertNotEmpty($data['html']);
        $this->assertStringContainsString('利用規約', $data['html']);
        $this->assertStringContainsString('サービス', $data['html']);
        
        // HTML形式で返されることを確認
        $this->assertStringContainsString('<', $data['html']);
        $this->assertStringContainsString('>', $data['html']);
    }

    /**
     * 無効なタイプでAPIを呼び出すとエラーが返ることを確認
     */
    public function test_無効なタイプでAPIを呼び出すとエラーが返る(): void
    {
        $response = $this->getJson('/api/legal/invalid-type');

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * コンテンツが空でないことを確認
     */
    public function test_コンテンツが空でない(): void
    {
        $privacyResponse = $this->getJson('/api/legal/privacy-policy');
        $termsResponse = $this->getJson('/api/legal/terms-of-service');

        $privacyContent = $privacyResponse->json('data.html');
        $termsContent = $termsResponse->json('data.html');

        $this->assertGreaterThan(1000, strlen($privacyContent), 'プライバシーポリシーの内容が短すぎます');
        $this->assertGreaterThan(1000, strlen($termsContent), '利用規約の内容が短すぎます');
    }

    /**
     * 最終更新日が含まれることを確認
     */
    public function test_バージョン情報が含まれる(): void
    {
        $response = $this->getJson('/api/legal/privacy-policy');

        $version = $response->json('data.version');
        $this->assertNotEmpty($version);
    }
}
