<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;

class PriorityClassifier
{
    private const PRIORITAS_ENUM = ['Tinggi', 'Sedang', 'Rendah'];

    public function classify(string $kategori, string $text): array
    {
        $vectorStoreId = config('openai.skasen_vector_store_id');
        $model = config('openai.model_priority', 'gpt-4o-mini');
        $useRag = !empty($vectorStoreId);

        $schema = [
            'type' => 'object',
            'properties' => [
                'prioritas' => [
                    'type' => 'string',
                    'enum' => self::PRIORITAS_ENUM,
                ],
                'confidence' => [
                    'type' => 'number',
                    'minimum' => 0,
                    'maximum' => 1,
                ],
                'alasan_singkat' => ['type' => 'string'],
            ],
            'required' => ['prioritas', 'confidence', 'alasan_singkat'],
            'additionalProperties' => false,
        ];

        $payload = [
            'model' => $model,
            'instructions' => $this->buildInstructions($useRag),
            'input' => [
                ['role' => 'user', 'content' => "Kategori: {$kategori}\nIsi: {$text}"],
            ],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'priority_classification',
                    'schema' => $schema,
                    'strict' => true,
                ],
            ],
            'temperature' => 0.0,
        ];

        if ($useRag) {
            $payload['tools'] = [
                [
                    'type' => 'file_search',
                    'vector_store_ids' => [$vectorStoreId],
                    'max_num_results' => 8,
                ],
            ];
        }

        $resp = OpenAI::responses()->create($payload);

        // outputText berisi JSON string karena kita paksa format json_schema
        $json = $resp->outputText ?? '';
        if ($json === '') {
            throw new \RuntimeException('Model output is empty.');
        }

        $data = json_decode($json, true);

        if (!is_array($data)) {
            throw new \RuntimeException('Model output is not valid JSON.');
        }

        $this->assertValidOutput($data);

        return $data;
    }

    private function buildInstructions(bool $useRag): string
    {
        $lines = [
            'Kamu adalah classifier prioritas aspirasi sekolah.',
            'Gunakan kategori dan isi aspirasi untuk menentukan prioritas.',
            "Definisi:",
            "- Tinggi: keamanan/ancaman/kekerasan, narkoba, pemerasan, kerusakan berat, insiden mendesak, isu hukum.",
            "- Sedang: masalah operasional penting, fasilitas bermasalah sedang, kebijakan, keluhan berulang.",
            "- Rendah: saran umum, permintaan informasi, perbaikan kecil/kenyamanan.",
        ];

        if ($useRag) {
            $lines[] = 'Gunakan contoh dari file_search sebagai referensi pembanding.';
        }

        $lines[] = 'alasan_singkat harus ringkas (1-2 kalimat) dan berbahasa Indonesia.';
        $lines[] = 'Output HARUS JSON sesuai schema (tanpa markdown atau teks tambahan).';

        return implode("\n", $lines);
    }

    private function assertValidOutput(array $data): void
    {
        $expectedKeys = ['prioritas', 'confidence', 'alasan_singkat'];
        $keys = array_keys($data);

        foreach ($expectedKeys as $key) {
            if (!array_key_exists($key, $data)) {
                throw new \RuntimeException("Model output missing key: {$key}");
            }
        }

        if (array_diff($keys, $expectedKeys) !== []) {
            throw new \RuntimeException('Model output contains unexpected keys.');
        }

        if (!is_string($data['prioritas']) || !in_array($data['prioritas'], self::PRIORITAS_ENUM, true)) {
            throw new \RuntimeException('Model output has invalid prioritas value.');
        }

        if (!is_numeric($data['confidence'])) {
            throw new \RuntimeException('Model output has invalid confidence value.');
        }

        $confidence = (float) $data['confidence'];
        if ($confidence < 0 || $confidence > 1) {
            throw new \RuntimeException('Model output confidence out of range.');
        }

        if (!is_string($data['alasan_singkat']) || trim($data['alasan_singkat']) === '') {
            throw new \RuntimeException('Model output has invalid alasan_singkat value.');
        }
    }
}
