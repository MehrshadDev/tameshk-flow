<x-layouts.terminal :title="'Web Terminal | ' . config('app.name')">
    <div x-data="webTerminal({
             endpoint: @js(route('terminal.execute')),
             csrf: @js(csrf_token()),
             catalog: @js($catalog),
             systemInfo: @js($systemInfo),
         })"
         @keydown.window="handleGlobalShortcut($event)"
         class="mx-auto flex h-dvh w-full max-w-[1560px] flex-col gap-3 overflow-hidden p-2.5 sm:p-4">

        <x-terminal.header :system-info="$systemInfo" />

        <div class="flex min-h-0 flex-1 flex-col gap-3 lg:flex-row">
            <x-terminal.console :system-info="$systemInfo" />
            <x-terminal.quick-panel :quick-groups="$quickGroups" />
        </div>

        <x-terminal.confirm-modal />
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('webTerminal', (cfg) => ({
                endpoint: cfg.endpoint,
                csrf: cfg.csrf,
                catalog: cfg.catalog || { artisan: [], shell: [], files: [] },
                systemInfo: cfg.systemInfo || {},
                cwd: '',
                input: '',
                running: false,
                isExpanded: false,
                activeTab: 'console',
                copiedAll: false,
                fontSize: Number(localStorage.getItem('tameshk_term_font_size')) || 13,
                entries: [],
                history: [],
                historyIndex: 0,
                tempInput: '',
                suggestions: [],
                suggestionIndex: 0,
                lastTabPrefix: null,
                confirmModal: {
                    open: false,
                    command: '',
                    dangerous_command: '',
                    warning: '',
                },

                init() {
                    try {
                        const saved = JSON.parse(localStorage.getItem('tameshk_term_history') || '[]');
                        if (Array.isArray(saved)) {
                            this.history = saved.slice(-100);
                        }
                    } catch {
                        this.history = [];
                    }

                    this.historyIndex = this.history.length;
                    this.$nextTick(() => this.$refs.cmdInput?.focus());
                },

                formattedCwd() {
                    return this.cwd ? '~/' + this.cwd : '~';
                },

                focusInput(event = null) {
                    if (event && window.getSelection()?.toString()) {
                        return;
                    }
                    this.$nextTick(() => {
                        const el = this.$refs.cmdInput;
                        if (el) {
                            el.focus();
                        }
                    });
                },

                moveCursorToEnd() {
                    this.$nextTick(() => {
                        const el = this.$refs.cmdInput;
                        if (el) {
                            const len = el.value.length;
                            el.focus();
                            el.setSelectionRange(len, len);
                        }
                    });
                },

                handleInputKeydown(e) {
                    if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        this.historyUp();
                    } else if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        this.historyDown();
                    } else if (e.key === 'Tab') {
                        e.preventDefault();
                        this.handleTab();
                    } else if (e.key === 'Enter') {
                        e.preventDefault();
                        this.submitCommand();
                    }
                },

                zoomIn() {
                    this.fontSize = Math.min(22, this.fontSize + 1);
                    localStorage.setItem('tameshk_term_font_size', String(this.fontSize));
                },

                zoomOut() {
                    this.fontSize = Math.max(11, this.fontSize - 1);
                    localStorage.setItem('tameshk_term_font_size', String(this.fontSize));
                },

                resetZoom() {
                    this.fontSize = 13;
                    localStorage.setItem('tameshk_term_font_size', '13');
                },

                clearScreen() {
                    this.entries = [];
                    this.suggestions = [];
                    this.focusInput();
                },

                clearHistory() {
                    this.history = [];
                    this.historyIndex = 0;
                    this.tempInput = '';
                    localStorage.removeItem('tameshk_term_history');
                },

                saveToHistory(cmd) {
                    const clean = cmd.trim();
                    if (!clean) return;

                    if (this.history[this.history.length - 1] !== clean) {
                        this.history.push(clean);
                        if (this.history.length > 100) {
                            this.history.shift();
                        }
                        localStorage.setItem('tameshk_term_history', JSON.stringify(this.history));
                    }
                    this.historyIndex = this.history.length;
                    this.tempInput = '';
                },

                historyUp() {
                    if (this.history.length === 0) return;

                    if (this.historyIndex >= this.history.length) {
                        this.tempInput = this.input;
                        this.historyIndex = this.history.length;
                    }

                    if (this.historyIndex > 0) {
                        this.historyIndex--;
                        this.input = this.history[this.historyIndex];
                        this.moveCursorToEnd();
                    }
                },

                historyDown() {
                    if (this.history.length === 0) return;

                    if (this.historyIndex < this.history.length - 1) {
                        this.historyIndex++;
                        this.input = this.history[this.historyIndex];
                        this.moveCursorToEnd();
                    } else {
                        this.historyIndex = this.history.length;
                        this.input = this.tempInput;
                        this.moveCursorToEnd();
                    }
                },

                selectHistoryPill(cmd) {
                    this.activeTab = 'console';
                    this.input = cmd;
                    this.moveCursorToEnd();
                },

                resetTabCycle() {
                    this.lastTabPrefix = null;
                    this.suggestions = [];
                    this.suggestionIndex = 0;
                },

                handleTab() {
                    const raw = this.input;
                    const trimmedLeft = raw.replace(/^\s+/, '');

                    if (this.suggestions.length > 1 && this.lastTabPrefix !== null) {
                        this.suggestionIndex = (this.suggestionIndex + 1) % this.suggestions.length;
                        this.applySuggestion(this.suggestions[this.suggestionIndex].value, true);
                        return;
                    }

                    this.lastTabPrefix = trimmedLeft;

                    let prefix = '';
                    let target = trimmedLeft;

                    const artisanMatch = trimmedLeft.match(/^(php\s+artisan\s+|artisan\s+)(.*)$/i);
                    if (artisanMatch) {
                        prefix = artisanMatch[1];
                        target = artisanMatch[2];
                    }

                    const parts = target.split(/\s+/);

                    if (parts.length <= 1) {
                        const token = (parts[0] || '').toLowerCase();
                        const matches = [];

                        if (!prefix) {
                            for (const sc of (this.catalog.shell || [])) {
                                const base = sc.split(' ')[0];
                                if (base.toLowerCase().startsWith(token)) {
                                    matches.push({ value: base });
                                }
                            }
                        }

                        for (const ac of (this.catalog.artisan || [])) {
                            if (ac.name.toLowerCase().startsWith(token)) {
                                matches.push({ value: prefix + ac.name });
                            }
                        }

                        const unique = Array.from(new Map(matches.map(m => [m.value, m])).values());

                        if (unique.length === 1) {
                            this.input = unique[0].value + ' ';
                            this.suggestions = [];
                            this.moveCursorToEnd();
                        } else if (unique.length > 1) {
                            this.suggestions = unique.slice(0, 20);
                            this.suggestionIndex = 0;
                            this.input = this.suggestions[0].value;
                            this.moveCursorToEnd();
                        }
                        return;
                    }

                    const lastArg = parts[parts.length - 1];
                    const headCmd = parts.slice(0, -1).join(' ');
                    const fileMatches = (this.catalog.files || [])
                        .filter(f => f.toLowerCase().startsWith(lastArg.toLowerCase()))
                        .map(f => ({
                            value: prefix + headCmd + ' ' + f,
                        }));

                    if (fileMatches.length === 1) {
                        this.input = fileMatches[0].value;
                        this.suggestions = [];
                        this.moveCursorToEnd();
                    } else if (fileMatches.length > 1) {
                        this.suggestions = fileMatches.slice(0, 20);
                        this.suggestionIndex = 0;
                        this.input = this.suggestions[0].value;
                        this.moveCursorToEnd();
                    }
                },

                applySuggestion(val, keepOpen = false) {
                    this.input = val + (val.endsWith('/') ? '' : ' ');
                    if (!keepOpen) {
                        this.suggestions = [];
                        this.lastTabPrefix = null;
                    }
                    this.moveCursorToEnd();
                },

                runQuick(command) {
                    this.activeTab = 'console';
                    this.input = command;
                    this.submitCommand();
                },

                async submitCommand(confirmed = false) {
                    const cmd = this.input.trim();
                    if (!cmd || this.running) return;

                    this.suggestions = [];
                    this.lastTabPrefix = null;

                    if (!confirmed) {
                        this.saveToHistory(cmd);
                    }

                    const currentCwd = this.cwd;
                    this.running = true;

                    try {
                        const response = await fetch(this.endpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrf,
                            },
                            body: JSON.stringify({
                                command: cmd,
                                cwd: currentCwd,
                                confirmed: confirmed,
                            }),
                        });

                        const data = await response.json();

                        if (data.status === 'confirm_required') {
                            this.confirmModal = {
                                open: true,
                                command: cmd,
                                dangerous_command: data.dangerous_command || cmd,
                                warning: data.warning || 'This command may modify or erase production data.',
                            };
                            return;
                        }

                        this.input = '';
                        this.historyIndex = this.history.length;

                        if (Array.isArray(data.files)) {
                            this.catalog.files = data.files;
                        }

                        if (typeof data.cwd === 'string') {
                            this.cwd = data.cwd;
                        }

                        if (data.status === 'clear') {
                            this.entries = [];
                            return;
                        }

                        this.entries.push({
                            id: Date.now() + Math.random(),
                            command: cmd,
                            cwd: currentCwd,
                            output: data.output ?? '',
                            exit_code: data.exit_code ?? 0,
                            duration_ms: data.duration_ms ?? 0,
                        });

                        this.scrollToBottom();
                    } catch {
                        this.entries.push({
                            id: Date.now() + Math.random(),
                            command: cmd,
                            cwd: currentCwd,
                            output: '[NETWORK ERROR] Failed to communicate with server endpoint.',
                            exit_code: 1,
                            duration_ms: null,
                        });
                        this.scrollToBottom();
                    } finally {
                        this.running = false;
                        this.focusInput();
                    }
                },

                confirmAndExecute() {
                    this.input = this.confirmModal.command;
                    this.confirmModal.open = false;
                    this.submitCommand(true);
                },

                cancelConfirm() {
                    this.confirmModal.open = false;
                    this.focusInput();
                },

                scrollToBottom() {
                    this.$nextTick(() => {
                        const el = this.$refs.viewport;
                        if (el) {
                            el.scrollTop = el.scrollHeight;
                        }
                    });
                },

                copyAllOutput() {
                    const text = this.entries
                        .map(e => `$ ${e.command}\n${e.output}`)
                        .join('\n\n');

                    navigator.clipboard.writeText(text || '');
                    this.copiedAll = true;
                    setTimeout(() => (this.copiedAll = false), 2000);
                },

                handleGlobalShortcut(e) {
                    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'l') {
                        e.preventDefault();
                        this.clearScreen();
                    } else if (e.key === 'Escape') {
                        if (this.confirmModal.open) {
                            this.cancelConfirm();
                        } else if (this.suggestions.length > 0) {
                            this.suggestions = [];
                        } else if (this.isExpanded) {
                            this.isExpanded = false;
                        }
                    }
                },
            }));
        });
    </script>
</x-layouts.terminal>
