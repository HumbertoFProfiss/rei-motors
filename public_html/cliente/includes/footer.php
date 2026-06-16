    </main><!-- /.page -->
</div><!-- /.cliente-content -->
</div><!-- /.cliente-wrapper -->

<p style="text-align:center;font-size:0.75rem;color:#888;padding:10px 0 4px">
    Site desenvolvido por <a href="https://rstechbr.com" target="_blank" rel="noopener"
    style="color:#D4AF37;text-decoration:none;font-weight:600">RS TECH</a>
</p>

<script>
// Sidebar toggle mobile
const menuBtn = document.getElementById('menuBtn');
const sidebar = document.getElementById('sidebar');
if (menuBtn && sidebar) {
    menuBtn.addEventListener('click', () => sidebar.classList.toggle('aberto'));
    document.addEventListener('click', (e) => {
        if (!sidebar.contains(e.target) && !menuBtn.contains(e.target)) {
            sidebar.classList.remove('aberto');
        }
    });
}
</script>
</body>
</html>
