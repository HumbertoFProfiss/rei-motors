    </main><!-- /.page -->
</div><!-- /.admin-content -->
</div><!-- /.admin-wrapper -->

<!-- MODAL DE CONFIRMAÇÃO GLOBAL -->
<div class="modal-overlay" id="modalConfirmacao">
    <div class="modal-box">
        <h3 class="modal-box__titulo" id="modalTitulo">Confirmar ação</h3>
        <p class="modal-box__texto" id="modalTexto">Tem certeza que deseja continuar?</p>
        <div class="modal-box__acoes">
            <button class="btn-admin btn-admin--secondary" onclick="fecharModal()">Cancelar</button>
            <a href="#" id="modalBtnConfirmar" class="btn-admin btn-admin--danger">Confirmar</a>
        </div>
    </div>
</div>

<p style="text-align:center;font-size:0.7rem;color:#444;padding:12px 0 4px">
    Desenvolvido por <a href="https://rstechbr.com" target="_blank" rel="noopener"
    style="color:#D4AF37;text-decoration:none;font-weight:600">RS TECH</a>
</p>

<script src="<?= $admin_root ?>assets/js/admin.js"></script>
<script>
// Injeta CSRF token automaticamente em todos os formulários POST do admin
(function () {
    var meta = document.querySelector('meta[name="csrf-token"]');
    if (!meta) return;
    var token = meta.getAttribute('content');
    document.querySelectorAll('form').forEach(function (form) {
        var method = (form.getAttribute('method') || 'get').toLowerCase();
        if (method !== 'post') return;
        if (form.querySelector('input[name="csrf_token"]')) return;
        var inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = 'csrf_token';
        inp.value = token;
        form.appendChild(inp);
    });
    // Para fetch/XMLHttpRequest do admin (ex: reordenação de fotos)
    var origFetch = window.fetch;
    window.fetch = function (url, opts) {
        opts = opts || {};
        if (opts.method && opts.method.toUpperCase() === 'POST') {
            opts.headers = opts.headers || {};
            opts.headers['X-CSRF-TOKEN'] = token;
        }
        return origFetch(url, opts);
    };
}());
</script>
</body>
</html>
