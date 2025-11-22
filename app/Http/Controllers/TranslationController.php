<?php

namespace App\Http\Controllers;

use App\Http\Requests\TranslateOcrRequest;
use App\Http\Requests\TranslateSpeechRequest;
use App\Http\Requests\TranslateTextRequest;
use App\Http\Resources\TranslationResource;
use App\Models\Translation;
use App\Services\Naver\ClovaOcrService;
use App\Services\Naver\ClovaSpeechService;
use App\Services\Naver\PapagoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TranslationController extends Controller
{
    public function __construct(
        protected PapagoService $papagoService,
        protected ClovaOcrService $ocrService,
        protected ClovaSpeechService $speechService
    ) {}

    /**
     * Translate text using Papago
     *
     * @OA\Post(
     *     path="/api/translations/text",
     *     summary="Translate text using NAVER Papago",
     *     tags={"Translations"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"text", "target_language"},
     *             @OA\Property(property="text", type="string", example="안녕하세요", description="Text to translate (max 5000 chars)"),
     *             @OA\Property(property="source_language", type="string", example="ko", description="Source language code (optional: auto-detect if omitted)", nullable=true),
     *             @OA\Property(property="target_language", type="string", example="en", description="Target language code")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Translation created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Translation")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function translateText(TranslateTextRequest $request)
    {
        $validated = $request->validated();

        // Call Papago service to translate text (null source_language = auto-detect)
        $result = $this->papagoService->translate(
            $validated['text'],
            $validated['target_language'],
            $validated['source_language'] ?? null
        );

        // Save translation record
        $translation = Translation::create([
            'user_id' => auth()->id(),
            'source_type' => 'text',
            'source_text' => $validated['text'],
            'source_language' => $validated['source_language'] ?? null,
            'translated_text' => $result['translatedText'],
            'target_language' => $validated['target_language'],
        ]);

        return TranslationResource::make($translation)->response()->setStatusCode(201);
    }

    /**
     * Translate image using Papago Image Translation API - File Upload
     *
     * @OA\Post(
     *     path="/api/translations/image",
     *     summary="Translate uploaded image using NAVER Papago (File Upload)",
     *     description="Upload an image file, save to storage, and translate using NAVER Papago Image Translation API.",
     *     tags={"Translations"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"image", "target_language"},
     *                 @OA\Property(property="image", type="string", format="binary", description="Image file to upload"),
     *                 @OA\Property(property="source_language", type="string", example="ko", description="Source language (optional: auto-detect)", nullable=true),
     *                 @OA\Property(property="target_language", type="string", example="en", description="Target language code")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Image translated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Translation")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function translateImage(TranslateOcrRequest $request)
    {
        $validated = $request->validated();

        if (!$request->hasFile('image')) {
            return response()->json([
                'message' => 'Image file is required. Use /api/translations/image-url for URL-based translation.',
            ], 422);
        }

        // Handle file upload: save to storage
        $file = $request->file('image');
        $extension = $file->extension();
        $filename = 'translations/' . auth()->id() . '/' . uniqid('img_') . '.' . $extension;
        
        // Save to storage
        Storage::put($filename, file_get_contents($file->getRealPath()));
        
        // Get filesystem path to send to Papago API
        $imagePath = Storage::path($filename);

        // Translate image text directly using Papago Image Translation API
        $result = $this->papagoService->translateImage(
            $imagePath,
            $validated['target_language'],
            $validated['source_language'] ?? null
        );

        // Save translation record with file path and detected text
        $translation = Translation::create([
            'user_id' => auth()->id(),
            'source_type' => 'image',
            'source_text' => $result['detectedText'],
            'source_language' => $result['sourceLang'],
            'translated_text' => $result['translatedText'],
            'target_language' => $validated['target_language'],
            'file_path' => $filename,
            'blocks' => $result['blocks'] ?? null,
        ]);

        return TranslationResource::make($translation)->response()->setStatusCode(201);
    }

    /**
     * Translate image using Papago Image Translation API - Image URL
     *
     * @OA\Post(
     *     path="/api/translations/image-url",
     *     summary="Translate image from URL using NAVER Papago (Image URL)",
     *     description="Provide an image URL to translate directly using NAVER Papago Image Translation API.",
     *     tags={"Translations"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"image", "target_language"},
     *             @OA\Property(property="image", type="string", format="uri", example="https://example.com/images/menu.jpg", description="Image URL to translate"),
     *             @OA\Property(property="source_language", type="string", example="ko", description="Source language (optional: auto-detect)", nullable=true),
     *             @OA\Property(property="target_language", type="string", example="en", description="Target language code")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Image translated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Translation")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function translateImageUrl(TranslateOcrRequest $request)
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            return response()->json([
                'message' => 'Please use /api/translations/image for file uploads.',
            ], 422);
        }

        // Handle URL: download image and pass to NAVER API
        $imageUrl = $validated['image'];
        
        // Download image from URL
        $imageContents = @file_get_contents($imageUrl);
        if ($imageContents === false) {
            return response()->json([
                'message' => 'Failed to download image from URL. Please ensure the URL is publicly accessible.',
            ], 422);
        }
        
        // Create temporary file for NAVER API
        $tempPath = tempnam(sys_get_temp_dir(), 'papago_');
        file_put_contents($tempPath, $imageContents);
        
        try {
            // Translate image text directly using Papago Image Translation API
            $result = $this->papagoService->translateImage(
                $tempPath,
                $validated['target_language'],
                $validated['source_language'] ?? null
            );

            // Save translation record without file path
            $translation = Translation::create([
                'user_id' => auth()->id(),
                'source_type' => 'image',
                'source_text' => $result['detectedText'],
                'source_language' => $result['sourceLang'],
                'translated_text' => $result['translatedText'],
                'target_language' => $validated['target_language'],
                'file_path' => null,
            ]);

            return TranslationResource::make($translation)->response()->setStatusCode(201);
        } finally {
            // Clean up temporary file
            @unlink($tempPath);
        }
    }

    /**
     * Extract text from image via OCR and translate - File Upload
     *
     * @OA\Post(
     *     path="/api/translations/ocr",
     *     summary="OCR and translate uploaded image using NAVER Clova OCR + Papago (File Upload)",
     *     description="Upload an image file, save to storage, extract text via OCR, and translate.",
     *     tags={"Translations"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"image", "target_language"},
     *                 @OA\Property(property="image", type="string", format="binary", description="Image file to upload"),
     *                 @OA\Property(property="source_language", type="string", example="ko", description="Source language (optional: auto-detect)", nullable=true),
     *                 @OA\Property(property="target_language", type="string", example="en", description="Target language code")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="OCR and translation completed",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Translation")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function translateOcr(TranslateOcrRequest $request)
    {
        $validated = $request->validated();

        if (!$request->hasFile('image')) {
            return response()->json([
                'message' => 'Image file is required. Use /api/translations/ocr-url for URL-based OCR.',
            ], 422);
        }

        // Handle file upload: save to storage
        $file = $request->file('image');
        $extension = $file->extension();
        $filename = 'translations/' . auth()->id() . '/' . uniqid('img_') . '.' . $extension;
        
        // Save to storage
        Storage::put($filename, file_get_contents($file->getRealPath()));
        
        // Get filesystem path to send to OCR API
        $imagePath = Storage::path($filename);

        // Extract text from image using Clova OCR
        $ocrResult = $this->ocrService->extractText($imagePath);
        $extractedText = $ocrResult['text'];

        // Translate extracted text using Papago
        $result = $this->papagoService->translate(
            $extractedText,
            $validated['target_language'],
            $validated['source_language'] ?? null
        );

        // Save translation record with file path
        $translation = Translation::create([
            'user_id' => auth()->id(),
            'source_type' => 'image',
            'source_text' => $extractedText,
            'source_language' => $result['sourceLang'],
            'translated_text' => $result['translatedText'],
            'target_language' => $validated['target_language'],
            'file_path' => $filename,
        ]);

        return TranslationResource::make($translation)->response()->setStatusCode(201);
    }

    /**
     * Extract text from image via OCR and translate - Image URL
     *
     * @OA\Post(
     *     path="/api/translations/ocr-url",
     *     summary="OCR and translate image from URL using NAVER Clova OCR + Papago (Image URL)",
     *     description="Provide an image URL to extract text via OCR and translate.",
     *     tags={"Translations"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"image", "target_language"},
     *             @OA\Property(property="image", type="string", format="uri", example="https://example.com/images/sign.jpg", description="Image URL to process"),
     *             @OA\Property(property="source_language", type="string", example="ko", description="Source language (optional: auto-detect)", nullable=true),
     *             @OA\Property(property="target_language", type="string", example="en", description="Target language code")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="OCR and translation completed",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Translation")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function translateOcrUrl(TranslateOcrRequest $request)
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            return response()->json([
                'message' => 'Please use /api/translations/ocr for file uploads.',
            ], 422);
        }

        // Handle URL: download image and pass to NAVER API
        $imageUrl = $validated['image'];
        
        // Download image from URL
        $imageContents = @file_get_contents($imageUrl);
        if ($imageContents === false) {
            return response()->json([
                'message' => 'Failed to download image from URL. Please ensure the URL is publicly accessible.',
            ], 422);
        }
        
        // Create temporary file for NAVER API
        $tempPath = tempnam(sys_get_temp_dir(), 'ocr_');
        file_put_contents($tempPath, $imageContents);
        
        try {
            // Extract text from image using Clova OCR
            $ocrResult = $this->ocrService->extractText($tempPath);
            $extractedText = $ocrResult['text'];

            // Translate extracted text using Papago
            $result = $this->papagoService->translate(
                $extractedText,
                $validated['target_language'],
                $validated['source_language'] ?? null
            );

            // Save translation record without file path
            $translation = Translation::create([
                'user_id' => auth()->id(),
                'source_type' => 'image',
                'source_text' => $extractedText,
                'source_language' => $result['sourceLang'],
                'translated_text' => $result['translatedText'],
                'target_language' => $validated['target_language'],
                'file_path' => null,
            ]);

            return TranslationResource::make($translation)->response()->setStatusCode(201);
        } finally {
            // Clean up temporary file
            @unlink($tempPath);
        }
    }

    /**
     * Transcribe speech to text and translate
     *
     * @OA\Post(
     *     path="/api/translations/speech",
     *     summary="Transcribe audio and translate using NAVER Clova Speech + Papago",
     *     tags={"Translations"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"audio", "target_language"},
     *                 @OA\Property(property="audio", type="string", format="binary", description="Audio file (mp3, wav, m4a, max 20MB)"),
     *                 @OA\Property(property="source_language", type="string", example="ko", description="Source language (optional: auto-detect if omitted)", nullable=true),
     *                 @OA\Property(property="target_language", type="string", example="en")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Speech-to-text and translation completed",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Translation")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function translateSpeech(TranslateSpeechRequest $request)
    {
        $validated = $request->validated();

        // Store uploaded audio file on public disk
        $filePath = $request->file('audio')->store('translations/' . auth()->id(), config('filesystems.public_disk'));

        // Transcribe audio to text using Clova Speech
        $sttResult = $this->speechService->speechToText($request->file('audio'));
        $transcribedText = $sttResult['text'];

        // Translate transcribed text using Papago (null source_language = auto-detect)
        $result = $this->papagoService->translate(
            $transcribedText,
            $validated['target_language'],
            $validated['source_language'] ?? null
        );

        // Save translation record with file path
        $translation = Translation::create([
            'user_id' => auth()->id(),
            'source_type' => 'speech',
            'source_text' => $transcribedText,
            'source_language' => $result['sourceLang'],
            'translated_text' => $result['translatedText'],
            'target_language' => $validated['target_language'],
            'file_path' => $filePath,
        ]);

        return TranslationResource::make($translation)->response()->setStatusCode(201);
    }

    /**
     * List user's translations with optional filters
     *
     * @OA\Get(
     *     path="/api/translations",
     *     summary="List user's translation history",
     *     tags={"Translations"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="source_type",
     *         in="query",
     *         description="Filter by source type",
     *         @OA\Schema(type="string", enum={"text", "image", "speech"})
     *     ),
     *     @OA\Parameter(
     *         name="source_language",
     *         in="query",
     *         description="Filter by source language",
     *         @OA\Schema(type="string", example="ko")
     *     ),
     *     @OA\Parameter(
     *         name="target_language",
     *         in="query",
     *         description="Filter by target language",
     *         @OA\Schema(type="string", example="en")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Translation list",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Translation")),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
     */
    public function index(Request $request)
    {
        $query = auth()->user()->translations()->latest();

        // Filter by source type
        if ($request->has('source_type')) {
            $query->where('source_type', $request->source_type);
        }

        // Filter by language pair
        if ($request->has('source_language') && $request->has('target_language')) {
            $query->where('source_language', $request->source_language)
                  ->where('target_language', $request->target_language);
        }

        $translations = $query->paginate(15);

        return TranslationResource::collection($translations);
    }

    /**
     * View a single translation
     *
     * @OA\Get(
     *     path="/api/translations/{translation}",
     *     summary="View translation details",
     *     tags={"Translations"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="translation",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Translation details",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Translation")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function show(Translation $translation)
    {
        $this->authorize('view', $translation);

        return TranslationResource::make($translation);
    }

    /**
     * Delete a translation and its file (if any)
     *
     * @OA\Delete(
     *     path="/api/translations/{translation}",
     *     summary="Delete a translation",
     *     tags={"Translations"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="translation",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Translation deleted successfully"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(Translation $translation)
    {
        $this->authorize('delete', $translation);

        // Delete file if exists
        if ($translation->file_path) {
            Storage::delete($translation->file_path);
        }

        $translation->delete();

        return response()->noContent();
    }
}
