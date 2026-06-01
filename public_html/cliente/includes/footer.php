    </main><!-- /.page -->
</div><!-- /.cliente-content -->
</div><!-- /.cliente-wrapper -->

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
