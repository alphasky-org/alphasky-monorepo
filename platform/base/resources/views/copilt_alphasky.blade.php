@if(auth()->check() && auth()->user()->isSuperUser())  
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ui = {
                toggleButton: document.getElementById('ai-copilot-toggle-button'),
                closeButton: document.getElementById('ai-copilot-close-button'),
                sendButton: document.getElementById('ai-copilot-send-button'),
                attachButton: document.getElementById('ai-copilot-attach-button'),
                micButton: document.getElementById('ai-copilot-mic-button'),
                panel: document.getElementById('ai-copilot-panel'),
                resizer: document.getElementById('ai-copilot-resizer'),
                chatBody: document.getElementById('ai-copilot-panel-body'),
                input: document.getElementById('ai-copilot-input'),
                iframe: document.getElementById('content-iframe'),
                fileInput: document.getElementById('ai-copilot-file-input'),
                attachmentsPreview: document.getElementById('ai-copilot-attachments-preview'),
            };

            let attachedFiles = [];
            let isResizing = false;
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            let recognition;
            let isRecording = false;

            if (SpeechRecognition) {
                recognition = new SpeechRecognition();
                recognition.continuous = true;
                recognition.interimResults = true;
                recognition.onresult = (event) => {
                    let interimTranscript = '';
                    let finalTranscript = '';
                    for (let i = event.resultIndex; i < event.results.length; ++i) {
                        if (event.results[i].isFinal) {
                            finalTranscript += event.results[i][0].transcript;
                        } else {
                            interimTranscript += event.results[i][0].transcript;
                        }
                    }
                    ui.input.value = finalTranscript + interimTranscript;
                };
            }

            function addMessage(text, sender) {
                const messageElement = document.createElement('div');
                messageElement.classList.add('chat-message', sender === 'user' ? 'user-message' : 'bot-message');
                messageElement.textContent = text;
                ui.chatBody.appendChild(messageElement);
                ui.chatBody.scrollTop = ui.chatBody.scrollHeight;
            }

            function sendMessage() {
                const text = ui.input.value.trim();
                if (text === '' && attachedFiles.length === 0) return;

                if (text !== '') {
                    addMessage(text, 'user');
                }
                if (attachedFiles.length > 0) {
                    addMessage(`Attached ${attachedFiles.length} file(s): ${attachedFiles.map(f => f.name).join(', ')}`, 'user');
                }
                
                ui.input.value = '';
                attachedFiles = [];
                updateAttachmentsPreview();

                setTimeout(() => addMessage('I have received your command. Refreshing the dashboard...', 'bot'), 500);
                ui.iframe.src = ui.iframe.src.split('#')[0];
            }

            function updateAttachmentsPreview() {
                ui.attachmentsPreview.innerHTML = '';
                attachedFiles.forEach((file, index) => {
                    const item = document.createElement('div');
                    item.className = 'attachment-preview-item';
                    const nameSpan = document.createElement('span');
                    nameSpan.textContent = file.name;
                    const removeBtn = document.createElement('button');
                    removeBtn.innerHTML = '&times;';
                    removeBtn.onclick = () => {
                        attachedFiles.splice(index, 1);
                        updateAttachmentsPreview();
                    };
                    item.appendChild(nameSpan);
                    item.appendChild(removeBtn);
                    ui.attachmentsPreview.appendChild(item);
                });
            }

            function toggleVoiceRecognition() {
                if (!recognition) {
                    alert('Speech recognition is not supported in your browser.');
                    return;
                }
                if (isRecording) {
                    recognition.stop();
                    ui.micButton.classList.remove('recording');
                } else {
                    recognition.start();
                    ui.micButton.classList.add('recording');
                }
                isRecording = !isRecording;
            }

            function show(element) {
                element.style.display = 'flex';
                setTimeout(() => {
                    element.classList.add('open');
                }, 10);
            }

            function hide(element) {
                element.classList.remove('open');
                setTimeout(() => {
                    element.style.display = 'none';
                }, 300); // Match transition duration
            }

            ui.toggleButton.addEventListener('click', () => show(ui.panel));
            ui.closeButton.addEventListener('click', () => hide(ui.panel));
            ui.sendButton.addEventListener('click', sendMessage);
        
            ui.input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault(); // يمنع إضافة سطر جديد عند الضغط على Enter فقط
                    sendMessage();
                }
            });

            ui.input.addEventListener('input', () => {
                // يجعل الحقل ينمو مع الكتابة
                ui.input.style.height = 'auto';
                ui.input.style.height = (ui.input.scrollHeight) + 'px';
            });
            ui.attachButton.addEventListener('click', () => ui.fileInput.click());
            ui.micButton.addEventListener('click', toggleVoiceRecognition);

            ui.fileInput.addEventListener('change', (e) => {
                attachedFiles.push(...e.target.files);
                updateAttachmentsPreview();
                ui.fileInput.value = ''; // Reset for next selection
            });

            ui.resizer.addEventListener('mousedown', (e) => {
                isResizing = true;
                document.body.style.userSelect = 'none';
                document.body.style.pointerEvents = 'none';
            });
            document.addEventListener('mousemove', (e) => {
                if (!isResizing) return;
                const newWidth = window.innerWidth - e.clientX;
                if (newWidth > 300 && newWidth < window.innerWidth * 0.9) {
                    ui.panel.style.width = newWidth + 'px';
                }
            });
            document.addEventListener('mouseup', () => {
                isResizing = false;
                document.body.style.userSelect = '';
                document.body.style.pointerEvents = '';
            });

            addMessage('Welcome! How can I help you today?', 'bot');
        });
    </script>
 

  
    <div id="ai-copilot-panel">
        <div id="ai-copilot-resizer"></div>
        <div id="ai-copilot-panel-header">
            <h5>AI Alphasky Copilot</h5>
            <button id="ai-copilot-close-button">&times;</button>
        </div>
        <div id="ai-copilot-panel-body"></div>
        <div id="ai-copilot-panel-footer">
            <div id="ai-copilot-attachments-preview"></div>
            <div class="ai-copilot-input-container">
                <textarea id="ai-copilot-input" placeholder="Type a message... (Shift+Enter for new line)" rows="1"></textarea>
                <div class="ai-copilot-input-buttons">
                    <button id="ai-copilot-attach-button" title="Attach File"><i class="fa fa-paperclip"></i></button>
                    <button id="ai-copilot-mic-button" title="Record Voice"><i class="fa fa-microphone"></i></button>
                    <button id="ai-copilot-send-button" title="Send"><i class="fa fa-paper-plane"></i></button>
                </div>
            </div>
            <input type="file" id="ai-copilot-file-input" multiple style="display: none;" accept="image/*,application/pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt">
        </div>
    </div>

@endif

