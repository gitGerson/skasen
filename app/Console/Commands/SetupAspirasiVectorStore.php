<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use OpenAI\Laravel\Facades\OpenAI;

class SetupAspirasiVectorStore extends Command
{
    protected $signature = 'aspirasi:setup-vectorstore {path=storage/app/skasen_aspirasi_rag.jsonl}';
    protected $description = 'Upload JSONL and create/attach OpenAI Vector Store for file_search';

    public function handle(): int
    {
        $path = $this->argument('path');
        if (!file_exists($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        $uploadPath = $path;
        $tempPath = null;
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($extension === 'jsonl') {
            $this->info('Converting JSONL to JSON for upload...');
            try {
                $tempPath = $this->convertJsonlToJson($path);
            } catch (\RuntimeException $exception) {
                $this->error($exception->getMessage());
                return self::FAILURE;
            }
            $uploadPath = $tempPath;
        }

        $handle = fopen($uploadPath, 'r');
        if ($handle === false) {
            $this->error("Unable to read file: {$uploadPath}");
            if ($tempPath !== null && file_exists($tempPath)) {
                @unlink($tempPath);
            }
            return self::FAILURE;
        }

        try {
            // 1) Upload file
            $file = OpenAI::files()->upload([
                'purpose' => 'assistants', // umum dipakai untuk fitur retrieval/file_search
                'file' => $handle,
            ]);
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
            if ($tempPath !== null && file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }

        // 2) Create vector store (or create empty then attach)
        $vs = OpenAI::vectorStores()->create([
            'name' => 'SKASEN Aspirasi',
        ]);

        // 3) Attach file to vector store
        OpenAI::vectorStores()->files()->create(
            vectorStoreId: $vs->id,
            parameters: ['file_id' => $file->id]
        );

        $this->info("Vector store created: {$vs->id}");
        $this->info("Put this into .env => SKASEN_VECTOR_STORE_ID={$vs->id}");

        return self::SUCCESS;
    }

    private function convertJsonlToJson(string $path): string
    {
        $input = fopen($path, 'r');
        if ($input === false) {
            throw new \RuntimeException("Unable to read file: {$path}");
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'skasen_rag_');
        if ($tempPath === false) {
            fclose($input);
            throw new \RuntimeException('Unable to create temporary file.');
        }

        $jsonPath = $tempPath . '.json';
        if (!rename($tempPath, $jsonPath)) {
            fclose($input);
            @unlink($tempPath);
            throw new \RuntimeException('Unable to prepare temporary JSON file.');
        }

        $output = fopen($jsonPath, 'w');
        if ($output === false) {
            fclose($input);
            @unlink($jsonPath);
            throw new \RuntimeException('Unable to write temporary JSON file.');
        }

        fwrite($output, "[\n");
        $first = true;

        while (($line = fgets($input)) !== false) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }
            if (!$first) {
                fwrite($output, ",\n");
            }
            fwrite($output, $trimmed);
            $first = false;
        }

        fwrite($output, "\n]\n");

        fclose($input);
        fclose($output);

        return $jsonPath;
    }
}
