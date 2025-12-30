# agents.md - Codex Instructions (Laravel + OpenAI RAG Priority Classifier)

You are working inside a Laravel application that classifies **Prioritas** (`Tinggi|Sedang|Rendah`) based on:
- `kategori` (string)
- `isi aspirasi` (free text)

The app uses OpenAI for inference, and (optionally) OpenAI managed RAG via **Vector Store + file_search** using a JSONL dataset (`skasen_aspirasi_rag.jsonl`).

## Primary Goals
1. Implement a robust API endpoint to classify priority from `(kategori, text)`.
2. Prefer deterministic classification (low variance) with schema-validated JSON output.
3. Support managed RAG (`file_search`) by referencing a configured Vector Store ID.
4. Provide clear error handling, logging, and tests.

## Non-Goals
- Do not build a UI unless explicitly requested.
- Do not add heavy dependencies or unrelated architecture.

---

## Tech Stack
- Laravel (current project)
- `openai-php/laravel` package as the OpenAI client wrapper
- Optional: queue jobs for indexing / background tasks (only if requested)

---

## Key Environment Variables
Add / read from `.env`:
- `OPENAI_API_KEY=...`
- `OPENAI_ORGANIZATION=...` (optional)
- `OPENAI_PROJECT=...` (optional)
- `SKASEN_VECTOR_STORE_ID=vs_...` (required for managed RAG mode)
- `OPENAI_MODEL_PRIORITY=gpt-4o-mini` (default model used for classification)

Never hardcode secrets. Always use `config()` / `env()` via config files.

---

## Data Format
Dataset JSONL lines have:
```json
{
  "id": "skasen-0001",
  "text": "...",
  "metadata": {
    "no": 1,
    "kategori": "Aduan|Keluhan|Saran|...",
    "prioritas": "Tinggi|Sedang|Rendah",
    "source": "Dataset_Aspirasi_SKASEN_Final.csv"
  }
}
```

If implementing ingestion, keep this exact structure.

---

## Classification Contract

### Input
- `kategori`: required string, max 50
- `text`: required string, max 5000

### Output (strict JSON)
Return only:
```json
{
  "prioritas": "Tinggi|Sedang|Rendah",
  "confidence": 0.0,
  "alasan_singkat": "..."
}
```

Constraints:
- `confidence` in [0, 1]
- `alasan_singkat` should be short (1-2 sentences)
- No extra keys

Use OpenAI Structured Outputs / JSON schema when available.

---

## Preferred Architecture

Files:
- `app/Services/PriorityClassifier.php`
- `app/Http/Controllers/AspirasiController.php`
- `routes/api.php`
- `config/openai.php` and/or `config/services.php` updates if needed
- `tests/Feature/AspirasiClassifyTest.php`

### Service Responsibilities
`PriorityClassifier` must:
- Validate required configuration (vector store id if RAG enabled)
- Build a safe prompt (Indonesian) focusing on classification
- Use `temperature = 0` (or as close to deterministic as possible)
- Use `file_search` tool if RAG enabled; otherwise fallback to non-RAG prompt
- Enforce JSON schema output; parse JSON; raise explicit errors on invalid output

### Controller Responsibilities
- Validate request
- Call classifier service
- Return JSON response
- Handle exceptions: return 4xx for validation, 5xx for upstream issues

---

## Prompting Rules
Write prompts in Bahasa Indonesia. Include:
- A short definition of each prioritas class
- Instruction to consider kategori and text
- Instruction to use retrieved examples as reference when file_search is enabled
- Output must match schema exactly (no markdown, no extra text)

Suggested high-level rubric:
- Tinggi: safety/violence/threats, drugs, harassment, extortion, severe damage, urgent incidents, legal issues
- Sedang: important operational issues, moderate facilities problems, policy issues, recurring concerns
- Rendah: suggestions, general info requests, minor convenience improvements

---

## Managed RAG (Vector Store + file_search)
If `SKASEN_VECTOR_STORE_ID` exists:
- Use tools: `[{ type: "file_search", vector_store_ids: [..], max_num_results: 8 }]`
- Keep results small (top 5-10)
- Do not leak raw retrieved data to the end-user unless requested; use it for reasoning

If missing:
- Fall back to no-RAG mode but still classify

---

## Error Handling & Logging
- Log upstream OpenAI errors with context (request id if available), but never log API keys or full user text in production logs
- Return safe error messages to clients
- Add retries only if requested; keep initial implementation simple and reliable

---

## Coding Standards
- PHP 8+
- Strict types if project uses them
- Keep functions small and testable
- Avoid "magic strings": use constants or config where appropriate
- Use Laravel validation rules and form requests when helpful

---

## Tests
Create feature tests for `/api/aspirasi/classify`:
- Valid request returns JSON with correct keys and enum value
- Missing fields return 422
- Long text boundary test

If possible, mock OpenAI client layer (do not call real OpenAI in CI).

If mocking is hard, implement an interface wrapper around the OpenAI calls:
- `App\\Contracts\\PriorityModelClient`
- `App\\Services\\OpenAIPriorityModelClient`

Mock the contract in tests.

---

## Commands & Developer Workflow
When adding commands, prefer Artisan:
- `php artisan make:command ...`

If implementing vector store setup, provide a one-time command that:
- Uploads the JSONL
- Creates vector store
- Attaches file
- Prints the vector store id to set in `.env`

---

## Security & Privacy
- Treat user input as sensitive
- Do not store raw user text unless required
- If stored, ensure minimal retention and access control
- Never include personal data in prompts unless essential

---

## Output Language
- API responses should be Indonesian for `alasan_singkat`.
- API error messages should be Indonesian unless project convention says otherwise.

---

## What to Do When Requirements Are Ambiguous
Make a reasonable default choice:
- Prefer managed RAG if vector store id is configured
- Otherwise run classification without RAG

Do not ask for clarification unless absolutely necessary; implement sensible defaults.

---

## Done Criteria
- Endpoint works locally
- Deterministic JSON outputs
- Clear logs and errors
- Tests pass
- Minimal, clean code with Laravel conventions
