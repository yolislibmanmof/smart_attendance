<?php
$pageTitle = "Analitik AI";
require_once '../config/database.php';
require_once '../includes/admin_header.php';

$rows = $pdo->query("SELECT a.*, u.name FROM attendances a JOIN users u ON a.user_id = u.id WHERE a.date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY) AND a.clock_in_time IS NOT NULL ORDER BY a.user_id, a.date ASC")->fetchAll();

$byUser = [];
foreach ($rows as $r) $byUser[$r['user_id']][] = $r;

$insights = [];
$stats = [];
$dayNames = [0=>'Minggu',1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu'];

foreach ($byUser as $uid => $recs) {
    $name  = $recs[0]['name'];
    $total = count($recs);
    $lates = array_values(array_filter($recs, fn($r) => $r['status'] === 'late'));
    $lateRate = $total ? round(count($lates) / $total * 100) : 0;
    $streakLate = 0;
    foreach (array_reverse($recs) as $r) { if ($r['status'] === 'late') $streakLate++; else break; }
    $dayCount = [];
    foreach ($lates as $r) { $d = (int)date('w', strtotime($r['date'])); $dayCount[$d] = ($dayCount[$d] ?? 0) + 1; }
    $favDay = null;
    if ($dayCount) { arsort($dayCount); $favDay = $dayNames[array_key_first($dayCount)]; }
    $hours = []; $anomalies = 0;
    foreach ($recs as $r) {
        if (!$r['clock_out_time']) continue;
        $h = (strtotime($r['clock_out_time']) - strtotime($r['clock_in_time'])) / 3600;
        if (strtotime($r['date']) >= strtotime('-14 days')) $hours[] = $h;
        if ($h < 2) $anomalies++;
    }
    $avgHours = $hours ? array_sum($hours) / count($hours) : 0;
    $lateRecent = 0; $latePrev = 0;
    foreach ($lates as $r) {
        $t = strtotime($r['date']);
        if ($t >= strtotime('-14 days')) $lateRecent++;
        elseif ($t >= strtotime('-28 days')) $latePrev++;
    }
    $trend = $lateRecent > $latePrev ? '📈 naik' : ($lateRecent < $latePrev ? '📉 turun' : '➡️ stabil');
    $predict = $lateRecent > $latePrev ? $lateRecent + 1 : ($lateRecent < $latePrev ? max(0, $lateRecent - 1) : $lateRecent);
    $risk = min(100, round($lateRate * 0.4 + ($avgHours > 9.5 ? 30 : ($avgHours > 8.5 ? 15 : 0)) + min(30, $anomalies * 10) + min(20, $streakLate * 5)));
    $riskLabel = $risk >= 60 ? ['danger', '🔥 Tinggi'] : ($risk >= 30 ? ['warning', '⚠️ Sedang'] : ['success', '✨ Rendah']);
    $riskColor = $risk >= 60 ? '#f43f5e' : ($risk >= 30 ? '#f59e0b' : '#10b981');
    $riskGrad = $risk >= 60 ? 'linear-gradient(135deg, #f43f5e, #ec4899)' : ($risk >= 30 ? 'linear-gradient(135deg, #f59e0b, #f97316)' : 'linear-gradient(135deg, #10b981, #06b6d4)');

    $stats[] = compact('name', 'total', 'lateRate', 'streakLate', 'favDay', 'avgHours', 'anomalies', 'trend', 'predict', 'risk', 'riskLabel', 'riskColor', 'riskGrad');
    if ($streakLate >= 2) $insights[] = ['type' => 'pattern', 'icon' => '🔁', 'text' => "<b>$name</b> terlambat {$streakLate}x beruntun" . ($favDay ? " (pola: sering terlambat hari <b>$favDay</b>)" : '') . " — disarankan pendekatan personal."];
    if ($avgHours > 9.5)  $insights[] = ['type' => 'burnout', 'icon' => '🥵', 'text' => "<b>$name</b> rata-rata kerja " . number_format($avgHours, 1) . " jam/hari dalam 2 minggu — <b>risiko burnout</b>, pertimbangkan redistribusi beban."];
    if ($anomalies > 0)   $insights[] = ['type' => 'anomaly', 'icon' => '🕵️', 'text' => "<b>$name</b> memiliki {$anomalies}x anomali durasi kerja &lt; 2 jam — perlu verifikasi aktivitas."];
    if ($lateRecent > $latePrev) $insights[] = ['type' => 'predict', 'icon' => '📈', 'text' => "Prediksi keterlambatan <b>$name</b> minggu depan: ±{$predict}x (tren naik)."];
}
usort($stats, fn($a, $b) => $b['risk'] <=> $a['risk']);

$moods = $pdo->query("SELECT mood, COUNT(*) c FROM attendances WHERE mood IS NOT NULL AND DATE_FORMAT(date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') GROUP BY mood ORDER BY mood")->fetchAll();
$maxMood = max(array_merge([1], array_column($moods, 'c')));
$totalMood = array_sum(array_column($moods, 'c'));
?>

<style>
    .nx-ai-hero {
        background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4c1d95 100%);
        border-radius: var(--nx-radius-lg);
        padding: 26px 30px;
        margin-bottom: 24px;
        border: 1px solid rgba(139,92,246,0.3);
        box-shadow: 0 10px 40px rgba(99,102,241,0.25);
        position: relative;
        overflow: hidden;
    }
    .nx-ai-hero::before {
        content: '';
        position: absolute;
        top: -50%; right: -10%;
        width: 400px; height: 400px;
        background: radial-gradient(circle, rgba(236,72,153,0.3) 0%, transparent 70%);
        animation: nxFloat 8s ease-in-out infinite;
    }
    .nx-ai-hero-content { position: relative; z-index: 1; display: flex; align-items: center; gap: 18px; flex-wrap: wrap; }
    .nx-ai-badge {
        width: 56px; height: 56px;
        border-radius: 16px;
        background: var(--nx-gradient-1);
        display: flex; align-items: center; justify-content: center;
        font-size: 28px;
        box-shadow: 0 8px 24px rgba(99,102,241,0.5);
        flex-shrink: 0;
    }
    .nx-ai-hero h2 { margin: 0; font-size: 22px; color: #fff; font-weight: 800; letter-spacing: -0.4px; }
    .nx-ai-hero p { margin: 4px 0 0; color: rgba(255,255,255,0.75); font-size: 13px; }

    .nx-insight-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 14px; }
    .nx-insight-card {
        background: var(--nx-surface);
        border: 1px solid var(--nx-border);
        border-radius: var(--nx-radius);
        padding: 18px;
        display: flex;
        gap: 14px;
        align-items: flex-start;
        transition: all 0.25s;
    }
    .nx-insight-card:hover { transform: translateY(-2px); border-color: var(--nx-border-strong); box-shadow: var(--nx-shadow); }
    .nx-insight-card::before { display: none; }
    .nx-insight-ico {
        width: 38px; height: 38px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }
    .nx-insight-card.pattern .nx-insight-ico { background: rgba(245,158,11,0.15); }
    .nx-insight-card.burnout .nx-insight-ico { background: rgba(244,63,94,0.15); }
    .nx-insight-card.anomaly .nx-insight-ico { background: rgba(99,102,241,0.15); }
    .nx-insight-card.predict .nx-insight-ico { background: rgba(16,185,129,0.15); }
    .nx-insight-text { flex: 1; font-size: 13px; color: var(--nx-text); line-height: 1.5; }

    .nx-risk-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; padding: 22px; }

    .nx-risk-card {
        background: var(--nx-surface);
        border: 1px solid var(--nx-border);
        border-radius: var(--nx-radius);
        padding: 18px;
        position: relative;
        overflow: hidden;
        transition: all 0.25s;
    }
    .nx-risk-card:hover { transform: translateY(-3px); border-color: var(--nx-border-strong); box-shadow: var(--nx-shadow-lg); }

    .nx-risk-head { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px; }
    .nx-risk-head .info { flex: 1; }
    .nx-risk-head .info strong { display: block; font-size: 14px; color: #fff; font-weight: 700; margin-bottom: 2px; }
    .nx-risk-head .info small { font-size: 11px; color: var(--nx-text-muted); }

    .nx-risk-score {
        width: 58px; height: 58px;
        border-radius: 50%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        color: #fff;
        font-size: 16px;
        line-height: 1;
        box-shadow: 0 6px 18px rgba(0,0,0,0.25);
        flex-shrink: 0;
        position: relative;
    }
    .nx-risk-score small { font-size: 8px; font-weight: 600; opacity: 0.8; margin-top: 2px; letter-spacing: 0.5px; text-transform: uppercase; }

    .nx-risk-metrics { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 12px; }
    .nx-risk-metric { background: var(--nx-bg-2); border-radius: 6px; padding: 8px 10px; }
    .nx-risk-metric .lbl { font-size: 10px; color: var(--nx-text-muted); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; margin-bottom: 2px; display: block; }
    .nx-risk-metric .val { font-size: 13px; color: #fff; font-weight: 700; }
    .nx-risk-metric .val.mono { font-family: var(--nx-mono); color: var(--nx-cyan); font-size: 12px; }

    .nx-risk-bar-wrap { margin-top: 8px; }
    .nx-risk-bar-label { display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 4px; }
    .nx-risk-bar-label .l { color: var(--nx-text-muted); }
    .nx-risk-bar-label .r { font-weight: 700; }
    .nx-risk-bar { height: 6px; background: var(--nx-bg-2); border-radius: 3px; overflow: hidden; }
    .nx-risk-bar-inner { height: 100%; border-radius: 3px; transition: width 1s ease; }

    .nx-trend-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 8px;
        background: var(--nx-bg-2);
        border-radius: 50px;
        font-size: 11px;
        font-weight: 600;
        color: var(--nx-text);
    }

    .nx-mood-section { padding: 22px; }
    .nx-mood-hero {
        display: flex;
        justify-content: space-around;
        align-items: center;
        gap: 20px;
        padding: 20px;
        background: var(--nx-bg-2);
        border-radius: var(--nx-radius);
        margin-bottom: 22px;
        flex-wrap: wrap;
    }
    .nx-mood-hero .big-emoji { font-size: 60px; line-height: 1; }
    .nx-mood-hero .big-text { flex: 1; min-width: 180px; }
    .nx-mood-hero .big-text h3 { margin: 0; color: #fff; font-size: 20px; font-weight: 800; }
    .nx-mood-hero .big-text p { margin: 4px 0 0; color: var(--nx-text-dim); font-size: 13px; }

    .nx-mood-row { display: flex; align-items: center; gap: 14px; margin-bottom: 14px; }
    .nx-mood-emoji { width: 36px; font-size: 24px; text-align: center; }
    .nx-mood-bar-wrap { flex: 1; height: 14px; background: var(--nx-bg-2); border-radius: 7px; overflow: hidden; position: relative; }
    .nx-mood-bar-inner { height: 100%; border-radius: 7px; transition: width 1s ease; position: relative; }
    .nx-mood-bar-inner.m1 { background: linear-gradient(90deg, #f43f5e, #fb7185); }
    .nx-mood-bar-inner.m2 { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
    .nx-mood-bar-inner.m3 { background: linear-gradient(90deg, #06b6d4, #3b82f6); }
    .nx-mood-bar-inner.m4 { background: linear-gradient(90deg, #10b981, #34d399); }
    .nx-mood-bar-inner.m5 { background: linear-gradient(90deg, #8b5cf6, #ec4899); }
    .nx-mood-count { font-size: 13px; font-weight: 700; color: #fff; min-width: 40px; text-align: right; }
    .nx-mood-pct { font-size: 11px; color: var(--nx-text-muted); min-width: 40px; text-align: right; }
</style>

<!-- ===== AI HERO ===== -->
<div class="nx-ai-hero">
    <div class="nx-ai-hero-content">
        <div class="nx-ai-badge">🧠</div>
        <div>
            <h2>Analitik AI — 90 Hari Terakhir</h2>
            <p>Deteksi pola, prediksi keterlambatan, & insight otomatis untuk tim Anda.</p>
        </div>
    </div>
</div>

<!-- ===== INSIGHT CARDS ===== -->
<div class="panel" style="margin-bottom: 26px;">
    <div class="panel-header">
        <h2>💡 Insight Otomatis</h2>
        <span class="badge info"><?= count($insights) ?> insight</span>
    </div>
    <div style="padding: 22px;">
        <?php if (empty($insights)): ?>
            <div class="empty-row">
                <span class="nx-empty-icon">🎉</span>
                <div><strong>Tim Anda Sehat!</strong></div>
                <small style="color:var(--nx-text-muted);">Tidak ada anomali atau pola negatif yang terdeteksi dalam 90 hari terakhir.</small>
            </div>
        <?php else: ?>
            <div class="nx-insight-grid">
                <?php foreach ($insights as $i): ?>
                <div class="nx-insight-card <?= $i['type'] ?>">
                    <div class="nx-insight-ico"><?= $i['icon'] ?></div>
                    <div class="nx-insight-text"><?= $i['text'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ===== RISK CARDS ===== -->
<div class="panel" style="margin-bottom: 26px;">
    <div class="panel-header">
        <h2>🎯 Skor Risiko per Karyawan</h2>
        <span class="badge info">diurutkan dari tertinggi</span>
    </div>
    <?php if (empty($stats)): ?>
        <div class="empty-row">
            <span class="nx-empty-icon">📊</span>
            <div><strong>Belum cukup data</strong></div>
            <small style="color:var(--nx-text-muted);">Sistem butuh minimal beberapa minggu data untuk menghitung skor risiko.</small>
        </div>
    <?php else: ?>
        <div class="nx-risk-grid">
            <?php foreach ($stats as $s):
                $parts = explode(' ', $s['name']);
                $initials = strtoupper(substr($parts[0], 0, 1)) . (isset($parts[1]) ? strtoupper(substr($parts[1], 0, 1)) : '');
            ?>
            <div class="nx-risk-card">
                <div class="nx-risk-head">
                    <div class="info" style="display: flex; gap: 12px; align-items: center;">
                        <div class="nx-avatar" style="width: 40px; height: 40px; font-size: 13px;"><?= htmlspecialchars($initials) ?></div>
                        <div>
                            <strong><?= htmlspecialchars($s['name']) ?></strong>
                            <small><?= $s['total'] ?> absensi tercatat</small>
                        </div>
                    </div>
                    <div class="nx-risk-score" style="background: <?= $s['riskGrad'] ?>;">
                        <?= $s['risk'] ?>
                        <small>risk</small>
                    </div>
                </div>

                <div class="nx-risk-metrics">
                    <div class="nx-risk-metric">
                        <span class="lbl">% Terlambat</span>
                        <span class="val"><?= $s['lateRate'] ?>%</span>
                    </div>
                    <div class="nx-risk-metric">
                        <span class="lbl">Beruntun</span>
                        <span class="val"><?= $s['streakLate'] ?>x <?= $s['favDay'] ? '<small style="color:var(--nx-text-muted);">('.$s['favDay'].')</small>' : '' ?></span>
                    </div>
                    <div class="nx-risk-metric">
                        <span class="lbl">Rata²/Hari</span>
                        <span class="val mono"><?= $s['avgHours'] ? number_format($s['avgHours'], 1).' jam' : '—' ?></span>
                    </div>
                    <div class="nx-risk-metric">
                        <span class="lbl">Anomali</span>
                        <span class="val"><?= $s['anomalies'] ?>x</span>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <span class="nx-trend-chip"><?= $s['trend'] ?></span>
                    <small style="color: var(--nx-text-muted); font-size: 11px;">Prediksi: <span style="color: #fff; font-weight: 700;">±<?= $s['predict'] ?>x</span></small>
                </div>

                <div class="nx-risk-bar-wrap">
                    <div class="nx-risk-bar-label">
                        <span class="l">Skor Risiko</span>
                        <span class="r" style="color: <?= $s['riskColor'] ?>;"><?= $s['riskLabel'][1] ?></span>
                    </div>
                    <div class="nx-risk-bar">
                        <div class="nx-risk-bar-inner" style="width: <?= $s['risk'] ?>%; background: <?= $s['riskGrad'] ?>;"></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ===== MOOD ===== -->
<div class="panel">
    <div class="panel-header">
        <h2>😊 Mood Perusahaan — Bulan Ini</h2>
        <span class="badge info"><?= $totalMood ?> respon</span>
    </div>
    <div class="nx-mood-section">
        <?php if (empty($moods)): ?>
            <div class="empty-row">
                <span class="nx-empty-icon">😊</span>
                <div><strong>Belum ada data mood</strong></div>
                <small style="color:var(--nx-text-muted);">Karyawan dapat mengisi mood setelah absen pulang. Data akan muncul di sini.</small>
            </div>
        <?php else:
            $emojis = [1=>'😞',2=>'😐',3=>'😊',4=>'😄',5=>'🤩'];
            $moodData = [];
            foreach ([1,2,3,4,5] as $v) {
                $c = 0;
                foreach ($moods as $m) if ((int)$m['mood'] === $v) $c = (int)$m['c'];
                $moodData[$v] = $c;
            }
            $dominant = array_keys($moodData, max($moodData))[0];
            $pct = $totalMood > 0 ? round(($moodData[$dominant] / $totalMood) * 100) : 0;
        ?>
            <div class="nx-mood-hero">
                <div class="big-emoji"><?= $emojis[$dominant] ?></div>
                <div class="big-text">
                    <h3>Mood Dominan: <?= ['','Kurang Baik','Netral','Baik','Sangat Baik','Luar Biasa'][$dominant] ?></h3>
                    <p><?= $moodData[$dominant] ?> dari <?= $totalMood ?> respon (<?= $pct ?>%) memilih mood ini bulan ini.</p>
                </div>
            </div>

            <?php foreach ([1,2,3,4,5] as $val):
                $c = $moodData[$val];
                $w = $maxMood ? round(($c / $maxMood) * 100) : 0;
                $pctVal = $totalMood > 0 ? round(($c / $totalMood) * 100) : 0;
            ?>
            <div class="nx-mood-row">
                <div class="nx-mood-emoji"><?= $emojis[$val] ?></div>
                <div class="nx-mood-bar-wrap">
                    <div class="nx-mood-bar-inner m<?= $val ?>" style="width: <?= $w ?>%;"></div>
                </div>
                <div class="nx-mood-count"><?= $c ?></div>
                <div class="nx-mood-pct"><?= $pctVal ?>%</div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>