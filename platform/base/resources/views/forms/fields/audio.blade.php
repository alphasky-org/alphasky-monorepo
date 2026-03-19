@php
    $maxDuration = $options['max_duration'] ?? 300; // 5 minutes
    $audioFormat = $options['audio_format'] ?? 'audio/webm';
    $required = $options['required'] ?? false;
    $showWaveform = $options['show_waveform'] ?? true;
    $showClearButton = $options['show_clear_button'] ?? true;
    $showDownloadButton = $options['show_download_button'] ?? true;
    $recordButtonColor = $options['record_button_color'] ?? '#dc3545';
    $playButtonColor = $options['play_button_color'] ?? '#28a745';
    $fieldId = $name . '_audio_recorder';
    $audioId = $name . '_audio_player';
    $value = $options['value'] ?? null;
@endphp

<div class="mb-3">
        @if ($showLabel && $options['label'] !== false && $options['label_show'])
            {!! Form::customLabel($name, $options['label'], $options['label_attr']) !!}
        @endif
  
    
    <div class="audio-field-container" id="{{ $fieldId }}">
        <!-- Audio recorder controls -->
        <div class="audio-recorder-controls" style="border: 2px solid #ddd; border-radius: 8px; padding: 15px; background-color: #fff;">
            
            <!-- Recording status indicator -->
            <div class="recording-status mb-3" style="display: none;">
                <div class="d-flex align-items-center justify-content-center">
                    <div class="recording-pulse me-2"></div>
                    <span class="recording-text">{{ __('recording') }}...</span>
                    <span class="recording-timer ms-2">00:00</span>
                </div>
            </div>

            <!-- Waveform visualization -->
            @if($showWaveform)
                <div class="waveform-container mb-3" style="display: none;">
                    <canvas id="{{ $name }}_waveform" width="400" height="60" style="width: 100%; height: 60px; border: 1px solid #ddd; border-radius: 4px; background: #f8f9fa;"></canvas>
                </div>
            @endif

            <!-- Control buttons -->
            <div class="audio-controls d-flex flex-wrap gap-2 justify-content-center">
                <!-- Record/Stop button -->
                <button 
                    type="button" 
                    class="btn record-btn" 
                    id="{{ $name }}_record_btn"
                    data-field-name="{{ $name }}"
                    data-max-duration="{{ $maxDuration }}"
                    data-audio-format="{{ $audioFormat }}"
                    style="background-color: {{ $recordButtonColor }}; color: white; min-width: 100px;">
                    <i class="fas fa-microphone"></i>
                    <span class="btn-text">{{ __('record') }}</span>
                </button>

                <!-- Play/Pause button -->
                <button 
                    type="button" 
                    class="btn play-btn" 
                    id="{{ $name }}_play_btn"
                    data-field-name="{{ $name }}"
                    style="background-color: {{ $playButtonColor }}; color: white; min-width: 100px; display: none;">
                    <i class="fas fa-play"></i>
                    <span class="btn-text">{{ __('play') }}</span>
                </button>

                @if($showClearButton)
                    <!-- Clear button -->
                    <button 
                        type="button" 
                        class="btn btn-outline-danger clear-btn" 
                        id="{{ $name }}_clear_btn"
                        data-field-name="{{ $name }}"
                        style="min-width: 100px; display: none;">
                        <i class="fas fa-trash"></i>
                        <span class="btn-text">{{ __('clear audio') }}</span>
                    </button>
                @endif

                @if($showDownloadButton)
                    <!-- Download button -->
                    <button 
                        type="button" 
                        class="btn btn-outline-info download-btn" 
                        id="{{ $name }}_download_btn"
                        data-field-name="{{ $name }}"
                        style="min-width: 100px; display: none;">
                        <i class="fas fa-download"></i>
                        <span class="btn-text">{{ __('download') }}</span>
                    </button>
                @endif
            </div>

            <!-- Audio player for playback -->
            <div class="audio-player-section mt-3" style="display: none;">
                <audio 
                    id="{{ $audioId }}" 
                    controls 
                    class="w-100"
                    style="height: 40px;">
                    {{ __('your browser does not support audio playback') }}
                </audio>
            </div>

            <!-- Recording info -->
            <div class="recording-info mt-2" style="display: none;">
                <small class="text-muted">
                    {{ __('duration') }}: <span class="duration-text">00:00</span> |
                    {{ __('size') }}: <span class="size-text">0 KB</span> |
                    {{ __('format') }}: <span class="format-text">{{ $audioFormat }}</span>
                </small>
            </div>
        </div>
        
        <!-- Hidden field to store audio file path -->
        <input 
            type="hidden" 
            id="{{ $fieldId }}_data" 
            name="{{ $name }}" 
            value="{{ $value ?? '' }}"
            data-storage-mode="file"
            data-use-file-storage="true"
            data-upload-url="{{ route('audio.upload') }}"
            {!! Html::attributes($attributes ?? []) !!}
        >
        
        <!-- Current audio file preview (if exists) -->
        @if($value && !str_starts_with($value, 'data:audio/'))
            <div class="current-audio-preview mt-3 p-3" style="background: #f8f9fa; border-radius: 6px; border-left: 4px solid #28a745;">
                <label class="form-label text-success mb-2">
                    <i class="fas fa-volume-up me-1"></i>
                    {{ __('Current Recording') }}:
                </label>
                <div class="current-audio-player">
                    <audio controls class="w-100" style="height: 35px;">
                        <source src="{{ asset('storage/' . $value) }}" type="audio/webm">
                        <source src="{{ asset('storage/' . $value) }}" type="audio/wav">
                        <source src="{{ asset('storage/' . $value) }}" type="audio/mp4">
                        {{ __('Your browser does not support audio playback') }}
                    </audio>
                    <div class="mt-2 d-flex align-items-center justify-content-between">
                        <small class="text-muted">
                            <i class="fas fa-file-audio me-1"></i>
                            {{ basename($value) }}
                        </small>
                      
                    </div>
                </div>
            </div>
        @endif
    </div>
    
    <!-- Help text -->
    @if(isset($help_block))
        <small class="form-text text-muted">{!! $help_block['text'] ?? '' !!}</small>
    @endif
</div>

@once
    @push('footer')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize all audio recorder fields on the page
                const audioFields = document.querySelectorAll('.audio-field-container');
                
                audioFields.forEach(function(fieldContainer) {
                    const fieldId = fieldContainer.id;
                    const fieldName = fieldId.replace('_audio_recorder', '');
                    
                    const recordBtn = fieldContainer.querySelector(`#${fieldName}_record_btn`);
                    const playBtn = fieldContainer.querySelector(`#${fieldName}_play_btn`);
                    const clearBtn = fieldContainer.querySelector(`#${fieldName}_clear_btn`);
                    const downloadBtn = fieldContainer.querySelector(`#${fieldName}_download_btn`);
                    const audioPlayer = fieldContainer.querySelector(`#${fieldName}_audio_player`);
                    const hiddenField = fieldContainer.querySelector(`#${fieldId}_data`);
                    const recordingStatus = fieldContainer.querySelector('.recording-status');
                    const waveformContainer = fieldContainer.querySelector('.waveform-container');
                    const waveformCanvas = fieldContainer.querySelector(`#${fieldName}_waveform`);
                    const recordingInfo = fieldContainer.querySelector('.recording-info');
                    const audioPlayerSection = fieldContainer.querySelector('.audio-player-section');
                    
                    let mediaRecorder = null;
                    let audioChunks = [];
                    let recordingStartTime = 0;
                    let recordingTimer = null;
                    let audioContext = null;
                    let analyser = null;
                    let animationId = null;
                    
                    const maxDuration = parseInt(recordBtn.dataset.maxDuration) * 1000; // Convert to milliseconds
                    const audioFormat = recordBtn.dataset.audioFormat;

                    // Initialize waveform canvas
                    let waveformCtx = null;
                    if (waveformCanvas) {
                        waveformCtx = waveformCanvas.getContext('2d');
                    }

                    // Record button click handler
                    if (recordBtn) {
                        recordBtn.addEventListener('click', function() {
                            if (mediaRecorder && mediaRecorder.state === 'recording') {
                                stopRecording();
                            } else {
                                startRecording();
                            }
                        });
                    }

                    // Play button click handler
                    if (playBtn) {
                        playBtn.addEventListener('click', function() {
                            if (audioPlayer.paused) {
                                audioPlayer.play();
                                updatePlayButton(true);
                            } else {
                                audioPlayer.pause();
                                updatePlayButton(false);
                            }
                        });
                    }

                    // Clear button click handler
                    if (clearBtn) {
                        clearBtn.addEventListener('click', function() {
                            clearRecording();
                        });
                    }

                    // Download button click handler
                    if (downloadBtn) {
                        downloadBtn.addEventListener('click', function() {
                            downloadRecording();
                        });
                    }

                    // Audio player event listeners
                    if (audioPlayer) {
                        audioPlayer.addEventListener('ended', function() {
                            updatePlayButton(false);
                        });

                        audioPlayer.addEventListener('loadedmetadata', function() {
                            updateRecordingInfo();
                        });
                    }

                    async function startRecording() {
                        try {
                            const stream = await navigator.mediaDevices.getUserMedia({ 
                                audio: {
                                    echoCancellation: true,
                                    noiseSuppression: true,
                                    sampleRate: 44100
                                } 
                            });
                            
                            // Initialize audio context for waveform
                            if (waveformCanvas) {
                                audioContext = new (window.AudioContext || window.webkitAudioContext)();
                                analyser = audioContext.createAnalyser();
                                const source = audioContext.createMediaStreamSource(stream);
                                source.connect(analyser);
                                analyser.fftSize = 256;
                            }

                            mediaRecorder = new MediaRecorder(stream, {
                                mimeType: audioFormat.includes('webm') ? 'audio/webm' : 'audio/mp4'
                            });
                            
                            audioChunks = [];
                            recordingStartTime = Date.now();
                            
                            mediaRecorder.ondataavailable = function(event) {
                                if (event.data.size > 0) {
                                    audioChunks.push(event.data);
                                }
                            };

                            mediaRecorder.onstop = function() {
                                const audioBlob = new Blob(audioChunks, { type: audioFormat });
                                const audioUrl = URL.createObjectURL(audioBlob);
                                
                                // Check if file storage is enabled
                                const useFileStorage = hiddenField.getAttribute('data-use-file-storage') === 'true';
                                const uploadUrl = hiddenField.getAttribute('data-upload-url');
                                
                                if (useFileStorage && uploadUrl) {
                                    // Upload audio as file
                                    uploadAudioFile(audioBlob, fieldName, hiddenField, uploadUrl, audioUrl);
                                }
                                
                                // Stop all tracks to release microphone
                                stream.getTracks().forEach(track => track.stop());
                            };

                            mediaRecorder.start(100); // Collect data every 100ms
                            
                            // Update UI
                            updateRecordingUI(true);
                            startRecordingTimer();
                            
                            // Start waveform animation
                            if (waveformCanvas && analyser) {
                                drawWaveform();
                            }
                            
                            // Auto-stop after max duration
                            setTimeout(function() {
                                if (mediaRecorder && mediaRecorder.state === 'recording') {
                                    stopRecording();
                                }
                            }, maxDuration);
                            
                        } catch (error) {
                            console.error('{{ __('error accessing microphone') }}:', error);
                            alert('{{ __('cannot access microphone') }}');
                        }
                    }

                    function stopRecording() {
                        if (mediaRecorder && mediaRecorder.state === 'recording') {
                            mediaRecorder.stop();
                            updateRecordingUI(false);
                            stopRecordingTimer();
                            
                            if (animationId) {
                                cancelAnimationFrame(animationId);
                            }
                        }
                    }

                    function clearRecording() {
                        if (audioPlayer) {
                            audioPlayer.pause();
                            audioPlayer.src = '';
                        }
                        
                        hiddenField.value = '';
                        
                        // Hide controls
                        if (playBtn) playBtn.style.display = 'none';
                        if (clearBtn) clearBtn.style.display = 'none';
                        if (downloadBtn) downloadBtn.style.display = 'none';
                        if (audioPlayerSection) audioPlayerSection.style.display = 'none';
                        if (recordingInfo) recordingInfo.style.display = 'none';
                        
                        // Show record button
                        if (recordBtn) recordBtn.style.display = 'inline-block';
                        
                        // Success message
                        if (typeof Alphasky !== 'undefined' && Alphasky.showSuccess) {
                            Alphasky.showSuccess('{{ __('audio cleared successfully') }}');
                        }
                    }

                    function downloadRecording() {
                        if (hiddenField.value) {
                            const link = document.createElement('a');
                            link.href = hiddenField.value;
                            link.download = `recording_${Date.now()}.webm`;
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                        }
                    }

                    function updateRecordingUI(recording) {
                        if (recording) {
                            // Recording state
                            recordBtn.innerHTML = '<i class="fas fa-stop"></i> <span class="btn-text">{{ __('stop') }}</span>';
                            recordBtn.style.backgroundColor = '#6c757d';
                            
                            if (recordingStatus) recordingStatus.style.display = 'block';
                            if (waveformContainer) waveformContainer.style.display = 'block';
                        } else {
                            // Stopped state
                            recordBtn.innerHTML = '<i class="fas fa-microphone"></i> <span class="btn-text">{{ __('record') }}</span>';
                            recordBtn.style.backgroundColor = '{{ $recordButtonColor }}';
                            
                            if (recordingStatus) recordingStatus.style.display = 'none';
                            if (waveformContainer) waveformContainer.style.display = 'none';
                        }
                    }

                    function updatePlayButton(playing) {
                        if (playBtn) {
                            if (playing) {
                                playBtn.innerHTML = '<i class="fas fa-pause"></i> <span class="btn-text">{{ __('pause') }}</span>';
                                playBtn.style.backgroundColor = '#ffc107';
                            } else {
                                playBtn.innerHTML = '<i class="fas fa-play"></i> <span class="btn-text">{{ __('play') }}</span>';
                                playBtn.style.backgroundColor = '{{ $playButtonColor }}';
                            }
                        }
                    }

                    function showPlaybackControls() {
                        if (recordBtn) recordBtn.style.display = 'none';
                        if (playBtn) playBtn.style.display = 'inline-block';
                        if (clearBtn) clearBtn.style.display = 'inline-block';
                        if (downloadBtn) downloadBtn.style.display = 'inline-block';
                        if (audioPlayerSection) audioPlayerSection.style.display = 'block';
                        if (recordingInfo) recordingInfo.style.display = 'block';
                    }

                    function startRecordingTimer() {
                        recordingTimer = setInterval(function() {
                            const elapsed = Date.now() - recordingStartTime;
                            const seconds = Math.floor(elapsed / 1000);
                            const minutes = Math.floor(seconds / 60);
                            const displaySeconds = seconds % 60;
                            
                            const timeString = `${minutes.toString().padStart(2, '0')}:${displaySeconds.toString().padStart(2, '0')}`;
                            
                            const timerElement = fieldContainer.querySelector('.recording-timer');
                            if (timerElement) {
                                timerElement.textContent = timeString;
                            }
                        }, 1000);
                    }

                    function stopRecordingTimer() {
                        if (recordingTimer) {
                            clearInterval(recordingTimer);
                            recordingTimer = null;
                        }
                    }

                    function drawWaveform() {
                        if (!waveformCtx || !analyser) return;
                        
                        const bufferLength = analyser.frequencyBinCount;
                        const dataArray = new Uint8Array(bufferLength);
                        
                        function draw() {
                            animationId = requestAnimationFrame(draw);
                            
                            analyser.getByteFrequencyData(dataArray);
                            
                            waveformCtx.fillStyle = '#f8f9fa';
                            waveformCtx.fillRect(0, 0, waveformCanvas.width, waveformCanvas.height);
                            
                            const barWidth = (waveformCanvas.width / bufferLength) * 2.5;
                            let barHeight;
                            let x = 0;
                            
                            for (let i = 0; i < bufferLength; i++) {
                                barHeight = (dataArray[i] / 255) * waveformCanvas.height * 0.8;
                                
                                const gradient = waveformCtx.createLinearGradient(0, waveformCanvas.height - barHeight, 0, waveformCanvas.height);
                                gradient.addColorStop(0, '#007bff');
                                gradient.addColorStop(1, '#0056b3');
                                
                                waveformCtx.fillStyle = gradient;
                                waveformCtx.fillRect(x, waveformCanvas.height - barHeight, barWidth, barHeight);
                                
                                x += barWidth + 1;
                            }
                        }
                        
                        draw();
                    }

                    function updateRecordingInfo() {
                        if (audioPlayer && recordingInfo) {
                            const duration = audioPlayer.duration;
                            const minutes = Math.floor(duration / 60);
                            const seconds = Math.floor(duration % 60);
                            const durationText = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                            
                            const durationElement = recordingInfo.querySelector('.duration-text');
                            if (durationElement) {
                                durationElement.textContent = durationText;
                            }
                            
                            // Estimate file size (rough approximation)
                            const estimatedSize = Math.round((hiddenField.value.length * 0.75) / 1024); // KB
                            const sizeElement = recordingInfo.querySelector('.size-text');
                            if (sizeElement) {
                                sizeElement.textContent = estimatedSize + ' KB';
                            }
                        }
                    }
            // دالة رفع ملف الصوت
            function uploadAudioFile(audioBlob, fieldName, hiddenField, uploadUrl, audioUrl) {
                // إظهار مؤشر التحميل
                const container = hiddenField.closest('.audio-field-container');
                const loadingIndicator = document.createElement('div');
                loadingIndicator.className = 'audio-upload-loading';
                loadingIndicator.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __('Uploading audio...') }}';
                loadingIndicator.style.cssText = 'position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(255,255,255,0.9); padding: 10px; border-radius: 4px; z-index: 1000; font-size: 12px; border: 1px solid #ddd;';
                container.style.position = 'relative';
                container.appendChild(loadingIndicator);
                
                // إعداد البيانات للإرسال
                const formData = new FormData();
                formData.append('audio_file', audioBlob, `recording_${fieldName}_${Date.now()}.webm`);
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
                    // إخفاء مؤشر التحميل
                    if (loadingIndicator && loadingIndicator.parentNode) {
                        loadingIndicator.parentNode.removeChild(loadingIndicator);
                    }
                    
                    if (data.success) {
                       
                        // حفظ مسار الملف
                        hiddenField.value = data.file_path;
                        
                        // تحديث مشغل الصوت
                        const audioPlayer = container.querySelector('audio');
                        if (audioPlayer) {
                            console.log('File URL:', data.file_url);
                            audioPlayer.src = data.file_url;
                        }
                          // إظهار رسالة نجاح
                        if (typeof Alphasky !== 'undefined' && Alphasky.showSuccess) {
                            Alphasky.showSuccess(data.message);
                        }
                        
                        console.log('{{ __('audio uploaded successfully') }}');

                        // إظهار عناصر التحكم
                      
                          showPlaybackControls();
                        
                        
                      
                        
                    } else {
                        console.error('{{ __('Error uploading audio') }}:', data.message);
                        
                       
                     
                        
                        if (typeof Alphasky !== 'undefined' && Alphasky.showError) {
                            Alphasky.showError('{{ __('Audio upload failed, saved locally') }}: ' + data.message);
                        }
                    }
                })
                .catch(error => {
                    // إخفاء مؤشر التحميل
                    if (loadingIndicator && loadingIndicator.parentNode) {
                        loadingIndicator.parentNode.removeChild(loadingIndicator);
                    }
                    
                    console.error('{{ __('Network error') }}:', error);
                    
                    if (typeof Alphasky !== 'undefined' && Alphasky.showError) {
                        Alphasky.showError('{{ __('Connection issue, audio saved locally') }}');
                    }
                });
            }

                

                  
                });
            });


       
        </script>
        
        <style>
            .audio-field-container {
                position: relative;
            }
            
            .recording-pulse {
                width: 12px;
                height: 12px;
                background-color: #dc3545;
                border-radius: 50%;
                animation: pulse 1.5s ease-in-out infinite;
            }
            
            @keyframes pulse {
                0% {
                    transform: scale(1);
                    opacity: 1;
                }
                50% {
                    transform: scale(1.2);
                    opacity: 0.7;
                }
                100% {
                    transform: scale(1);
                    opacity: 1;
                }
            }
            
            .audio-controls .btn {
                transition: all 0.3s ease;
            }
            
            .audio-controls .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            }
            
            .recording-text {
                color: #dc3545;
                font-weight: bold;
            }
            
            .recording-timer {
                font-family: monospace;
                font-weight: bold;
            }
            
            @media (max-width: 768px) {
                .audio-controls {
                    flex-direction: column;
                }
                
                .audio-controls .btn {
                    width: 100%;
                    margin-bottom: 5px;
                }
            }
        </style>
    @endpush
@endonce