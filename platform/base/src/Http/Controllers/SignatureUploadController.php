<?php

namespace Alphasky\Base\Http\Controllers;

use Alphasky\Base\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SignatureUploadController extends BaseController
{
    /**
     * رفع ملف التوقيع
     */
    public function upload(Request $request): JsonResponse
    {
        try {
            // التحقق من صحة البيانات
            $validator = Validator::make($request->all(), [
                'signature_file' => 'nullable|file|mimes:png,jpg,jpeg,svg,webp|max:5120', // 5MB
                'field_name' => 'required|string|max:255',
                'signature_data' => 'nullable|string', // للـ Base64
            ], [
                'signature_file.required' => 'ملف التوقيع مطلوب',
                'signature_file.file' => 'يجب أن يكون التوقيع ملف صحيح',
                'signature_file.mimes' => 'نوع ملف التوقيع غير مدعوم (PNG, JPG, JPEG, SVG, WEBP فقط)',
                'signature_file.max' => 'حجم ملف التوقيع يجب أن يكون أقل من 5 ميجابايت',
                'field_name.required' => 'اسم الحقل مطلوب',
                'field_name.max' => 'اسم الحقل طويل جداً',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()
                ], 422);
            }

            // معالجة رفع الملف من Base64 إذا وجد
            if ($request->filled('signature_data') && !$request->hasFile('signature_file')) {
                return $this->handleBase64Upload($request);
            }

            // معالجة رفع الملف العادي
            $file = $request->file('signature_file');
            $fieldName = $request->input('field_name', 'signature');

            // التحقق من صحة الملف
            if (!$file->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ملف التوقيع تالف أو غير صحيح'
                ], 400);
            }

            // إنشاء اسم ملف فريد
            $extension = $file->getClientOriginalExtension();
            $fileName = 'signature_' . time() . '_' . Str::random(10) . '.' . $extension;
            
            // إنشاء مجلد منظم حسب التاريخ
            $directory = 'signatures/' . date('Y') . '/' . date('m');
            
            // التأكد من وجود المجلد
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory, 0755, true);
            }

            // حفظ الملف
            $filePath = $file->storeAs($directory, $fileName, 'public');

            if (!$filePath) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل في حفظ ملف التوقيع'
                ], 500);
            }

            // الحصول على معلومات الملف
            $fileSize = Storage::disk('public')->size($filePath);
            $fileUrl = Storage::disk('public')->url($filePath);

            return response()->json([
                'success' => true,
                'message' => 'تم رفع التوقيع بنجاح',
                'data' => [
                    'file_path' => $filePath,
                    'file_url' => $fileUrl,
                    'file_name' => $fileName,
                    'file_size' => $fileSize,
                    'field_name' => $fieldName,
                    'mime_type' => $file->getClientMimeType(),
                    'extension' => $extension,
                    'upload_time' => now()->toISOString(),
                    'storage_type' => 'file'
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('خطأ في رفع التوقيع: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء رفع التوقيع: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * معالجة رفع التوقيع من Base64
     */
    protected function handleBase64Upload(Request $request): JsonResponse
    {
        try {
            $base64Data = $request->input('signature_data');
            $fieldName = $request->input('field_name', 'signature');

            // التحقق من صحة البيانات
            if (!$base64Data || !str_contains($base64Data, 'data:image')) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات التوقيع Base64 غير صحيحة'
                ], 400);
            }

            // استخراج البيانات
            $base64Parts = explode(',', $base64Data, 2);
            if (count($base64Parts) !== 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'تنسيق بيانات التوقيع غير صحيح'
                ], 400);
            }

            $mimeType = $base64Parts[0];
            $data = base64_decode($base64Parts[1]);
            
            if ($data === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل في فك ترميز بيانات التوقيع'
                ], 400);
            }

            // التحقق من حجم البيانات (5MB)
            if (strlen($data) > 5 * 1024 * 1024) {
                return response()->json([
                    'success' => false,
                    'message' => 'حجم التوقيع كبير جداً (أقصى حد 5 ميجابايت)'
                ], 400);
            }

            // تحديد امتداد الملف
            $extension = 'png';
            if (str_contains($mimeType, 'jpeg')) {
                $extension = 'jpg';
            } elseif (str_contains($mimeType, 'svg')) {
                $extension = 'svg';
            } elseif (str_contains($mimeType, 'webp')) {
                $extension = 'webp';
            }

            // إنشاء اسم ملف فريد
            $fileName = 'signature_' . time() . '_' . Str::random(10) . '.' . $extension;
            
            // إنشاء مجلد منظم حسب التاريخ
            $directory = 'signatures/' . date('Y') . '/' . date('m');
            
            // التأكد من وجود المجلد
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory, 0755, true);
            }

            // حفظ الملف
            $filePath = $directory . '/' . $fileName;
            $saved = Storage::disk('public')->put($filePath, $data);

            if (!$saved) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل في حفظ ملف التوقيع'
                ], 500);
            }

            // الحصول على معلومات الملف
            $fileSize = Storage::disk('public')->size($filePath);
            $fileUrl = Storage::disk('public')->url($filePath);

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ التوقيع بنجاح',
                'data' => [
                    'file_path' => $filePath,
                    'file_url' => $fileUrl,
                    'file_name' => $fileName,
                    'file_size' => $fileSize,
                    'field_name' => $fieldName,
                    'mime_type' => $mimeType,
                    'extension' => $extension,
                    'upload_time' => now()->toISOString(),
                    'storage_type' => 'file',
                    'source' => 'base64'
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('خطأ في حفظ التوقيع Base64: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ التوقيع: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف ملف التوقيع
     */
    public function delete(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'file_path' => 'required|string',
            ], [
                'file_path.required' => 'مسار الملف مطلوب',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $filePath = $request->input('file_path');

            // تجاهل البيانات Base64
            if (str_contains($filePath, 'data:image')) {
                return response()->json([
                    'success' => true,
                    'message' => 'لا يوجد ملف للحذف (البيانات محفوظة كـ Base64)'
                ]);
            }

            // التحقق من الأمان - التأكد أن المسار ضمن مجلد signatures
            if (!str_starts_with($filePath, 'signatures/')) {
                return response()->json([
                    'success' => false,
                    'message' => 'مسار الملف غير آمن'
                ], 403);
            }

            // حذف الملف
            if (Storage::disk('public')->exists($filePath)) {
                $deleted = Storage::disk('public')->delete($filePath);
                
                if ($deleted) {
                    return response()->json([
                        'success' => true,
                        'message' => 'تم حذف ملف التوقيع بنجاح'
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'فشل في حذف ملف التوقيع'
                    ], 500);
                }
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'الملف غير موجود (ربما تم حذفه مسبقاً)'
                ]);
            }

        } catch (\Exception $e) {
            \Log::error('خطأ في حذف التوقيع: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف التوقيع: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * الحصول على معلومات ملف التوقيع
     */
    public function info(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'file_path' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $filePath = $request->input('file_path');

            // معالجة البيانات Base64
            if (str_contains($filePath, 'data:image')) {
                $base64Parts = explode(',', $filePath, 2);
                $dataSize = count($base64Parts) === 2 ? strlen($base64Parts[1]) * 0.75 : 0; // تقدير حجم البيانات

                return response()->json([
                    'success' => true,
                    'data' => [
                        'exists' => true,
                        'storage_type' => 'base64',
                        'estimated_size' => round($dataSize),
                        'mime_type' => $base64Parts[0] ?? 'unknown',
                        'is_base64' => true
                    ]
                ]);
            }

            // معالجة مسار الملف
            if (Storage::disk('public')->exists($filePath)) {
                $fileSize = Storage::disk('public')->size($filePath);
                $fileUrl = Storage::disk('public')->url($filePath);
                $lastModified = Storage::disk('public')->lastModified($filePath);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'exists' => true,
                        'file_path' => $filePath,
                        'file_url' => $fileUrl,
                        'file_size' => $fileSize,
                        'file_size_human' => $this->formatFileSize($fileSize),
                        'last_modified' => date('Y-m-d H:i:s', $lastModified),
                        'storage_type' => 'file',
                        'is_base64' => false
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'ملف التوقيع غير موجود',
                    'data' => [
                        'exists' => false,
                        'file_path' => $filePath
                    ]
                ], 404);
            }

        } catch (\Exception $e) {
            \Log::error('خطأ في الحصول على معلومات التوقيع: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الحصول على معلومات التوقيع: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * تنسيق حجم الملف
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}