<?php $isMunicipalOfficerLayout = isLoggedIn() && currentUserRole() === 'MUNICIPAL_OFFICER'; ?>

</main>

<?php if ($isMunicipalOfficerLayout): ?>
    </div>
<?php else: ?>
    <footer class="main-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> EcoLot LK. Academic prototype system.</p>
        </div>
    </footer>
<?php endif; ?>

</body>
</html>
