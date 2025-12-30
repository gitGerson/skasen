<?php

namespace App\Http\Controllers;

use App\Services\PriorityClassifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenAI\Exceptions\ErrorException;
use OpenAI\Exceptions\TransporterException;
use RuntimeException;
use Throwable;

class AspirasiController extends Controller
{
    public function classify(Request $request, PriorityClassifier $classifier): JsonResponse
    {
        $validated = $request->validate(
            [
                'kategori' => 'required|string|max:50',
                'text' => 'required|string|max:5000',
            ],
            [
                'kategori.required' => 'Kategori wajib diisi.',
                'kategori.string' => 'Kategori harus berupa teks.',
                'kategori.max' => 'Kategori maksimal 50 karakter.',
                'text.required' => 'Isi aspirasi wajib diisi.',
                'text.string' => 'Isi aspirasi harus berupa teks.',
                'text.max' => 'Isi aspirasi maksimal 5000 karakter.',
            ]
        );

        try {
            $result = $classifier->classify($validated['kategori'], $validated['text']);
        } catch (ErrorException $exception) {
            Log::error('OpenAI priority classification failed.', [
                'error' => $exception->getErrorMessage(),
                'type' => $exception->getErrorType(),
                'code' => $exception->getErrorCode(),
                'status' => $exception->getStatusCode(),
                'request_id' => $exception->response->getHeaderLine('x-request-id'),
                'kategori_length' => strlen($validated['kategori']),
                'text_length' => strlen($validated['text']),
            ]);

            return response()->json(
                ['message' => 'Terjadi kesalahan pada layanan AI. Coba lagi nanti.'],
                502
            );
        } catch (TransporterException $exception) {
            Log::error('OpenAI transporter error during priority classification.', [
                'error' => $exception->getMessage(),
                'kategori_length' => strlen($validated['kategori']),
                'text_length' => strlen($validated['text']),
            ]);

            return response()->json(
                ['message' => 'Terjadi kesalahan pada layanan AI. Coba lagi nanti.'],
                502
            );
        } catch (RuntimeException $exception) {
            Log::warning('Priority classification output invalid.', [
                'error' => $exception->getMessage(),
                'kategori_length' => strlen($validated['kategori']),
                'text_length' => strlen($validated['text']),
            ]);

            return response()->json(
                ['message' => 'Hasil klasifikasi tidak valid.'],
                500
            );
        } catch (Throwable $exception) {
            Log::error('Unexpected error during priority classification.', [
                'error' => $exception->getMessage(),
                'kategori_length' => strlen($validated['kategori']),
                'text_length' => strlen($validated['text']),
            ]);

            return response()->json(
                ['message' => 'Terjadi kesalahan server.'],
                500
            );
        }

        return response()->json($result);
    }
}
