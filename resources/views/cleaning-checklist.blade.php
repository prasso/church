<div class="cc-root">
    <style>
        .cc-root {
            --cc-primary: #2563eb;
            --cc-primary-dark: #1d4ed8;
            --cc-accent: #10b981;
            --cc-bg: #f8fafc;
            --cc-card: #ffffff;
            --cc-border: #e2e8f0;
            --cc-text: #1e293b;
            --cc-muted: #64748b;
            --cc-done-bg: #ecfdf5;
            --cc-done-border: #a7f3d0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: var(--cc-text);
            background: var(--cc-bg);
            padding: 16px;
            max-width: 820px;
            margin: 0 auto;
            line-height: 1.5;
            -webkit-text-size-adjust: 100%;
        }
        .cc-root *, .cc-root *::before, .cc-root *::after { box-sizing: border-box; }

        .cc-header {
            background: linear-gradient(135deg, var(--cc-primary), var(--cc-primary-dark));
            color: #fff;
            border-radius: 14px;
            padding: 20px 20px 16px;
            margin-bottom: 16px;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.2);
        }
        .cc-header h1 {
            margin: 0 0 4px;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.01em;
        }
        .cc-header p {
            margin: 0;
            font-size: 13px;
            opacity: 0.9;
        }

        .cc-progress {
            margin-top: 14px;
            background: rgba(255,255,255,0.25);
            border-radius: 999px;
            height: 10px;
            overflow: hidden;
        }
        .cc-progress-bar {
            background: #fff;
            height: 100%;
            width: 0%;
            transition: width 0.3s ease;
        }
        .cc-progress-label {
            margin-top: 8px;
            font-size: 13px;
            display: flex;
            justify-content: space-between;
            opacity: 0.95;
        }

        .cc-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 16px;
        }
        .cc-info-box {
            background: var(--cc-card);
            border: 1px solid var(--cc-border);
            border-radius: 10px;
            padding: 10px 12px;
        }
        .cc-info-box label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--cc-muted);
            margin-bottom: 4px;
        }
        .cc-info-box input {
            width: 100%;
            border: none;
            background: transparent;
            font-size: 16px;
            color: var(--cc-text);
            padding: 2px 0;
            outline: none;
            font-weight: 500;
        }
        .cc-info-box input:focus { color: var(--cc-primary); }

        .cc-section {
            background: var(--cc-card);
            border: 1px solid var(--cc-border);
            border-radius: 12px;
            margin-bottom: 14px;
            overflow: hidden;
        }
        .cc-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px;
            cursor: pointer;
            user-select: none;
            background: #f1f5f9;
            border-bottom: 1px solid var(--cc-border);
        }
        .cc-section-header h2 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: var(--cc-text);
        }
        .cc-section-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: var(--cc-muted);
        }
        .cc-chevron {
            transition: transform 0.2s ease;
            font-size: 18px;
            line-height: 1;
            color: var(--cc-muted);
        }
        .cc-section.cc-collapsed .cc-chevron { transform: rotate(-90deg); }
        .cc-section.cc-collapsed .cc-task-list { display: none; }

        .cc-task-list {
            list-style: none;
            margin: 0;
            padding: 6px;
        }
        .cc-task-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.15s ease;
            min-height: 48px;
        }
        .cc-task-item + .cc-task-item { margin-top: 2px; }
        .cc-task-item:hover { background: #f8fafc; }
        .cc-task-item:active { background: #f1f5f9; }

        .cc-task-item.cc-done {
            background: var(--cc-done-bg);
        }
        .cc-task-item.cc-done .cc-task-text {
            color: var(--cc-muted);
            text-decoration: line-through;
        }

        .cc-check {
            flex-shrink: 0;
            width: 26px;
            height: 26px;
            border: 2px solid #cbd5e1;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            transition: all 0.15s ease;
        }
        .cc-task-item.cc-done .cc-check {
            background: var(--cc-accent);
            border-color: var(--cc-accent);
        }
        .cc-check svg {
            width: 16px;
            height: 16px;
            color: #fff;
            opacity: 0;
            transition: opacity 0.15s ease;
        }
        .cc-task-item.cc-done .cc-check svg { opacity: 1; }
        .cc-task-item input[type="checkbox"] {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .cc-task-text {
            flex: 1;
            font-size: 15px;
            line-height: 1.4;
        }

        .cc-actions {
            display: flex;
            gap: 10px;
            margin-top: 18px;
            flex-wrap: wrap;
        }
        .cc-btn {
            flex: 1;
            min-width: 140px;
            border: none;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.1s ease, box-shadow 0.15s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .cc-btn:active { transform: scale(0.98); }
        .cc-btn-primary {
            background: var(--cc-primary);
            color: #fff;
        }
        .cc-btn-primary:hover { background: var(--cc-primary-dark); }
        .cc-btn-secondary {
            background: #fff;
            color: var(--cc-text);
            border: 1px solid var(--cc-border);
        }
        .cc-btn-secondary:hover { background: #f8fafc; }

        @media (max-width: 600px) {
            .cc-root { padding: 10px; }
            .cc-header { padding: 16px; border-radius: 12px; }
            .cc-header h1 { font-size: 20px; }
            .cc-info { grid-template-columns: 1fr; }
            .cc-task-text { font-size: 16px; }
            .cc-task-item { padding: 14px; }
            .cc-actions { flex-direction: column; }
            .cc-btn { width: 100%; }
        }

        @media print {
            .cc-root {
                background: #fff;
                padding: 0;
                max-width: 100%;
            }
            .cc-header {
                background: #fff !important;
                color: #000 !important;
                box-shadow: none;
                border: 2px solid #000;
                -webkit-print-color-adjust: exact;
            }
            .cc-actions, .cc-chevron { display: none !important; }
            .cc-section { border: 1px solid #000; break-inside: avoid; }
            .cc-task-item { break-inside: avoid; }
            .cc-check { border-color: #000 !important; background: #fff !important; }
            .cc-task-item.cc-done .cc-check { background: #000 !important; }
        }
    </style>

    <div class="cc-header">
        <h1>Cleaning Checklist</h1>
        <p>Check off tasks as you go &mdash; progress saves automatically.</p>
        <div class="cc-progress"><div class="cc-progress-bar" id="ccProgressBar"></div></div>
        <div class="cc-progress-label">
            <span id="ccProgressText">0 of 0 complete</span>
            <span id="ccProgressPct">0%</span>
        </div>
    </div>

    <div class="cc-info">
        <div class="cc-info-box">
            <label for="cc-custodian">Today's Custodian</label>
            <input type="text" id="cc-custodian" placeholder="Enter name" autocomplete="name">
        </div>
        <div class="cc-info-box">
            <label for="cc-date">Date</label>
            <input type="text" id="cc-date" placeholder="{{ date('F j, Y') }}">
        </div>
    </div>

    <section class="cc-section" data-section="regular">
        <div class="cc-section-header" onclick="ccToggleSection(this)">
            <h2>Regular Tasks</h2>
            <div class="cc-section-meta">
                <span class="cc-section-count" data-count-for="regular">0 / {{ count($regularTasks) }}</span>
                <span class="cc-chevron">&#9662;</span>
            </div>
        </div>
        <ul class="cc-task-list">
            @foreach ($regularTasks as $i => $task)
                <li class="cc-task-item" data-task-id="regular-{{ $i }}" onclick="ccToggleTask(this)">
                    <span class="cc-check" aria-hidden="true">
                        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 10 8 14 16 6"></polyline></svg>
                    </span>
                    <input type="checkbox" tabindex="-1">
                    <span class="cc-task-text">{{ $task }}</span>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="cc-section" data-section="extras">
        <div class="cc-section-header" onclick="ccToggleSection(this)">
            <h2>Extras (not required weekly)</h2>
            <div class="cc-section-meta">
                <span class="cc-section-count" data-count-for="extras">0 / {{ count($extraTasks) }}</span>
                <span class="cc-chevron">&#9662;</span>
            </div>
        </div>
        <ul class="cc-task-list">
            @foreach ($extraTasks as $i => $task)
                <li class="cc-task-item" data-task-id="extras-{{ $i }}" onclick="ccToggleTask(this)">
                    <span class="cc-check" aria-hidden="true">
                        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 10 8 14 16 6"></polyline></svg>
                    </span>
                    <input type="checkbox" tabindex="-1">
                    <span class="cc-task-text">{{ $task }}</span>
                </li>
            @endforeach
        </ul>
    </section>

    <div class="cc-actions">
        <button type="button" class="cc-btn cc-btn-primary" onclick="window.print()">Print Checklist</button>
        <button type="button" class="cc-btn cc-btn-secondary" onclick="ccResetAll()">Reset All</button>
    </div>

    <script>
        (function () {
            var STORAGE_KEY = 'cc-checklist-v1';

            function loadState() {
                try { return JSON.parse(localStorage.getItem(STORAGE_KEY)) || {}; }
                catch (e) { return {}; }
            }
            function saveState(state) {
                try { localStorage.setItem(STORAGE_KEY, JSON.stringify(state)); } catch (e) {}
            }

            var state = loadState();

            function applyState() {
                document.querySelectorAll('.cc-task-item').forEach(function (el) {
                    var id = el.getAttribute('data-task-id');
                    var done = !!state[id];
                    el.classList.toggle('cc-done', done);
                    var cb = el.querySelector('input[type="checkbox"]');
                    if (cb) cb.checked = done;
                });
                if (state.__custodian) {
                    var c = document.getElementById('cc-custodian');
                    if (c) c.value = state.__custodian;
                }
                updateProgress();
            }

            function updateProgress() {
                var items = document.querySelectorAll('.cc-task-item');
                var done = document.querySelectorAll('.cc-task-item.cc-done').length;
                var total = items.length;
                var pct = total === 0 ? 0 : Math.round((done / total) * 100);
                var bar = document.getElementById('ccProgressBar');
                if (bar) bar.style.width = pct + '%';
                var txt = document.getElementById('ccProgressText');
                if (txt) txt.textContent = done + ' of ' + total + ' complete';
                var pctEl = document.getElementById('ccProgressPct');
                if (pctEl) pctEl.textContent = pct + '%';

                document.querySelectorAll('.cc-section').forEach(function (sec) {
                    var key = sec.getAttribute('data-section');
                    var secItems = sec.querySelectorAll('.cc-task-item');
                    var secDone = sec.querySelectorAll('.cc-task-item.cc-done').length;
                    var label = sec.querySelector('[data-count-for="' + key + '"]');
                    if (label) label.textContent = secDone + ' / ' + secItems.length;
                });
            }

            window.ccToggleTask = function (el) {
                var id = el.getAttribute('data-task-id');
                var isDone = el.classList.toggle('cc-done');
                var cb = el.querySelector('input[type="checkbox"]');
                if (cb) cb.checked = isDone;
                if (isDone) state[id] = true; else delete state[id];
                saveState(state);
                updateProgress();
            };

            window.ccToggleSection = function (header) {
                header.parentElement.classList.toggle('cc-collapsed');
            };

            window.ccResetAll = function () {
                if (!confirm('Reset all checkboxes?')) return;
                Object.keys(state).forEach(function (k) {
                    if (k.indexOf('__') !== 0) delete state[k];
                });
                saveState(state);
                document.querySelectorAll('.cc-task-item').forEach(function (el) {
                    el.classList.remove('cc-done');
                    var cb = el.querySelector('input[type="checkbox"]');
                    if (cb) cb.checked = false;
                });
                updateProgress();
            };

            // Auto-fill today's date
            var dateEl = document.getElementById('cc-date');
            if (dateEl) {
                dateEl.value = new Date().toLocaleDateString('en-US', {
                    year: 'numeric', month: 'long', day: 'numeric'
                });
            }

            // Persist custodian name
            var custEl = document.getElementById('cc-custodian');
            if (custEl) {
                custEl.addEventListener('input', function () {
                    state.__custodian = custEl.value;
                    saveState(state);
                });
            }

            // Prevent Enter from submitting in inputs
            document.querySelectorAll('.cc-info-box input').forEach(function (inp) {
                inp.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') e.preventDefault();
                });
            });

            applyState();
        })();
    </script>
</div>