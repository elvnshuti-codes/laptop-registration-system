@php
    // $kioskAllowed: this screen may use kiosk mode (set by the page that includes this file).
    // $kioskControls: show the Kiosk mode and Keyboard buttons (hidden on the sign-in page).
    $kioskAllowed = $kioskAllowed ?? false;
    $kioskControls = $kioskControls ?? $kioskAllowed;

    // In kiosk mode these screens return to their home after a quiet moment.
    $kioskHome = request()->routeIs('entry.*') ? route('entry.index')
        : (request()->routeIs('gate.*') ? route('gate.home') : null);

    // The long visitor form is not sent home; the sign-out timer still protects it.
    $returnHomeSeconds = request()->routeIs('gate.create') ? 0 : (int) config('vms.kiosk_return_home_seconds', 90);
    $logoutSeconds = auth()->check()
        ? ($kioskAllowed ? null : (int) config('vms.laptop_logout_seconds', 1800))
        : 0;
@endphp

<style>
    /* Everything scales with the screen: a laptop, a tablet or a large kiosk. */
    html.kiosk { font-size: clamp(17px, 0.55vw + 12px, 26px); }
    html.kiosk .btn, html.kiosk .form-control, html.kiosk .form-select { min-height: 3.2rem; }

    #kiosk-controls { position: fixed; right: 1rem; bottom: 1rem; z-index: 1050; display: flex; gap: .5rem; }
    #osk-open { display: none; }
    html.kiosk #osk-open { display: inline-block; }

    #osk {
        position: fixed; left: 0; right: 0; bottom: 0; z-index: 1060; display: none;
        background: #1f2933; padding: .5rem .5rem calc(.5rem + env(safe-area-inset-bottom));
        box-shadow: 0 -.5rem 1.5rem rgba(0, 0, 0, .35);
        user-select: none; -webkit-user-select: none; touch-action: manipulation;
    }
    #osk.open { display: block; }
    #osk .osk-row { display: flex; gap: .4rem; margin-bottom: .4rem; justify-content: center; }
    #osk .osk-row:last-child { margin-bottom: 0; }
    #osk button {
        flex: 1 1 0; min-width: 0; height: clamp(46px, 7.5vh, 74px);
        font-size: clamp(1rem, 2.2vh, 1.5rem); border: 0; border-radius: .5rem;
        background: #3e4c59; color: #fff;
    }
    #osk button:active { background: #616e7c; }
    #osk button.wide { flex: 1.6 1 0; }
    #osk button.space { flex: 5 1 0; }
    #osk button.enter { background: #198754; flex: 2 1 0; }
    #osk button.hide { background: #6c757d; flex: 1.6 1 0; }
    #osk button.on { background: #ffc107; color: #000; }

    @media print { #kiosk-controls, #osk, #idle-warning { display: none !important; } }
</style>

@if ($kioskControls)
    <div id="kiosk-controls">
        <button type="button" id="osk-open" class="btn btn-secondary shadow">Keyboard</button>
        <button type="button" id="kiosk-toggle" class="btn btn-dark shadow">Kiosk mode</button>
    </div>
@endif

<div id="osk" aria-label="On-screen keyboard"></div>

@auth
    <form id="auto-logout" method="POST" action="{{ route('logout') }}" class="d-none">@csrf</form>

    <div id="idle-warning" class="alert alert-warning shadow d-none" role="alert"
         style="position: fixed; left: 50%; bottom: 1rem; transform: translateX(-50%); z-index: 1070; max-width: 92vw; margin: 0">
        <strong>Still there?</strong> You will be signed out in <strong id="idle-count"></strong> seconds. Touch the screen to stay signed in.
    </div>
@endauth

<script>
    (function () {
        const root = document.documentElement;
        const toggle = document.getElementById('kiosk-toggle');
        const openButton = document.getElementById('osk-open');
        const osk = document.getElementById('osk');
        const logoutForm = document.getElementById('auto-logout');
        const warning = document.getElementById('idle-warning');
        const countdown = document.getElementById('idle-count');

        const allowed = @json($kioskAllowed);
        const home = @json($kioskHome);
        const returnHomeSeconds = @json($returnHomeSeconds);
        const kioskLogoutSeconds = @json((int) config('vms.kiosk_logout_seconds', 300));
        const laptopLogoutSeconds = @json((int) config('vms.laptop_logout_seconds', 1800));
        const signedIn = @json(auth()->check());
        const WARN_SECONDS = 30;
        const FIELDS = 'input:not([type]), input[type=text], input[type=search], input[type=tel], input[type=email], input[type=number], input[type=url], input[type=password], textarea';

        let target = null;      // the box the keyboard types into
        let lastField = null;   // the last text box that had focus
        let touched = false;    // the current focus came from a finger, not from the page or a scanner
        let shift = false;
        let caps = false;

        function read() { try { return localStorage.getItem('kiosk') === '1'; } catch (e) { return false; } }
        function write(on) { try { localStorage.setItem('kiosk', on ? '1' : '0'); } catch (e) {} }
        function isKiosk() { return root.classList.contains('kiosk'); }

        // ---------------------------------------------------------------- idle: go home, then sign out

        const KEY = 'vms_last_activity';
        let last = Date.now();
        let written = 0;
        let leaving = false;

        // The clock survives page changes within the tab, so going "home" does not give a fresh 5 minutes.
        try {
            const previous = Number(sessionStorage.getItem(KEY)) || 0;
            if (previous && previous <= Date.now()) last = previous;
        } catch (e) {}

        function activity() {
            last = Date.now();

            if (last - written > 1000) {
                written = last;
                try { sessionStorage.setItem(KEY, String(last)); } catch (e) {}
            }
        }

        function tick() {
            if (!signedIn || leaving) return;

            const idle = (Date.now() - last) / 1000;
            const limit = isKiosk() ? kioskLogoutSeconds : laptopLogoutSeconds;

            if (limit > 0 && idle >= limit) {
                leaving = true;
                if (logoutForm) logoutForm.submit();
                return;
            }

            if (warning && limit > WARN_SECONDS && limit - idle <= WARN_SECONDS) {
                countdown.textContent = Math.ceil(limit - idle);
                warning.classList.remove('d-none');
            } else if (warning) {
                warning.classList.add('d-none');
            }

            if (isKiosk() && home && returnHomeSeconds > 0 && idle >= returnHomeSeconds
                && location.pathname !== new URL(home).pathname) {
                leaving = true;
                location.href = home;
            }
        }

        ['pointerdown', 'keydown', 'mousemove', 'touchstart', 'wheel'].forEach(function (name) {
            document.addEventListener(name, activity, { passive: true });
        });

        if (signedIn) {
            activity();
            setInterval(tick, 1000);
        }

        // ---------------------------------------------------------------- on-screen keyboard

        const rows = [
            ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '-', '/'],
            ['q', 'w', 'e', 'r', 't', 'y', 'u', 'i', 'o', 'p'],
            ['a', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'l'],
            ['shift', 'z', 'x', 'c', 'v', 'b', 'n', 'm', 'back'],
            ['caps', '@', '_', '.', 'space', ',', 'enter', 'hide'],
        ];

        const labels = { shift: '\u21E7', caps: 'Caps', back: '\u232B', space: 'Space', enter: 'Enter', hide: 'Hide' };

        function render() {
            osk.innerHTML = '';

            rows.forEach(function (row) {
                const div = document.createElement('div');
                div.className = 'osk-row';

                row.forEach(function (key) {
                    const b = document.createElement('button');
                    b.type = 'button';
                    b.dataset.key = key;

                    const isLetter = /^[a-z]$/.test(key);
                    b.textContent = labels[key] || (isLetter && (shift || caps) ? key.toUpperCase() : key);

                    if (['shift', 'caps', 'back'].includes(key)) b.classList.add('wide');
                    if (key === 'space') b.classList.add('space');
                    if (key === 'enter') b.classList.add('enter');
                    if (key === 'hide') b.classList.add('hide');
                    if ((key === 'shift' && shift) || (key === 'caps' && caps)) b.classList.add('on');

                    div.appendChild(b);
                });

                osk.appendChild(div);
            });
        }

        function openFor(el) {
            target = el;
            lastField = el;
            osk.classList.add('open');
            document.body.style.paddingBottom = osk.offsetHeight + 'px';
            el.scrollIntoView({ block: 'center', behavior: 'smooth' });
        }

        function closeKeyboard() {
            osk.classList.remove('open');
            document.body.style.paddingBottom = '';
        }

        function insert(text) {
            if (!target) return;

            let start = null;
            let end = null;
            try { start = target.selectionStart; end = target.selectionEnd; } catch (e) {}

            const value = target.value;

            if (start === null) {
                target.value = value + text; // number and email boxes have no caret
            } else {
                target.value = value.slice(0, start) + text + value.slice(end);
                try { target.setSelectionRange(start + text.length, start + text.length); } catch (e) {}
            }

            target.dispatchEvent(new Event('input', { bubbles: true }));
        }

        function backspace() {
            if (!target) return;

            let start = null;
            let end = null;
            try { start = target.selectionStart; end = target.selectionEnd; } catch (e) {}

            const value = target.value;

            if (start === null) {
                target.value = value.slice(0, -1);
            } else if (start !== end) {
                target.value = value.slice(0, start) + value.slice(end);
                try { target.setSelectionRange(start, start); } catch (e) {}
            } else if (start > 0) {
                target.value = value.slice(0, start - 1) + value.slice(start);
                try { target.setSelectionRange(start - 1, start - 1); } catch (e) {}
            }

            target.dispatchEvent(new Event('input', { bubbles: true }));
        }

        function press(key) {
            if (key === 'shift') { shift = !shift; render(); return; }
            if (key === 'caps') { caps = !caps; render(); return; }
            if (key === 'back') { backspace(); return; }
            if (key === 'space') { insert(' '); return; }
            if (key === 'hide') { closeKeyboard(); return; }

            if (key === 'enter') {
                if (target && target.tagName === 'TEXTAREA') { insert('\n'); return; }

                const form = target ? target.form : null;
                closeKeyboard();

                if (form) { form.requestSubmit ? form.requestSubmit() : form.submit(); }
                return;
            }

            const isLetter = /^[a-z]$/.test(key);
            insert(isLetter && (shift || caps) ? key.toUpperCase() : key);

            if (shift) { shift = false; render(); }
        }

        // The keys must never take focus away from the box being typed into.
        osk.addEventListener('mousedown', function (e) { e.preventDefault(); });

        osk.addEventListener('click', function (e) {
            const button = e.target.closest('button[data-key]');
            if (button) press(button.dataset.key);
        });

        // A finger on a text box (not a scanner, not the page itself) opens the keyboard.
        document.addEventListener('pointerdown', function (e) {
            if (!isKiosk()) return;

            const field = e.target.closest(FIELDS);
            touched = !!field && (e.pointerType === 'touch' || e.pointerType === 'pen');

            if (!field && !e.target.closest('#osk') && !e.target.closest('#osk-open')) closeKeyboard();
        }, true);

        document.addEventListener('focusin', function (e) {
            const field = e.target.closest ? e.target.closest(FIELDS) : null;
            if (!field) return;

            lastField = field;

            if (isKiosk() && touched) openFor(field);

            touched = false;
        });

        if (openButton) {
            openButton.addEventListener('click', function () {
                const field = lastField || document.querySelector(FIELDS);
                if (!field) return;

                field.focus();
                openFor(field);
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeKeyboard();
        });

        // In kiosk mode the Windows touch keyboard must not open on top of ours. Physical keyboards and scanners are unaffected.
        function setInputMode(on) {
            document.querySelectorAll(FIELDS).forEach(function (el) {
                if (on && !el.hasAttribute('inputmode')) {
                    el.setAttribute('inputmode', 'none');
                    el.dataset.oskMode = '1';
                } else if (!on && el.dataset.oskMode) {
                    el.removeAttribute('inputmode');
                    delete el.dataset.oskMode;
                }
            });
        }

        // ---------------------------------------------------------------- kiosk mode switch

        function apply(on) {
            root.classList.toggle('kiosk', on);
            if (toggle) toggle.textContent = on ? 'Exit kiosk mode' : 'Kiosk mode';
            setInputMode(on);

            if (!on) closeKeyboard();
        }

        if (toggle) {
            toggle.addEventListener('click', function () {
                const on = !isKiosk();
                write(on);
                apply(on);
            });
        }

        render();
        apply(allowed && read());
    })();
</script>