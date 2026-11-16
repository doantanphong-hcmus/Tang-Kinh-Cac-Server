<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Media;

use App\Actions\Media\CreateSignedMediaUrl;
use App\BookFileType;
use App\Models\BookFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

final class CreateSignedMediaUrlTest extends TestCase
{
    public function test_creates_expiring_url_with_only_the_opaque_file_identifier(): void
    {
        $file = BookFile::factory()->make([
            'id' => 42,
            'storage_disk' => 'media',
            'storage_path' => 'private/secret-title.epub',
        ]);
        $this->travelTo('2026-11-15 12:00:00');

        $url = app(CreateSignedMediaUrl::class)->handle($file, now()->addMinutes(5));
        $request = Request::create($url);

        $this->assertTrue(URL::hasValidSignature($request));
        $this->assertStringContainsString('/api/v1/media/42/download', $url);
        $this->assertStringNotContainsString('media', parse_url($url, PHP_URL_QUERY) ?: '');
        $this->assertStringNotContainsString('secret-title.epub', $url);
    }

    public function test_modified_file_identifier_invalidates_signature(): void
    {
        $file = BookFile::factory()->make(['id' => 42]);

        $url = app(CreateSignedMediaUrl::class)->handle($file, now()->addMinutes(5));
        $modifiedRequest = Request::create(str_replace('/42/download', '/43/download', $url));

        $this->assertFalse(URL::hasValidSignature($modifiedRequest));
    }

    public function test_creates_signed_audio_stream_url(): void
    {
        $track = BookFile::factory()->make([
            'id' => 21,
            'type' => BookFileType::Audio,
            'storage_path' => 'private/secret-track.mp3',
        ]);

        $url = app(CreateSignedMediaUrl::class)->handle($track, now()->addMinutes(5));
        $request = Request::create($url);

        $this->assertTrue(URL::hasValidSignature($request));
        $this->assertStringContainsString('/api/v1/audio-tracks/21/stream', $url);
        $this->assertStringNotContainsString('secret-track.mp3', $url);
    }
}
