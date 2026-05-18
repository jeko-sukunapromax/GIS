<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MapUpload;
use App\Models\Barangay;
use App\Traits\ParsesBoundaryFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UploadController extends Controller
{
    use ParsesBoundaryFiles;

    public function index()
    {
        $uploads = MapUpload::orderBy('created_at', 'desc')->get();
        $barangays = Barangay::orderBy('name')->get();
        return view('admin.uploads.index', compact('uploads', 'barangays'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'upload_files' => 'required|array',
            'upload_files.*' => 'file|max:51200' // Max 50MB per file
        ]);

        $files = $request->file('upload_files');
        $successMessages = [];
        $errorMessages = [];
        
        foreach ($files as $file) {
            $fileName = $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension());
            
            $type = 'Unknown';
            if (in_array($extension, ['json', 'geojson'])) $type = 'GeoJSON';
            elseif ($extension === 'shp' || $extension === 'zip') $type = 'Shapefile';
            elseif ($extension === 'kml') $type = 'KML';
            elseif ($extension === 'csv') $type = 'CSV';

            $size = $file->getSize();
            $sizeFormatted = $size > 1048576 ? round($size / 1048576, 1) . ' MB' : round($size / 1024, 1) . ' KB';

            try {
                $status = 'Processed';
                $message = "<strong>{$fileName}</strong>: Successfully processed! ";
                
                if ($extension === 'geojson' || $extension === 'json') {
                    $result = $this->parseBulkGeoJson($file, $fileName);
                    $message .= "Automatically matched/created {$result['matched']} Barangays.";
                    if (count($result['unmatched']) > 0) {
                        $message .= " Could not find matches for: " . implode(', ', array_slice($result['unmatched'], 0, 3)) . "...";
                    }
                } elseif ($extension === 'zip') {
                    $result = $this->parseBulkShapefileZip($file, $fileName);
                    $message .= "Automatically matched/created {$result['matched']} Barangays.";
                    if (count($result['unmatched']) > 0) {
                        $message .= " Could not find matches for: " . implode(', ', array_slice($result['unmatched'], 0, 3)) . "...";
                    }
                } else {
                    throw new \Exception('For automatic bulk boundary mapping, please upload a GeoJSON or a ZIP file containing Shapefile (.shp + .dbf).');
                }

                // Save upload record
                MapUpload::create([
                    'file_name' => $fileName,
                    'file_type' => $type,
                    'file_size' => $sizeFormatted,
                    'uploaded_by' => 'admin',
                    'status' => $status
                ]);

                $successMessages[] = $message;

            } catch (\Exception $e) {
                Log::error('Upload error for ' . $fileName . ': ' . $e->getMessage());
                
                // Record the failed upload
                MapUpload::create([
                    'file_name' => $fileName,
                    'file_type' => $type,
                    'file_size' => $sizeFormatted,
                    'uploaded_by' => 'admin',
                    'status' => 'Failed'
                ]);
                
                $errorMessages[] = "<strong>{$fileName}</strong>: " . $e->getMessage();
            }
        }

        $response = back();
        if (count($successMessages) > 0) {
            $response = $response->with('success', implode('<br>', $successMessages));
        }
        if (count($errorMessages) > 0) {
            $response = $response->with('error', implode('<br>', $errorMessages));
        }

        return $response;
    }

    public function destroy(MapUpload $upload)
    {
        $upload->delete();
        return back()->with('success', 'Upload history record deleted successfully.');
    }
}
