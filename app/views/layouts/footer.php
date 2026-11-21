<?php
// app/views/layouts/footer.php
if (!defined('APP_NAME')) exit;
?>
    </div><!-- /page-container -->

    <footer class="app-footer">
        &copy; <?= date('Y') ?> <strong><?= APP_NAME ?></strong> &mdash; <?= APP_INSTITUTION ?> (<?= APP_AUTHOR ?>)
    </footer>
</main>
</div><!-- /app-wrapper -->

<!-- Bootstrap JS (Local first, CDN fallback) -->
<script src="<?= BASE_URL ?>/assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script>
if (typeof bootstrap === 'undefined') {
    var s = document.createElement('script');
    s.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js';
    document.head.appendChild(s);
}
</script>
<!-- Activate Bootstrap dropdowns (also works without BS JS via minimal fallback) -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Pindahkan semua modal ke <body> agar tidak terjebak di dalam
    // tabel/kartu/sidebar (stacking context) yang membuatnya tertutup.
    document.querySelectorAll('.modal').forEach(function (m) {
        if (m.parentElement !== document.body) document.body.appendChild(m);
    });

    // Bersihkan sisa backdrop / kunci scroll jika modal ditutup
    document.addEventListener('hidden.bs.modal', function () {
        if (!document.querySelector('.modal.show')) {
            document.querySelectorAll('.modal-backdrop').forEach(function (b) { b.remove(); });
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
        }
    });

    // Dropdown toggle (Bootstrap handles it, but add fallback for safety)
    document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            var menu = btn.nextElementSibling;
            if (!menu) return;
            var isOpen = menu.classList.contains('show');
            // Close all
            document.querySelectorAll('.dropdown-menu.show').forEach(function(m){ m.classList.remove('show'); });
            if (!isOpen) menu.classList.add('show');
        });
    });
    document.addEventListener('click', function() {
        document.querySelectorAll('.dropdown-menu.show').forEach(function(m){ m.classList.remove('show'); });
    });

    // Alert dismiss
    document.querySelectorAll('.alert .btn-close').forEach(function(btn){
        btn.addEventListener('click', function(){
            btn.closest('.alert').remove();
        });
    });
});
</script>
</body>
</html>
