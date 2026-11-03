<?php
// admin/stored_procedures.php - Halaman management stored procedures
include 'components/header.php';

// Get current lab stats
try {
    $stats = $pdo->query("SELECT * FROM get_lab_stats()")->fetch();
} catch (Exception $e) {
    $stats = null;
}
?>

<div class="header-flex">
    <h1>Management Stored Procedures</h1>
    <div>
        <button onclick="refreshStats()" class="btn btn-primary">Refresh Stats</button>
    </div>
</div>

<!-- Current Stats Card -->
<?php if ($stats): ?>
<div class="table-card" style="margin-bottom: 20px;">
    <h3>Current Lab Statistics</h3>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-info">
                <h3><?= number_format($stats['total_news']) ?></h3>
                <p>Total Berita</p>
            </div>
            <div class="stat-icon"><i class="fas fa-newspaper"></i></div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h3><?= number_format($stats['total_projects']) ?></h3>
                <p>Total Proyek</p>
            </div>
            <div class="stat-icon"><i class="fas fa-laptop-code"></i></div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h3><?= number_format($stats['total_members']) ?></h3>
                <p>Total Anggota</p>
            </div>
            <div class="stat-icon"><i class="fas fa-users"></i></div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h3><?= number_format($stats['total_comments']) ?></h3>
                <p>Total Komentar</p>
            </div>
            <div class="stat-icon"><i class="fas fa-comments"></i></div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h3><?= number_format($stats['total_unread_feedback']) ?></h3>
                <p>Feedback Belum Dibaca</p>
            </div>
            <div class="stat-icon"><i class="fas fa-envelope"></i></div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Backup Section -->
<div class="table-card" style="margin-bottom: 20px;">
    <h3>Backup Data Lab</h3>
    <p>Buat backup data lab dalam format JSON</p>
    
    <div style="display: flex; gap: 10px; margin-bottom: 15px;">
        <button onclick="performBackup('full')" class="btn btn-primary">
            <i class="fas fa-database"></i> Full Backup
        </button>
        <button onclick="performBackup('minimal')" class="btn btn-secondary">
            <i class="fas fa-compress"></i> Minimal Backup
        </button>
    </div>
    
    <div id="backupResult"></div>
</div>

<!-- Cleanup Section -->
<div class="table-card" style="margin-bottom: 20px;">
    <h3>Cleanup Data Lama</h3>
    <p>Hapus atau arsipkan data yang sudah tidak relevan</p>
    
    <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 15px;">
        <label>Hapus data lebih dari:</label>
        <input type="number" id="daysOld" value="365" min="1" max="3650" style="width: 80px; padding: 5px;">
        <span>hari</span>
        <button onclick="performCleanup()" class="btn btn-warning">
            <i class="fas fa-broom"></i> Cleanup
        </button>
    </div>
    
    <div id="cleanupResult"></div>
</div>

<!-- Report Section -->
<div class="table-card" style="margin-bottom: 20px;">
    <h3>Generate Laporan Bulanan</h3>
    <p>Generate laporan aktivitas bulanan</p>
    
    <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 15px;">
        <label>Bulan:</label>
        <input type="month" id="reportMonth" value="<?= date('Y-m') ?>" style="padding: 5px;">
        <button onclick="generateReport()" class="btn btn-success">
            <i class="fas fa-file-alt"></i> Generate Report
        </button>
    </div>
    
    <div id="reportResult"></div>
</div>

<!-- Materialized Views Section -->
<div class="table-card">
    <h3>Refresh Materialized Views</h3>
    <p>Refresh manual materialized views untuk data terbaru</p>
    
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <button onclick="refreshView('mv_lab_dashboard_stats')" class="btn btn-info">Dashboard Stats</button>
        <button onclick="refreshView('mv_news_with_stats')" class="btn btn-info">News Stats</button>
        <button onclick="refreshView('mv_project_details')" class="btn btn-info">Project Details</button>
        <button onclick="refreshView('mv_feedback_summary')" class="btn btn-info">Feedback Summary</button>
        <button onclick="refreshView('mv_monthly_activity')" class="btn btn-info">Monthly Activity</button>
        <button onclick="refreshAllViews()" class="btn btn-primary">Refresh All</button>
    </div>
    
    <div id="refreshResult" style="margin-top: 15px;"></div>
</div>

<style>
.alert {
    padding: 10px 15px;
    border-radius: 4px;
    margin: 10px 0;
}
.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
.alert-warning {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}
.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
.alert-info {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}
.loading {
    display: inline-block;
    margin-left: 10px;
}
</style>

<script>
function showAlert(containerId, message, type) {
    const container = document.getElementById(containerId);
    container.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
    setTimeout(() => {
        container.innerHTML = '';
    }, 5000);
}

function showLoading(containerId, show = true) {
    const container = document.getElementById(containerId);
    if (show) {
        container.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Processing...</div>';
    }
}

function refreshStats() {
    location.reload();
}

function performBackup(type) {
    showLoading('backupResult');
    
    fetch('api/backup_lab_data.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            backup_type: type
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let message = `Backup ${type} berhasil dibuat!<br>`;
            message += `File: ${data.data.filename}<br>`;
            message += `Size: ${formatBytes(data.data.size)}<br>`;
            message += `Summary: ${data.data.summary.total_projects} proyek, ${data.data.summary.total_news} berita, ${data.data.summary.total_members} anggota`;

            // Di server tanpa penyimpanan permanen, berkas dikirim lewat tautan unduh.
            if (!data.data.file_path && data.data.download) {
                message += `<br><a href="${data.data.download}" download="${data.data.filename}">Unduh berkas backup</a>`;
            }

            showAlert('backupResult', message, 'success');
        } else {
            showAlert('backupResult', data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('backupResult', 'Terjadi kesalahan saat backup', 'danger');
    });
}

function performCleanup() {
    const daysOld = document.getElementById('daysOld').value;
    
    if (!confirm(`Apakah Anda yakin ingin cleanup data yang lebih lama dari ${daysOld} hari?`)) {
        return;
    }
    
    showLoading('cleanupResult');
    
    fetch('api/cleanup_old_data.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            days_old: parseInt(daysOld)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let message = `Cleanup berhasil! Total ${data.data.total_cleaned} item diproses:<br>`;
            message += `${data.data.details.comments_deleted}<br>`;
            message += `${data.data.details.feedback_deleted}<br>`;
            message += `${data.data.details.projects_archived}<br>`;
            message += `${data.data.details.news_archived}`;
            showAlert('cleanupResult', message, 'success');
        } else {
            showAlert('cleanupResult', data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('cleanupResult', 'Terjadi kesalahan saat cleanup', 'danger');
    });
}

function generateReport() {
    const month = document.getElementById('reportMonth').value;
    
    showLoading('reportResult');
    
    fetch('api/generate_report.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            month: month + '-01'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('reportResult', `Laporan bulanan berhasil dibuat untuk ${month}`, 'success');
        } else {
            showAlert('reportResult', data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('reportResult', 'Terjadi kesalahan saat generate laporan', 'danger');
    });
}

function refreshView(viewName) {
    showLoading('refreshResult');
    
    fetch('api/refresh_view.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            view_name: viewName
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('refreshResult', `${viewName} berhasil di-refresh`, 'success');
        } else {
            showAlert('refreshResult', data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('refreshResult', 'Terjadi kesalahan saat refresh view', 'danger');
    });
}

function refreshAllViews() {
    showLoading('refreshResult');
    
    const views = ['mv_lab_dashboard_stats', 'mv_news_with_stats', 'mv_project_details', 'mv_feedback_summary', 'mv_monthly_activity'];
    let completed = 0;
    let errors = [];
    
    views.forEach(view => {
        fetch('api/refresh_view.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                view_name: view
            })
        })
        .then(response => response.json())
        .then(data => {
            completed++;
            if (!data.success) {
                errors.push(`${view}: ${data.message}`);
            }
            
            if (completed === views.length) {
                if (errors.length === 0) {
                    showAlert('refreshResult', 'Semua materialized views berhasil di-refresh', 'success');
                } else {
                    showAlert('refreshResult', 'Beberapa view gagal: ' + errors.join(', '), 'warning');
                }
            }
        })
        .catch(error => {
            completed++;
            errors.push(`${view}: Network error`);
            
            if (completed === views.length) {
                showAlert('refreshResult', 'Beberapa view gagal: ' + errors.join(', '), 'warning');
            }
        });
    });
}

function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}
</script>

<?php include 'components/footer.php'; ?>
