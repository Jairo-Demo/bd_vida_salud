{{-- CU29 Ejecutar comando de voz (Web Speech API). Si el navegador no es compatible, el botón no se muestra. --}}
<style>
    .voz-btn { position: fixed; right: 26px; bottom: 26px; width: 58px; height: 58px; border-radius: 50%; border: none; cursor: pointer; z-index: 300;
               background: linear-gradient(135deg, #0284c7 0%, #0d9488 100%); color: #fff; font-size: 24px; box-shadow: 0 6px 18px rgba(2,132,199,.35); display: none; }
    .voz-btn.escuchando { animation: vozPulso 1.2s infinite; }
    @keyframes vozPulso { 0% { box-shadow: 0 0 0 0 rgba(2,132,199,.55); } 70% { box-shadow: 0 0 0 18px rgba(2,132,199,0); } 100% { box-shadow: 0 0 0 0 rgba(2,132,199,0); } }
    .voz-panel { position: fixed; right: 26px; bottom: 96px; width: 340px; background: #0f172a; color: #e2e8f0; border-radius: 14px; padding: 14px 16px;
                 font-size: 13px; line-height: 1.5; z-index: 300; display: none; box-shadow: 0 10px 30px rgba(0,0,0,.25); }
    .voz-panel b { color: #7dd3fc; } .voz-panel .err { color: #fca5a5; } .voz-panel .ok { color: #86efac; }
    .voz-panel .ayuda { color: #94a3b8; font-size: 11.5px; margin-top: 6px; }
    @media print { .voz-btn, .voz-panel { display: none !important; } }
</style>
<button type="button" class="voz-btn" id="vozBtn" title="Comando de voz (CU29)">🎙</button>
<div class="voz-panel" id="vozPanel"></div>
<script>
(function () {
    const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
    const btn = document.getElementById('vozBtn'), panel = document.getElementById('vozPanel');
    if (!SR) return; // Navegador no compatible: el botón permanece oculto
    btn.style.display = 'block';

    const rec = new SR();
    rec.lang = 'es-ES'; rec.interimResults = false; rec.maxAlternatives = 1;
    let campo = null, escuchando = false, ocultar = null;

    const mostrar = (html, ms) => { panel.innerHTML = html; panel.style.display = 'block'; clearTimeout(ocultar); if (ms) ocultar = setTimeout(() => panel.style.display = 'none', ms); };
    const esCampo = el => el && ((el.tagName === 'TEXTAREA') || (el.tagName === 'INPUT' && ['text', 'search', ''].includes(el.type)));
    const esc = t => t.replace(/[&<>"]/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;'}[c]));

    // Mantener el foco del campo de texto para el dictado (alterno A)
    btn.addEventListener('mousedown', e => e.preventDefault());
    btn.addEventListener('click', () => {
        if (escuchando) { rec.stop(); return; }
        campo = esCampo(document.activeElement) ? document.activeElement : null;
        try { rec.start(); } catch (e) { return; }
    });

    rec.onstart = () => {
        escuchando = true; btn.classList.add('escuchando');
        mostrar(campo ? '🎙 Dictando en el campo seleccionado…' : '🎙 Escuchando… diga un comando<div class="ayuda">Ej.: "ir a citas", "abrir agenda", "buscar paciente Juan Mamani"</div>');
    };
    rec.onend = () => { escuchando = false; btn.classList.remove('escuchando'); };
    rec.onerror = e => {
        const msg = e.error === 'not-allowed' ? 'Permita el acceso al micrófono para usar comandos de voz.' : 'No se pudo reconocer la voz. Intente de nuevo.';
        mostrar('<span class="err">' + msg + '</span>', 4000);
    };

    rec.onresult = async ev => {
        const texto = ev.results[0][0].transcript.trim();
        if (campo) { // Dictado: insertar el texto reconocido en el campo
            const ini = campo.selectionStart ?? campo.value.length;
            const sep = campo.value && !campo.value.endsWith(' ') && ini > 0 ? ' ' : '';
            campo.value = campo.value.slice(0, ini) + sep + texto + campo.value.slice(campo.selectionEnd ?? ini);
            campo.dispatchEvent(new Event('input', { bubbles: true }));
            mostrar('Texto dictado: <b>"' + esc(texto) + '"</b>', 2500);
            return;
        }
        mostrar('Comando reconocido: <b>"' + esc(texto) + '"</b><br>Procesando…');
        try {
            const resp = await fetch("{{ route('voz.comando') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
                           'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ texto })
            });
            const data = await resp.json();
            mostrar('Comando reconocido: <b>"' + esc(texto) + '"</b><br><span class="' + (data.ok ? 'ok' : 'err') + '">' + esc(data.mensaje || '') + '</span>', data.url ? 0 : 5000);
            if (data.ok && data.url) setTimeout(() => window.location.href = data.url, 900);
        } catch (e) {
            mostrar('<span class="err">No se pudo procesar el comando.</span>', 4000);
        }
    };
})();
</script>
