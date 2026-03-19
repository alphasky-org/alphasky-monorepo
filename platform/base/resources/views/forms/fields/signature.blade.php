@php
    $signaturePadColor = $options['signature_pad_color'] ?? '#000000';
    $signaturePadWidth = $options['signature_pad_width'] ?? 500;
    $required = $options['required'] ?? false;
    $signaturePadHeight = $options['signature_pad_height'] ?? 200;
    $showClearButton = $options['show_clear_button'] ?? true;
    $fieldId = $name . '_signature_pad';
    $canvasId = $name . '_canvas';
    $value = $options['value'] ?? null;
    $useFileStorage = $options['use_file_storage'] ?? true; // تفعيل نظام الملفات افتراضياً
@endphp

<div class="mb-3">
 
        @if ($showLabel && $options['label'] !== false && $options['label_show'])
            {!! Form::customLabel($name, $options['label'], $options['label_attr']) !!}
        @endif
  
    
    <div class="signature-field-container row">
        <!-- Canvas للتوقيع -->
        <div class="signature-canvas-container col-9" style="border: 2px solid #ddd; border-radius: 8px; position: relative; display: inline-block; background-color: #fff;">
            <canvas 
                id="{{ $canvasId }}"
                width="{{ $signaturePadWidth }}" 
                height="{{ $signaturePadHeight }}"
                style="display: block; cursor: crosshair;"
                data-field-name="{{ $name }}"
                data-signature-value="{{ $value }}"
                data-signature-color="{{ $signaturePadColor }}">
            </canvas>
            
            @if($showClearButton)
                <button 
                    type="button" 
                    class="btn btn-sm btn-outline-danger signature-clear-btn"
                    style="position: absolute; top: 5px; right: 5px;"
                    data-canvas-id="{{ $canvasId }}"
                    data-field-name="{{ $name }}">
                    <i class="fas fa-times"></i> {{ __('delete') }}
                </button>
            @endif
        </div>
        
        <!-- حقل مخفي لحفظ بيانات التوقيع -->
        <input 
            type="hidden" 
            id="{{ $fieldId }}" 
            name="{{ $name }}" 
            value="{{ $value ?? '' }}"
            data-use-file-storage="{{ $useFileStorage ? 'true' : 'false' }}"
            data-upload-url="{{ route('signature.upload') }}"
            {!! Html::attributes($attributes ?? []) !!}
        >
        
        <!-- معاينة التوقيع الحالي إذا وجد -->
        @if($value)
            <div class="current-signature-preview col-3 " id="{{ $fieldId }}_preview">
                @if(str_starts_with($value, 'data:image'))
                    <!-- Base64 signature -->
                    <div class="signature-preview-container">
                        <label class="form-label small text-muted">{{ __('Current signature') }}:</label>
                        <div>
                            <img src="{{ $value }}" alt="{{ __('Current signature') }}" class="signature-preview" style="max-width: 100%; max-height: 100px; border: 1px solid #ddd; border-radius: 4px; background: white; padding: 2px;">
                        </div>
                    </div>
                @else
                    <!-- File signature -->
                    @if(\Storage::disk('public')->exists($value))
                        <div class="signature-file-preview ">   
                            <label class="form-label small text-muted">{{ __('Current signature') }}:</label>
                            <div class="d-flex align-items-center gap-2">
                                <img src="{{ \Storage::disk('public')->url($value) }}" alt="{{ __('Current signature') }}" class="signature-preview" style="max-width: 100%; max-height: 100px; border: 1px solid #ddd; border-radius: 4px; background: white; padding: 2px;">
                               
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        @endif
        

    </div>
    
    <!-- رسائل المساعدة -->
    @if(isset($help_block))
        <small class="form-text text-muted">{!! $help_block['text'] ?? '' !!}</small>
    @endif
</div>

@once
    @push('footer')
        <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // تهيئة جميع حقول التوقيع في الصفحة
                const canvases = document.querySelectorAll('canvas[data-field-name]');
                
                canvases.forEach(function(canvas) {
                    const fieldName = canvas.getAttribute('data-field-name');
                    const signatureColor = canvas.getAttribute('data-signature-color') || '#000000';
                    const hiddenField = document.getElementById(fieldName + '_signature_pad');
                    const clearBtn = document.querySelector(`[data-canvas-id="${canvas.id}"]`);
                    
                    // إنشاء SignaturePad
                    const signaturePad = new SignaturePad(canvas, {
                        backgroundColor: 'rgb(255, 255, 255)',
                        penColor: signatureColor,
                        minWidth: 2,
                        maxWidth: 4,
                    });
                    
                    // حفظ التوقيع عند تغييره
                    signaturePad.addEventListener('endStroke', function() {
                        if (!signaturePad.isEmpty()) {
                            try {
                                const dataURL = signaturePad.toDataURL('image/png', 0.8);
                                
                                // تحقق من استخدام نظام الملفات
                                const useFileStorage = hiddenField.getAttribute('data-use-file-storage') === 'true';
                                const uploadUrl = hiddenField.getAttribute('data-upload-url');
                                
                                if (useFileStorage && uploadUrl) {
                                    // رفع التوقيع كملف
                                    uploadSignatureFile(dataURL, fieldName, hiddenField, uploadUrl);
                                } else {
                                    // حفظ كـ Base64 (النظام القديم)
                                    hiddenField.value = dataURL;
                                }
                                
                                // إزالة معاينة التوقيع القديم
                                const preview = canvas.closest('.signature-field-container').querySelector('.signature-preview');
                                if (preview) {
                                    preview.remove();
                                }
                                
                            } catch (error) {
                                console.error('{{ __('error saving signature') }}:', error);
                            }
                        }
                    });
                    
                    // وظيفة مسح التوقيع
                    if (clearBtn) {
                        clearBtn.addEventListener('click', function() {
                            const currentValue = hiddenField.value;
                            
                            // مسح التوقيع من Canvas
                            signaturePad.clear();
                            hiddenField.value = '';
                            
                            // حذف الملف إذا كان موجوداً في الستورج
                            if (currentValue && !currentValue.startsWith('data:image')) {
                                deleteSignatureFile(currentValue);
                            }
                            
                            // إزالة معاينة التوقيع
                            const preview = canvas.closest('.signature-field-container').querySelector('.signature-preview');
                            if (preview) {
                                preview.remove();
                            }
                            
                            // مسح معاينة التوقيع الحالي
                            const previewContainer = document.getElementById(hiddenField.id + '_preview');
                            if (previewContainer) {
                                previewContainer.innerHTML = '';
                            }
                            
                            // إظهار رسالة تأكيد
                            if (typeof Alphasky !== 'undefined' && Alphasky.showSuccess) {
                                Alphasky.showSuccess('{{ __('signature cleared') }}');
                            }
                        });
                    }
                    
                    // تحميل التوقيع المحفوظ سابقاً
                    if (hiddenField.value && hiddenField.value.trim() !== '') {
                        const img = new Image();
                        img.onload = function() {
                            const ctx = canvas.getContext('2d');
                            ctx.clearRect(0, 0, canvas.width, canvas.height);
                            // Calculate scaling to fit the image in the canvas
                            const scale = Math.min(canvas.width / img.width, canvas.height / img.height);
                            const x = (canvas.width - img.width * scale) / 2;
                            const y = (canvas.height - img.height * scale) / 2;
                            ctx.drawImage(img, x, y, img.width * scale, img.height * scale);
                        };
                    
                        img.src = hiddenField.value;
                    }
                    
                    // التعامل مع تغيير حجم النافذة
                    function resizeCanvas() {
                        const ratio = Math.max(window.devicePixelRatio || 1, 1);
                        canvas.width = canvas.offsetWidth * ratio;
                        canvas.height = canvas.offsetHeight * ratio;
                        canvas.getContext('2d').scale(ratio, ratio);
                        signaturePad.clear();
                    }
                    
                    window.addEventListener('resize', resizeCanvas);
                    
                    // حفظ التوقيع عند إرسال النموذج
                    const form = canvas.closest('form');
                    if (form) {
                        form.addEventListener('submit', function(e) {
                            if (!signaturePad.isEmpty()) {
                                try {
                                    const dataURL = signaturePad.toDataURL('image/png', 0.8);
                                    
                                    // التحقق من أن البيانات هي مسار ملف وليس Base64
                                    if (hiddenField.value && hiddenField.value.startsWith('data:image/')) {
                                        // البيانات لا تزال Base64، نحتاج لرفعها أولاً
                                        const useFileStorage = hiddenField.getAttribute('data-use-file-storage') === 'true';
                                        const uploadUrl = hiddenField.getAttribute('data-upload-url');
                                        
                                        if (useFileStorage && uploadUrl) {
                                            e.preventDefault();
                                            
                                            // رفع التوقيع قبل إرسال النموذج
                                            uploadSignatureFileSync(dataURL, fieldName, hiddenField, uploadUrl)
                                                .then(() => {
                                                    // إعادة إرسال النموذج بعد رفع الملف
                                                    form.submit();
                                                })
                                                .catch(error => {
                                                    console.error('خطأ في رفع التوقيع قبل الإرسال:', error);
                                                    // في حالة الفشل، المتابعة بـ Base64
                                                    hiddenField.value = dataURL;
                                                    form.submit();
                                                });
                                        } else {
                                            // النظام القديم - Base64
                                            hiddenField.value = dataURL;
                                        }
                                    } else if (!hiddenField.value) {
                                        // لا يوجد توقيع محفوظ، حفظ الحالي
                                        const useFileStorage = hiddenField.getAttribute('data-use-file-storage') === 'true';
                                        const uploadUrl = hiddenField.getAttribute('data-upload-url');
                                        
                                        if (useFileStorage && uploadUrl) {
                                            e.preventDefault();
                                            
                                            uploadSignatureFileSync(dataURL, fieldName, hiddenField, uploadUrl)
                                                .then(() => {
                                                    form.submit();
                                                })
                                                .catch(error => {
                                                    console.error('خطأ في رفع التوقيع:', error);
                                                    hiddenField.value = dataURL;
                                                    form.submit();
                                                });
                                        } else {
                                            hiddenField.value = dataURL;
                                        }
                                    }
                                    
                                } catch (error) {
                                    console.error('{{ __('Signature processing error') }}:', error);
                                }
                            }
                        });
                    }
                });
                
                // دالة رفع التوقيع كملف
                function uploadSignatureFile(dataURL, fieldName, hiddenField, uploadUrl) {
                    // إظهار مؤشر التحميل
                    const loadingIndicator = showLoadingIndicator(hiddenField);
                    
                    // إعداد البيانات للإرسال
                    const formData = new FormData();
                    formData.append('signature_data', dataURL);
                    formData.append('field_name', fieldName);
                    formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '');
                    
                    // إرسال الطلب
                    fetch(uploadUrl, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        hideLoadingIndicator(loadingIndicator);
                        
                        if (data.success) {
                            // حفظ مسار الملف
                            hiddenField.value = data.data.file_path;
                            
                            // إظهار رسالة نجاح
                            if (typeof Alphasky !== 'undefined' && Alphasky.showSuccess) {
                                Alphasky.showSuccess(data.message);
                            }
                            
                            // تحديث معاينة التوقيع
                            updateSignaturePreview(hiddenField, data.data.file_url);
                            
                        } else {
                            console.error('{{ __('Error uploading signature') }}:', data.message);
                            // العودة إلى النظام القديم في حالة الفشل
                            hiddenField.value = dataURL;
                            
                            if (typeof Alphasky !== 'undefined' && Alphasky.showError) {
                                Alphasky.showError('{{ __('Signature upload failed, saved locally') }}: ' + data.message);
                            }
                        }
                    })
                    .catch(error => {
                        hideLoadingIndicator(loadingIndicator);
                        console.error('{{ __('Network error') }}:', error);
                        
                        // العودة إلى النظام القديم في حالة فشل الشبكة
                        hiddenField.value = dataURL;
                        
                        if (typeof Alphasky !== 'undefined' && Alphasky.showError) {
                            Alphasky.showError('{{ __('Connection issue, signature saved locally') }}');
                        }
                    });
                }
                
                // إظهار مؤشر التحميل
                function showLoadingIndicator(hiddenField) {
                    const container = hiddenField.closest('.signature-field-container');
                    const indicator = document.createElement('div');
                    indicator.className = 'signature-loading';
                    indicator.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __('Saving signature...') }}';
                    indicator.style.cssText = 'position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(255,255,255,0.9); padding: 10px; border-radius: 4px; z-index: 1000; font-size: 12px;';
                    container.style.position = 'relative';
                    container.appendChild(indicator);
                    return indicator;
                }
                
                // إخفاء مؤشر التحميل
                function hideLoadingIndicator(indicator) {
                    if (indicator && indicator.parentNode) {
                        indicator.parentNode.removeChild(indicator);
                    }
                }
                
                // تحديث معاينة التوقيع
                function updateSignaturePreview(hiddenField, fileUrl) {
                    const previewContainer = document.getElementById(hiddenField.id + '_preview');
                    if (previewContainer) {
                        const currentSignatureText = '{{ __('Current signature') }}';
                        const downloadSignatureText = '{{ __('Download signature') }}';
                        
                        previewContainer.innerHTML = `
                            <div class="signature-file-preview">
                                <label class="form-label small text-muted">${currentSignatureText}:</label>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="${fileUrl}" alt="${currentSignatureText}" class="signature-preview" style="max-width: 100%; max-height: 100px; border: 1px solid #ddd; border-radius: 4px; background: white; padding: 2px;">
                                  
                                </div>
                            </div>
                        `;
                    }
                }
                
                // دالة حذف ملف التوقيع
                function deleteSignatureFile(filePath) {
                    const deleteUrl = '/admin/signature/delete'; // مسار حذف التوقيع
                    
                    fetch(deleteUrl, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            file_path: filePath
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            console.warn('{{ __('Warning: Failed to delete signature file') }}:', data.message);
                        }
                    })
                    .catch(error => {
                        console.warn('{{ __('Warning: Error deleting signature file') }}:', error);
                    });
                }

            // دالة رفع التوقيع مع إرجاع Promise
            function uploadSignatureFileSync(dataURL, fieldName, hiddenField, uploadUrl) {
                return new Promise((resolve, reject) => {
                    // إعداد البيانات للإرسال
                    const formData = new FormData();
                    formData.append('signature_data', dataURL);
                    formData.append('field_name', fieldName);
                    formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '');
                    
                    // إرسال الطلب
                    fetch(uploadUrl, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // حفظ مسار الملف
                            hiddenField.value = data.data.file_path;
                            console.log('{{ __('Signature uploaded successfully before form submission') }}:', data.data.file_path);
                            resolve(data);
                        } else {
                            console.error('{{ __('Error uploading signature') }}:', data.message);
                            reject(new Error(data.message));
                        }
                    })
                    .catch(error => {
                        console.error('{{ __('Network error') }}:', error);
                        reject(error);
                    });
                });
            }
            });
        </script>
        
        <style>
            .signature-field-container .signature-canvas-container {
                position: relative;
                display: inline-block;
            }
            
            .signature-field-container canvas {
                touch-action: none;
                border-radius: 6px;
            }
            
            .signature-clear-btn {
                z-index: 10;
                font-size: 12px;
                padding: 4px 8px;
            }
            
            .signature-preview img {
                border: 1px solid #dee2e6;
                border-radius: 4px;
            }
            
            @media (max-width: 768px) {
                .signature-field-container canvas {
                    max-width: 100%;
                    height: auto;
                }
            }
        </style>
    @endpush
@endonce