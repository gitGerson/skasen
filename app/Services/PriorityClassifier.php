<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PriorityClassifier
{
    private const PRIORITAS_ENUM = ['Tinggi', 'Sedang', 'Rendah'];

    public function classify(string $kategori, string $text): array
    {
        return $this->classifyWithGroq($kategori, $text);
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function classifyWithGroq(string $kategori, string $text): array
    {
        $apiKey = (string) config('services.groq.api_key', '');
        if ($apiKey === '') {
            throw new RuntimeException('GROQ API key belum dikonfigurasi.');
        }

        $baseUrl = rtrim((string) config('services.groq.base_url', 'https://api.groq.com/openai/v1'), '/');
        $model = (string) config('services.groq.model_priority', 'meta-llama/llama-4-scout-17b-16e-instruct');

        $payload = [
            'model' => $model,
            'temperature' => 0,
            'top_p' => 1,
            'max_completion_tokens' => 500,
            'stream' => false,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->buildInstructions(),
                ],
                [
                    'role' => 'user',
                    'content' => "Kategori: {$kategori}\nIsi: {$text}",
                ],
            ],
        ];

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('openai.request_timeout', 30))
            ->post("{$baseUrl}/chat/completions", $payload)
            ->throw();

        $json = (string) data_get($response->json(), 'choices.0.message.content', '');
        if ($json === '') {
            throw new RuntimeException('Model output is empty.');
        }

        $data = json_decode($json, true);

        if (!is_array($data)) {
            throw new RuntimeException('Model output is not valid JSON.');
        }

        $this->assertValidOutput($data);

        return $data;
    }

    private function buildInstructions(): string
    {
        return implode("\n", [
            'Kamu adalah classifier prioritas aspirasi sekolah.',
            'Gunakan kategori dan isi aspirasi untuk menentukan prioritas.',
            'Definisi:',
            "- Tinggi: keamanan/ancaman/kekerasan, narkoba, pemerasan, kerusakan berat, insiden mendesak, isu hukum.",
            "- Sedang: masalah operasional penting, fasilitas bermasalah sedang, kebijakan, keluhan berulang.",
            "- Rendah: saran umum, permintaan informasi, perbaikan kecil/kenyamanan.",
            'Balas HANYA JSON object valid dengan tepat 3 key berikut: prioritas, confidence, alasan_singkat.',
            'prioritas harus salah satu dari: Tinggi, Sedang, Rendah.',
            'confidence harus angka 0 sampai 1.',
            'alasan_singkat harus ringkas 1-2 kalimat dalam Bahasa Indonesia.',
            'Jangan sertakan markdown, penjelasan tambahan, atau key lain.',
        ]);
    }

    private function assertValidOutput(array $data): void
    {
        $expectedKeys = ['prioritas', 'confidence', 'alasan_singkat'];
        $keys = array_keys($data);

        foreach ($expectedKeys as $key) {
            if (!array_key_exists($key, $data)) {
                throw new RuntimeException("Model output missing key: {$key}");
            }
        }

        if (array_diff($keys, $expectedKeys) !== []) {
            throw new RuntimeException('Model output contains unexpected keys.');
        }

        if (!is_string($data['prioritas']) || !in_array($data['prioritas'], self::PRIORITAS_ENUM, true)) {
            throw new RuntimeException('Model output has invalid prioritas value.');
        }

        if (!is_numeric($data['confidence'])) {
            throw new RuntimeException('Model output has invalid confidence value.');
        }

        $confidence = (float) $data['confidence'];
        if ($confidence < 0 || $confidence > 1) {
            throw new RuntimeException('Model output confidence out of range.');
        }

        if (!is_string($data['alasan_singkat']) || trim($data['alasan_singkat']) === '') {
            throw new RuntimeException('Model output has invalid alasan_singkat value.');
        }
    }
}
