<?php

namespace Rz\LaravelAutoTranslator\Dashboard\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Rz\LaravelAutoTranslator\Export\ExportService;
use Rz\LaravelAutoTranslator\Export\ImportService;
use Rz\LaravelAutoTranslator\Models\TranslationKey;
use Rz\LaravelAutoTranslator\Models\TranslationValue;
use Rz\LaravelAutoTranslator\Services\DeadKeyDetector;
use Rz\LaravelAutoTranslator\Services\TranslationService;

class TranslationDashboardController extends Controller
{
    public function __construct(
        protected TranslationService $translationService,
        protected DeadKeyDetector $deadKeyDetector,
        protected ExportService $exportService,
        protected ImportService $importService
    ) {
    }

    /**
     * Main dashboard page.
     */
    public function index(Request $request)
    {
        $stats = $this->translationService->getStats();
        $locales = config('rz-translator.locales', ['en']);
        $deadStats = $this->deadKeyDetector->stats();

        return view('rz-translator::dashboard.index', compact('stats', 'locales', 'deadStats'));
    }

    /**
     * Paginated list of translation keys with filtering.
     */
    public function keys(Request $request)
    {
        $locales = config('rz-translator.locales', ['en']);
        $perPage = config('rz-translator.dashboard.per_page', 50);

        $query = TranslationKey::query()->with('values');

        // Search filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('key', 'like', "%{$search}%")
                  ->orWhere('group', 'like', "%{$search}%");
            });
        }

        // Group filter
        if ($group = $request->input('group')) {
            $query->where('group', $group);
        }

        // Missing translations filter
        if ($request->boolean('missing')) {
            $missingLocale = $request->input('missing_locale', $locales[0] ?? 'en');
            $query->whereHas('values', function ($q) use ($missingLocale) {
                $q->where('locale', $missingLocale)->whereNull('value');
            });
        }

        // Dead keys filter
        if ($request->boolean('dead')) {
            $query->where('is_dead', true);
        } else {
            $query->where('is_dead', false);
        }

        $keys = $query->orderBy('group')->orderBy('key')->paginate($perPage);

        $groups = TranslationKey::select('group')->distinct()->pluck('group');

        return view('rz-translator::dashboard.keys', compact('keys', 'locales', 'groups'));
    }

    /**
     * Translate a single key into all (or specified) locales.
     */
    public function translateKey(Request $request, TranslationKey $key)
    {
        $locale = $request->input('locale');
        $this->translationService->translateMissing($locale);

        return response()->json(['success' => true, 'message' => 'Translation initiated.']);
    }

    /**
     * Update a translation value inline.
     */
    public function updateValue(Request $request, TranslationValue $value)
    {
        $validated = $request->validate([
            'value' => 'required|string|max:65535',
        ]);

        $value->update([
            'value'              => $validated['value'],
            'is_auto_translated' => false,
        ]);

        return response()->json(['success' => true, 'value' => $value->value]);
    }

    /**
     * Delete a translation key and all its values.
     */
    public function destroyKey(TranslationKey $key)
    {
        $key->values()->delete();
        $key->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Delete all dead translation keys.
     */
    public function destroyDeadKeys()
    {
        $deleted = $this->deadKeyDetector->deleteDeadKeys();

        return response()->json([
            'success' => true,
            'deleted' => $deleted,
            'message' => "{$deleted} dead keys removed.",
        ]);
    }

    /**
     * Trigger a project scan via AJAX.
     */
    public function scan()
    {
        $result = $this->translationService->scan();

        return response()->json([
            'success' => true,
            'result'  => $result,
            'message' => "Scan complete: {$result['new']} new keys, {$result['dead']} dead keys.",
        ]);
    }

    /**
     * Translate all missing keys via AJAX.
     */
    public function translateMissing(Request $request)
    {
        $locale = $request->input('locale');
        $result = $this->translationService->translateMissing($locale);

        return response()->json([
            'success' => true,
            'result'  => $result,
            'message' => "{$result['translated']} translations completed.",
        ]);
    }

    /**
     * Export translations.
     */
    public function export(Request $request)
    {
        $format = $request->input('format', 'json');
        $locale = $request->input('locale');

        $file = match ($format) {
            'csv'  => $this->exportService->exportCsv($locale),
            'zip'  => $this->exportService->exportZip(),
            default => $this->exportService->exportJson($locale),
        };

        return response()->download($file)->deleteFileAfterSend(false);
    }

    /**
     * Import translations from uploaded file.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:json,csv,txt',
        ]);

        $uploaded = $request->file('file');
        $ext = strtolower($uploaded->getClientOriginalExtension());
        $path = $uploaded->store('rz-translator/imports', 'local');
        $fullPath = storage_path('app/' . $path);

        try {
            $result = match ($ext) {
                'csv'   => $this->importService->importCsv($fullPath),
                default => $this->importService->importJson($fullPath),
            };

            return response()->json([
                'success' => true,
                'result'  => $result,
                'message' => "{$result['imported']} translations imported.",
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } finally {
            // Clean up temp file
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
    }

    /**
     * Get status data as JSON (for dashboard AJAX refresh).
     */
    public function status()
    {
        return response()->json($this->translationService->getStats());
    }
}
