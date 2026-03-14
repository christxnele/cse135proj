<?php
    require_once 'auth.php';
    require_once 'db.php';

    $viewId = isset($_GET['id']) ? (int)$_GET['id'] : null;
    $report = null;

    if ($viewId) {
        $stmt = $pdo->prepare("SELECT * FROM saved_reports WHERE id = :id");
        $stmt->execute(['id' => $viewId]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$report) {
            header('Location: /report.php');
            exit;
        }
    } else {
        $sections = getAllowedSections();
        if ($currentRole === 'super_admin') {
            $stmt = $pdo->query("SELECT * FROM saved_reports ORDER BY created_at DESC");
        } elseif ($currentRole === 'analyst' && !empty($sections)) {
            $in = implode(',', array_fill(0, count($sections), '?'));
            $stmt = $pdo->prepare("SELECT * FROM saved_reports WHERE category IN ($in) ORDER BY created_at DESC");
            $stmt->execute($sections);
        } else {
            $stmt = $pdo->query("SELECT * FROM saved_reports ORDER BY created_at DESC");
        }
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reports</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
        <nav class="navbar">
            <button class="nav-left" id="hamburger">&#9776;</button>
            <a href="<?= $currentRole === 'viewer' ? '/report.php' : '/reports.php' ?>" class="nav-title">Analytics Dashboard</a>
            <a href="/logout.php" class="nav-right">Logout</a>
        </nav>
        <div class="sidebar" id="sidebar">
            <?php if ($currentRole !== 'viewer'): ?>
                <a href="/reports.php">Analytics Dashboard</a>
                <a href="/dashboard.php">Dashboard (HW4 Checkpoint)</a>
            <?php endif; ?>
            <?php if ($currentRole === 'super_admin'): ?>
                <a href="/admin.php">Manage Accounts</a>
            <?php endif; ?>
            <a href="/report.php">View Reports</a>
        </div>
        <div class="overlay" id="overlay"></div>


    <div class="content">
    <?php if ($report): ?>
        <a href="/report.php">&larr; Back to Reports</a>
        <h2><?= htmlspecialchars($report['name']) ?></h2>
        <p style="color:#666;font-size:0.9em;">Category: <?= htmlspecialchars($report['category']) ?> &middot; Saved: <?= $report['created_at'] ?></p>
        <?php if ($report['analyst_comment']): ?>
            <div style="margin:1em 0;padding:1em;background:#f9f9f9;border-left:3px solid #333;">
                <strong>Analyst Comment:</strong>
                <p><?= nl2br(htmlspecialchars($report['analyst_comment'])) ?></p>
            </div>
        <?php endif; ?>
        <div id="report-charts"></div>
        <script>
        const reportData = <?= json_encode(json_decode($report['report_data'], true) ?? []) ?>;
        const category   = <?= json_encode($report['category']) ?>;
        const chartInstances = {};

        function makeChart(id, config) {
            if (chartInstances[id]) chartInstances[id].destroy();
            const canvas = document.getElementById(id);
            if (!canvas) return;
            chartInstances[id] = new Chart(canvas.getContext('2d'), config);
        }

        function esc(str) {
            if (!str) return '';
            return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        }

        function kpiCard(value, label) {
            return `<div class="kpi-card"><span class="kpi-value">${value}</span><span class="kpi-label">${label}</span></div>`;
        }

        function scoreLabel(metric, val) {
            if (val === null || val === undefined) return '—';
            const n = parseFloat(val);
            let cls = '', text = '';
            if (metric === 'lcp')  { cls = n < 2500 ? 'score-good' : n < 4000 ? 'score-needs' : 'score-poor'; }
            if (metric === 'cls')  { cls = n < 0.1  ? 'score-good' : n < 0.25  ? 'score-needs' : 'score-poor'; }
            if (metric === 'inp')  { cls = n < 200  ? 'score-good' : n < 500   ? 'score-needs' : 'score-poor'; }
            text = cls === 'score-good' ? 'Good' : cls === 'score-needs' ? 'Needs Improvement' : 'Poor';
            return `<span class="${cls}">${text}</span>`;
        }

        const container = document.getElementById('report-charts');

        if (category === 'traffic' && reportData.kpi) {
            const kpi = reportData.kpi;
            const pps = kpi.unique_sessions > 0 ? (kpi.total_pageviews / kpi.unique_sessions).toFixed(1) : '0';
            container.innerHTML = `
                <div class="kpi-row">
                    ${kpiCard(Number(kpi.total_pageviews).toLocaleString(), 'Total Pageviews')}
                    ${kpiCard(Number(kpi.unique_sessions).toLocaleString(), 'Unique Sessions')}
                    ${kpiCard(pps, 'Avg Pages / Session')}
                </div>
                <div class="charts-row">
                    <div class="chart-box"><h3>Pageviews per Day</h3><canvas id="c-daily"></canvas></div>
                    <div class="chart-box"><h3>Top Pages</h3><canvas id="c-pages"></canvas></div>
                </div>
                <h3>Page Breakdown</h3>
                <table class="report-table"><thead><tr><th>URL</th><th>Views</th><th>Sessions</th><th>First Seen</th><th>Last Seen</th></tr></thead>
                <tbody>${(reportData.top_pages||[]).map(r=>`<tr><td>${esc(r.url)}</td><td>${r.views}</td><td>${r.sessions}</td><td>${(r.first_seen||'').slice(0,10)}</td><td>${(r.last_seen||'').slice(0,10)}</td></tr>`).join('')}</tbody></table>`;
            const daily = reportData.pageviews_per_day || [];
            makeChart('c-daily', { type:'line', data:{ labels:daily.map(r=>r.day), datasets:[{ label:'Pageviews', data:daily.map(r=>Number(r.views)), borderColor:'#1565c0', backgroundColor:'rgba(21,101,192,0.1)', tension:0.3, fill:true }]}, options:{ scales:{y:{beginAtZero:true}}, plugins:{legend:{display:false}}}});
            const top = reportData.top_pages || [];
            makeChart('c-pages', { type:'bar', data:{ labels:top.map(r=>{ try{return new URL(r.url).pathname;}catch(e){return r.url;}}), datasets:[{label:'Views',data:top.map(r=>Number(r.views)),backgroundColor:'#1565c0'}]}, options:{scales:{y:{beginAtZero:true}},plugins:{legend:{display:false}}}});
        }

        if (category === 'performance' && reportData.distributions) {
            const dist = reportData.distributions;
            function pctGood(m) {
                const rows = dist.filter(r=>r.metric===m), total = rows.reduce((s,r)=>s+Number(r.count),0);
                const good = rows.filter(r=>r.score==='good').reduce((s,r)=>s+Number(r.count),0);
                return total > 0 ? Math.round(good/total*100) : 0;
            }
            container.innerHTML = `
                <div class="kpi-row">
                    ${kpiCard(pctGood('lcp')+'%','% Good LCP')}
                    ${kpiCard(pctGood('cls')+'%','% Good CLS')}
                    ${kpiCard(pctGood('inp')+'%','% Good INP')}
                </div>
                <div class="charts-row">
                    <div class="chart-box"><h3>Web Vitals Distribution</h3><canvas id="c-dist"></canvas></div>
                    <div class="chart-box"><h3>Avg LCP by Page</h3><canvas id="c-lcp"></canvas></div>
                </div>
                <h3>Per-Page Vitals</h3>
                <table class="report-table"><thead><tr><th>URL</th><th>Avg LCP</th><th>LCP Score</th><th>Avg CLS</th><th>CLS Score</th><th>Avg INP</th><th>INP Score</th><th>Samples</th></tr></thead>
                <tbody>${(reportData.pages||[]).map(p=>`<tr><td>${esc(p.url)}</td><td>${p.avg_lcp??'—'}</td><td>${scoreLabel('lcp',p.avg_lcp)}</td><td>${p.avg_cls!=null?parseFloat(p.avg_cls).toFixed(3):'—'}</td><td>${scoreLabel('cls',p.avg_cls)}</td><td>${p.avg_inp??'—'}</td><td>${scoreLabel('inp',p.avg_inp)}</td><td>${p.samples}</td></tr>`).join('')}</tbody></table>`;
            const scoreTypes = ['good','needs-improvement','poor'];
            const colors = {good:'#43a047','needs-improvement':'#fb8c00',poor:'#e53935'};
            makeChart('c-dist',{type:'bar',data:{labels:['LCP','CLS','INP'],datasets:scoreTypes.map(s=>({label:s,data:['lcp','cls','inp'].map(m=>{const r=dist.find(r=>r.metric===m&&r.score===s);return r?Number(r.count):0;}),backgroundColor:colors[s]}))},options:{scales:{y:{beginAtZero:true}}}});
            const pages=(reportData.pages||[]).filter(p=>p.avg_lcp!==null).slice(0,10);
            makeChart('c-lcp',{type:'bar',data:{labels:pages.map(p=>{try{return new URL(p.url).pathname;}catch(e){return p.url;}}),datasets:[{label:'Avg LCP (ms)',data:pages.map(p=>Number(p.avg_lcp)),backgroundColor:pages.map(p=>p.avg_lcp<2500?'#43a047':p.avg_lcp<4000?'#fb8c00':'#e53935')}]},options:{indexAxis:'y',scales:{x:{beginAtZero:true}},plugins:{legend:{display:false}}}});
        }

        if (category === 'errors' && reportData.kpi) {
            const k = reportData.kpi;
            container.innerHTML = `
                <div class="kpi-row">
                    ${kpiCard(Number(k.js_errors).toLocaleString(),'JS Errors')}
                    ${kpiCard(Number(k.promise_rejections).toLocaleString(),'Promise Rejections')}
                    ${kpiCard(Number(k.resource_errors).toLocaleString(),'Resource Errors')}
                    ${kpiCard(Number(k.affected_sessions).toLocaleString(),'Affected Sessions')}
                </div>
                <div class="charts-row">
                    <div class="chart-box"><h3>Error Counts by Type</h3><canvas id="c-errtype"></canvas></div>
                </div>
                <h3>Top Error Messages</h3>
                <table class="report-table"><thead><tr><th>Type</th><th>Message</th><th>URL</th><th>Count</th></tr></thead>
                <tbody>${(reportData.top_messages||[]).map(r=>`<tr><td>${esc(r.event_type)}</td><td>${esc(r.message)}</td><td>${esc(r.url)}</td><td>${r.occurrences}</td></tr>`).join('')}</tbody></table>`;
            const typeColors={'js-error':'#e53935','promise-rejection':'#fb8c00','resource-error':'#1565c0'};
            makeChart('c-errtype',{type:'bar',data:{labels:(reportData.by_type||[]).map(r=>r.event_type),datasets:[{label:'Count',data:(reportData.by_type||[]).map(r=>Number(r.count)),backgroundColor:(reportData.by_type||[]).map(r=>typeColors[r.event_type]??'#888')}]},options:{scales:{y:{beginAtZero:true}},plugins:{legend:{display:false}}}});
        }

        if (category === 'behavior' && reportData.scroll_depth) {
            const sd = reportData.scroll_depth || [], mt = reportData.mouse_travel || [];
            container.innerHTML = `
                <div class="kpi-row">
                    ${kpiCard(sd.length,'Pages with Scroll Data')}
                    ${kpiCard(mt.length,'Pages with Mouse Data')}
                </div>
                <div class="charts-row">
                    <div class="chart-box"><h3>Avg Scroll Depth (px)</h3><canvas id="c-scroll"></canvas></div>
                    <div class="chart-box"><h3>Avg Mouse Travel (px)</h3><canvas id="c-mouse"></canvas></div>
                </div>
                <h3>Scroll Depth per Page</h3>
                <table class="report-table"><thead><tr><th>URL</th><th>Sessions</th><th>Avg Max Scroll</th><th>Reached 200px</th><th>Reached 500px</th><th>Reached 1000px</th></tr></thead>
                <tbody>${sd.map(r=>`<tr><td>${esc(r.url)}</td><td>${r.sessions}</td><td>${Number(r.avg_max_scroll_px).toLocaleString()}</td><td>${r.reached_200}</td><td>${r.reached_500}</td><td>${r.reached_1000}</td></tr>`).join('')}</tbody></table>`;
            makeChart('c-scroll',{type:'bar',data:{labels:sd.map(r=>{try{return new URL(r.url).pathname;}catch(e){return r.url;}}),datasets:[{label:'Avg Max Scroll (px)',data:sd.map(r=>Number(r.avg_max_scroll_px)),backgroundColor:'#00838f'}]},options:{indexAxis:'y',scales:{x:{beginAtZero:true}},plugins:{legend:{display:false}}}});
            makeChart('c-mouse',{type:'bar',data:{labels:mt.map(r=>{try{return new URL(r.url).pathname;}catch(e){return r.url;}}),datasets:[{label:'Avg Travel (px)',data:mt.map(r=>Number(r.avg_travel_px)),backgroundColor:'#2e7d32'}]},options:{indexAxis:'y',scales:{x:{beginAtZero:true}},plugins:{legend:{display:false}}}});
        }
        </script>

    <?php else: ?>
        <h2>Saved Reports</h2>

        <?php if ($currentRole === 'analyst' || $currentRole === 'super_admin'): ?>
            <a href="/create-report.php">+ Create Report</a>
        <?php endif; ?>

        <?php if (empty($reports)): ?>
            <p>No reports found.</p>
        <?php else: ?>
            <?php foreach ($reports as $r): ?>
                <div class="report-item">
                    <a href="/report.php?id=<?= $r['id'] ?>"><strong><?= htmlspecialchars($r['name']) ?></strong></a>
                    <span><?= htmlspecialchars($r['category']) ?></span>
                    <span><?= $r['created_at'] ?></span>
                    <?php if ($currentRole !== 'viewer'): ?>
                    <form method="POST" action="/delete-report.php">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <button type="submit">Delete</button>
                    </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
    </div>
    <script>
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        const hamburger = document.getElementById('hamburger');

        hamburger.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('open');
        });

        overlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('open');
        });
    </script>
</body>
</html>