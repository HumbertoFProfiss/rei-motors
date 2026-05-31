// ===== SIDEBAR MOBILE =====
(function () {
    var menuBtn = document.getElementById('menuBtn');
    var sidebar = document.getElementById('sidebar');
    if (!menuBtn || !sidebar) return;

    menuBtn.addEventListener('click', function () {
        sidebar.classList.toggle('aberto');
    });

    document.addEventListener('click', function (e) {
        if (sidebar.classList.contains('aberto') &&
            !sidebar.contains(e.target) &&
            !menuBtn.contains(e.target)) {
            sidebar.classList.remove('aberto');
        }
    });
})();

// ===== MODAL DE CONFIRMAÇÃO =====
function confirmarAcao(url, titulo, texto) {
    document.getElementById('modalTitulo').textContent = titulo || 'Confirmar ação';
    document.getElementById('modalTexto').textContent = texto || 'Tem certeza que deseja continuar?';
    document.getElementById('modalBtnConfirmar').href = url;
    document.getElementById('modalConfirmacao').classList.add('ativo');
    return false;
}

function fecharModal() {
    var m = document.getElementById('modalConfirmacao');
    if (m) m.classList.remove('ativo');
}

document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('modalConfirmacao');
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) fecharModal();
        });
    }

    // ESC fecha modal
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') fecharModal();
    });

    // Auto-dismiss alertas de sucesso
    document.querySelectorAll('.alert-admin--success').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity 0.5s';
            el.style.opacity = '0';
            setTimeout(function () { if (el.parentNode) el.parentNode.removeChild(el); }, 500);
        }, 4000);
    });

    // Drag & drop na zona de upload
    var uploadZone = document.querySelector('.foto-upload-zone');
    if (uploadZone) {
        uploadZone.addEventListener('dragover', function (e) {
            e.preventDefault();
            uploadZone.classList.add('drag-over');
        });
        uploadZone.addEventListener('dragleave', function () {
            uploadZone.classList.remove('drag-over');
        });
        uploadZone.addEventListener('drop', function (e) {
            e.preventDefault();
            uploadZone.classList.remove('drag-over');
            var input = uploadZone.querySelector('input[type="file"]');
            if (input && e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                input.dispatchEvent(new Event('change'));
            }
        });
    }

    // Preview de fotos antes do upload
    var fotoInput = document.getElementById('fotoInput');
    var previewContainer = document.getElementById('previewContainer');
    if (fotoInput && previewContainer) {
        fotoInput.addEventListener('change', function () {
            previewContainer.innerHTML = '';
            Array.from(this.files).forEach(function (file) {
                if (!file.type.startsWith('image/')) return;
                var reader = new FileReader();
                reader.onload = function (e) {
                    var div = document.createElement('div');
                    div.className = 'foto-item';
                    var img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = file.name;
                    div.appendChild(img);
                    previewContainer.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
        });
    }
});
