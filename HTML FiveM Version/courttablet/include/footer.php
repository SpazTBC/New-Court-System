<?php
$menu = "FOOTER";
if ($menu === "FOOTER") {
?>
<footer class="footer mt-auto py-3 bg-dark text-white">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <p class="mb-0">&copy; Shawn <?php echo date('Y'); ?> All Rights Reserved.</p>
            </div>
            <div class="col-md-6 text-end">
                <a href="#" class="text-white text-decoration-none me-3">Terms</a>
                <a href="#" class="text-white text-decoration-none me-3">Privacy</a>
                <a href="#" class="text-white text-decoration-none">Contact</a>
            </div>
        </div>
    </div>
</footer>
<?php
}
?>
