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
     */
    public function translateText(TranslateTextRequest $request)
    {
        $validated = $request->validated();

        // Call Papago service to translate text
        $result = $this->papagoService->translate(
            $validated['text'],
            $validated['target_language'],
            $validated['source_language']
        );

        // Save translation record
        $translation = Translation::create([
            'user_id' => auth()->id(),
            'source_type' => 'text',
            'source_text' => $validated['text'],
            'source_language' => $validated['source_language'],
            'translated_text' => $result['translatedText'],
            'target_language' => $validated['target_language'],
        ]);

        return TranslationResource::make($translation)->response()->setStatusCode(201);
    }

    /**
     * Extract text from image via OCR and translate
     */
    public function translateOcr(TranslateOcrRequest $request)
    {
        $validated = $request->validated();

        // Store uploaded image
        $filePath = $request->file('image')->store('translations/' . auth()->id(), 'public');

        // Extract text from image using Clova OCR
        $ocrResult = $this->ocrService->extractText($request->file('image'));
        $extractedText = $ocrResult['text'];

        // Translate extracted text using Papago
        $result = $this->papagoService->translate(
            $extractedText,
            $validated['target_language'],
            $validated['source_language']
        );

        // Save translation record with file path
        $translation = Translation::create([
            'user_id' => auth()->id(),
            'source_type' => 'image',
            'source_text' => $extractedText,
            'source_language' => $validated['source_language'],
            'translated_text' => $result['translatedText'],
            'target_language' => $validated['target_language'],
            'file_path' => $filePath,
        ]);

        return TranslationResource::make($translation)->response()->setStatusCode(201);
    }

    /**
     * Transcribe speech to text and translate
     */
    public function translateSpeech(TranslateSpeechRequest $request)
    {
        $validated = $request->validated();

        // Store uploaded audio file
        $filePath = $request->file('audio')->store('translations/' . auth()->id(), 'public');

        // Transcribe audio to text using Clova Speech
        $sttResult = $this->speechService->speechToText($request->file('audio'));
        $transcribedText = $sttResult['text'];

        // Translate transcribed text using Papago
        $result = $this->papagoService->translate(
            $transcribedText,
            $validated['target_language'],
            $validated['source_language']
        );

        // Save translation record with file path
        $translation = Translation::create([
            'user_id' => auth()->id(),
            'source_type' => 'speech',
            'source_text' => $transcribedText,
            'source_language' => $validated['source_language'],
            'translated_text' => $result['translatedText'],
            'target_language' => $validated['target_language'],
            'file_path' => $filePath,
        ]);

        return TranslationResource::make($translation)->response()->setStatusCode(201);
    }

    /**
     * List user's translations with optional filters
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
     */
    public function show(Translation $translation)
    {
        $this->authorize('view', $translation);

        return TranslationResource::make($translation);
    }

    /**
     * Delete a translation and its file (if any)
     */
    public function destroy(Translation $translation)
    {
        $this->authorize('delete', $translation);

        // Delete file if exists
        if ($translation->file_path) {
            Storage::disk('public')->delete($translation->file_path);
        }

        $translation->delete();

        return response()->noContent();
    }
}
