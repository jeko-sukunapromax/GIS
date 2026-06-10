<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\MapLayerType;
use App\Models\MapUpload;
use App\Services\ActivityLogger;
use App\Services\FeatureGeoJsonImporter;
use App\Services\GisFileConverter;
use App\Traits\ParsesBoundaryFiles;
use App\Traits\RefreshesPostgisGeometry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    use ParsesBoundaryFiles;
    use RefreshesPostgisGeometry;

    public function index()
    {
        $uploads = MapUpload::orderBy('created_at', 'desc')->get();
        $barangays = Barangay::query()
            ->where('is_municipal_boundary', false)
            ->orderBy('name')
            ->get();
        $layerTypes = MapLayerType::query()
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return view('admin.uploads.index', compact('uploads', 'barangays', 'layerTypes'));
    }

    public function preview(Request $request, FeatureGeoJsonImporter $featureImporter, GisFileConverter $converter)
    {
        $request->validate([
            'upload_files' => 'required|array',
            'upload_files.*' => 'file|max:51200|extensions:geojson,json,kml,zip',
        ]);
        $context = $this->uploadContext($request);

        $token = (string) Str::uuid();
        $previewDir = storage_path("app/upload-previews/{$token}");
        File::ensureDirectoryExists($previewDir);

        $files = [];
        $summaries = [];

        try {
            foreach ($request->file('upload_files') as $index => $file) {
                $fileName = $file->getClientOriginalName();
                $extension = strtolower($file->getClientOriginalExtension());
                $storedName = $index.'-'.Str::slug(pathinfo($fileName, PATHINFO_FILENAME)).'.'.$extension;
                $storedPath = "{$previewDir}/{$storedName}";

                $file->move($previewDir, $storedName);

                $conversion = $this->storeConvertedGeoJson($storedPath, $extension, $fileName, $previewDir, $index, $converter);
                $preview = $this->previewFile($conversion['path'], $fileName, $context, $featureImporter);
                $summary = $this->previewSummary(
                    $fileName,
                    $extension,
                    filesize($storedPath) ?: 0,
                    $preview,
                    $conversion,
                    route('admin.uploads.preview.converted', ['preview_token' => $token, 'file' => $index]),
                );

                $files[] = [
                    'path' => $storedPath,
                    'converted_path' => $conversion['path'],
                    'converted_name' => $conversion['download_name'],
                    'name' => $fileName,
                    'extension' => $extension,
                    'type' => $this->fileType($extension, $context['mode']),
                    'size' => $summary['file_size'],
                    'source_format' => $conversion['source_format'],
                    'converted' => $conversion['converted'],
                ];
                $summaries[] = $summary;
            }
        } catch (\Exception $e) {
            $this->cleanupPreviewToken($token);

            return back()->with('error', 'Unable to preview upload: '.$e->getMessage());
        }

        $request->session()->put("upload_previews.{$token}", [
            'files' => $files,
            'context' => $context,
            'created_at' => now()->toISOString(),
        ]);

        app(ActivityLogger::class)->log('upload.previewed', 'Previewed '.count($files).' upload file(s).', null, [
            'files' => collect($files)->pluck('name')->values()->all(),
            'mode' => $context['mode'],
            'token' => $token,
        ], $request);

        return back()->with('upload_preview', [
            'token' => $token,
            'files' => $summaries,
        ]);
    }

    public function store(Request $request, FeatureGeoJsonImporter $featureImporter, GisFileConverter $converter)
    {
        if ($request->filled('preview_token')) {
            return $this->storePreviewedUpload($request, $featureImporter);
        }

        $request->validate([
            'upload_files' => 'required|array',
            'upload_files.*' => 'file|max:51200|extensions:geojson,json,kml,zip',
        ]);
        $context = $this->uploadContext($request);

        $files = $request->file('upload_files');
        $successMessages = [];
        $errorMessages = [];
        $postgisRefreshNeeded = false;
        
        foreach ($files as $file) {
            $fileName = $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension());
            
            $type = $this->fileType($extension, $context['mode']);
            $sizeFormatted = $this->formatFileSize((int) $file->getSize());

            try {
                $status = 'Processed';
                $conversionDir = storage_path('app/upload-conversions/'.(string) Str::uuid());
                File::ensureDirectoryExists($conversionDir);

                try {
                    $conversion = $this->storeConvertedGeoJson($file->getRealPath(), $extension, $fileName, $conversionDir, 0, $converter);
                    $result = $this->processConvertedFile($conversion['path'], $fileName, $context, $featureImporter);
                } finally {
                    File::deleteDirectory($conversionDir);
                }

                $postgisRefreshNeeded = true;

                $upload = MapUpload::create([
                    'file_name' => $fileName,
                    'file_type' => $type,
                    'file_size' => $sizeFormatted,
                    'uploaded_by' => $this->uploadedBy(),
                    'status' => $status
                ]);

                app(ActivityLogger::class)->log('upload.processed', "Processed upload {$fileName}.", $upload, [
                    'file_type' => $type,
                    'file_size' => $sizeFormatted,
                    'mode' => $context['mode'],
                    'matched' => $result['matched'],
                    'created' => count($result['unmatched']),
                    'source_format' => $conversion['source_format'] ?? null,
                    'converted' => $conversion['converted'] ?? false,
                ], $request);

                $successMessages[] = $this->formatResultMessage($fileName, $result);

            } catch (\Exception $e) {
                Log::error('Upload error for ' . $fileName . ': ' . $e->getMessage());
                
                $upload = MapUpload::create([
                    'file_name' => $fileName,
                    'file_type' => $type,
                    'file_size' => $sizeFormatted,
                    'uploaded_by' => $this->uploadedBy(),
                    'status' => 'Failed'
                ]);

                app(ActivityLogger::class)->log('upload.failed', "Upload failed for {$fileName}.", $upload, [
                    'file_type' => $type,
                    'file_size' => $sizeFormatted,
                    'error' => $e->getMessage(),
                ], $request);
                
                $errorMessages[] = "{$fileName}: ".$e->getMessage();
            }
        }

        if ($postgisRefreshNeeded) {
            $this->refreshPostgisGeometry('upload.processed');
        }

        $response = back();
        if (count($successMessages) > 0) {
            $response = $response->with('success', implode("\n", $successMessages));
        }
        if (count($errorMessages) > 0) {
            $response = $response->with('error', implode("\n", $errorMessages));
        }

        return $response;
    }

    public function cancelPreview(Request $request)
    {
        $validated = $request->validate([
            'preview_token' => 'required|string',
        ]);

        $token = $validated['preview_token'];

        $request->session()->forget("upload_previews.{$token}");
        $this->cleanupPreviewToken($token);

        app(ActivityLogger::class)->log('upload.preview_canceled', 'Canceled upload preview before saving.', null, [
            'token' => $token,
        ], $request);

        return back()->with('success', 'Upload preview canceled. No changes were saved.');
    }

    public function downloadConverted(Request $request)
    {
        $validated = $request->validate([
            'preview_token' => 'required|string',
            'file' => 'required|integer|min:0',
        ]);

        $preview = $request->session()->get("upload_previews.{$validated['preview_token']}");
        $file = $preview['files'][$validated['file']] ?? null;

        if (! $file || empty($file['converted_path']) || ! File::exists($file['converted_path'])) {
            abort(404, 'Converted GeoJSON preview is no longer available.');
        }

        return response()->download(
            $file['converted_path'],
            $file['converted_name'] ?? Str::slug(pathinfo($file['name'], PATHINFO_FILENAME)).'-converted.geojson',
            ['Content-Type' => 'application/geo+json']
        );
    }

    private function storePreviewedUpload(Request $request, FeatureGeoJsonImporter $featureImporter)
    {
        $validated = $request->validate([
            'preview_token' => 'required|string',
            'boundary_decisions' => 'nullable|array',
            'boundary_decisions.*' => 'nullable|array',
            'boundary_decisions.*.*.action' => 'nullable|in:default,match,create,skip',
            'boundary_decisions.*.*.barangay_id' => 'nullable|integer|exists:barangays,id',
        ]);

        $token = $validated['preview_token'];
        $preview = $request->session()->get("upload_previews.{$token}");

        if (! $preview || empty($preview['files'])) {
            return back()->with('error', 'Upload preview expired. Please select the file again.');
        }

        $context = $preview['context'] ?? ['mode' => 'boundaries', 'default_barangay_id' => null, 'default_feature_type' => null];

        $successMessages = [];
        $errorMessages = [];
        $postgisRefreshNeeded = false;

        foreach ($preview['files'] as $fileIndex => $file) {
            $fileName = $file['name'];
            $type = $file['type'];
            $sizeFormatted = $file['size'];
            $boundaryDecisions = $context['mode'] === 'boundaries'
                ? (array) ($validated['boundary_decisions'][$fileIndex] ?? [])
                : [];

            try {
                $result = $this->processConvertedFile($file['converted_path'] ?? $file['path'], $fileName, $context, $featureImporter, $boundaryDecisions);
                $postgisRefreshNeeded = true;

                $upload = MapUpload::create([
                    'file_name' => $fileName,
                    'file_type' => $type,
                    'file_size' => $sizeFormatted,
                    'uploaded_by' => $this->uploadedBy(),
                    'status' => 'Processed'
                ]);

                app(ActivityLogger::class)->log('upload.confirmed', "Confirmed and processed upload {$fileName}.", $upload, [
                    'file_type' => $type,
                    'file_size' => $sizeFormatted,
                    'mode' => $context['mode'],
                    'matched' => $result['matched'],
                    'created' => count($result['unmatched']),
                    'source_format' => $file['source_format'] ?? null,
                    'converted' => $file['converted'] ?? false,
                ], $request);

                $successMessages[] = $this->formatResultMessage($fileName, $result);
            } catch (\Exception $e) {
                Log::error('Confirmed upload error for '.$fileName.': '.$e->getMessage());

                $upload = MapUpload::create([
                    'file_name' => $fileName,
                    'file_type' => $type,
                    'file_size' => $sizeFormatted,
                    'uploaded_by' => $this->uploadedBy(),
                    'status' => 'Failed'
                ]);

                app(ActivityLogger::class)->log('upload.failed', "Confirmed upload failed for {$fileName}.", $upload, [
                    'file_type' => $type,
                    'file_size' => $sizeFormatted,
                    'error' => $e->getMessage(),
                ], $request);

                $errorMessages[] = "{$fileName}: ".$e->getMessage();
            }
        }

        $request->session()->forget("upload_previews.{$token}");
        $this->cleanupPreviewToken($token);

        if ($postgisRefreshNeeded) {
            $this->refreshPostgisGeometry('upload.confirmed');
        }

        $response = back();
        if (count($successMessages) > 0) {
            $response = $response->with('success', implode("\n", $successMessages));
        }
        if (count($errorMessages) > 0) {
            $response = $response->with('error', implode("\n", $errorMessages));
        }

        return $response;
    }

    public function destroy(Request $request, MapUpload $upload)
    {
        app(ActivityLogger::class)->log('upload.deleted', "Deleted upload history {$upload->file_name}.", $upload, [
            'status' => $upload->status,
            'file_type' => $upload->file_type,
            'file_size' => $upload->file_size,
        ], $request);

        $upload->delete();

        return back()->with('success', 'Upload history record deleted successfully.');
    }

    private function fileType(string $extension, string $mode = 'boundaries'): string
    {
        if ($mode === 'features') {
            return match ($extension) {
                'geojson', 'json' => 'GeoJSON Features',
                'kml' => 'KML Features',
                'zip' => 'Shapefile Features',
                default => 'Unsupported',
            };
        }

        return match ($extension) {
            'geojson', 'json' => 'GeoJSON',
            'kml' => 'KML Boundary',
            'zip' => 'Shapefile ZIP',
            default => 'Unsupported',
        };
    }

    private function formatFileSize(int $size): string
    {
        return $size > 1048576
            ? round($size / 1048576, 1).' MB'
            : round(max($size, 1) / 1024, 1).' KB';
    }

    private function uploadedBy(): string
    {
        return auth()->user()?->name ?? 'Admin';
    }

    private function previewFile(string $geoJsonPath, string $fileName, array $context, FeatureGeoJsonImporter $featureImporter): array
    {
        if ($context['mode'] === 'features') {
            return $featureImporter->preview(
                $this->storedFile($geoJsonPath),
                $context['default_barangay_id'],
                $context['default_feature_type'],
                $fileName,
            );
        }

        return $this->previewBulkGeoJson($this->storedFile($geoJsonPath), $fileName);
    }

    private function processConvertedFile(string $geoJsonPath, string $fileName, array $context, FeatureGeoJsonImporter $featureImporter, array $boundaryDecisions = []): array
    {
        if ($context['mode'] === 'features') {
            return $featureImporter->import(
                $this->storedFile($geoJsonPath),
                $context['default_barangay_id'],
                $context['default_feature_type'],
                $fileName,
            );
        }

        return $this->parseBulkGeoJson($this->storedFile($geoJsonPath), $fileName, $boundaryDecisions);
    }

    private function storeConvertedGeoJson(string $sourcePath, string $extension, string $fileName, string $targetDir, int $index, GisFileConverter $converter): array
    {
        $conversion = $converter->convert($this->storedFile($sourcePath), $extension, $fileName);
        $storedName = $index.'-'.Str::slug(pathinfo($fileName, PATHINFO_FILENAME)).'-converted.geojson';
        $convertedPath = "{$targetDir}/{$storedName}";

        $converter->writeGeoJson($conversion['geojson'], $convertedPath);

        unset($conversion['geojson']);

        return array_merge($conversion, [
            'path' => $convertedPath,
            'stored_name' => $storedName,
        ]);
    }

    private function storedFile(string $path): object
    {
        return new class($path) {
            public function __construct(private string $path) {}

            public function getRealPath(): string
            {
                return $this->path;
            }
        };
    }

    private function previewSummary(
        string $fileName,
        string $extension,
        int $size,
        array $preview,
        array $conversion = [],
        ?string $downloadUrl = null,
    ): array
    {
        $mode = $preview['mode'] ?? 'boundaries';

        return [
            'file_name' => $fileName,
            'file_type' => $this->fileType($extension, $mode),
            'file_size' => $this->formatFileSize($size),
            'mode' => $mode,
            'source_format' => $conversion['source_format'] ?? $this->fileType($extension, $mode),
            'converted' => $conversion['converted'] ?? false,
            'converted_file_name' => $conversion['download_name'] ?? null,
            'converted_download_url' => $downloadUrl,
            'feature_count' => $conversion['feature_count'] ?? count($preview['items'] ?? []),
            'matched' => $preview['matched'],
            'created' => $preview['created'],
            'skipped' => $preview['skipped'],
            'municipal' => $preview['municipal'],
            'items' => collect($preview['items'])
                ->map(fn (array $item) => [
                    'display_name' => $item['display_name'],
                    'action' => $item['action'],
                    'reason' => $item['reason'] ?? null,
                    'geometry_type' => $item['geometry_type'] ?? null,
                    'is_municipal_boundary' => $item['is_municipal_boundary'],
                    'barangay_id' => $item['barangay_id'] ?? null,
                    'barangay_name' => $item['barangay_name'] ?? null,
                    'suggested_barangay_id' => $item['suggested_barangay_id'] ?? null,
                    'suggested_barangay_name' => $item['suggested_barangay_name'] ?? null,
                    'feature_type_name' => $item['feature_type_name'] ?? null,
                    'metadata_count' => $item['metadata_count'] ?? 0,
                    'area' => $item['area'],
                ])
                ->values()
                ->all(),
        ];
    }

    private function cleanupPreviewToken(string $token): void
    {
        $path = storage_path("app/upload-previews/{$token}");

        if (File::isDirectory($path)) {
            File::deleteDirectory($path);
        }
    }

    /**
     * @param  array{mode?: string, matched: int, created?: int, skipped?: int, unmatched: array<int, string>}  $result
     */
    private function formatResultMessage(string $fileName, array $result): string
    {
        if (($result['mode'] ?? 'boundaries') === 'features') {
            $message = "{$fileName}: imported {$result['created']} new map feature(s)";

            if ($result['matched'] > 0) {
                $message .= " and updated {$result['matched']} existing feature(s)";
            }

            if ($result['skipped'] > 0) {
                $message .= ". Skipped {$result['skipped']} invalid feature(s)";
            }

            return $message.'.';
        }

        $message = "{$fileName}: processed {$result['matched']} barangay boundaries.";

        if (count($result['unmatched']) > 0) {
            $message .= ' Created new records: '.Str::limit(implode(', ', array_slice($result['unmatched'], 0, 3)), 120);
        }

        return $message;
    }

    private function uploadContext(Request $request): array
    {
        $validated = $request->validate([
            'import_mode' => 'nullable|in:boundaries,features',
            'feature_barangay_id' => 'nullable|integer|exists:barangays,id',
            'feature_type' => 'nullable|string|exists:map_layer_types,code',
        ]);

        $mode = $validated['import_mode'] ?? 'boundaries';

        return [
            'mode' => $mode,
            'default_barangay_id' => $mode === 'features' ? ($validated['feature_barangay_id'] ?? null) : null,
            'default_feature_type' => $mode === 'features' ? ($validated['feature_type'] ?? null) : null,
        ];
    }
}
