<?php

namespace Tests\Feature;

use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Responses\CreateResponse;
use Tests\TestCase;

class AspirasiClassifyTest extends TestCase
{
    public function test_classify_returns_priority_payload(): void
    {
        config(['openai.skasen_vector_store_id' => 'vs_test']);

        $payload = [
            'prioritas' => 'Tinggi',
            'confidence' => 0.87,
            'alasan_singkat' => 'Masalah ini bersifat mendesak dan berisiko tinggi.',
        ];

        OpenAI::fake([
            CreateResponse::fake([
                'output' => [
                    [
                        'type' => 'message',
                        'id' => 'msg_test',
                        'status' => 'completed',
                        'role' => 'assistant',
                        'content' => [
                            [
                                'type' => 'output_text',
                                'text' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                                'annotations' => [],
                            ],
                        ],
                    ],
                ],
            ]),
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
        $payload = [
            'prioritas' => 'Sedang',
            'confidence' => 0.6,
            'alasan_singkat' => 'Ini masalah penting namun tidak darurat.',
        ];

        OpenAI::fake([
            CreateResponse::fake([
                'output' => [
                    [
                        'type' => 'message',
                        'id' => 'msg_long',
                        'status' => 'completed',
                        'role' => 'assistant',
                        'content' => [
                            [
                                'type' => 'output_text',
                                'text' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                                'annotations' => [],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->postJson('/api/aspirasi/classify', [
            'kategori' => 'Saran',
            'text' => str_repeat('a', 5000),
        ]);

        $response->assertOk();
        $response->assertJson($payload);
    }
}
