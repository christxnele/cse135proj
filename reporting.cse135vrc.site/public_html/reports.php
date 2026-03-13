<?php
require_once 'auth.php';

$isViewer  = ($currentRole === 'viewer');
$canComment = in_array($currentRole, ['analyst', 'super_admin']);

$showTraffic     = $isViewer || canAccessSection('traffic');
$showPerformance = $isViewer || canAccessSection('performance');
$showErrors      = $isViewer || canAccessSection('errors');

$visibleTabs = [];
if ($showTraffic)     $visibleTabs[] = 'traffic';
if ($showPerformance) $visibleTabs[] = 'performance';
if ($showErrors)      $visibleTabs[] = 'errors';
$defaultTab = $visibleTabs[0] ?? null;
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
        <a href="/dashboard.php" class="nav-title">Analytics Dashboard</a>
        <a href="/logout.php" class="nav-right">Logout</a>
    </nav>

    <div class="sidebar" id="sidebar">
        <?php if ($currentRole === 'super_admin'): ?>
            <a href="/admin.php">Manage Accounts</a>
        <?php endif; ?>
        <a href="/reports.php">View Reports</a>
    </div>
    <div class="overlay" id="overlay"></div>

    <div class="content">
        <h2 style="margin-bottom:1em;">Reports</h2>

        <?php if (!$defaultTab): ?>
            <p>You do not have access to any report sections.</p>
        <?php else: ?>

        <div class="tabs-nav">
            <?php if ($showTraffic): ?>
                <button class="tab-btn <?= $defaultTab === 'traffic' ? 'active' : '' ?>" data-tab="traffic">Traffic &amp; Engagement</button>
            <?php endif; ?>
            <?php if ($showPerformance): ?>
                <button class="tab-btn <?= $defaultTab === 'performance' ? 'active' : '' ?>" data-tab="performance">Performance</button>
            <?php endif; ?>
            <?php if ($showErrors): ?>
                <button class="tab-btn <?= $defaultTab === 'errors' ? 'active' : '' ?>" data-tab="errors">Errors &amp; Reliability</button>
            <?php endif; ?>
        </div>

        <!-- ===== TRAFFIC TAB ===== -->
        <?php if ($showTraffic): ?>
        <div class="tab-panel <?= $defaultTab === 'traffic' ? 'active' : '' ?>" id="tab-traffic">
            <p class="loading-msg" id="traffic-loading">Loading&hellip;</p>

            <div class="kpi-row" id="traffic-kpis"></div>

            <div class="charts-row">
                <div class="chart-box">
                    <h3>Pageviews per Day</h3>
                    <canvas id="chart-traffic-daily"></canvas>
                </div>
                <div class="chart-box">
                    <h3>Top 10 Pages by Views</h3>
                    <canvas id="chart-traffic-pages"></canvas>
                </div>
            </div>

            <h3 style="margin-bottom:0.5em;">Page Breakdown</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>URL</th><th>Views</th><th>Sessions</th><th>First Seen</th><th>Last Seen</th>
                    </tr>
                </thead>
                <tbody id="traffic-table-body"></tbody>
            </table>

            <div class="comments-section">
                <h3>Analyst Comments</h3>
                <ul class="comment-list" id="traffic-comments"></ul>
                <?php if ($canComment): ?>
                <form class="comment-form" id="traffic-comment-form">
                    <textarea placeholder="Add a comment about traffic &amp; engagement trends&hellip;" required></textarea>
                    <button type="submit">Post Comment</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ===== PERFORMANCE TAB ===== -->
        <?php if ($showPerformance): ?>
        <div class="tab-panel <?= $defaultTab === 'performance' ? 'active' : '' ?>" id="tab-performance">
            <p class="loading-msg" id="performance-loading">Loading&hellip;</p>

            <div class="kpi-row" id="performance-kpis"></div>

            <div class="charts-row">
                <div class="chart-box">
                    <h3>Web Vitals Score Distribution</h3>
                    <canvas id="chart-perf-dist"></canvas>
                </div>
                <div class="chart-box">
                    <h3>Average LCP by Page</h3>
                    <canvas id="chart-perf-lcp"></canvas>
                </div>
            </div>

            <h3 style="margin-bottom:0.5em;">Per-Page Vitals Averages</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>URL</th><th>Avg LCP (ms)</th><th>LCP Score</th><th>Avg CLS</th><th>CLS Score</th><th>Avg INP (ms)</th><th>INP Score</th><th>Samples</th>
                    </tr>
                </thead>
                <tbody id="performance-table-body"></tbody>
            </table>

            <div class="comments-section">
                <h3>Analyst Comments</h3>
                <ul class="comment-list" id="performance-comments"></ul>
                <?php if ($canComment): ?>
                <form class="comment-form" id="performance-comment-form">
                    <textarea placeholder="Add a comment about Web Vitals or page performance&hellip;" required></textarea>
                    <button type="submit">Post Comment</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ===== ERRORS TAB ===== -->
        <?php if ($showErrors): ?>
        <div class="tab-panel <?= $defaultTab === 'errors' ? 'active' : '' ?>" id="tab-errors">
            <p class="loading-msg" id="errors-loading">Loading&hellip;</p>

            <div class="kpi-row" id="errors-kpis"></div>

            <div class="charts-row">
                <div class="chart-box" style="max-width:480px;">
                    <h3>Error Counts by Type</h3>
                    <canvas id="chart-errors-type"></canvas>
                </div>
            </div>

            <h3 style="margin-bottom:0.5em;">Top Error Messages</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Type</th><th>Message / Source</th><th>URL</th><th>Count</th>
                    </tr>
                </thead>
                <tbody id="errors-table-body"></tbody>
            </table>

            <div class="comments-section">
                <h3>Analyst Comments</h3>
                <ul class="comment-list" id="errors-comments"></ul>
                <?php if ($canComment): ?>
                <form class="comment-form" id="errors-comment-form">
                    <textarea placeholder="Add a comment about errors or reliability issues&hellip;" required></textarea>
                    <button type="submit">Post Comment</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php endif; // end if defaultTab ?>
    </div><!-- .content -->

<script>
const CURRENT_USER = <?= json_encode($currentUser) ?>;
const CAN_COMMENT  = <?= json_encode($canComment) ?>;
const CURRENT_ROLE = <?= json_encode($currentRole) ?>;
const chartInstances = {};
const tabLoaded = {};

// ---- Tab switching ----
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const tab = btn.dataset.tab;
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('tab-' + tab).classList.add('active');
        if (!tabLoaded[tab]) {
            tabLoaded[tab] = true;
            loadTab(tab);
        }
    });
});

function loadTab(name) {
    if (name === 'traffic')     loadTraffic();
    else if (name === 'performance') loadPerformance();
    else if (name === 'errors') loadErrors();
}

// Load the default tab on page load
const defaultTab = <?= json_encode($defaultTab) ?>;
if (defaultTab) {
    tabLoaded[defaultTab] = true;
    loadTab(defaultTab);
}

// ---- Helpers ----
function fmtDate(str) {
    if (!str) return '—';
    return str.slice(0, 10);
}

function kpiCard(value, label) {
    return `<div class="kpi-card"><span class="kpi-value">${value}</span><span class="kpi-label">${label}</span></div>`;
}

function scoreClass(metric, val) {
    if (val === null || val === undefined) return '';
    const n = parseFloat(val);
    if (metric === 'lcp')  return n < 2500 ? 'score-good' : n < 4000 ? 'score-needs' : 'score-poor';
    if (metric === 'cls')  return n < 0.1  ? 'score-good' : n < 0.25  ? 'score-needs' : 'score-poor';
    if (metric === 'inp')  return n < 200  ? 'score-good' : n < 500   ? 'score-needs' : 'score-poor';
    return '';
}
function scoreLabel(metric, val) {
    if (val === null || val === undefined) return '—';
    const cls = scoreClass(metric, val);
    const text = cls === 'score-good' ? 'Good' : cls === 'score-needs' ? 'Needs Improvement' : 'Poor';
    return `<span class="${cls}">${text}</span>`;
}

function esc(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function makeChart(id, config) {
    if (chartInstances[id]) chartInstances[id].destroy();
    const ctx = document.getElementById(id).getContext('2d');
    chartInstances[id] = new Chart(ctx, config);
}

// ---- Comments ----
async function loadComments(reportType) {
    const list = document.getElementById(reportType + '-comments');
    const resp = await fetch('/api/comments?report=' + reportType);
    const data = await resp.json();
    list.innerHTML = '';
    if (!data.length) {
        list.innerHTML = '<li style="color:#aaa;font-style:italic;">No comments yet.</li>';
        return;
    }
    data.forEach(c => {
        const li = document.createElement('li');
        const canDelete = CAN_COMMENT && (CURRENT_ROLE === 'super_admin' || c.author === CURRENT_USER);
        li.innerHTML = `
            ${canDelete ? `<button class="comment-delete" data-id="${c.id}" data-report="${reportType}">Delete</button>` : ''}
            <div class="comment-meta">${esc(c.author)} &middot; ${fmtDate(c.created_at)} ${c.created_at ? c.created_at.slice(11,16) : ''}</div>
            <div>${esc(c.comment_text)}</div>`;
        list.appendChild(li);
    });
    list.querySelectorAll('.comment-delete').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (!confirm('Delete this comment?')) return;
            await fetch('/api/comments/' + btn.dataset.id, { method: 'DELETE' });
            loadComments(btn.dataset.report);
        });
    });
}

if (CAN_COMMENT) {
    ['traffic', 'performance', 'errors'].forEach(tab => {
        const form = document.getElementById(tab + '-comment-form');
        if (!form) return;
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const text = form.querySelector('textarea').value.trim();
            if (!text) return;
            const btn = form.querySelector('button');
            btn.disabled = true;
            await fetch('/api/comments', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ report_type: tab, comment_text: text }),
            });
            form.querySelector('textarea').value = '';
            btn.disabled = false;
            loadComments(tab);
        });
    });
}

// ---- Traffic ----
async function loadTraffic() {
    const loading = document.getElementById('traffic-loading');
    try {
        const resp = await fetch('/api/reports/traffic');
        const data = await resp.json();

        if (data.error) {
            loading.textContent = 'API error: ' + data.error;
            return;
        }
        loading.style.display = 'none';

        const kpi = data.kpi;
        const pps = kpi.unique_sessions > 0
            ? (kpi.total_pageviews / kpi.unique_sessions).toFixed(1)
            : '0';
        document.getElementById('traffic-kpis').innerHTML =
            kpiCard(Number(kpi.total_pageviews).toLocaleString(), 'Total Pageviews') +
            kpiCard(Number(kpi.unique_sessions).toLocaleString(), 'Unique Sessions') +
            kpiCard(pps, 'Avg Pages / Session');

        // Line chart: pageviews per day
        const daily = data.pageviews_per_day;
        makeChart('chart-traffic-daily', {
            type: 'line',
            data: {
                labels: daily.map(r => r.day),
                datasets: [{
                    label: 'Pageviews',
                    data: daily.map(r => Number(r.views)),
                    borderColor: '#1565c0',
                    backgroundColor: 'rgba(21,101,192,0.1)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 3,
                }]
            },
            options: { scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } }
        });

        // Bar chart: top pages
        const top = data.top_pages;
        const labels = top.map(r => {
            try { return new URL(r.url).pathname; } catch(e) { return r.url || '(unknown)'; }
        });
        makeChart('chart-traffic-pages', {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Views',
                    data: top.map(r => Number(r.views)),
                    backgroundColor: '#1565c0',
                }]
            },
            options: {
                scales: { y: { beginAtZero: true } },
                plugins: { legend: { display: false } }
            }
        });

        // Table
        const tbody = document.getElementById('traffic-table-body');
        tbody.innerHTML = top.map(r => `
            <tr>
                <td>${esc(r.url)}</td>
                <td>${Number(r.views).toLocaleString()}</td>
                <td>${Number(r.sessions).toLocaleString()}</td>
                <td>${fmtDate(r.first_seen)}</td>
                <td>${fmtDate(r.last_seen)}</td>
            </tr>`).join('');

    } catch (err) {
        loading.style.display = 'block';
        loading.textContent = 'Failed to load traffic data: ' + err.message;
        console.error('Traffic load error:', err);
    }
    loadComments('traffic');
}

// ---- Performance ----
async function loadPerformance() {
    const loading = document.getElementById('performance-loading');
    try {
        const resp = await fetch('/api/reports/performance');
        const data = await resp.json();
        loading.style.display = 'none';

        // Compute % good per metric from distributions
        const dist = data.distributions;
        function pctGood(metric) {
            const rows = dist.filter(r => r.metric === metric);
            const total = rows.reduce((s, r) => s + Number(r.count), 0);
            const good  = rows.filter(r => r.score === 'good').reduce((s, r) => s + Number(r.count), 0);
            return total > 0 ? Math.round(good / total * 100) : 0;
        }
        document.getElementById('performance-kpis').innerHTML =
            kpiCard(pctGood('lcp') + '%', '% Good LCP') +
            kpiCard(pctGood('cls') + '%', '% Good CLS') +
            kpiCard(pctGood('inp') + '%', '% Good INP');

        // Grouped bar: distribution per metric
        const metrics = ['lcp', 'cls', 'inp'];
        const scoreTypes = ['good', 'needs-improvement', 'poor'];
        const colors = { good: '#43a047', 'needs-improvement': '#fb8c00', poor: '#e53935' };
        const scoreLabels = { good: 'Good', 'needs-improvement': 'Needs Improvement', poor: 'Poor' };

        const datasets = scoreTypes.map(score => ({
            label: scoreLabels[score],
            data: metrics.map(m => {
                const row = dist.find(r => r.metric === m && r.score === score);
                return row ? Number(row.count) : 0;
            }),
            backgroundColor: colors[score],
        }));

        makeChart('chart-perf-dist', {
            type: 'bar',
            data: { labels: ['LCP', 'CLS', 'INP'], datasets },
            options: { scales: { y: { beginAtZero: true } } }
        });

        // Horizontal bar: avg LCP by page (top 10)
        const pages = data.pages.filter(p => p.avg_lcp !== null).slice(0, 10);
        const pageLabels = pages.map(p => {
            try { return new URL(p.url).pathname; } catch(e) { return p.url || '(unknown)'; }
        });
        makeChart('chart-perf-lcp', {
            type: 'bar',
            data: {
                labels: pageLabels,
                datasets: [{
                    label: 'Avg LCP (ms)',
                    data: pages.map(p => Number(p.avg_lcp)),
                    backgroundColor: pages.map(p => p.avg_lcp < 2500 ? '#43a047' : p.avg_lcp < 4000 ? '#fb8c00' : '#e53935'),
                }]
            },
            options: {
                indexAxis: 'y',
                scales: { x: { beginAtZero: true } },
                plugins: { legend: { display: false } }
            }
        });

        // Table
        const tbody = document.getElementById('performance-table-body');
        tbody.innerHTML = data.pages.map(p => `
            <tr>
                <td>${esc(p.url)}</td>
                <td>${p.avg_lcp !== null ? Number(p.avg_lcp).toLocaleString() : '—'}</td>
                <td>${scoreLabel('lcp', p.avg_lcp)}</td>
                <td>${p.avg_cls !== null ? parseFloat(p.avg_cls).toFixed(3) : '—'}</td>
                <td>${scoreLabel('cls', p.avg_cls)}</td>
                <td>${p.avg_inp !== null ? Number(p.avg_inp).toLocaleString() : '—'}</td>
                <td>${scoreLabel('inp', p.avg_inp)}</td>
                <td>${p.samples}</td>
            </tr>`).join('');

    } catch (err) {
        loading.textContent = 'Failed to load performance data.';
    }
    loadComments('performance');
}

// ---- Errors ----
async function loadErrors() {
    const loading = document.getElementById('errors-loading');
    try {
        const resp = await fetch('/api/reports/errors');
        const data = await resp.json();
        loading.style.display = 'none';

        const k = data.kpi;
        document.getElementById('errors-kpis').innerHTML =
            kpiCard(Number(k.js_errors).toLocaleString(), 'JS Errors') +
            kpiCard(Number(k.promise_rejections).toLocaleString(), 'Promise Rejections') +
            kpiCard(Number(k.resource_errors).toLocaleString(), 'Resource Errors') +
            kpiCard(Number(k.affected_sessions).toLocaleString(), 'Affected Sessions');

        const typeColors = {
            'js-error':          '#e53935',
            'promise-rejection': '#fb8c00',
            'resource-error':    '#1565c0',
        };
        makeChart('chart-errors-type', {
            type: 'bar',
            data: {
                labels: data.by_type.map(r => r.event_type),
                datasets: [{
                    label: 'Count',
                    data: data.by_type.map(r => Number(r.count)),
                    backgroundColor: data.by_type.map(r => typeColors[r.event_type] ?? '#888'),
                }]
            },
            options: {
                scales: { y: { beginAtZero: true } },
                plugins: { legend: { display: false } }
            }
        });

        const tbody = document.getElementById('errors-table-body');
        tbody.innerHTML = data.top_messages.map(r => `
            <tr>
                <td>${esc(r.event_type)}</td>
                <td style="max-width:400px;word-break:break-word;">${esc(r.message)}</td>
                <td>${esc(r.url)}</td>
                <td>${Number(r.occurrences).toLocaleString()}</td>
            </tr>`).join('');

    } catch (err) {
        loading.textContent = 'Failed to load error data.';
    }
    loadComments('errors');
}

// ---- Sidebar ----
const sidebar  = document.getElementById('sidebar');
const overlay  = document.getElementById('overlay');
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
