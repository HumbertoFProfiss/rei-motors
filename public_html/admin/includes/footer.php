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

<script src="<?= $admin_root ?>assets/js/admin.js"></script>
</body>
</html>
