<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Muktinath Bank Assistant</title>
    <style>
        :root {
            --brand: #7a1f2b;
            --brand-dark: #5c1620;
            --bg: #f3f0ee;
            --surface: #ffffff;
            --line: #e6e1de;
            --text: #1f2430;
            --muted: #6f6a68;
            --danger-bg: #fdecec;
            --danger-text: #a61b1b;
        }

        * { box-sizing: border-box; }

        html, body { height: 100%; }

        body {
            margin: 0;
            font-family: 'Segoe UI', system-ui, -apple-system, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            display: flex;
            justify-content: center;
        }

        .app {
            width: 100%;
            max-width: 760px;
            height: 100%;
            display: flex;
            flex-direction: column;
            background: var(--bg);
            padding-top: env(safe-area-inset-top, 0px);
        }

        /* ---------- Header ---------- */
        .chat-header {
            background: var(--brand);
            color: #fff;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }

        .chat-header .avatar {
            width: 40px; height: 40px;
            border-radius: 50%;
            background: #fff;
            color: var(--brand);
            display: grid; place-items: center;
            font-weight: 700; font-size: 15px;
        }

        .chat-header .title { flex: 1; min-width: 0; }
        .chat-header h1 { font-size: 16px; margin: 0; font-weight: 600; }
        .chat-header p { margin: 2px 0 0; font-size: 12px; opacity: .85; }

        .status-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: #4ade80;
            display: inline-block;
            margin-right: 5px;
        }

        .clear-btn {
            background: rgba(255,255,255,.14);
            color: #fff;
            border: 1px solid rgba(255,255,255,.35);
            border-radius: 18px;
            padding: 7px 14px;
            font-size: 13px;
            font-family: inherit;
            cursor: pointer;
            display: flex; align-items: center; gap: 6px;
            transition: background .15s;
        }
        .clear-btn:hover { background: rgba(255,255,255,.26); }
        .clear-btn:disabled { opacity: .5; cursor: not-allowed; }

        button:focus-visible, textarea:focus-visible {
            outline: 2px solid #fff;
            outline-offset: 2px;
        }
        .chat-input textarea:focus-visible, .chip:focus-visible, .send-btn:focus-visible {
            outline-color: var(--brand);
        }

        /* ---------- Messages ---------- */
        .chat-box {
            flex: 1;
            overflow-y: auto;
            padding: 20px 18px 8px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            scroll-behavior: smooth;
        }

        .day-label {
            align-self: center;
            font-size: 12px;
            color: var(--muted);
            background: rgba(0,0,0,.05);
            padding: 3px 12px;
            border-radius: 12px;
            margin: 10px 0;
        }

        .msg-row {
            display: flex;
            align-items: flex-end;
            gap: 8px;
            max-width: 86%;
            margin-top: 10px;
        }
        .msg-row.user { align-self: flex-end; flex-direction: row-reverse; }
        .msg-row.bot { align-self: flex-start; }

        .msg-avatar {
            width: 28px; height: 28px;
            border-radius: 50%;
            flex-shrink: 0;
            display: grid; place-items: center;
            font-size: 11px; font-weight: 700; color: #fff;
            background: var(--brand);
        }
        .msg-row.user .msg-avatar { display: none; }

        .bubble-wrap { display: flex; flex-direction: column; min-width: 0; }
        .msg-row.user .bubble-wrap { align-items: flex-end; }

        .bubble {
            padding: 11px 15px;
            border-radius: 16px;
            line-height: 1.5;
            font-size: 14.5px;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }
        .msg-row.user .bubble {
            background: var(--brand);
            color: #fff;
            border-bottom-right-radius: 4px;
        }
        .msg-row.bot .bubble {
            background: var(--surface);
            border: 1px solid var(--line);
            border-bottom-left-radius: 4px;
        }
        .msg-row.error .bubble {
            background: var(--danger-bg);
            border-color: #f3c2c2;
            color: var(--danger-text);
        }

        .time {
            font-size: 11px;
            color: var(--muted);
            margin: 3px 4px 0;
        }

        /* ---------- Typing indicator ---------- */
        .typing .bubble {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 15px 16px;
        }
        .typing .dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: #a39d9b;
            animation: bounce 1.2s infinite ease-in-out;
        }
        .typing .dot:nth-child(2) { animation-delay: .15s; }
        .typing .dot:nth-child(3) { animation-delay: .3s; }

        @keyframes bounce {
            0%, 60%, 100% { transform: translateY(0); opacity: .5; }
            30% { transform: translateY(-5px); opacity: 1; }
        }

        @media (prefers-reduced-motion: reduce) {
            .typing .dot { animation: none; opacity: .7; }
            .chat-box { scroll-behavior: auto; }
        }

        /* ---------- Suggestions ---------- */
        .suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding: 6px 18px 4px;
        }
        .suggestions:empty { display: none; }

        .chip {
            background: var(--surface);
            color: var(--brand);
            border: 1px solid var(--brand);
            border-radius: 16px;
            padding: 7px 14px;
            font-size: 13px;
            font-family: inherit;
            cursor: pointer;
            transition: background .15s, color .15s;
        }
        .chip:hover { background: var(--brand); color: #fff; }

        /* ---------- Input ---------- */
        .chat-input {
            flex-shrink: 0;
            background: var(--surface);
            border-top: 1px solid var(--line);
            padding: 12px 16px calc(10px + env(safe-area-inset-bottom, 0px));
        }
        .chat-input form { display: flex; align-items: flex-end; gap: 10px; }

        .chat-input textarea {
            flex: 1;
            resize: none;
            max-height: 120px;
            border: 1px solid #d6d0cd;
            border-radius: 20px;
            padding: 10px 16px;
            font-size: 14.5px;
            font-family: inherit;
            line-height: 1.4;
        }
        .chat-input textarea:focus { outline: none; border-color: var(--brand); }

        .send-btn {
            background: var(--brand);
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 42px; height: 42px;
            flex-shrink: 0;
            cursor: pointer;
            display: grid; place-items: center;
            font-size: 17px;
            transition: background .15s;
        }
        .send-btn:hover { background: var(--brand-dark); }
        .send-btn:disabled { background: #c9c4c2; cursor: not-allowed; }

        .hint {
            font-size: 11px;
            color: var(--muted);
            text-align: center;
            margin-top: 6px;
        }

        @media (max-width: 480px) {
            .clear-btn span { display: none; }
            .hint { display: none; }
        }
    </style>
</head>
<body>
    <div class="app">

        <header class="chat-header">
            <div class="avatar">MB</div>
            <div class="title">
                <h1>Muktinath Bank Assistant</h1>
                <p><span class="status-dot"></span>Online</p>
            </div>
            <button type="button" class="clear-btn" id="clearBtn" title="Clear chat">
                &#128465; <span>Clear chat</span>
            </button>
        </header>

        <main class="chat-box" id="chatBox" aria-live="polite"></main>

        <div class="suggestions" id="suggestions"></div>

        <div class="chat-input">
            <form id="chatForm" autocomplete="off">
                <textarea id="questionInput" placeholder="Type your message..." rows="1" required></textarea>
                <button type="submit" class="send-btn" id="sendBtn" aria-label="Send message">&#10148;</button>
            </form>
            <div class="hint">Enter to send &middot; Shift+Enter for a new line</div>
        </div>
    </div>

    <script>
        const SEND_URL = @json(route('rag.chat.send'));
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;
        const STORAGE_KEY = 'muktinath_chat_history_v1';
        const MAX_MESSAGES = 100;

        const WELCOME = 'Hello! I can help with accounts, loans, branches and services at Muktinath Bank. What would you like to know?';
        const SUGGESTIONS = [
            'How do I open a savings account?',
            'What loans do you offer?',
        ];

        const chatBox = document.getElementById('chatBox');
        const form = document.getElementById('chatForm');
        const input = document.getElementById('questionInput');
        const sendBtn = document.getElementById('sendBtn');
        const clearBtn = document.getElementById('clearBtn');
        const suggestionsEl = document.getElementById('suggestions');

        let history = loadHistory();
        let busy = false;

        /* ---------- Storage ---------- */
        function loadHistory() {
            try {
                const data = JSON.parse(localStorage.getItem(STORAGE_KEY));
                return Array.isArray(data) ? data : [];
            } catch (e) { return []; }
        }

        function saveHistory() {
            try {
                history = history.slice(-MAX_MESSAGES);
                localStorage.setItem(STORAGE_KEY, JSON.stringify(history));
            } catch (e) { /* storage full or blocked: chat still works */ }
        }

        /* ---------- Rendering ---------- */
        function formatTime(ts) {
            return new Date(ts).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        }

        function formatDay(ts) {
            const d = new Date(ts), today = new Date();
            const yesterday = new Date(); yesterday.setDate(today.getDate() - 1);
            if (d.toDateString() === today.toDateString()) return 'Today';
            if (d.toDateString() === yesterday.toDateString()) return 'Yesterday';
            return d.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
        }

        function buildMessage(msg) {
            const row = document.createElement('div');
            row.className = 'msg-row ' + (msg.role === 'user' ? 'user' : 'bot') + (msg.error ? ' error' : '');

            if (msg.role !== 'user') {
                const av = document.createElement('div');
                av.className = 'msg-avatar';
                av.textContent = 'MB';
                row.appendChild(av);
            }

            const wrap = document.createElement('div');
            wrap.className = 'bubble-wrap';

            const bubble = document.createElement('div');
            bubble.className = 'bubble';
            bubble.textContent = msg.text; // textContent keeps output safe from HTML injection

            const time = document.createElement('div');
            time.className = 'time';
            time.textContent = formatTime(msg.ts);

            wrap.append(bubble, time);
            row.appendChild(wrap);
            return row;
        }

        function scrollToBottom() {
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        function render() {
            chatBox.innerHTML = '';

            const welcome = { role: 'bot', text: WELCOME, ts: history.length ? history[0].ts : Date.now() };
            const all = [welcome, ...history];

            let lastDay = '';
            all.forEach(msg => {
                const day = formatDay(msg.ts);
                if (day !== lastDay) {
                    const label = document.createElement('div');
                    label.className = 'day-label';
                    label.textContent = day;
                    chatBox.appendChild(label);
                    lastDay = day;
                }
                chatBox.appendChild(buildMessage(msg));
            });

            renderSuggestions();
            scrollToBottom();
        }

        function renderSuggestions() {
            suggestionsEl.innerHTML = '';
            if (history.length > 0) return;
            SUGGESTIONS.forEach(text => {
                const chip = document.createElement('button');
                chip.type = 'button';
                chip.className = 'chip';
                chip.textContent = text;
                chip.addEventListener('click', () => send(text));
                suggestionsEl.appendChild(chip);
            });
        }

        /* ---------- Typing indicator ---------- */
        function showTyping() {
            const row = document.createElement('div');
            row.className = 'msg-row bot typing';
            row.id = 'typingRow';
            row.innerHTML =
                '<div class="msg-avatar">MB</div>' +
                '<div class="bubble-wrap"><div class="bubble" role="status" aria-label="Assistant is typing">' +
                '<span class="dot"></span><span class="dot"></span><span class="dot"></span></div></div>';
            chatBox.appendChild(row);
            scrollToBottom();
        }

        function hideTyping() {
            const row = document.getElementById('typingRow');
            if (row) row.remove();
        }

        /* ---------- Sending ---------- */
        function setBusy(state) {
            busy = state;
            sendBtn.disabled = state;
            clearBtn.disabled = state;
        }

        async function send(text) {
            text = text.trim();
            if (!text || busy) return;

            setBusy(true);
            history.push({ role: 'user', text, ts: Date.now() });
            saveHistory();
            render();

            input.value = '';
            input.style.height = 'auto';
            showTyping();

            try {
                const res = await fetch(SEND_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ question: text })
                });

                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.answer) {
                    throw new Error(data.error || 'The assistant could not answer right now. Please try again.');
                }

                history.push({ role: 'bot', text: data.answer, ts: Date.now() });
            } catch (err) {
                history.push({
                    role: 'bot',
                    error: true,
                    text: err.message || 'Something went wrong. Check your connection and try again.',
                    ts: Date.now()
                });
            } finally {
                hideTyping();
                saveHistory();
                render();
                setBusy(false);
                input.focus();
            }
        }

        /* ---------- Events ---------- */
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            send(input.value);
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                send(input.value);
            }
        });

        input.addEventListener('input', () => {
            input.style.height = 'auto';
            input.style.height = Math.min(input.scrollHeight, 120) + 'px';
        });

        clearBtn.addEventListener('click', () => {
            if (history.length === 0) return;
            if (!confirm('Clear this conversation? This cannot be undone.')) return;
            history = [];
            try { localStorage.removeItem(STORAGE_KEY); } catch (e) {}
            render();
        });

        render();
    </script>
</body>
</html>