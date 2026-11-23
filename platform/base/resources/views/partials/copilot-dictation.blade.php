function bindCopilotDictation(input, button, unsupported) {
    const Recognizer = window.SpeechRecognition || window.webkitSpeechRecognition;
    let active = null;

    function release(session) {
        if (active !== session) return;
        active = null;
        button.classList.remove('recording');
        button.setAttribute('aria-pressed', 'false');
    }

    function cancel() {
        if (!active) return;
        const session = active;
        release(session);
        session.abort();
    }

    input.addEventListener('beforeinput', cancel);
    button.addEventListener('click', () => {
        if (!Recognizer) return unsupported();
        if (active) {
            active.stop();
            return;
        }

        const draft = input.value;
        const session = new Recognizer();
        session.continuous = true;
        session.interimResults = true;
        session.onresult = ({ results }) => {
            if (active !== session) return;
            // Results include earlier final phrases and the current, revisable hypothesis.
            const speech = Array.from(results, result => result[0].transcript.trim()).filter(Boolean).join(' ');
            const separator = draft && speech && !/\s$/.test(draft) ? ' ' : '';
            input.value = draft + separator + speech;
            input.dispatchEvent(new Event('input', { bubbles: true }));
        };
        session.onend = session.onerror = () => release(session);
        session.start();
        active = session;
        button.classList.add('recording');
        button.setAttribute('aria-pressed', 'true');
        input.focus();
    });
    return cancel;
}
