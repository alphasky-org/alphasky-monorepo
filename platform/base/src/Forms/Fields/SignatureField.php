<?php

namespace Alphasky\Base\Forms\Fields;

use Alphasky\Base\Forms\FormField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SignatureField extends FormField
{
    protected function getTemplate(): string
    {
        return 'core/base::forms.fields.signature';
    }

    /**
     * معالجة رفع ملف التوقيع
     */
    public function handleSignatureUpload(Request $request, string $fieldName): ?string
    {
        try {
            if (!$request->hasFile($fieldName)) {
                return null;
            }

            $file = $request->file($fieldName);
            
            // التحقق من صحة الملف
            if (!$file->isValid()) {
                throw new \Exception('ملف التوقيع غير صحيح');
            }

            // التحقق من نوع الملف
            $allowedTypes = ['png', 'jpg', 'jpeg', 'svg', 'webp'];
            $extension = strtolower($file->getClientOriginalExtension());
            
            if (!in_array($extension, $allowedTypes)) {
                throw new \Exception('نوع الملف غير مدعوم للتوقيع');
            }

            // التحقق من حجم الملف (5MB كحد أقصى)
            if ($file->getSize() > 5 * 1024 * 1024) {
                throw new \Exception('حجم ملف التوقيع كبير جداً');
            }

            // إنشاء اسم ملف فريد
            $fileName = time() . '_' . Str::random(10) . '.' . $extension;
            
            // إنشاء مجلد منظم حسب التاريخ
            $directory = 'signatures/' . date('Y') . '/' . date('m');
            
            // حفظ الملف
            $filePath = $file->storeAs($directory, $fileName, 'public');
            
            return $filePath;
            
        } catch (\Exception $e) {
            \Log::error('خطأ في رفع التوقيع: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * معالجة رفع التوقيع من Base64
     */
    public function handleBase64SignatureUpload(string $base64Data, string $fieldName = 'signature'): ?string
    {
        try {
            // التحقق من صحة البيانات
            if (!$base64Data || !str_contains($base64Data, 'data:image')) {
                return null;
            }

            // استخراج البيانات
            $base64Parts = explode(',', $base64Data, 2);
            if (count($base64Parts) !== 2) {
                throw new \Exception('تنسيق بيانات التوقيع غير صحيح');
            }

            $mimeType = $base64Parts[0];
            $data = base64_decode($base64Parts[1]);
            
            if ($data === false) {
                throw new \Exception('فشل في فك ترميز بيانات التوقيع');
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
                Storage::disk('public')->makeDirectory($directory);
            }

            // حفظ الملف
            $filePath = $directory . '/' . $fileName;
            Storage::disk('public')->put($filePath, $data);
            
            return $filePath;
            
        } catch (\Exception $e) {
            \Log::error('خطأ في حفظ التوقيع Base64: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * حذف ملف التوقيع
     */
    public function deleteSignatureFile(?string $filePath): bool
    {
        try {
            if (!$filePath || str_contains($filePath, 'data:image')) {
                return true; // Base64 data, no file to delete
            }

            if (Storage::disk('public')->exists($filePath)) {
                return Storage::disk('public')->delete($filePath);
            }
            
            return true;
            
        } catch (\Exception $e) {
            \Log::error('خطأ في حذف ملف التوقيع: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * الحصول على رابط التوقيع
     */
    public function getSignatureUrl(?string $filePath): ?string
    {
        if (!$filePath) {
            return null;
        }

        // إذا كانت بيانات Base64
        if (str_contains($filePath, 'data:image')) {
            return $filePath;
        }

        // إذا كان مسار ملف
        if (Storage::disk('public')->exists($filePath)) {
            return Storage::disk('public')->url($filePath);
        }

        return null;
    }

    /**
     * التحقق من وجود التوقيع
     */
    public function hasSignature(?string $value): bool
    {
        if (!$value) {
            return false;
        }

        // Base64 signature
        if (str_contains($value, 'data:image')) {
            $base64Parts = explode(',', $value, 2);
            return count($base64Parts) === 2 && !empty($base64Parts[1]);
        }

        // File path signature
        return Storage::disk('public')->exists($value);
    }
}