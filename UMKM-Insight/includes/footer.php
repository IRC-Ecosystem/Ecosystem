<?php if(isLoggedIn() && (!isset($activePage) || $activePage !== 'landing')): ?>
        <!-- End of Main Content Wrap (main-wrap) -->
    </div>
<?php endif; ?>

    <!-- Global Toast Container -->
    <div id="toast-container" class="toast-container"></div>

    <!-- Scripts -->
    <script src="assets/js/app.js"></script>
    <script>
        // Inisialisasi data atau notifikasi global jika diperlukan
        <?php if(isLoggedIn()): ?>
            console.log("Logged in as: <?php echo $_SESSION['username']; ?>");
        <?php endif; ?>
    </script>
</body>
</html>
