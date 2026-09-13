<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$isAuthPage = in_array($currentPage, ['index.php', 'register.php']);
?>

<?php if (!$isAuthPage): ?>
        <!-- ===== FOOTER BAR ===== -->
        <footer style="
            margin-top: 40px;
            padding: 20px 32px;
            border-top: 1px solid var(--nx-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: var(--nx-text-muted);
            flex-wrap: wrap;
            gap: 12px;
            background: rgba(255,255,255,0.5);
            backdrop-filter: blur(10px);
        ">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="
                    width: 28px; height: 28px;
                    border-radius: 8px;
                    background: var(--nx-grad-primary);
                    display: flex; align-items: center; justify-content: center;
                    font-size: 14px;
                    box-shadow: 0 4px 10px rgba(79,70,229,0.25);
                ">⏰</div>
                <span>
                    <strong style="color: var(--nx-text);">Smart Attendance</strong>
                    <span style="opacity: 0.6; margin-left: 4px;">v2.0</span>
                </span>
            </div>
            <div style="display: flex; gap: 18px; flex-wrap: wrap;">
                <span>© <?= date('Y') ?> — Dibuat dengan <span style="color: var(--nx-rose);">❤</span> untuk produktivitas Anda</span>
                <span style="font-family: var(--nx-mono); color: var(--nx-primary);"><?= $nxGreet[1] ?> <?= date('H:i') ?></span>
            </div>
        </footer>

        </div><!-- /emp-body -->
    </main>
</div><!-- /emp-layout -->
<?php endif; ?>

<script src="../assets/js/public.js"></script>
</body>
</html>