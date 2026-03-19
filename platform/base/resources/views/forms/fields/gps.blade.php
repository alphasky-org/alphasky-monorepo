<x-core::form.field
    :showLabel="$showLabel"
    :showField="$showField"
    :options="$options"
    :name="$name"
    :prepend="$prepend ?? null"
    :append="$append ?? null"
    :showError="$showError"
    :nameKey="$nameKey"
>
 
        <x-slot:label>
            @if ($showLabel && $options['label'] !== false && $options['label_show'])
                {!! Form::customLabel($name, $options['label'], $options['label_attr']) !!}
            @endif
        </x-slot:label>                        
        <div class="row"> 
            <div class="col-md-4">
                <input type="hidden" name="{{ $name }}" value="{{ $options['values']['latitude'].','.$options['values']['longitude'] }}">
                <button type="button" class="btn btn-info" id="getLocationBtn">
                    <i class="fas fa-map-marker-alt"></i> {{ __('get current location') }}
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm mt-1" id="debugLocationBtn" title="{{ __('diagnostic info') }}">
                    <i class="fas fa-bug"></i> {{ __('debug') }}
                </button>
            </div>
            <div class="col-md-4">

                <input type="text" class="form-control" name="{{ $options['attr']['latitude'] }}" value="{{ $options['values']['latitude'] }}" id="latitude" placeholder="{{ __('latitude') }}" >
            </div>
            <div class="col-md-4">

                <input type="text" class="form-control" name="{{ $options['attr']['longitude'] }}" value="{{ $options['values']['longitude'] }}" id="longitude" placeholder="{{ __('longitude') }}" >
            </div>
        </div>
   
</x-core::form.field>
                        <script>
                            document.addEventListener("DOMContentLoaded", function() {
                                const getLocationBtn = document.getElementById("getLocationBtn");
                                const debugLocationBtn = document.getElementById("debugLocationBtn");
                                const clearLocationBtn = document.getElementById("clearLocationBtn");
                                const latitudeInput = document.getElementById("latitude");
                                const longitudeInput = document.getElementById("longitude");
                                // add listener to set #{{ $name }} to latitude,longitude on change
                                latitudeInput.addEventListener("change", function() {
                                    const lat = latitudeInput.value;
                                    const lng = longitudeInput.value;
                                    const hiddenInput = document.querySelector("input[name=\"{{ $name }}\"]");
                                    if (hiddenInput) {
                                        hiddenInput.value = lat + "," + lng;
                                    }
                                });
                                longitudeInput.addEventListener("change", function() {
                                    const lat = latitudeInput.value;
                                    const lng = longitudeInput.value;
                                    const hiddenInput = document.querySelector("input[name=\"{{ $name }}\"]");
                                    if (hiddenInput) {
                                        hiddenInput.value = lat + "," + lng;
                                    }
                                });
                                getLocationBtn.addEventListener("click", function() {
                                    if (navigator.geolocation) {
                                        getLocationBtn.innerHTML = "<i class=\"fas fa-spinner fa-spin\"></i> {{ __('getting location...') }}";
                                        getLocationBtn.disabled = true;
                                        
                                        // First try with high accuracy, then fallback to less accurate
                                        const tryGetPosition = (options) => {
                                            return new Promise((resolve, reject) => {
                                                navigator.geolocation.getCurrentPosition(resolve, reject, options);
                                            });
                                        };

                                        const getLocationWithFallback = async () => {
                                            // First attempt: High accuracy
                                            try {
                                                console.log('{{ __('attempting high accuracy location') }}...');
                                                const position = await tryGetPosition({
                                                    enableHighAccuracy: true,
                                                    timeout: 15000,
                                                    maximumAge: 300000 // 5 minutes cache
                                                });
                                                return position;
                                            } catch (error) {
                                                console.log('{{ __('high accuracy failed, trying lower accuracy') }}...', error);
                                                
                                                // Second attempt: Lower accuracy but faster
                                                try {
                                                    const position = await tryGetPosition({
                                                        enableHighAccuracy: false,
                                                        timeout: 30000,
                                                        maximumAge: 600000 // 10 minutes cache
                                                    });
                                                    return position;
                                                } catch (error2) {
                                                    console.log('فشل في الحصول على الموقع مع دقة أقل، محاولة أخيرة...', error2);
                                                    
                                                    // Third attempt: Very permissive settings
                                                    const position = await tryGetPosition({
                                                        enableHighAccuracy: false,
                                                        timeout: 60000,
                                                        maximumAge: 900000 // 15 minutes cache
                                                    });
                                                    return position;
                                                }
                                            }
                                        };

                                        getLocationWithFallback()
                                            .then(function(position) {
                                                const lat = position.coords.latitude.toFixed(6);
                                                const lng = position.coords.longitude.toFixed(6);
                                                const accuracy = position.coords.accuracy ? position.coords.accuracy.toFixed(0) : 'غير معروف';
                                                
                                                console.log(`تم الحصول على الموقع: ${lat}, ${lng} (دقة: ${accuracy}م)`);
                                                
                                                latitudeInput.value = lat;
                                                longitudeInput.value = lng;
                                                
                                                // Update hidden fields for form submission
                                                const latHidden = document.querySelector("input[name=\"latitude\"]");
                                                const lngHidden = document.querySelector("input[name=\"longitude\"]");
                                                if (latHidden) latHidden.value = lat;
                                                if (lngHidden) lngHidden.value = lng;
                                                const hiddenInput = document.querySelector("input[name=\"{{ $name }}\"]");
                                                if (hiddenInput) {
                                                    hiddenInput.value = lat + "," + lng;
                                                }
                                                
                                                getLocationBtn.innerHTML = "<i class=\"fas fa-check\"></i> {{ __('location fetched successfully') }}";
                                                getLocationBtn.className = "btn btn-success";
                                                
                                                setTimeout(function() {
                                                    getLocationBtn.innerHTML = "<i class=\"fas fa-map-marker-alt\"></i> {{ __('get current location') }}";
                                                    getLocationBtn.className = "btn btn-info";
                                                    getLocationBtn.disabled = false;
                                                }, 3000);
                                                
                                                // Show success message with accuracy
                                                const successMsg = `{{ __('location fetched successfully') }} (دقة: ${accuracy}م)`;
                                                if (typeof Alphasky !== "undefined" && Alphasky.showSuccess) {
                                                    Alphasky.showSuccess(successMsg);
                                                } else {
                                                    alert(successMsg);
                                                }
                                            })
                                            .catch(function(error) {
                                                console.error('خطأ في الحصول على الموقع:', error);
                                                
                                                let errorMessage = "";
                                                let troubleshootingTips = "";
                                                
                                                switch(error.code) {
                                                    case error.PERMISSION_DENIED:
                                                        errorMessage = "تم رفض الإذن للوصول للموقع";
                                                        troubleshootingTips = "تأكد من السماح للمتصفح بالوصول للموقع في إعدادات النظام";
                                                        break;
                                                    case error.POSITION_UNAVAILABLE:
                                                        errorMessage = "معلومات الموقع غير متوفرة";
                                                        troubleshootingTips = "تأكد من تشغيل خدمات الموقع وأن لديك اتصال بالإنترنت";
                                                        break;
                                                    case error.TIMEOUT:
                                                        errorMessage = "انتهت مهلة طلب الموقع";
                                                        troubleshootingTips = "حاول مرة أخرى في مكان مفتوح أو بالقرب من نافذة";
                                                        break;
                                                    default:
                                                        errorMessage = "خطأ غير معروف في الحصول على الموقع";
                                                        troubleshootingTips = "تأكد من أن المتصفح يدعم خدمات الموقع";
                                                        break;
                                                }

                                                getLocationBtn.innerHTML = "<i class=\"fas fa-exclamation-triangle\"></i> فشل";
                                                getLocationBtn.className = "btn btn-danger";
                                                
                                                setTimeout(function() {
                                                    getLocationBtn.innerHTML = "<i class=\"fas fa-map-marker-alt\"></i> {{ __('get current location') }}";
                                                    getLocationBtn.className = "btn btn-info";
                                                    getLocationBtn.disabled = false;
                                                }, 5000);
                                                
                                                // Show detailed error message
                                                const fullErrorMsg = `${errorMessage}\n\nنصائح لحل المشكلة:\n${troubleshootingTips}`;
                                                if (typeof Alphasky !== "undefined" && Alphasky.showError) {
                                                    Alphasky.showError(fullErrorMsg);
                                                } else {
                                                    alert(fullErrorMsg);
                                                }
                                            });
                                    } else {
                                        if (typeof Alphasky !== "undefined" && Alphasky.showError) {
                                            Alphasky.showError("{{ __('browser does not support geolocation') }}");
                                        } else {
                                            alert("{{ __('browser does not support geolocation') }}");
                                        }
                                    }
                                });

                                // Debug button functionality
                                debugLocationBtn.addEventListener("click", function() {
                                    const debugInfo = [];
                                    
                                    // Browser info
                                    debugInfo.push("=== {{ __('browser info') }} ===");
                                    debugInfo.push(`{{ __('browser') }}: ${navigator.userAgent}`);
                                    debugInfo.push(`{{ __('geolocation support') }}: ${navigator.geolocation ? '{{ __('yes') }}' : '{{ __('no') }}'}`);
                                    debugInfo.push(`HTTPS: ${location.protocol === 'https:' ? '{{ __('yes') }}' : '{{ __('no') }}'}`);
                                    debugInfo.push(`{{ __('url') }}: ${location.href}`);
                                    
                                    // Permissions API (if supported)
                                    if ('permissions' in navigator) {
                                        navigator.permissions.query({name: 'geolocation'}).then(function(result) {
                                            debugInfo.push(`حالة الإذن: ${result.state}`);
                                            showDebugModal(debugInfo);
                                        }).catch(function() {
                                            debugInfo.push("حالة الإذن: غير متوفرة");
                                            showDebugModal(debugInfo);
                                        });
                                    } else {
                                        debugInfo.push("حالة الإذن: غير مدعومة");
                                        showDebugModal(debugInfo);
                                    }
                                });

                                function showDebugModal(debugInfo) {
                                    const debugText = debugInfo.join('\n');
                                    
                                    // Create modal content
                                    const modalHtml = `
                                        <div class="modal fade" id="debugModal" tabindex="-1">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">{{ __('diagnostic info') }} - {{ __('location services') }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="alert alert-info">
                                                            <h6>خطوات حل المشكلة:</h6>
                                                            <ol>
                                                                <li><strong>تأكد من تشغيل خدمات الموقع:</strong>
                                                                    <br>• macOS: إعدادات النظام → الخصوصية والأمان → خدمات الموقع
                                                                    <br>• تأكد من تشغيل "خدمات الموقع" و "Safari/Chrome"
                                                                </li>
                                                                <li><strong>إعدادات المتصفح:</strong>
                                                                    <br>• Safari: تطوير → إعدادات الموقع → الموقع → السماح
                                                                    <br>• Chrome: الإعدادات → الخصوصية والأمان → إعدادات الموقع → الموقع
                                                                </li>
                                                                <li><strong>تأكد من استخدام HTTPS أو localhost</strong></li>
                                                                <li><strong>أعد تشغيل المتصفح بعد تغيير الإعدادات</strong></li>
                                                            </ol>
                                                        </div>
                                                        <pre style="background: #f8f9fa; padding: 15px; border-radius: 5px; font-size: 12px;">${debugText}</pre>
                                                        <div class="mt-3">
                                                            <button type="button" class="btn btn-primary" onclick="copyDebugInfo()">نسخ المعلومات</button>
                                                            <button type="button" class="btn btn-warning" onclick="testLocationPermission()">اختبار الإذن</button>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    `;
                                    
                                    // Remove existing modal
                                    const existingModal = document.getElementById('debugModal');
                                    if (existingModal) {
                                        existingModal.remove();
                                    }
                                    
                                    // Add new modal
                                    document.body.insertAdjacentHTML('beforeend', modalHtml);
                                    
                                    // Show modal
                                    const modal = new bootstrap.Modal(document.getElementById('debugModal'));
                                    modal.show();
                                    
                                    // Add helper functions
                                    window.copyDebugInfo = function() {
                                        navigator.clipboard.writeText(debugText).then(function() {
                                            alert('تم نسخ معلومات التشخيص');
                                        }).catch(function() {
                                            const textArea = document.createElement('textarea');
                                            textArea.value = debugText;
                                            document.body.appendChild(textArea);
                                            textArea.select();
                                            document.execCommand('copy');
                                            document.body.removeChild(textArea);
                                            alert('تم نسخ معلومات التشخيص');
                                        });
                                    };
                                    
                                    window.testLocationPermission = function() {
                                        if (navigator.geolocation) {
                                            navigator.geolocation.getCurrentPosition(
                                                function(position) {
                                                    alert(`اختبار ناجح!\nالمواقع: ${position.coords.latitude.toFixed(6)}, ${position.coords.longitude.toFixed(6)}`);
                                                },
                                                function(error) {
                                                    alert(`اختبار فاشل!\nالخطأ: ${error.message}\nالكود: ${error.code}`);
                                                },
                                                { timeout: 10000 }
                                            );
                                        } else {
                                            alert('المتصفح لا يدعم خدمات الموقع');
                                        }
                                    };
                                }
                            });
                        </script>