// Audio recording functionality
window.AudioRecorder = window.AudioRecorder || class AudioRecorder {
  constructor(fieldId) {
    this.fieldId = fieldId;
    this.mediaRecorder = null;
    this.audioChunks = [];
    this.recordedAudio = null;
    this.isRecording = false;
    this.isPaused = false;
    this.audioContext = null;
    this.analyser = null;
    this.animationId = null;
    this.startTime = null;
    this.pausedTime = 0;

    this.initializeElements();
    this.setupEventListeners();
  }

  initializeElements() {
    this.container = document.querySelector(
      `[data-field-id="${this.fieldId}"]`,
    );
    if (!this.container) {
      console.error(`Audio container not found for field: ${this.fieldId}`);
      return;
    }

    this.recordButton = this.container.querySelector(".record-btn");
    this.stopButton = this.container.querySelector(".stop-btn");
    this.playButton = this.container.querySelector(".play-btn");
    this.clearButton = this.container.querySelector(".clear-btn");
    this.waveformCanvas = this.container.querySelector(".waveform-canvas");
    this.timeDisplay = this.container.querySelector(".recording-time");
    this.statusText = this.container.querySelector(".recording-status");
    this.audioPreview = this.container.querySelector(".audio-preview");
    this.hiddenInput = this.container.querySelector('input[type="hidden"]');
    this.errorMessage = this.container.querySelector(".error-message");

    // Initialize canvas
    if (this.waveformCanvas) {
      this.canvasContext = this.waveformCanvas.getContext("2d");
      this.resizeCanvas();
    }
  }

  setupEventListeners() {
    if (this.recordButton) {
      this.recordButton.addEventListener("click", () => this.startRecording());
    }
    if (this.stopButton) {
      this.stopButton.addEventListener("click", () => this.stopRecording());
    }
    if (this.playButton) {
      this.playButton.addEventListener("click", () => this.togglePlayback());
    }
    if (this.clearButton) {
      this.clearButton.addEventListener("click", () => this.clearRecording());
    }

    // Handle window resize for canvas
    window.addEventListener("resize", () => this.resizeCanvas());
  }

  resizeCanvas() {
    if (this.waveformCanvas) {
      const rect = this.waveformCanvas.getBoundingClientRect();
      this.waveformCanvas.width = rect.width * 2; // High DPI
      this.waveformCanvas.height = rect.height * 2;
      this.waveformCanvas.style.width = rect.width + "px";
      this.waveformCanvas.style.height = rect.height + "px";
      this.canvasContext.scale(2, 2);
    }
  }

  async startRecording() {
    try {
      this.showStatus("جاري طلب إذن الوصول للميكروفون...", "info");

      const stream = await navigator.mediaDevices.getUserMedia({
        audio: {
          echoCancellation: true,
          noiseSuppression: true,
          autoGainControl: true,
          sampleRate: 44100,
        },
      });

      this.setupAudioContext(stream);
      this.setupMediaRecorder(stream);

      this.mediaRecorder.start(100); // Collect data every 100ms
      this.isRecording = true;
      this.startTime = Date.now();
      this.pausedTime = 0;

      this.updateUI("recording");
      this.showStatus("جاري التسجيل...", "recording");
      this.startTimer();
      this.startWaveformAnimation();
    } catch (error) {
      console.error("Error starting recording:", error);
      this.showError("خطأ في الوصول للميكروفون: " + error.message);
    }
  }

  setupAudioContext(stream) {
    this.audioContext = new (
      window.AudioContext || window.webkitAudioContext
    )();
    this.analyser = this.audioContext.createAnalyser();
    const source = this.audioContext.createMediaStreamSource(stream);
    source.connect(this.analyser);

    this.analyser.fftSize = 256;
    this.bufferLength = this.analyser.frequencyBinCount;
    this.dataArray = new Uint8Array(this.bufferLength);
  }

  setupMediaRecorder(stream) {
    // Try different MIME types
    const mimeTypes = [
      "audio/webm;codecs=opus",
      "audio/webm",
      "audio/ogg;codecs=opus",
      "audio/mp4",
      "audio/mpeg",
    ];

    let selectedMimeType = "";
    for (const mimeType of mimeTypes) {
      if (MediaRecorder.isTypeSupported(mimeType)) {
        selectedMimeType = mimeType;
        break;
      }
    }

    this.mediaRecorder = new MediaRecorder(stream, {
      mimeType: selectedMimeType || undefined,
    });

    this.audioChunks = [];

    this.mediaRecorder.ondataavailable = (event) => {
      if (event.data.size > 0) {
        this.audioChunks.push(event.data);
      }
    };

    this.mediaRecorder.onstop = () => {
      this.processRecording();
    };

    this.mediaRecorder.onerror = (error) => {
      console.error("MediaRecorder error:", error);
      this.showError("خطأ في التسجيل: " + error.error);
    };
  }

  stopRecording() {
    if (this.mediaRecorder && this.isRecording) {
      this.mediaRecorder.stop();
      this.isRecording = false;

      // Stop audio context
      if (this.audioContext) {
        this.audioContext.close();
        this.audioContext = null;
      }

      // Stop all tracks
      if (this.mediaRecorder.stream) {
        this.mediaRecorder.stream.getTracks().forEach((track) => track.stop());
      }

      this.stopTimer();
      this.stopWaveformAnimation();
      this.updateUI("processing");
      this.showStatus("جاري معالجة التسجيل...", "info");
    }
  }

  processRecording() {
    if (this.audioChunks.length === 0) {
      this.showError("لم يتم تسجيل أي صوت");
      this.updateUI("idle");
      return;
    }

    const audioBlob = new Blob(this.audioChunks, {
      type: this.mediaRecorder.mimeType || "audio/webm",
    });

    // Create audio URL for preview
    const audioUrl = URL.createObjectURL(audioBlob);
    this.recordedAudio = new Audio(audioUrl);

    // Check if using file storage mode
    const storageMode = this.hiddenInput.dataset.storageMode;

    if (storageMode === "file") {
      // Upload file to server storage
      this.uploadAudioFile(audioBlob, audioUrl);
    } else {
      // Legacy: Save as base64 (fallback)
      this.saveAsBase64(audioBlob, audioUrl);
    }
  }

  uploadAudioFile(audioBlob, audioUrl) {
    const formData = new FormData();
    const fileName = `audio_${Date.now()}_${Math.random().toString(36).substr(2, 9)}.webm`;
    formData.append("audio_file", audioBlob, fileName);
    formData.append("field_name", this.fieldId);
    formData.append(
      "_token",
      document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content") || "",
    );

    this.showStatus("جاري رفع الملف...", "info");

    fetch("/admin/audio/upload", {
      method: "POST",
      body: formData,
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Save file path instead of base64
          this.hiddenInput.value = data.file_path;
          this.createAudioPreview(audioUrl, data.file_path, true);
          this.updateUI("recorded");
          this.showStatus("تم رفع الملف بنجاح!", "success");
        } else {
          throw new Error(data.message || "فشل في رفع الملف");
        }
      })
      .catch((error) => {
        console.error("Upload error:", error);
        this.showError("خطأ في رفع الملف: " + error.message);
        // Fallback to base64 storage
        this.saveAsBase64(audioBlob, audioUrl);
      })
      .finally(() => {
        this.audioChunks = [];
      });
  }

  saveAsBase64(audioBlob, audioUrl) {
    // Legacy fallback method
    const reader = new FileReader();
    reader.onload = (e) => {
      const base64Audio = e.target.result;
      this.hiddenInput.value = base64Audio;
      this.createAudioPreview(audioUrl, base64Audio, false);
      this.updateUI("recorded");
      this.showStatus("تم التسجيل بنجاح!", "success");
      this.audioChunks = [];
    };

    reader.onerror = () => {
      this.showError("خطأ في معالجة التسجيل الصوتي");
      this.updateUI("idle");
    };

    reader.readAsDataURL(audioBlob);
  }

  createAudioPreview(audioUrl, audioData, isFile = false) {
    if (!this.audioPreview) return;

    const duration = this.getCurrentDuration();
    let size = 0;
    let displayInfo = "";

    if (isFile) {
      // File mode - show file path info
      const fileName = audioData.split("/").pop();
      displayInfo = `ملف صوتي - ${duration} - ${fileName}`;

      // Try to get file size from server response or estimate
      size = Math.round(audioUrl.size / 1024) || "غير معروف";
    } else {
      // Base64 mode - calculate from data
      size = Math.round(audioData.length / 1024);
      displayInfo = `تسجيل صوتي - ${duration} - ${size} KB`;
    }

    this.audioPreview.innerHTML = `
            <div class="audio-preview-content">
                <div class="audio-info">
                    <i class="fas fa-microphone text-success me-2"></i>
                    <span class="audio-details">
                        ${displayInfo}
                    </span>
                </div>
                <audio controls class="audio-player w-100 mt-2">
                    <source src="${audioUrl}" type="${this.mediaRecorder?.mimeType || "audio/webm"}">
                    <source src="${audioUrl}" type="audio/wav">
                    <source src="${audioUrl}" type="audio/mp4">
                    متصفحك لا يدعم تشغيل الصوت
                </audio>
                ${
                  isFile
                    ? `
                <div class="mt-2">
                    <a href="${audioUrl}" download class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-download me-1"></i>تحميل الملف
                    </a>
                </div>
                `
                    : ""
                }
            </div>
        `;

    // Setup audio events
    const audioElement = this.audioPreview.querySelector("audio");
    if (audioElement) {
      audioElement.addEventListener("play", () => {
        this.playButton.innerHTML = '<i class="fas fa-pause"></i>';
        this.playButton.classList.remove("btn-success");
        this.playButton.classList.add("btn-warning");
      });

      audioElement.addEventListener("pause", () => {
        this.playButton.innerHTML = '<i class="fas fa-play"></i>';
        this.playButton.classList.remove("btn-warning");
        this.playButton.classList.add("btn-success");
      });

      audioElement.addEventListener("ended", () => {
        this.playButton.innerHTML = '<i class="fas fa-play"></i>';
        this.playButton.classList.remove("btn-warning");
        this.playButton.classList.add("btn-success");
      });
    }
  }

  togglePlayback() {
    const audioElement = this.audioPreview?.querySelector("audio");
    if (audioElement) {
      if (audioElement.paused) {
        audioElement.play();
      } else {
        audioElement.pause();
      }
    }
  }

  clearRecording() {
    // Stop any ongoing recording
    if (this.isRecording) {
      this.stopRecording();
    }

    // Delete file from server if it's a file path
    const currentValue = this.hiddenInput.value;
    if (
      currentValue &&
      !currentValue.startsWith("data:audio/") &&
      this.hiddenInput.dataset.storageMode === "file"
    ) {
      this.deleteAudioFile(currentValue);
    }

    // Clear audio data
    this.recordedAudio = null;
    this.audioChunks = [];
    this.hiddenInput.value = "";

    // Clear preview
    if (this.audioPreview) {
      this.audioPreview.innerHTML = "";
    }

    // Reset UI
    this.updateUI("idle");
    this.clearCanvas();
    this.resetTimer();
    this.showStatus("", "");
    this.clearError();
  }

  deleteAudioFile(filePath) {
    if (!filePath || filePath.startsWith("data:audio/")) {
      return;
    }

    fetch("/admin/audio/delete", {
      method: "DELETE",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
        "X-CSRF-TOKEN":
          document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content") || "",
      },
      body: JSON.stringify({
        file_path: filePath,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          console.log("Audio file deleted successfully:", filePath);
        } else {
          console.warn("Failed to delete audio file:", data.message);
        }
      })
      .catch((error) => {
        console.error("Error deleting audio file:", error);
      });
  }

  updateUI(state) {
    // Reset all buttons
    this.recordButton?.classList.remove("d-none");
    this.stopButton?.classList.add("d-none");
    this.playButton?.classList.add("d-none");
    this.clearButton?.classList.add("d-none");

    // Remove all button states
    [
      this.recordButton,
      this.stopButton,
      this.playButton,
      this.clearButton,
    ].forEach((btn) => {
      if (btn) {
        btn.classList.remove(
          "btn-danger",
          "btn-warning",
          "btn-success",
          "btn-secondary",
        );
      }
    });

    switch (state) {
      case "idle":
        if (this.recordButton) {
          this.recordButton.classList.add("btn-danger");
          this.recordButton.innerHTML =
            '<i class="fas fa-microphone"></i> ابدأ التسجيل';
        }
        break;

      case "recording":
        if (this.recordButton) {
          this.recordButton.classList.add("d-none");
        }
        if (this.stopButton) {
          this.stopButton.classList.remove("d-none");
          this.stopButton.classList.add("btn-warning");
          this.stopButton.innerHTML =
            '<i class="fas fa-stop"></i> إيقاف التسجيل';
        }
        break;

      case "processing":
        if (this.stopButton) {
          this.stopButton.innerHTML =
            '<i class="fas fa-spinner fa-spin"></i> جاري المعالجة...';
          this.stopButton.disabled = true;
        }
        break;

      case "recorded":
        if (this.stopButton) {
          this.stopButton.classList.add("d-none");
          this.stopButton.disabled = false;
        }
        if (this.recordButton) {
          this.recordButton.classList.remove("d-none");
          this.recordButton.classList.add("btn-secondary");
          this.recordButton.innerHTML =
            '<i class="fas fa-redo"></i> إعادة تسجيل';
        }
        if (this.playButton) {
          this.playButton.classList.remove("d-none");
          this.playButton.classList.add("btn-success");
          this.playButton.innerHTML = '<i class="fas fa-play"></i>';
        }
        if (this.clearButton) {
          this.clearButton.classList.remove("d-none");
          this.clearButton.classList.add("btn-danger");
        }
        break;
    }
  }

  startTimer() {
    this.timerInterval = setInterval(() => {
      if (this.timeDisplay) {
        const elapsed = Date.now() - this.startTime - this.pausedTime;
        const seconds = Math.floor(elapsed / 1000);
        const minutes = Math.floor(seconds / 60);
        const displaySeconds = seconds % 60;
        this.timeDisplay.textContent = `${minutes.toString().padStart(2, "0")}:${displaySeconds.toString().padStart(2, "0")}`;
      }
    }, 1000);
  }

  stopTimer() {
    if (this.timerInterval) {
      clearInterval(this.timerInterval);
    }
  }

  resetTimer() {
    this.stopTimer();
    if (this.timeDisplay) {
      this.timeDisplay.textContent = "00:00";
    }
  }

  getCurrentDuration() {
    if (this.timeDisplay) {
      return this.timeDisplay.textContent;
    }
    return "00:00";
  }

  startWaveformAnimation() {
    if (!this.analyser || !this.canvasContext) return;

    const animate = () => {
      if (!this.isRecording) return;

      this.animationId = requestAnimationFrame(animate);
      this.analyser.getByteFrequencyData(this.dataArray);
      this.drawWaveform();
    };

    animate();
  }

  stopWaveformAnimation() {
    if (this.animationId) {
      cancelAnimationFrame(this.animationId);
      this.animationId = null;
    }
  }

  drawWaveform() {
    if (!this.canvasContext || !this.waveformCanvas) return;

    const canvas = this.waveformCanvas;
    const ctx = this.canvasContext;
    const width = canvas.width / 2; // Account for scaling
    const height = canvas.height / 2;

    // Clear canvas
    ctx.clearRect(0, 0, width, height);

    // Create gradient
    const gradient = ctx.createLinearGradient(0, 0, 0, height);
    gradient.addColorStop(0, "rgba(74, 144, 226, 0.8)");
    gradient.addColorStop(0.5, "rgba(80, 200, 120, 0.6)");
    gradient.addColorStop(1, "rgba(255, 193, 7, 0.4)");

    // Draw waveform
    const barWidth = (width / this.bufferLength) * 2.5;
    let barHeight;
    let x = 0;

    for (let i = 0; i < this.bufferLength; i++) {
      barHeight = (this.dataArray[i] / 255) * height * 0.8;

      ctx.fillStyle = gradient;
      ctx.fillRect(x, height - barHeight, barWidth, barHeight);

      x += barWidth + 1;
    }

    // Add center line
    ctx.strokeStyle = "rgba(255, 255, 255, 0.3)";
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(0, height / 2);
    ctx.lineTo(width, height / 2);
    ctx.stroke();
  }

  clearCanvas() {
    if (this.canvasContext && this.waveformCanvas) {
      const width = this.waveformCanvas.width / 2;
      const height = this.waveformCanvas.height / 2;
      this.canvasContext.clearRect(0, 0, width, height);
    }
  }

  showStatus(message, type) {
    if (this.statusText) {
      this.statusText.textContent = message;
      this.statusText.className = `recording-status text-${this.getStatusClass(type)}`;
    }
  }

  getStatusClass(type) {
    switch (type) {
      case "recording":
        return "danger";
      case "success":
        return "success";
      case "info":
        return "info";
      case "warning":
        return "warning";
      default:
        return "muted";
    }
  }

  showError(message) {
    if (this.errorMessage) {
      this.errorMessage.textContent = message;
      this.errorMessage.style.display = "block";
    }
    this.updateUI("idle");
  }

  clearError() {
    if (this.errorMessage) {
      this.errorMessage.textContent = "";
      this.errorMessage.style.display = "none";
    }
  }
};

function autoloadAfterLoad() {
  // This function can be used to initialize any JavaScript code that needs to run after the page has loaded.
  // For example, you can initialize plugins or set up event listeners here.
  $("*[data-relevant]").each(function () {
    var relevant = $(this).attr("data-relevant");
    if (relevant) {
      var evalx = eval("if(" + relevant + "){e= true;} else {e= false;}");
      console.log(relevant, evalx);
      if (evalx) {
        $(this).closest(".col-lg-12, .col-lg-6").show();
        $(this).closest(".meta-boxes").show();
      } else {
        $(this).closest(".col-lg-12, .col-lg-6").hide();
        $(this).closest(".meta-boxes").hide();
      }
    }
  });
}

function starsLoad() {
  $("[data-stars]").html("");
  $("[data-stars]").each(function () {
    var x = $(this).attr("data-stars");
    for (i = 0; i < 5; i++) {
      if (i >= x) {
        $(this).append('<i class="fas fa-star"></i>');
      } else {
        $(this).append('<i class="fas fa-star color-yellow"></i>');
      }
    }
  });
}

function showSignatureModal(img) {
  // Create modal HTML
  const modalHtml = `
                    <div id="signatureModal" class="modal fade" tabindex="-1" role="dialog" style="z-index: 9999;">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">عرض التوقيع</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-center">
                                    <img src="${img.src}" alt="التوقيع" style="max-width: 100%; height: auto; border: 1px solid #dee2e6; border-radius: 8px;">
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                                    <a href="${img.src}" download="signature.png" class="btn btn-primary">تحميل التوقيع</a>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

  // Remove existing modal
  const existingModal = document.getElementById("signatureModal");
  if (existingModal) {
    existingModal.remove();
  }

  // Add modal to body
  document.body.insertAdjacentHTML("beforeend", modalHtml);

  // Show modal
  const modal = new bootstrap.Modal(document.getElementById("signatureModal"));
  modal.show();

  // Clean up after modal is hidden
  document
    .getElementById("signatureModal")
    .addEventListener("hidden.bs.modal", function () {
      this.remove();
    });
}

// Audio playback functions for tables
function toggleAudioPlayback(button) {
  const audioContainer = button.closest(".audio-player-container");
  const audio = audioContainer.querySelector("audio");
  const icon = button.querySelector("i");

  if (audio.paused) {
    // Pause all other audio players first
    const allAudios = document.querySelectorAll(
      ".audio-player-container audio",
    );
    allAudios.forEach(function (otherAudio) {
      if (otherAudio !== audio && !otherAudio.paused) {
        otherAudio.pause();
        const otherButton = otherAudio
          .closest(".audio-player-container")
          .querySelector(".play-pause-btn");
        if (otherButton) {
          const otherIcon = otherButton.querySelector("i");
          otherIcon.className = "fas fa-play";
          otherButton.classList.remove("btn-warning");
          otherButton.classList.add("btn-primary");
        }
      }
    });

    audio.play();
    icon.className = "fas fa-pause";
    button.classList.remove("btn-primary");
    button.classList.add("btn-warning");

    // Add visual feedback
    audioContainer.style.background =
      "linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%)";
  } else {
    audio.pause();
    icon.className = "fas fa-play";
    button.classList.remove("btn-warning");
    button.classList.add("btn-primary");

    // Remove visual feedback
    audioContainer.style.background =
      "linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%)";
  }

  audio.addEventListener("ended", function () {
    icon.className = "fas fa-play";
    button.classList.remove("btn-warning");
    button.classList.add("btn-primary");
    audioContainer.style.background =
      "linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%)";
  });
}

function showAudioModal(button) {
  const audioContainer = button.closest(".audio-player-container");
  const audio = audioContainer.querySelector("audio");
  const source = audio.querySelector("source");

  // Create modal HTML
  const modalHtml = `
                    <div id="audioModal" class="modal fade" tabindex="-1" role="dialog" style="z-index: 9999;">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">
                                        <i class="fas fa-volume-up me-2"></i>
                                        تفاصيل التسجيل الصوتي
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="text-center mb-3">
                                        <div class="audio-visualization mb-3">
                                            <div class="audio-waves">
                                                <div class="wave"></div>
                                                <div class="wave"></div>
                                                <div class="wave"></div>
                                                <div class="wave"></div>
                                                <div class="wave"></div>
                                            </div>
                                        </div>
                                        <audio id="audioModalPlayer" controls class="w-100" style="height: 50px; border-radius: 8px;">
                                            <source src="${source.src}" type="audio/webm">
                                            <source src="${source.src}" type="audio/mp4">
                                            متصفحك لا يدعم تشغيل الصوت
                                        </audio>
                                    </div>
                                    
                                    <div class="audio-info-card">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="info-item">
                                                    <i class="fas fa-clock text-primary"></i>
                                                    <span class="info-label">المدة:</span>
                                                    <span class="info-value" id="audioDuration">جاري التحميل...</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="info-item">
                                                    <i class="fas fa-file-audio text-success"></i>
                                                    <span class="info-label">النوع:</span>
                                                    <span class="info-value">Audio</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="info-item">
                                                    <i class="fas fa-database text-info"></i>
                                                    <span class="info-label">الحجم:</span>
                                                    <span class="info-value" id="audioSize">${audio.dataset.audioLength || "غير معروف"} حرف</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="info-item">
                                                    <i class="fas fa-check-circle text-success"></i>
                                                    <span class="info-label">الحالة:</span>
                                                    <span class="info-value">${audio.dataset.audioValid === "true" ? "صحيح" : "غير صحيح"}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        <i class="fas fa-times me-1"></i>إغلاق
                                    </button>
                                    <a href="${source.src}" download="audio_recording.webm" class="btn btn-primary">
                                        <i class="fas fa-download me-1"></i>تحميل التسجيل
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

  // Remove existing modal
  const existingModal = document.getElementById("audioModal");
  if (existingModal) {
    existingModal.remove();
  }

  // Add modal to body
  document.body.insertAdjacentHTML("beforeend", modalHtml);

  // Show modal
  const modal = new bootstrap.Modal(document.getElementById("audioModal"));
  modal.show();

  // Update duration when loaded
  const modalPlayer = document.getElementById("audioModalPlayer");
  modalPlayer.addEventListener("loadedmetadata", function () {
    const duration = modalPlayer.duration;
    const minutes = Math.floor(duration / 60);
    const seconds = Math.floor(duration % 60);
    const durationText = `${minutes.toString().padStart(2, "0")}:${seconds.toString().padStart(2, "0")}`;
    document.getElementById("audioDuration").textContent = durationText;
  });

  // Clean up after modal is hidden
  document
    .getElementById("audioModal")
    .addEventListener("hidden.bs.modal", function () {
      this.remove();
    });

  // Add CSS for audio visualization
  if (!document.getElementById("audioModalCSS")) {
    const style = document.createElement("style");
    style.id = "audioModalCSS";
    style.textContent = `
                        .audio-visualization {
                            display: flex;
                            justify-content: center;
                            align-items: center;
                            height: 60px;
                            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                            border-radius: 8px;
                            margin-bottom: 15px;
                        }
                        
                        .audio-waves {
                            display: flex;
                            gap: 4px;
                            align-items: center;
                        }
                        
                        .audio-waves .wave {
                            width: 4px;
                            height: 20px;
                            background: rgba(255,255,255,0.8);
                            border-radius: 2px;
                            animation: audioWave 1.5s ease-in-out infinite;
                        }
                        
                        .audio-waves .wave:nth-child(2) { animation-delay: 0.1s; }
                        .audio-waves .wave:nth-child(3) { animation-delay: 0.2s; }
                        .audio-waves .wave:nth-child(4) { animation-delay: 0.3s; }
                        .audio-waves .wave:nth-child(5) { animation-delay: 0.4s; }
                        
                        @keyframes audioWave {
                            0%, 100% { height: 20px; }
                            50% { height: 40px; }
                        }
                        
                        .audio-info-card {
                            background: #f8f9fa;
                            border-radius: 8px;
                            padding: 20px;
                            border: 1px solid #dee2e6;
                        }
                        
                        .info-item {
                            display: flex;
                            align-items: center;
                            margin-bottom: 10px;
                        }
                        
                        .info-item i {
                            width: 20px;
                            margin-left: 10px;
                        }
                        
                        .info-label {
                            font-weight: 500;
                            margin-left: 8px;
                            color: #495057;
                        }
                        
                        .info-value {
                            color: #212529;
                            font-weight: 600;
                        }
                    `;
    document.head.appendChild(style);
  }
}


// Initialize audio recorders
function initializeAudioRecorders() {
  document.querySelectorAll(".audio-field-container").forEach((container) => {
    const fieldId = container.dataset.fieldId;
    if (fieldId) {
      new window.AudioRecorder(fieldId);
    }
  });
}

$(document).ready(function () {
  autoloadAfterLoad();
  $(document).on("keyup click scroll", function (event) {
    autoloadAfterLoad();
  });

  starsLoad();

  // Initialize audio recorders
  initializeAudioRecorders();
});
