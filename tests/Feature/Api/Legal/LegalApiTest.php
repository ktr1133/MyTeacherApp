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
                    'content',
                    'version',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $data = $response->json('data');
        $this->assertEquals('privacy-policy', $data['type']);
        $this->assertNotEmpty($data['content']);
        $this->assertStringContainsString('プライバシーポリシー', $data['content']);
        $this->assertStringContainsString('個人情報', $data['content']);
        
        // HTMLタグが除去されていることを確認
        $this->assertStringNotContainsString('<div', $data['content']);
        $this->assertStringNotContainsString('<p>', $data['content']);
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
                    'content',
                    'version',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $data = $response->json('data');
        $this->assertEquals('terms-of-service', $data['type']);
        $this->assertNotEmpty($data['content']);
        $this->assertStringContainsString('利用規約', $data['content']);
        $this->assertStringContainsString('サービス', $data['content']);
        
        // HTMLタグが除去されていることを確認
        $this->assertStringNotContainsString('<div', $data['content']);
        $this->assertStringNotContainsString('<p>', $data['content']);
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

        $privacyContent = $privacyResponse->json('data.content');
        $termsContent = $termsResponse->json('data.content');

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
