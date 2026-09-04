@if(auth()->check() && auth()->user()->isSuperUser() && env('enable_ai_copilot', false) && env('copilot_user_id') == auth()->id())
<style>
    .bot-message.ai-copilot-working-message {
      position: relative;
    overflow: hidden;
    border: 1px solid rgba(13, 110, 253, 0.35);
    box-shadow: 0 0 0 1px rgba(13, 110, 253, 0.08), 0px 2px 2px 0px rgba(13, 110, 253, 0.10);
    animation: ai-copilot-working-pulse 1.35s cubic-bezier(0.11, -0.2, 0.03, 0.64) infinite;
    display: table;
    }

    .bot-message.ai-copilot-working-message::after {
      content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(110deg, #fcfcfc1a 36%, rgb(255 255 255 / 25%) 68% 20%, transparent 31%);
    transform: translateX(-120%);
    animation: ai-copilot-working-shine 1.65s cubic-bezier(0.85, 0.46, 0.17, 0.75) infinite;
    pointer-events: none;
    }

    @keyframes ai-copilot-working-pulse {
        0%, 100% { border-color: rgba(13, 110, 253, 0.24); }
        50% { border-color: rgba(13, 110, 253, 0.65); }
    }

    @keyframes ai-copilot-working-shine {
        0% { transform: translateX(-120%); }
        100% { transform: translateX(120%); }
    }

    .chat-message {
        position: relative;
        padding-inline-end: 82px;
    }

    .ai-copilot-message-actions {
        position: absolute;
        top: 4px;
        inset-inline-end: 6px;
        display: inline-flex;
        gap: 3px;
        opacity: 0;
        pointer-events: none;
        transition: opacity .15s ease;
        z-index: 3;
    }

    .chat-message:hover .ai-copilot-message-actions,
    .chat-message:focus-within .ai-copilot-message-actions {
        opacity: 1;
        pointer-events: auto;
    }

    .ai-copilot-message-action {
        width: 22px;
        height: 22px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(17, 24, 39, .12);
        border-radius: 5px;
        background: rgba(255, 255, 255, .92);
        color: #374151;
        cursor: pointer;
        font-size: 11px;
        line-height: 1;
    }

    .ai-copilot-message-editor {
        width: 100%;
        min-height: 58px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        padding: 7px 8px;
        resize: vertical;
        outline: none;
        font: inherit;
    }

    .ai-copilot-message-editor-actions {
        display: flex;
        gap: 6px;
        margin-top: 8px;
        flex-wrap: wrap;
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const i18n = {
            activationTitle: @json(__('core/base::system.alphasky_copilot.activation_title')),
            activationRequired: @json(__('core/base::system.alphasky_copilot.activation_required')),
            activationPlaceholder: @json(__('core/base::system.alphasky_copilot.activation_placeholder')),
            activateButton: @json(__('core/base::system.alphasky_copilot.activate_button')),
            enterActivationFirst: @json(__('core/base::system.alphasky_copilot.enter_activation_first')),
            activating: @json(__('core/base::system.alphasky_copilot.activating')),
            activationFailed: @json(__('core/base::system.alphasky_copilot.activation_failed')),
            invalidServerData: @json(__('core/base::system.alphasky_copilot.invalid_server_data')),
            invalidMd5: @json(__('core/base::system.alphasky_copilot.invalid_md5')),
            activationSuccess: @json(__('core/base::system.alphasky_copilot.activation_success')),
            activationConnectionError: @json(__('core/base::system.alphasky_copilot.activation_connection_error')),
            speechNotSupported: @json(__('core/base::system.alphasky_copilot.speech_not_supported')),
            attachedFiles: @json(__('core/base::system.alphasky_copilot.attached_files')),
            botWorking: @json(__('core/base::system.alphasky_copilot.bot_working')),
            taskCompleted: @json(__('core/base::system.alphasky_copilot.task_completed')),
            connectionError: @json(__('core/base::system.alphasky_copilot.connection_error')),
            welcome: @json(__('core/base::system.alphasky_copilot.welcome')),
            stopTask: @json(__('core/base::system.alphasky_copilot.stop_task')),
            stopTitle: @json(__('core/base::system.alphasky_copilot.stop_title')),
            sendTitle: @json(__('core/base::system.alphasky_copilot.send_title')),
            pullingPluginPackage: @json(__('core/base::system.alphasky_copilot.pulling_plugin_package')),
            pluginInstallFailed: @json(__('core/base::system.alphasky_copilot.plugin_install_failed')),
            pluginInstalled: @json(__('core/base::system.alphasky_copilot.plugin_installed')),
            pluginActionQuestion: @json(__('core/base::system.alphasky_copilot.plugin_action_question')),
            selectedAnswer: @json(__('core/base::system.alphasky_copilot.selected_answer')),
            executingPluginCommands: @json(__('core/base::system.alphasky_copilot.executing_plugin_commands')),
            pluginCommandsFailed: @json(__('core/base::system.alphasky_copilot.plugin_commands_failed')),
            pluginCommandsSuccess: @json(__('core/base::system.alphasky_copilot.plugin_commands_success')),
            activatePlugin: @json(__('core/base::system.alphasky_copilot.activate_plugin')),
            updatePlugin: @json(__('core/base::system.alphasky_copilot.update_plugin')),
            no: @json(__('core/base::system.alphasky_copilot.no')),
            sendAnswerFailed: @json(__('core/base::system.alphasky_copilot.send_answer_failed')),
            customAnswerPlaceholder: @json(__('core/base::system.alphasky_copilot.custom_answer_placeholder')),
            send: @json(__('core/base::system.alphasky_copilot.send')),
            pluginPackagePullFailed: @json(__('core/base::system.alphasky_copilot.plugin_package_pull_failed')),
            menuAddModule: @json(__('core/base::system.alphasky_copilot.menu_add_module')),
            menuEditModule: @json(__('core/base::system.alphasky_copilot.menu_edit_module')),
            menuAsk: @json(__('core/base::system.alphasky_copilot.menu_ask')),
            resendMessage: @json(__('core/base::system.alphasky_copilot.resend_message')),
            editMessage: @json(__('core/base::system.alphasky_copilot.edit_message')),
            deleteMessage: @json(__('core/base::system.alphasky_copilot.delete_message')),
            saveMessage: @json(__('core/base::system.alphasky_copilot.save_message')),
            cancelMessage: @json(__('core/base::system.alphasky_copilot.cancel_message')),
            messageUpdateFailed: @json(__('core/base::system.alphasky_copilot.message_update_failed')),
            messageDeleteFailed: @json(__('core/base::system.alphasky_copilot.message_delete_failed')),
        };

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
            activationOverlay: document.getElementById('ai-copilot-activation-overlay'),
            activationInput: document.getElementById('ai-copilot-activation-input'),
            activationButton: document.getElementById('ai-copilot-activation-button'),
            activationStatus: document.getElementById('ai-copilot-activation-status'),
            tokenBalance: document.getElementById('ai-copilot-token-balance'),
        };

        let attachedFiles = [];
        let isResizing = false;
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        let recognition;
        let isRecording = false;
        let activeEventSource = null;
        let isTaskRunning = false;
        let activeConversationToken = '';
        let activeConversationSurveyId = 0;
        let activeWorkingMessage = null;

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

        function isActivated() {
            const activateAlphasky = localStorage.getItem('activateAlphasky');
            const alphaskyKey = (localStorage.getItem('alphaskyKey') || '').trim();

            return activateAlphasky === '1' && alphaskyKey !== '';
        }

        function clearWorkingMessage() {
            if (activeWorkingMessage) {
                activeWorkingMessage.classList.remove('ai-copilot-working-message');
                activeWorkingMessage = null;
            }
        }

        function setWorkingMessage(messageElement) {
            clearWorkingMessage();

            if (messageElement) {
                messageElement.classList.add('ai-copilot-working-message');
                activeWorkingMessage = messageElement;
            }
        }

        function getMessageText(messageElement) {
            return messageElement.querySelector('.ai-copilot-message-text')?.textContent || '';
        }

        function setMessageText(messageElement, text) {
            const textElement = messageElement.querySelector('.ai-copilot-message-text');

            if (textElement) {
                textElement.textContent = text;
            }
        }

        function syncConversationMessage(messageElement, action, chat = '') {
            const chatId = Number(messageElement.dataset.chatId || 0);

            if (!chatId || !activeConversationToken) {
                return Promise.resolve({});
            }

            return postJson('{{ route('system.alphasky-conversation-message') }}', {
                action,
                id: chatId,
                token: activeConversationToken,
                chat,
                alphasky_key: (localStorage.getItem('alphaskyKey') || '').trim(),
                domain: window.location.hostname,
            }).then(async (response) => {
                const result = await response.json().catch(() => ({}));

                if (!response.ok || result.error) {
                    throw new Error(result.message || (action === 'delete' ? i18n.messageDeleteFailed : i18n.messageUpdateFailed));
                }

                return result;
            });
        }

        function resendChatMessage(messageElement) {
            const text = getMessageText(messageElement).trim();

            if (text === '' || isTaskRunning) {
                return;
            }

            ui.input.value = text;
            ui.input.dispatchEvent(new Event('input'));
            sendMessage();
        }

        function editChatMessage(messageElement) {
            if (messageElement.dataset.editing === '1') {
                return;
            }

            const originalText = getMessageText(messageElement);
            const textElement = messageElement.querySelector('.ai-copilot-message-text');
            const actionsElement = messageElement.querySelector('.ai-copilot-message-actions');
            const editor = document.createElement('textarea');
            const editorActions = document.createElement('div');
            const saveButton = document.createElement('button');
            const cancelButton = document.createElement('button');

            messageElement.dataset.editing = '1';
            editor.className = 'ai-copilot-message-editor';
            editor.value = originalText;
            editorActions.className = 'ai-copilot-message-editor-actions';
            saveButton.type = 'button';
            saveButton.className = 'btn btn-sm btn-primary';
            saveButton.textContent = i18n.saveMessage;
            cancelButton.type = 'button';
            cancelButton.className = 'btn btn-sm btn-secondary';
            cancelButton.textContent = i18n.cancelMessage;

            const finishEditing = () => {
                editorActions.remove();
                editor.replaceWith(textElement);
                messageElement.dataset.editing = '0';

                if (actionsElement) {
                    actionsElement.style.display = '';
                }
            };

            saveButton.onclick = async () => {
                const nextText = editor.value.trim();

                if (nextText === '') {
                    return;
                }

                saveButton.disabled = true;

                try {
                    await syncConversationMessage(messageElement, 'update', nextText);
                    setMessageText(messageElement, nextText);
                    finishEditing();
                } catch (error) {
                    addMessage(error.message || i18n.messageUpdateFailed, 'bot');
                    saveButton.disabled = false;
                }
            };

            cancelButton.onclick = finishEditing;
            editorActions.appendChild(saveButton);
            editorActions.appendChild(cancelButton);

            if (actionsElement) {
                actionsElement.style.display = 'none';
            }

            textElement.replaceWith(editor);
            messageElement.appendChild(editorActions);
            editor.focus();
            editor.select();
        }

        async function deleteChatMessage(messageElement) {
            try {
                await syncConversationMessage(messageElement, 'delete');
                messageElement.remove();
            } catch (error) {
                addMessage(error.message || i18n.messageDeleteFailed, 'bot');
            }
        }

        function appendMessageActions(messageElement) {
            const actionsElement = document.createElement('div');
            const actions = [
                { title: i18n.resendMessage, icon: 'fa fa-repeat', handler: () => resendChatMessage(messageElement) },
                { title: i18n.editMessage, icon: 'fa fa-pencil', handler: () => editChatMessage(messageElement) },
                { title: i18n.deleteMessage, icon: 'fa fa-trash', handler: () => deleteChatMessage(messageElement) },
            ];

            actionsElement.className = 'ai-copilot-message-actions';

            actions.forEach((action) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'ai-copilot-message-action';
                button.title = action.title;
                button.innerHTML = `<i class="${action.icon}"></i>`;
                button.onclick = (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    action.handler();
                };
                actionsElement.appendChild(button);
            });

            messageElement.appendChild(actionsElement);
        }

        function addMessage(text, sender, options = {}) {
            const messageElement = document.createElement('div');
            const textElement = document.createElement('span');

            messageElement.classList.add('chat-message', sender === 'user' ? 'user-message' : 'bot-message');
            messageElement.dataset.chatId = options.chatId ? String(options.chatId) : '';
            textElement.className = 'ai-copilot-message-text';
            textElement.textContent = text;
            messageElement.appendChild(textElement);
            appendMessageActions(messageElement);
            ui.chatBody.appendChild(messageElement);
            ui.chatBody.scrollTop = ui.chatBody.scrollHeight;

            if (sender === 'bot' && isTaskRunning && text !== i18n.taskCompleted) {
                setWorkingMessage(messageElement);
            }

            return messageElement;
        }

        function csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        }

        function updateTokenBalance(tokenCount) {
            const normalizedTokenCount = Number(tokenCount || 0);
            localStorage.setItem('alphaskyTokenCount', String(Math.max(0, normalizedTokenCount)));
            ui.tokenBalance.textContent = `Tokens: ${Math.max(0, normalizedTokenCount).toLocaleString()}`;
        }

        function setTaskRunning(running) {
            isTaskRunning = running;
            ui.input.disabled = running;
            ui.attachButton.disabled = running;
            ui.micButton.disabled = running;

            if (!running) {
                clearWorkingMessage();
            }

            const sendIcon = ui.sendButton.querySelector('i');
            if (sendIcon) {
                sendIcon.className = running ? 'fa fa-stop' : 'fa fa-paper-plane';
            }

            ui.sendButton.title = running ? i18n.stopTitle : i18n.sendTitle;
        }

        function stopCurrentTask() {
            if (activeEventSource) {
                activeEventSource.close();
                activeEventSource = null;
            }

            setTaskRunning(false);
            addMessage(i18n.stopTask, 'bot');
        }

        function startNewConversation() {
            const conversationSelect = document.getElementById('ai-copilot-menu');

            if (activeEventSource) {
                activeEventSource.close();
                activeEventSource = null;
                setTaskRunning(false);
            }

            activeConversationToken = '';
            activeConversationSurveyId = 0;
            conversationSelect.value = '__new__';
            ui.chatBody.innerHTML = '';
            addMessage(i18n.welcome, 'bot');
        }

        function logoutAlphaskyCopilot() {
            if (activeEventSource) {
                activeEventSource.close();
                activeEventSource = null;
            }

            localStorage.removeItem('activateAlphasky');
            localStorage.removeItem('alphaskyKey');
            localStorage.removeItem('alphaskyTokenCount');
            activeConversationToken = '';
            activeConversationSurveyId = 0;
            setTaskRunning(false);
            ui.chatBody.innerHTML = '';
            showActivationOverlay();
        }

        function sendPromptAnswer(requestId, answer) {
            return fetch('{{ route('system.alphasky-answer') }}', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({
                    request_id: requestId,
                    answer: answer,
                    alphasky_key: (localStorage.getItem('alphaskyKey') || '').trim(),
                    domain: window.location.hostname,
                }),
            });
        }

        function postJson(url, payload) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify(payload),
            });
        }

        async function postEventStream(url, payload, onMessage) {
            const abortController = new AbortController();
            activeEventSource = {
                close: () => abortController.abort(),
            };

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    Accept: 'text/event-stream',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify(payload),
                signal: abortController.signal,
            });

            if (!response.ok || !response.body) {
                throw new Error(i18n.connectionError);
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';

            while (true) {
                const { value, done } = await reader.read();

                if (done) {
                    break;
                }

                buffer += decoder.decode(value, { stream: true }).replace(/\r\n|\r/g, '\n');

                let eventEnd = buffer.indexOf('\n\n');

                while (eventEnd !== -1) {
                    const eventText = buffer.slice(0, eventEnd);
                    buffer = buffer.slice(eventEnd + 2);
                    const dataText = eventText
                        .split('\n')
                        .filter((line) => line.startsWith('data:'))
                        .map((line) => line.slice(5).trimStart())
                        .join('\n');

                    if (dataText !== '') {
                        onMessage(JSON.parse(dataText));
                    }

                    eventEnd = buffer.indexOf('\n\n');
                }
            }
        }

        async function handlePluginPackage(data) {
            const beforeInstallCommands = Array.isArray(data.before_install_commands) ? data.before_install_commands : [];

            for (const command of beforeInstallCommands) {
                await handlePluginCommand({
                    ...command,
                    reload: false,
                });
            }

            addMessage(i18n.pullingPluginPackage, 'bot');

            const installResponse = await postJson('{{ route('system.alphasky-install-plugin') }}', {
                survey_id: data.survey_id,
                module_name: data.module_name,
                path: data.path,
            });
            const installResult = await installResponse.json().catch(() => ({}));

            if (!installResponse.ok || installResult.error) {
                throw new Error(installResult.message || i18n.pluginInstallFailed);
            }

            addMessage(installResult.message || i18n.pluginInstalled, 'bot');
            renderLocalPluginActionQuestion(installResult);
        }

        async function handlePluginCommand(data) {
            addMessage(i18n.executingPluginCommands, 'bot');

            const commandResponse = await postJson('{{ route('system.alphasky-plugin-command') }}', {
                module_name: data.module_name,
                action: data.action,
                drop_tables: Array.isArray(data.drop_tables) ? data.drop_tables : [],
                ignore_missing: data.ignore_missing === true,
            });
            const commandResult = await commandResponse.json().catch(() => ({}));

            if (!commandResponse.ok || commandResult.error) {
                throw new Error(commandResult.message || i18n.pluginCommandsFailed);
            }

            addMessage(commandResult.message || i18n.pluginCommandsSuccess, 'bot');

            if (data.reload !== false) {
                setTimeout(() => {
                    window.location.reload();
                }, 1200);
            }
        }

        async function handlePluginAsset(data) {
            addMessage(i18n.executingPluginCommands, 'bot');

            const assetResponse = await postJson('{{ route('system.alphasky-plugin-asset') }}', {
                module_name: data.module_name,
                path: data.path,
                content: data.content,
                encoding: data.encoding || 'base64',
            });
            const assetResult = await assetResponse.json().catch(() => ({}));

            if (!assetResponse.ok || assetResult.error) {
                throw new Error(assetResult.message || i18n.pluginCommandsFailed);
            }

            addMessage(assetResult.message || i18n.pluginCommandsSuccess, 'bot');
        }

        async function handleClientAiToolRequest(data) {
            addMessage(i18n.executingPluginCommands, 'bot');

            const toolResponse = await postJson('{{ route('system.alphasky-plugin-ai-tool') }}', {
                tool: data.tool,
                params: data.params || {},
            });
            const toolResult = await toolResponse.json().catch(() => ({}));

            if (!toolResponse.ok || toolResult.error) {
                throw new Error(toolResult.message || i18n.pluginCommandsFailed);
            }

            if (data.request_id) {
                const answerResponse = await sendPromptAnswer(data.request_id, JSON.stringify(toolResult));
                const answerResult = await answerResponse.json().catch(() => ({}));

                if (!answerResponse.ok || answerResult.error) {
                    throw new Error(answerResult.message || i18n.sendAnswerFailed);
                }
            }

            addMessage(toolResult.message || i18n.pluginCommandsSuccess, 'bot');
        }

        function renderLocalPluginActionQuestion(pluginData) {
            const questionElement = document.createElement('div');
            questionElement.classList.add('chat-message', 'bot-message');
            questionElement.appendChild(document.createTextNode(pluginData.question || i18n.pluginActionQuestion));

            const buttonsWrapper = document.createElement('div');
            buttonsWrapper.style.display = 'flex';
            buttonsWrapper.style.gap = '8px';
            buttonsWrapper.style.marginTop = '10px';
            buttonsWrapper.style.flexWrap = 'wrap';

            let answered = false;

            const setAnswered = (label) => {
                answered = true;
                buttonsWrapper.replaceChildren(document.createTextNode(i18n.selectedAnswer.replace(':answer', label)));
                const reply = document.createElement('div');
                reply.classList.add('chat-message', 'user-message');
                reply.textContent = label;
                ui.chatBody.appendChild(reply);
                ui.chatBody.scrollTop = ui.chatBody.scrollHeight;
            };

            const yesButton = document.createElement('button');
            yesButton.type = 'button';
            yesButton.className = 'btn btn-sm btn-primary';
            yesButton.textContent = pluginData.is_active ? i18n.updatePlugin : i18n.activatePlugin;
            yesButton.onclick = async () => {
                if (answered) {
                    return;
                }

                setAnswered(yesButton.textContent);
                addMessage(i18n.executingPluginCommands, 'bot');

                try {
                    const applyResponse = await postJson('{{ route('system.alphasky-apply-plugin') }}', {
                        module_name: pluginData.module_name,
                    });
                    const applyResult = await applyResponse.json().catch(() => ({}));

                    if (!applyResponse.ok || applyResult.error) {
                        throw new Error(applyResult.message || i18n.pluginCommandsFailed);
                    }

                    addMessage(applyResult.message || i18n.pluginCommandsSuccess, 'bot');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1200);
                } catch (error) {
                    addMessage(error.message || i18n.pluginCommandsFailed, 'bot');
                }
            };

            const noButton = document.createElement('button');
            noButton.type = 'button';
            noButton.className = 'btn btn-sm btn-secondary';
            noButton.textContent = i18n.no;
            noButton.onclick = () => {
                if (!answered) {
                    setAnswered(i18n.no);
                }
            };

            buttonsWrapper.appendChild(yesButton);
            buttonsWrapper.appendChild(noButton);
            questionElement.appendChild(buttonsWrapper);
            ui.chatBody.appendChild(questionElement);
            ui.chatBody.scrollTop = ui.chatBody.scrollHeight;
        }

        function renderQuestion(data, eventSource) {
            const questionElement = document.createElement('div');
            questionElement.classList.add('chat-message', 'bot-message');
            questionElement.appendChild(document.createTextNode(data.message || ''));

            const buttonsWrapper = document.createElement('div');
            buttonsWrapper.style.display = 'flex';
            buttonsWrapper.style.gap = '8px';
            buttonsWrapper.style.marginTop = '10px';
            buttonsWrapper.style.flexWrap = 'wrap';

            let answered = false;

            const submitAnswer = async (answer) => {
                if (answered) {
                    return;
                }

                answered = true;

                try {
                    const response = await sendPromptAnswer(data.request_id, answer);
                    const result = await response.json().catch(() => ({}));

                    if (!response.ok || result.error) {
                        throw new Error(result.message || i18n.sendAnswerFailed);
                    }

                    buttonsWrapper.replaceChildren(document.createTextNode(i18n.selectedAnswer.replace(':answer', answer)));
                    const reply = document.createElement('div');
                    reply.classList.add('chat-message', 'user-message');
                    reply.textContent = String(answer);
                    ui.chatBody.appendChild(reply);
                    ui.chatBody.scrollTop = ui.chatBody.scrollHeight;
                } catch (error) {
                    console.error('Failed to submit answer:', error);
                    answered = false;
                    addMessage(error.message || i18n.sendAnswerFailed, 'bot');
                }
            };

            (Array.isArray(data.options) ? data.options : ['1', '2']).forEach((option) => {
                const optionData = typeof option === 'object' && option !== null ? option : {
                    label: String(option),
                    value: String(option),
                    type: 'button',
                };

                if (optionData.type === 'text' || optionData.value === '__custom__') {
                    const textInput = document.createElement('input');
                    textInput.type = 'text';
                    textInput.placeholder = optionData.label || i18n.customAnswerPlaceholder;
                    textInput.style.minWidth = '180px';
                    textInput.style.padding = '6px 8px';
                    textInput.style.border = '1px solid #d1d5db';
                    textInput.style.borderRadius = '6px';

                    const textButton = document.createElement('button');
                    textButton.type = 'button';
                    textButton.className = 'btn btn-sm btn-primary';
                    textButton.textContent = i18n.send;
                    textButton.onclick = () => {
                        const answer = textInput.value.trim();

                        if (answer !== '') {
                            submitAnswer(answer);
                        }
                    };

                    textInput.addEventListener('keydown', (event) => {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            textButton.click();
                        }
                    });

                    buttonsWrapper.appendChild(textInput);
                    buttonsWrapper.appendChild(textButton);

                    return;
                }

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn btn-sm btn-primary';
                button.textContent = String(optionData.label || optionData.value || '');
                button.onclick = () => submitAnswer(String(optionData.value || optionData.label || ''));

                buttonsWrapper.appendChild(button);
            });

            questionElement.appendChild(buttonsWrapper);
            ui.chatBody.appendChild(questionElement);
            ui.chatBody.scrollTop = ui.chatBody.scrollHeight;
        }

        function showActivationOverlay() {
            ui.activationOverlay.style.display = 'flex';
            ui.activationStatus.textContent = '';
        }

        function hideActivationOverlay() {
            ui.activationOverlay.style.display = 'none';
            ui.activationStatus.textContent = '';
        }
        async function requestActivation(key) {
            const response = await fetch(`{{ rtrim((string) env('SERVER_ALPHASKY_URL', ''), '/') . '/api/v1/activatekey' }}?key=${encodeURIComponent(key)}`, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-API-KEY': 'Seec0aw0MUAB4ITMf6N1gp2TIEdhOXw6',
                },
            });

            const result = await response.json();

            if (!response.ok || result.error) {
                throw new Error(result.message || i18n.activationFailed);
            }

            const activatedValue = String(result?.data?.activateAlphasky ?? '');
            const returnedKey = String(result?.data?.alphaskyKey ?? '').trim();

            if (activatedValue !== '1' || !returnedKey) {
                throw new Error(i18n.invalidServerData);
            }

            localStorage.setItem('activateAlphasky', activatedValue);
            localStorage.setItem('alphaskyKey', returnedKey);
            updateTokenBalance(result?.data?.token_count || 0);

            return result;
        }

        async function verifyStoredActivation() {
            const storedKey = (localStorage.getItem('alphaskyKey') || '').trim();

            if (!storedKey) {
                return false;
            }

            try {
                await requestActivation(storedKey);
                return true;
            } catch (error) {
                localStorage.removeItem('activateAlphasky');
                localStorage.removeItem('alphaskyKey');
                return false;
            }
        }

        async function activateAlphaskyKey() {
            const key = (ui.activationInput.value || '').trim();

            if (!key) {
                ui.activationStatus.textContent = i18n.enterActivationFirst;
                ui.activationStatus.style.color = '#dc3545';
                return;
            }

            ui.activationButton.disabled = true;
            ui.activationStatus.textContent = i18n.activating;
            ui.activationStatus.style.color = '#6c757d';

            try {
                await requestActivation(key);

                ui.activationStatus.textContent = i18n.activationSuccess;
                ui.activationStatus.style.color = '#198754';
                activeConversationToken = '';
                activeConversationSurveyId = 0;
                hideActivationOverlay();
                ui.chatBody.innerHTML = '';
                addMessage(i18n.welcome, 'bot');
                loadConversations();
            } catch (error) {
                ui.activationStatus.textContent = error.message || i18n.activationConnectionError;
                ui.activationStatus.style.color = '#dc3545';
            } finally {
                ui.activationButton.disabled = false;
            }
        }

        async function sendMessage() {
            if (isTaskRunning) {
                stopCurrentTask();
                return;
            }

            const text = ui.input.value.trim();
            const conversationSelect = document.getElementById('ai-copilot-menu');

            if (text === '' && attachedFiles.length === 0) {
                return;
            }

            if (conversationSelect.value === '__new__') {
                activeConversationToken = '';
                activeConversationSurveyId = 0;
            }

            setTaskRunning(true);

            if (text !== '') {
                addMessage(text, 'user');
            }

            if (attachedFiles.length > 0) {
                addMessage(
                    i18n.attachedFiles
                        .replace(':count', attachedFiles.length)
                        .replace(':files', attachedFiles.map((f) => f.name).join(', ')),
                    'user'
                );
            }

            const lastBotMsg = document.createElement('div');
            lastBotMsg.classList.add('chat-message', 'bot-message');
            lastBotMsg.textContent = i18n.botWorking;
            ui.chatBody.appendChild(lastBotMsg);
            ui.chatBody.scrollTop = ui.chatBody.scrollHeight;
            setWorkingMessage(lastBotMsg);
            let hasReceivedStreamMessage = false;

            const removeWorkingMessage = () => {
                if (!hasReceivedStreamMessage && lastBotMsg.parentNode) {
                    lastBotMsg.remove();
                }

                hasReceivedStreamMessage = true;
            };

            try {
                const handleStreamMessage = (data) => {
                    if (data.token_count !== undefined) {
                        updateTokenBalance(data.token_count);
                    }

                    if (data.type === 'token_usage') {
                        return;
                    }

                    removeWorkingMessage();

                    if (data.conversation_token) {
                        activeConversationToken = data.conversation_token;
                    }

                    if (data.surveys_id !== undefined) {
                        activeConversationSurveyId = Number(data.surveys_id) || 0;
                    }

                    if (data.type === 'question') {
                        renderQuestion(data, activeEventSource);
                        return;
                    }

                    if (data.type === 'plugin_package') {
                        handlePluginPackage(data).catch((error) => {
                            console.error('Failed to install plugin package:', error);
                            addMessage(error.message || i18n.pluginPackagePullFailed, 'bot');
                        });
                        return;
                    }

                    if (data.type === 'plugin_command') {
                        handlePluginCommand(data).catch((error) => {
                            console.error('Failed to run plugin command:', error);
                            addMessage(error.message || i18n.pluginCommandsFailed, 'bot');
                        });
                        return;
                    }

                    if (data.type === 'plugin_asset') {
                        handlePluginAsset(data).catch((error) => {
                            console.error('Failed to write plugin asset:', error);
                            addMessage(error.message || i18n.pluginCommandsFailed, 'bot');
                        });
                        return;
                    }

                    if (data.type === 'client_ai_tool_request') {
                        handleClientAiToolRequest(data).catch((error) => {
                            console.error('Failed to execute client AI tool:', error);
                            addMessage(error.message || i18n.pluginCommandsFailed, 'bot');
                        });
                        return;
                    }

                    addMessage(data.message, 'bot', { chatId: data.chat_id });

                    if (data.message === i18n.taskCompleted) {
                        clearWorkingMessage();
                        activeEventSource = null;
                        setTaskRunning(false);
                        loadConversations(activeConversationToken);
                    }
                };

                await postEventStream('{{ route('system.alphasky') }}', {
                    userInput: text,
                    key: 'conversation',
                    surveys_id: activeConversationSurveyId,
                    conversation_token: activeConversationToken,
                    alphasky_key: (localStorage.getItem('alphaskyKey') || '').trim(),
                    domain: window.location.hostname,
                }, handleStreamMessage);
            } catch (error) {
                if (error.name === 'AbortError') {
                    return;
                }

                console.error('Error:', error);
                lastBotMsg.textContent = i18n.connectionError;
                activeEventSource = null;
                setTaskRunning(false);
            }

            ui.input.value = '';
            attachedFiles = [];
            updateAttachmentsPreview();
        }

        async function loadConversations(selectedToken = activeConversationToken) {
            const conversationSelect = document.getElementById('ai-copilot-menu');
            const response = await fetch('{{ route('system.alphasky-conversations') }}', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({
                    alphasky_key: (localStorage.getItem('alphaskyKey') || '').trim(),
                    domain: window.location.hostname,
                }),
            });
            const result = await response.json().catch(() => ({ data: [] }));
            const conversations = Array.isArray(result.data) ? result.data : [];

            conversationSelect.innerHTML = '';

            const newOption = document.createElement('option');
            newOption.value = '__new__';
            newOption.dataset.surveysId = '0';
            newOption.textContent = @json(__('core/base::system.alphasky_copilot.new_conversation'));
            conversationSelect.appendChild(newOption);

            conversations.forEach((conversation) => {
                const option = document.createElement('option');
                option.value = conversation.token;
                option.dataset.surveysId = String(conversation.surveys_id || 0);
                option.textContent = conversation.title;
                conversationSelect.appendChild(option);
            });

            if (selectedToken && conversations.some((conversation) => conversation.token === selectedToken)) {
                conversationSelect.value = selectedToken;
            } else {
                conversationSelect.value = '__new__';
            }
        }

        async function loadConversationMessages(token) {
            if (!token) {
                ui.chatBody.innerHTML = '';
                activeConversationToken = '';
                activeConversationSurveyId = 0;
                addMessage(i18n.welcome, 'bot');
                return;
            }

            const response = await fetch('{{ route('system.alphasky-conversation') }}', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({
                    token,
                    alphasky_key: (localStorage.getItem('alphaskyKey') || '').trim(),
                    domain: window.location.hostname,
                }),
            });
            const result = await response.json().catch(() => ({ data: [] }));
            const messages = Array.isArray(result.data) ? result.data : [];
            const selectedOption = Array.from(document.getElementById('ai-copilot-menu').options)
                .find((option) => option.value === token);

            activeConversationToken = token;
            activeConversationSurveyId = Number(selectedOption?.dataset?.surveysId || messages.at(-1)?.surveys_id || 0);
            ui.chatBody.innerHTML = '';

            messages.forEach((message) => {
                addMessage(message.chat, String(message.speaker_type) === '1' ? 'user' : 'bot', { chatId: message.id });
            });
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
                alert(i18n.speechNotSupported);
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
            }, 300);
        }

        ui.toggleButton.addEventListener('click', () => show(ui.panel));
        ui.closeButton.addEventListener('click', () => hide(ui.panel));
        ui.sendButton.addEventListener('click', sendMessage);
        document.getElementById('ai-copilot-new-conversation-button').addEventListener('click', startNewConversation);
        document.getElementById('ai-copilot-logout-button').addEventListener('click', logoutAlphaskyCopilot);

        ui.input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey && !isTaskRunning) {
                e.preventDefault();
                sendMessage();
            }
        });

        ui.input.addEventListener('input', () => {
            ui.input.style.height = 'auto';
            ui.input.style.height = `${ui.input.scrollHeight}px`;
        });

        ui.attachButton.addEventListener('click', () => ui.fileInput.click());
        ui.micButton.addEventListener('click', toggleVoiceRecognition);

        ui.fileInput.addEventListener('change', (e) => {
            attachedFiles.push(...e.target.files);
            updateAttachmentsPreview();
            ui.fileInput.value = '';
        });

        document.getElementById('ai-copilot-menu').addEventListener('change', (event) => {
            loadConversationMessages(event.target.value === '__new__' ? '' : event.target.value);
        });

        ui.resizer.addEventListener('mousedown', () => {
            isResizing = true;
            document.body.style.userSelect = 'none';
            document.body.style.pointerEvents = 'none';
        });

        document.addEventListener('mousemove', (e) => {
            if (!isResizing) {
                return;
            }

            const newWidth = window.innerWidth - e.clientX;
            if (newWidth > 300 && newWidth < window.innerWidth * 0.9) {
                ui.panel.style.width = `${newWidth}px`;
            }
        });

        document.addEventListener('mouseup', () => {
            isResizing = false;
            document.body.style.userSelect = '';
            document.body.style.pointerEvents = '';
        });

        ui.activationButton.addEventListener('click', activateAlphaskyKey);
        ui.activationInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                activateAlphaskyKey();
            }
        });

        (async function initActivationState() {
            if (!isActivated()) {
                showActivationOverlay();
                addMessage(i18n.welcome, 'bot');
                loadConversations();
                return;
            }

            showActivationOverlay();
            ui.activationButton.disabled = true;
            ui.activationStatus.textContent = i18n.activating;
            ui.activationStatus.style.color = '#6c757d';

            const verified = await verifyStoredActivation();
            ui.activationButton.disabled = false;

            if (verified) {
                hideActivationOverlay();
                updateTokenBalance(localStorage.getItem('alphaskyTokenCount') || 0);
            } else {
                showActivationOverlay();
                ui.activationStatus.textContent = i18n.activationFailed;
                ui.activationStatus.style.color = '#dc3545';
            }

            addMessage(i18n.welcome, 'bot');
            loadConversations();
        })();
    });
</script>

<div id="ai-copilot-panel">
    <div id="ai-copilot-resizer"></div>
    <div id="ai-copilot-activation-overlay" style="position:absolute; inset:0; z-index:9999; background:rgba(255,255,255,0.96); display:none; align-items:center; justify-content:center; padding:20px; text-align:center;">
        <div style="width:min(480px, 100%); background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:20px; box-shadow:0 10px 30px rgba(0,0,0,0.08);">
            <div style="font-size:18px; font-weight:700; margin-bottom:8px; color:#111827;">{{ __('core/base::system.alphasky_copilot.activation_title') }}</div>
            <p style="margin:0 0 14px; color:#374151;">{{ __('core/base::system.alphasky_copilot.activation_required') }}</p>
            <div style="display:flex; gap:8px; align-items:center;">
                <input id="ai-copilot-activation-input" type="text" placeholder="{{ __('core/base::system.alphasky_copilot.activation_placeholder') }}" style="flex:1; padding:10px 12px; border:1px solid #d1d5db; border-radius:8px; outline:none;" />
                <button id="ai-copilot-activation-button" type="button" style="padding:10px 14px; border:none; background:#111827; color:#fff; border-radius:8px; cursor:pointer;">{{ __('core/base::system.alphasky_copilot.activate_button') }}</button>
            </div>
            <div id="ai-copilot-activation-status" style="min-height:20px; margin-top:10px; font-size:13px;"></div>
        </div>
    </div>

    <div id="ai-copilot-panel-header" style="display:flex; flex-direction:column; gap:8px; padding:10px 12px; border-bottom:1px solid #e5e7eb; background:#fff;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;min-width:0;">
            <span style="font-size: 12px;font-weight:700;letter-spacing:.04em;color:#6b7280;text-transform:uppercase;line-height:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;text-align: center;display: block;">AI Alphasky Copilot</span>
            <div style="display:flex; align-items:center; gap:6px; flex-shrink:0;">
                    <button id="ai-copilot-new-conversation-button" type="button" title="{{ __('core/base::system.alphasky_copilot.new_conversation') }}" style="width:30px; height:30px; display:inline-flex; align-items:center; justify-content:center; border:1px solid #e5e7eb; border-radius:7px; background:#f9fafb; color:#111827; cursor:pointer;">
                    <i class="fa fa-plus"></i>
                </button>
               
                <button id="ai-copilot-logout-button" type="button" title="تسجيل الخروج" style="width:30px; height:30px; display:inline-flex; align-items:center; justify-content:center; border:1px solid #e5e7eb; border-radius:7px; background:#f9fafb; color:#374151; cursor:pointer;">
                    <i class="fa fa-sign-out"></i>
                </button>
             <button id="ai-copilot-close-button" type="button" title="إغلاق" style="width:30px;height:30px;display:inline-flex;align-items:center;justify-content:center;border:1px solid #e5e7eb;border-radius:7px;background:#111827;color:#fff;cursor:pointer;font-size:16px;line-height:1;z-index: 9999999;">
                    <i class="fa fa-times"></i>
                </button>
            </div>
        </div>
        <div style="position:relative; display:flex; align-items:center; width:100%; min-width:0;">
            <select id="ai-copilot-menu" style="width:100%; min-width:0; height:32px; padding:0 28px 0 10px; border:1px solid #e5e7eb; border-radius:7px; background:#f9fafb; color:#111827; font-size:12px; font-weight:600; outline:none; cursor:pointer; appearance:auto;">
                <option value="__new__">{{ __('core/base::system.alphasky_copilot.new_conversation') }}</option>
            </select>
        </div>
    </div>

    <div id="ai-copilot-panel-body"></div>

    <div id="ai-copilot-panel-footer">
        <div id="ai-copilot-attachments-preview"></div>
        <div id="ai-copilot-token-balance" style="font-size:12px; font-weight:700; color:#374151; padding:0 2px 6px;">Tokens: 0</div>
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
