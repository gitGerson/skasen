<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AspirasiClassifyTest extends TestCase
{
    public function test_classify_returns_priority_payload(): void
    {
        config([
            'services.groq.api_key' => 'gsk_test_key',
            'services.groq.model_priority' => 'meta-llama/llama-4-scout-17b-16e-instruct',
        ]);

        $payload = [
            'prioritas' => 'Tinggi',
            'confidence' => 0.87,
            'alasan_singkat' => 'Masalah ini bersifat mendesak dan berisiko tinggi.',
        ];

        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->postJson('/api/aspirasi/classify', [
            'kategori' => 'Aduan',
            'text' => 'Ada kerusakan parah di fasilitas sekolah yang membahayakan siswa.',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['prioritas', 'confidence', 'alasan_singkat']);
        $response->assertJson($payload);
    }

    public function test_classify_requires_fields(): void
    {
        $response = $this->postJson('/api/aspirasi/classify', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['kategori', 'text']);
    }

    public function test_classify_accepts_max_text_length(): void
    {
        config(['services.groq.api_key' => 'gsk_test_key']);

        $payload = [
            'prioritas' => 'Sedang',
            'confidence' => 0.6,
            'alasan_singkat' => 'Ini masalah penting namun tidak darurat.',
        ];

        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->postJson('/api/aspirasi/classify', [
            'kategori' => 'Saran',
            'text' => str_repeat('a', 5000),
        ]);

        $response->assertOk();
        $response->assertJson($payload);
    }
}
