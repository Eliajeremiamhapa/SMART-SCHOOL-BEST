<?php
// ASSETS_OFFICER/includes/asset_footer.php
?>
    </main>
    
    <script>
        const hamburgerBtn = document.getElementById('hamburgerBtn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        
        if(hamburgerBtn) {
            hamburgerBtn.addEventListener('click', function() {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('active');
            });
        }
        
        if(overlay) {
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
            });
        }
        
        function confirmDelete(assetName) {
            return confirm('Je, una uhakika unataka kufuta mali: ' + assetName + '?\n\nHii action haiwezi kutenduliwa!');
        }
        
        // Auto hide alerts after 5 seconds
        document.querySelectorAll('.alert').forEach(function(alert) {
            setTimeout(function() {
                alert.style.opacity = '0';
                setTimeout(function() { alert.style.display = 'none'; }, 500);
            }, 5000);
        });
    </script>
</body>
</html>