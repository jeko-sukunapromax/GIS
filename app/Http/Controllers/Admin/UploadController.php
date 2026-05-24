<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MapUpload;
use App\Traits\ParsesBoundaryFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    use ParsesBoundaryFiles;

    public function index()
    {
        $uploads = MapUpload::orderBy('created_at', 'desc')->get();

        return view('admin.uploads.index', compact('uploads'));
    }

    public function preview(Request $request)
    {
        $request->validate([
            'upload_files' => 'required|array',
            'upload_files.*' => 'file|max:51200|extensions:geojson,json,zip',
        ]);

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

                $preview = $this->previewFile($storedPath, $extension, $fileName);
                $summary = $this->previewSummary($fileName, $extension, filesize($storedPath) ?: 0, $preview);

                $files[] = [
                    'path' => $storedPath,
                    'name' => $fileName,
                    'extension' => $extension,
                    'type' => $this->fileType($extension),
                    'size' => $summary['file_size'],
                ];
                $summaries[] = $summary;
            }
        } catch (\Exception $e) {
            $this->cleanupPreviewToken($token);

            return back()->with('error', 'Unable to preview upload: '.$e->getMessage());
        }

        $request->session()->put("upload_previews.{$token}", [
            'files' => $files,
            'created_at' => now()->toISOString(),
        ]);

        return back()->with('upload_preview', [
            'token' => $token,
            'files' => $summaries,
        ]);
    }

    public function store(Request $request)
    {
        if ($request->filled('preview_token')) {
            return $this->storePreviewedUpload($request);
        }

        $request->validate([
            'upload_files' => 'required|array',
            'upload_files.*' => 'file|max:51200|extensions:geojson,json,zip',
        ]);

        $files = $request->file('upload_files');
        $successMessages = [];
        $errorMessages = [];
        
        foreach ($files as $file) {
            $fileName = $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension());
            
            $type = $this->fileType($extension);
            $sizeFormatted = $this->formatFileSize((int) $file->getSize());

            try {
                $status = 'Processed';
                
                if ($extension === 'geojson' || $extension === 'json') {
                    $result = $this->parseBulkGeoJson($file, $fileName);
                } elseif ($extension === 'zip') {
                    $result = $this->parseBulkShapefileZip($file, $fileName);
                } else {
                    throw new \Exception('Please upload a GeoJSON file or a ZIP containing Shapefile files (.shp + .dbf).');
                }

                MapUpload::create([
                    'file_name' => $fileName,
                    'file_type' => $type,
                    'file_size' => $sizeFormatted,
                    'uploaded_by' => $this->uploadedBy(),
                    'status' => $status
                ]);

                $successMessages[] = $this->formatResultMessage($fileName, $result);

            } catch (\Exception $e) {
                Log::error('Upload error for ' . $fileName . ': ' . $e->getMessage());
                
                MapUpload::create([
                    'file_name' => $fileName,
                    'file_type' => $type,
                    'file_size' => $sizeFormatted,
                    'uploaded_by' => $this->uploadedBy(),
                    'status' => 'Failed'
                ]);
                
                $errorMessages[] = "{$fileName}: ".$e->getMessage();
            }
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

    private function storePreviewedUpload(Request $request)
    {
        $validated = $request->validate([
            'preview_token' => 'required|string',
        ]);

        $token = $validated['preview_token'];
        $preview = $request->session()->get("upload_previews.{$token}");

        if (! $preview || empty($preview['files'])) {
            return back()->with('error', 'Upload preview expired. Please select the file again.');
        }

        $successMessages = [];
        $errorMessages = [];

        foreach ($preview['files'] as $file) {
            $fileName = $file['name'];
            $extension = $file['extension'];
            $type = $file['type'];
            $sizeFormatted = $file['size'];

            try {
                $result = $this->processStoredFile($file['path'], $extension, $fileName);

                MapUpload::create([
                    'file_name' => $fileName,
                    'file_type' => $type,
                    'file_size' => $sizeFormatted,
                    'uploaded_by' => $this->uploadedBy(),
                    'status' => 'Processed'
                ]);

                $successMessages[] = $this->formatResultMessage($fileName, $result);
            } catch (\Exception $e) {
                Log::error('Confirmed upload error for '.$fileName.': '.$e->getMessage());

                MapUpload::create([
                    'file_name' => $fileName,
                    'file_type' => $type,
                    'file_size' => $sizeFormatted,
                    'uploaded_by' => $this->uploadedBy(),
                    'status' => 'Failed'
                ]);

                $errorMessages[] = "{$fileName}: ".$e->getMessage();
            }
        }

        $request->session()->forget("upload_previews.{$token}");
        $this->cleanupPreviewToken($token);

        $response = back();
        if (count($successMessages) > 0) {
            $response = $response->with('success', implode("\n", $successMessages));
        }
        if (count($errorMessages) > 0) {
            $response = $response->with('error', implode("\n", $errorMessages));
        }

        return $response;
    }

    public function destroy(MapUpload $upload)
    {
        $upload->delete();
        return back()->with('success', 'Upload history record deleted successfully.');
    }

    private function fileType(string $extension): string
    {
        return match ($extension) {
            'geojson', 'json' => 'GeoJSON',
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

    private function previewFile(string $path, string $extension, string $fileName): array
    {
        if ($extension === 'geojson' || $extension === 'json') {
            return $this->previewBulkGeoJson($this->storedFile($path), $fileName);
        }

        if ($extension === 'zip') {
            return $this->previewBulkShapefileZip($this->storedFile($path), $fileName);
        }

        throw new \Exception('Please upload a GeoJSON file or a ZIP containing Shapefile files (.shp + .dbf).');
    }

    private function processStoredFile(string $path, string $extension, string $fileName): array
    {
        if ($extension === 'geojson' || $extension === 'json') {
            return $this->parseBulkGeoJson($this->storedFile($path), $fileName);
        }

        if ($extension === 'zip') {
            return $this->parseBulkShapefileZip($this->storedFile($path), $fileName);
        }

        throw new \Exception('Please upload a GeoJSON file or a ZIP containing Shapefile files (.shp + .dbf).');
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

    private function previewSummary(string $fileName, string $extension, int $size, array $preview): array
    {
        return [
            'file_name' => $fileName,
            'file_type' => $this->fileType($extension),
            'file_size' => $this->formatFileSize($size),
            'matched' => $preview['matched'],
            'created' => $preview['created'],
            'skipped' => $preview['skipped'],
            'municipal' => $preview['municipal'],
            'items' => collect($preview['items'])
                ->map(fn (array $item) => [
                    'display_name' => $item['display_name'],
                    'action' => $item['action'],
                    'is_municipal_boundary' => $item['is_municipal_boundary'],
                    'points' => $item['points'],
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
     * @param  array{matched: int, unmatched: array<int, string>}  $result
     */
    private function formatResultMessage(string $fileName, array $result): string
    {
        $message = "{$fileName}: processed {$result['matched']} barangay boundaries.";

        if (count($result['unmatched']) > 0) {
            $message .= ' Created new records: '.Str::limit(implode(', ', array_slice($result['unmatched'], 0, 3)), 120);
        }

        return $message;
    }
}
