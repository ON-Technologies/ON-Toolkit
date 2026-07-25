/**
 * ON Toolkit — Raycast Style Command Palette (`Cmd + K`)
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        createModalHTML();

        const overlay = document.getElementById('ontk-cmd-overlay');
        const input = document.getElementById('ontk-cmd-input');
        const results = document.getElementById('ontk-cmd-results');

        if (!overlay || !input || !results) return;

        // Toggle Command Palette on Cmd+K or Ctrl+K
        document.addEventListener('keydown', function (e) {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                togglePalette();
            }
            if (e.key === 'Escape' && overlay.classList.contains('active')) {
                togglePalette(false);
            }
        });

        function togglePalette(show) {
            const isActive = overlay.classList.contains('active');
            const shouldShow = show !== undefined ? show : !isActive;

            if (shouldShow) {
                overlay.classList.add('active');
                input.value = '';
                input.focus();
                renderItems();
            } else {
                overlay.classList.remove('active');
            }
        }

        const actions = [
            {
                title: 'Open ON Toolkit Dashboard',
                shortcut: '↵',
                action: () => { window.location.href = window.ontkSettings.adminUrl; }
            },
            {
                title: 'Run Quick Health Check',
                shortcut: '↵',
                action: () => { window.location.href = window.ontkSettings.adminUrl + '&tab=dashboard'; }
            },
            {
                title: 'Inspect Unused Media Files',
                shortcut: '↵',
                action: () => { window.location.href = window.ontkSettings.adminUrl + '&tab=media'; }
            },
            {
                title: 'Clean Expired Transients & Trash',
                shortcut: '↵',
                action: () => { window.location.href = window.ontkSettings.adminUrl + '&tab=db'; }
            }
        ];

        function renderItems(filterText = '') {
            results.innerHTML = '';
            const filtered = actions.filter(a => a.title.toLowerCase().includes(filterText.toLowerCase()));

            filtered.forEach((item, idx) => {
                const el = document.createElement('div');
                el.className = `ontk-cmd-item ${idx === 0 ? 'selected' : ''}`;
                el.innerHTML = `<span>${item.title}</span><span class="ontk-cmd-shortcut">${item.shortcut}</span>`;
                el.addEventListener('click', () => {
                    togglePalette(false);
                    item.action();
                });
                results.appendChild(el);
            });
        }

        input.addEventListener('input', function (e) {
            renderItems(e.target.value);
        });

        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) {
                togglePalette(false);
            }
        });
    });

    function createModalHTML() {
        if (document.getElementById('ontk-cmd-overlay')) return;

        const div = document.createElement('div');
        div.id = 'ontk-cmd-overlay';
        div.className = 'ontk-cmd-overlay';
        div.innerHTML = `
            <div class="ontk-cmd-modal">
                <div class="ontk-cmd-input-wrap">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input type="text" id="ontk-cmd-input" class="ontk-cmd-input" placeholder="Type a command or search (Cmd + K)..." />
                </div>
                <div id="ontk-cmd-results" class="ontk-cmd-results"></div>
            </div>
        `;
        document.body.appendChild(div);
    }
})();
