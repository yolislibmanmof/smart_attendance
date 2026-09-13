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
        ">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 16px;">⏰</span>
                <span>
                    <strong style="color: var(--nx-text);">Smart Attendance</strong>
                    <span style="opacity: 0.6;">v2.0 Ultimate</span>
                </span>
            </div>
            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                <span>© <?= date('Y') ?> — Dikembangkan dengan <span style="color: #ec4899;">❤</span> untuk produktivitas tim Anda</span>
                <span class="nx-mono" style="color: var(--nx-text-dim);">Server Time: <?= date('H:i:s') ?></span>
            </div>
        </footer>

        </div><!-- /.admin-body -->
    </main>
</div><!-- /.admin-layout -->

<script src="../assets/js/admin.js"></script>
</body>
</html>