<?php

namespace Alphasky\Base\Http\Controllers;

use Alphasky\Base\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;

class AudioUploadController extends BaseController
{
    /**
     * Upload audio file to storage
     */
    public function upload(Request $request): JsonResponse
    {
        try {
            // Validate the request
            $request->validate([
                'audio_file' => 'nullable|file|mimes:webm,wav,mp3,mp4,ogg|max:10240', // Max 10MB
                'field_name' => 'nullable|string'
            ]);

            $audioFile = $request->file('audio_file');
            $fieldName = $request->input('field_name');
            
            // Generate unique filename
            $originalName = $audioFile->getClientOriginalName();
            $extension = $audioFile->getClientOriginalExtension() ?: 'webm';
            $fileName = 'audio_' . time() . '_' . Str::random(10) . '.' . $extension;
            
            // Create directory structure: audio/YYYY/MM/
            $directory = 'audio/' . date('Y') . '/' . date('m');
            $filePath = $directory . '/' . $fileName;
            
            // Store the file
            $storedPath = Storage::disk('public')->putFileAs($directory, $audioFile, $fileName);
            
            if (!$storedPath) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل في حفظ الملف الصوتي'
                ], 500);
            }

            // Get file info
            $fullPath = Storage::disk('public')->path($storedPath);
            $fileSize = Storage::disk('public')->size($storedPath);
            $fileUrl = Storage::disk('public')->url($storedPath);

            return response()->json([
                'success' => true,
                'message' => 'تم رفع الملف الصوتي بنجاح',
                'file_path' => $storedPath,
                'file_url' => $fileUrl,
                'file_name' => $fileName,
                'original_name' => $originalName,
                'file_size' => $fileSize,
                'file_size_kb' => round($fileSize / 1024, 2),
                'field_name' => $fieldName
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            \Log::error('Audio upload error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطأ في رفع الملف: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete audio file from storage
     */
    public function delete(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file_path' => 'required|string'
            ]);

            $filePath = $request->input('file_path');
            
            // Security check - ensure file is in audio directory
            if (!str_starts_with($filePath, 'audio/')) {
                return response()->json([
                    'success' => false,
                    'message' => 'مسار ملف غير صحيح'
                ], 400);
            }

            // Delete the file
            $deleted = Storage::disk('public')->delete($filePath);

            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم حذف الملف الصوتي بنجاح'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل في حذف الملف الصوتي'
                ], 500);
            }

        } catch (\Exception $e) {
            \Log::error('Audio delete error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'خطأ في حذف الملف: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get audio file info
     */
    public function info(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file_path' => 'required|string'
            ]);

            $filePath = $request->input('file_path');
            
            if (!Storage::disk('public')->exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'الملف غير موجود'
                ], 404);
            }

            $fileSize = Storage::disk('public')->size($filePath);
            $fileUrl = Storage::disk('public')->url($filePath);
            $lastModified = Storage::disk('public')->lastModified($filePath);

            return response()->json([
                'success' => true,
                'file_path' => $filePath,
                'file_url' => $fileUrl,
                'file_size' => $fileSize,
                'file_size_kb' => round($fileSize / 1024, 2),
                'file_size_mb' => round($fileSize / (1024 * 1024), 2),
                'last_modified' => date('Y-m-d H:i:s', $lastModified),
                'exists' => true
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في جلب معلومات الملف: ' . $e->getMessage()
            ], 500);
        }
    }
}