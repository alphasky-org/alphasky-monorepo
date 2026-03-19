<?php

namespace Alphasky\Base\Forms\Fields;

use Alphasky\Base\Forms\FormField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AudioField extends FormField
{
    protected function getTemplate(): string
    {
        return 'core/base::forms.fields.audio';
    }

    /**
     * Handle audio file upload and return file path
     */
    public static function handleAudioUpload(Request $request, string $fieldName): ?string
    {
        $audioData = $request->input($fieldName);
        
        if (!$audioData || !str_starts_with($audioData, 'data:audio/')) {
            return null;
        }

        // Parse the base64 data
        $audioData = str_replace('data:audio/webm;base64,', '', $audioData);
        $audioData = str_replace('data:audio/wav;base64,', '', $audioData);
        $audioData = str_replace('data:audio/mp4;base64,', '', $audioData);
        $audioData = str_replace(' ', '+', $audioData);
        
        $decodedAudio = base64_decode($audioData);
        
        if (!$decodedAudio) {
            return null;
        }

        // Generate unique filename
        $fileName = 'audio_' . time() . '_' . Str::random(10) . '.webm';
        $filePath = 'audio/' . date('Y/m') . '/' . $fileName;
        
        // Store the file
        Storage::disk('public')->put($filePath, $decodedAudio);
        
        return $filePath;
    }

    /**
     * Delete audio file from storage
     */
    public static function deleteAudioFile(?string $filePath): bool
    {
        if (!$filePath) {
            return false;
        }

        return Storage::disk('public')->delete($filePath);
    }

    /**
     * Get full URL for audio file
     */
    public static function getAudioUrl(?string $filePath): ?string
    {
        if (!$filePath) {
            return null;
        }

        return Storage::disk('public')->url($filePath);
    }
}