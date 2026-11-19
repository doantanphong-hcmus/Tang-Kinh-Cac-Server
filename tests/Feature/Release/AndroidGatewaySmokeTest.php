<?php

declare(strict_types=1);

namespace Tests\Feature\Release;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class AndroidGatewaySmokeTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_seeded_release_candidate_supports_all_android_gateways(): void
    {
        Storage::fake('media');
        $this->seed();

        // AuthGateway
        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'reader@example.test',
            'password' => 'password',
            'device_name' => 'Android release smoke test',
        ])->assertOk();
        $token = $login->json('data.token');
        $this->assertIsString($token);
        [$tokenId] = explode('|', $token, 2);
        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'reader@example.test');

        // BookCatalogGateway
        $this->getJson('/api/v1/books?q=TRUYEN%20KIEU')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'truyen-kieu')
            ->assertJsonPath('data.0.has_epub', true)
            ->assertJsonPath('data.0.has_audiobook', true);
        $this->getJson('/api/v1/books/truyen-kieu')
            ->assertOk()
            ->assertJsonPath('data.available_content_types', ['epub', 'audiobook']);
        $this->getJson('/api/v1/genres')
            ->assertOk()
            ->assertJsonFragment(['slug' => 'van-hoc-co-dien']);
        $this->getJson('/api/v1/genres/van-hoc-co-dien/books')
            ->assertOk()
            ->assertJsonFragment(['slug' => 'truyen-kieu']);

        // BookContentGateway
        $epubDescriptor = $this->getJson('/api/v1/books/truyen-kieu/epub')->assertOk();
        $downloadUrl = $epubDescriptor->json('data.download_url');
        $this->assertIsString($downloadUrl);
        $epubDownload = $this->get($downloadUrl)
            ->assertOk()
            ->assertStreamed()
            ->assertHeader('Content-Type', 'application/epub+zip');
        $epubBytes = $epubDownload->streamedContent();
        $this->assertSame($epubDescriptor->json('data.size'), strlen($epubBytes));
        $this->assertSame($epubDescriptor->json('data.checksum_sha256'), hash('sha256', $epubBytes));

        // AudiobookGateway
        $this->getJson('/api/v1/books/truyen-kieu/audiobook')
            ->assertOk()
            ->assertJsonPath('data.available', true)
            ->assertJsonPath('data.track_count', 2);
        $tracks = $this->getJson('/api/v1/books/truyen-kieu/audio-tracks')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.sort_order', 1)
            ->assertJsonPath('data.1.sort_order', 2);
        $streamUrl = $tracks->json('data.1.stream_url');
        $this->assertIsString($streamUrl);
        $this->withHeaders([
            'Accept-Encoding' => 'identity',
            'Range' => 'bytes=44-',
        ])->get($streamUrl)
            ->assertStatus(206)
            ->assertHeader('Accept-Ranges', 'bytes')
            ->assertHeader('Content-Length', '8000')
            ->assertHeader('Content-Range', 'bytes 44-8043/8044')
            ->assertHeader('Content-Type', 'audio/wav');

        $this->postJson('/api/v1/auth/logout')->assertNoContent();
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => (int) $tokenId]);
        Auth::forgetGuards();
        $this->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }
}
