<?php
/**
 * Plugin Name: Analisis Jam Sibuk (Penunjung & Transaksi)
 * Plugin URI: https://github.com/adeism/slims-analisis-jam-sibuk
 * Description: Laporan ini berfungsi untuk menganalisis jam sibuk perpustakaan berdasarkan aktivitas pengunjung dan transaksi sirkulasi, lengkap dengan perbandingan periode dan visualisasi data.
 * Version: 1.0.0
 * Author: Ade Ismail Siregar (adeismailbox@gmail.com) 2025-08-20 14:07
 */

// Kunci otentikasi untuk SLiMS
define('INDEX_AUTH', '1');

// Memuat file konfigurasi utama SLiMS
require '../../../../sysconfig.inc.php';

// Pembatasan akses berbasis IP
require LIB . 'ip_based_access.inc.php';
do_checkIP('smc');
do_checkIP('smc-reporting');

// Memuat sesi dan otentikasi admin
require SB . 'admin/default/session.inc.php';
require SB . 'admin/default/session_check.inc.php';

// Pengecekan hak akses untuk modul pelaporan
$can_read = utility::havePrivilege('reporting', 'r');
if (!$can_read) {
    die('<div class="errorBox">' . __('You don\'t have enough privileges to access this area!') . '</div>');
}

function fetchData($dbs, $startDate, $untilDate) {
    $report_data = array_fill(0, 24, ['visitors' => 0, 'transactions' => 0]);
    $startDateTime = $startDate . ' 00:00:00';
    $endDateTime = $untilDate . ' 23:59:59';

    $sql_visitors = "SELECT HOUR(checkin_date) AS visit_hour, COUNT(visitor_id) AS total FROM visitor_count WHERE checkin_date BETWEEN ? AND ? GROUP BY visit_hour";
    $stmt_visitors = $dbs->prepare($sql_visitors);
    if ($stmt_visitors) {
        $stmt_visitors->bind_param('ss', $startDateTime, $endDateTime);
        $stmt_visitors->execute();
        $result = $stmt_visitors->get_result();
        while ($row = $result->fetch_assoc()) { $report_data[$row['visit_hour']]['visitors'] = $row['total']; }
        $stmt_visitors->close();
    }

    $sql_transactions = "SELECT HOUR(input_date) AS transaction_hour, COUNT(loan_id) AS total FROM loan WHERE input_date BETWEEN ? AND ? GROUP BY transaction_hour";
    $stmt_transactions = $dbs->prepare($sql_transactions);
    if ($stmt_transactions) {
        $stmt_transactions->bind_param('ss', $startDateTime, $endDateTime);
        $stmt_transactions->execute();
        $result = $stmt_transactions->get_result();
        while ($row = $result->fetch_assoc()) { $report_data[$row['transaction_hour']]['transactions'] = $row['total']; }
        $stmt_transactions->close();
    }
    
    return $report_data;
}

$page_title = __('Analisis Jam Sibuk (Pengunjung & Transaksi)');
$report_title = '';
$reportView = isset($_GET['reportView']) ? true : false;

if (!$reportView) {
    include SB . 'admin/default/header.php';
    $base_url = $_SERVER['PHP_SELF'] . '?mod=reporting&p='.basename(__FILE__, '.php').'&reportView=true';
?>
    <style>
        #report-container { display: flex; flex-direction: row; }
        #report-filter-sidebar { width: 280px; min-width: 280px; padding: 15px; border-right: 1px solid #ddd; background-color: #f9f9f9; }
        #report-iframe-container { flex-grow: 1; padding-left: 15px; }
        .filter-group { margin-bottom: 25px; }
        .filter-group .btn-group-vertical .btn { text-align: left; }
    </style>
    <div id="report-container">
        <div id="report-filter-sidebar">
            <h3><?php echo __('Filter Laporan'); ?></h3><hr>
            <div class="filter-group">
                <h5><?=__('Periode Tunggal Kustom');?></h5>
                <form method="get" action="<?=$_SERVER['PHP_SELF'];?>" target="reportView">
                    <input type="hidden" name="mod" value="reporting"><input type="hidden" name="p" value="<?=basename(__FILE__, '.php')?>">
                    <div class="form-group" id="range_single">
                        <label><?=__('Dari')?></label><input type="text" name="startDate" class="form-control form-control-sm mb-2" value="<?=date('Y-m-d')?>">
                        <label><?=__('sampai')?></label><input type="text" name="untilDate" class="form-control form-control-sm" value="<?=date('Y-m-d')?>">
                    </div>
                    <input type="submit" name="applyFilter" class="btn btn-success btn-block mt-2" value="<?php echo __('Terapkan Filter Kustom'); ?>" />
                    <input type="hidden" name="reportView" value="true" />
                </form>
            </div>
            <div class="filter-group">
                <h5><?=__('Periode Tunggal Cepat');?></h5>
                <div class="btn-group-vertical d-block">
                    <a href="<?=$base_url . '&range=today'?>" class="btn btn-sm btn-outline-primary" target="reportView"><?=__('Hari Ini')?></a>
                    <a href="<?=$base_url . '&range=yesterday'?>" class="btn btn-sm btn-outline-primary" target="reportView"><?=__('Kemarin')?></a>
                    <a href="<?=$base_url . '&range=this_week'?>" class="btn btn-sm btn-outline-primary" target="reportView"><?=__('Minggu Ini')?></a>
                    <a href="<?=$base_url . '&range=last_week'?>" class="btn btn-sm btn-outline-primary" target="reportView"><?=__('Minggu Lalu')?></a>
                    <a href="<?=$base_url . '&range=this_month'?>" class="btn btn-sm btn-outline-primary" target="reportView"><?=__('Bulan Ini')?></a>
                    <a href="<?=$base_url . '&range=last_month'?>" class="btn btn-sm btn-outline-primary" target="reportView"><?=__('Bulan Lalu')?></a>
                    <a href="<?=$base_url . '&range=this_year'?>" class="btn btn-sm btn-outline-primary" target="reportView"><?=__('Tahun Ini')?></a>
                    <a href="<?=$base_url . '&range=last_year'?>" class="btn btn-sm btn-outline-primary" target="reportView"><?=__('Tahun Lalu')?></a>
                </div>
            </div>
            <div class="filter-group">
                <h5><?=__('Perbandingan');?></h5>
                <div class="btn-group-vertical d-block mb-3">
                    <a href="<?=$base_url . '&compare=day'?>" class="btn btn-sm btn-outline-info" target="reportView"><?=__('Hari Ini vs Kemarin')?></a>
                    <a href="<?=$base_url . '&compare=week'?>" class="btn btn-sm btn-outline-info" target="reportView"><?=__('Minggu Ini vs Minggu Lalu')?></a>
                    <a href="<?=$base_url . '&compare=month'?>" class="btn btn-sm btn-outline-info" target="reportView"><?=__('Bulan Ini vs Bulan Lalu')?></a>
                    <a href="<?=$base_url . '&compare=year'?>" class="btn btn-sm btn-outline-info" target="reportView"><?=__('Tahun Ini vs Tahun Lalu')?></a>
                </div>
                <h6><?=__('Perbandingan Kustom');?></h6>
                <form method="get" action="<?=$_SERVER['PHP_SELF'];?>" target="reportView">
                    <input type="hidden" name="mod" value="reporting"><input type="hidden" name="p" value="<?=basename(__FILE__, '.php')?>">
                    <input type="hidden" name="compare" value="custom">
                    <div class="form-group mb-2"><label class="font-weight-bold">Periode 1</label><div id="range_compare1"><input type="text" name="startDate1" class="form-control form-control-sm mb-1" value="<?=date('Y-m-d', strtotime('-1 week'))?>"><input type="text" name="untilDate1" class="form-control form-control-sm" value="<?=date('Y-m-d', strtotime('-1 week'))?>"></div></div>
                    <div class="form-group"><label class="font-weight-bold">Periode 2</label><div id="range_compare2"><input type="text" name="startDate2" class="form-control form-control-sm mb-1" value="<?=date('Y-m-d')?>"><input type="text" name="untilDate2" class="form-control form-control-sm" value="<?=date('Y-m-d')?>"></div></div>
                    <input type="submit" name="applyFilter" class="btn btn-info btn-block mt-2" value="<?php echo __('Terapkan Perbandingan Kustom'); ?>" /><input type="hidden" name="reportView" value="true" />
                </form>
            </div>
        </div>
        <div id="report-iframe-container"><iframe name="reportView" id="reportView" src="<?=$base_url . '&range=today'?>" frameborder="0" style="width: 100%; height: 85vh;"></iframe></div>
    </div>
    <script>
    $(document).ready(function() {
        const datepickerOptions = { language: '<?=substr($sysconf['default_lang'], 0, 2)?>', format: 'yyyy-mm-dd', autohide: true };
        new DateRangePicker(document.getElementById('range_single'), datepickerOptions);
        new DateRangePicker(document.getElementById('range_compare1'), datepickerOptions);
        new DateRangePicker(document.getElementById('range_compare2'), datepickerOptions);
    })
    </script>
<?php
    include SB . 'admin/default/footer.php';
} else {
    ob_start();
?>
    <style>
        .summary-card-container { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; }
        .summary-card { flex: 1; min-width: 200px; padding: 15px; border-radius: 5px; color: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border: 1px solid #ccc; }
        .summary-card h5 { margin: 0 0 5px 0; font-size: 1em; font-weight: normal; opacity: 0.9; }
        .summary-card .value { font-size: 1.8em; font-weight: bold; }
        .summary-card .comparison-value { font-size: 1.1em; }
        .bg-card-1 { background-color: #007bff; } .bg-card-2 { background-color: #28a745; }
        .bg-card-3 { background-color: #ffc107; color: #333 !important; } .bg-card-4 { background-color: #17a2b8; }
        .progress-bar-container { background-color: #f1f1f1; border-radius: 2px; }
        .progress-bar-value { height: 18px; border-radius: 2px; color: white; text-align: right; padding-right: 5px; font-size: 12px; line-height: 18px; }
        .bg-visitors { background-color: #007bff; } .bg-transactions { background-color: #28a745; }
        .peak-hour-highlight td { background-color: #e2f5e6 !important; font-weight: bold; }
        @media print {
            body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .non-printable { display: none; }
            .printable-area { margin: 0; padding: 0; }
            h2 { font-size: 1.5em; }
            .summary-card { color: #000 !important; }
            .progress-bar-container {
                background-color: #fff !important;
                border: 1px solid #ccc;
            }
            .progress-bar-value {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
<?php
    $is_comparison = isset($_GET['compare']);
    
    if ($is_comparison) {
        $compare_mode = $_GET['compare'];
        $date_ranges = [];
        $labels = [];
        $report_title = '';
        $today = date('Y-m-d');
        $is_period1_inprogress = false;
        $inprogress_text = '';
        switch ($compare_mode) {
            case 'day':
                $date_ranges[0] = ['start' => $today, 'end' => $today];
                $date_ranges[1] = ['start' => date('Y-m-d', strtotime('-1 day')), 'end' => date('Y-m-d', strtotime('-1 day'))];
                $labels = [__('Hari Ini'), __('Kemarin')];
                break;
            case 'week':
                $start_this_week = date('Y-m-d', strtotime('monday this week'));
                $end_this_week_theory = date('Y-m-d', strtotime('sunday this week'));
                if ($end_this_week_theory > $today) { $is_period1_inprogress = true; $inprogress_text = __('catatan: minggu ini belum berakhir'); }
                $date_ranges[0] = ['start' => $start_this_week, 'end' => ($is_period1_inprogress) ? $today : $end_this_week_theory];
                $date_ranges[1] = ['start' => date('Y-m-d', strtotime('monday last week')), 'end' => date('Y-m-d', strtotime('sunday last week'))];
                $labels = [__('Minggu Ini'), __('Minggu Lalu')];
                break;
            case 'month':
                $start_this_month = date('Y-m-01');
                $end_this_month_theory = date('Y-m-t');
                if ($end_this_month_theory > $today) { $is_period1_inprogress = true; $inprogress_text = __('catatan: bulan ini belum berakhir'); }
                $date_ranges[0] = ['start' => $start_this_month, 'end' => ($is_period1_inprogress) ? $today : $end_this_month_theory];
                $date_ranges[1] = ['start' => date('Y-m-01', strtotime('last month')), 'end' => date('Y-m-t', strtotime('last month'))];
                $labels = [__('Bulan Ini'), __('Bulan Lalu')];
                break;
            case 'year':
                $start_this_year = date('Y-01-01');
                $end_this_year_theory = date('Y-12-31');
                if ($end_this_year_theory > $today) { $is_period1_inprogress = true; $inprogress_text = __('catatan: tahun ini belum berakhir'); }
                $date_ranges[0] = ['start' => $start_this_year, 'end' => ($is_period1_inprogress) ? $today : $end_this_year_theory];
                $date_ranges[1] = ['start' => date('Y-01-01', strtotime('last year')), 'end' => date('Y-12-31', strtotime('last year'))];
                $labels = [__('Tahun Ini'), __('Tahun Lalu')];
                break;
            case 'custom':
                $startDate1 = $dbs->escape_string($_GET['startDate1']);
                $untilDate1 = $dbs->escape_string($_GET['untilDate1']);
                $startDate2 = $dbs->escape_string($_GET['startDate2']);
                $untilDate2 = $dbs->escape_string($_GET['untilDate2']);
                $date_ranges[0] = ['start' => $startDate1, 'end' => $untilDate1];
                $date_ranges[1] = ['start' => $startDate2, 'end' => $untilDate2];
                $labels = ['Periode 1', 'Periode 2'];
                break;
        }
        $date_format1 = ($date_ranges[0]['start'] == $date_ranges[0]['end']) ? $date_ranges[0]['start'] : $date_ranges[0]['start'] . ' &ndash; ' . $date_ranges[0]['end'];
        $date_format2 = ($date_ranges[1]['start'] == $date_ranges[1]['end']) ? $date_ranges[1]['start'] : $date_ranges[1]['start'] . ' &ndash; ' . $date_ranges[1]['end'];
        $label1_full = $labels[0] . ' <small>(' . $date_format1 . ')</small>';
        $label2_full = $labels[1] . ' <small>(' . $date_format2 . ')</small>';
        $report_title = $label1_full . ' vs ' . $label2_full;
        if ($is_period1_inprogress) { $report_title .= ' <small class="text-warning font-italic">('.$inprogress_text.')</small>'; }
        
        $data1 = fetchData($dbs, $date_ranges[0]['start'], $date_ranges[0]['end']);
        $data2 = fetchData($dbs, $date_ranges[1]['start'], $date_ranges[1]['end']);
        
        $v1_counts = array_column($data1, 'visitors');
        $t1_counts = array_column($data1, 'transactions');
        $max_v1 = !empty($v1_counts) ? max($v1_counts) : 0;
        $max_t1 = !empty($t1_counts) ? max($t1_counts) : 0;
        $peak_v1_hour = ($max_v1 > 0) ? array_keys($v1_counts, $max_v1)[0] : -1;
        $peak_t1_hour = ($max_t1 > 0) ? array_keys($t1_counts, $max_t1)[0] : -1;
        
        $v2_counts = array_column($data2, 'visitors');
        $t2_counts = array_column($data2, 'transactions');
        $max_v2 = !empty($v2_counts) ? max($v2_counts) : 0;
        $max_t2 = !empty($t2_counts) ? max($t2_counts) : 0;
        $peak_v2_hour = ($max_v2 > 0) ? array_keys($v2_counts, $max_v2)[0] : -1;
        $peak_t2_hour = ($max_t2 > 0) ? array_keys($t2_counts, $max_t2)[0] : -1;
        
        $totals = ['v1' => array_sum($v1_counts), 'v2' => array_sum($v2_counts), 't1' => array_sum($t1_counts), 't2' => array_sum($t2_counts)];
        
        unset($_SESSION['chart']);
        $chart_xAxis = [];
        $chart_data[__('Pengunjung').' ('.$labels[0].')'] = []; $chart_data[__('Pengunjung').' ('.$labels[1].')'] = [];
        $chart_data[__('Transaksi').' ('.$labels[0].')'] = []; $chart_data[__('Transaksi').' ('.$labels[1].')'] = [];
        for ($h=0; $h<24; $h++) {
            $hour_label = str_pad($h, 2, '0', STR_PAD_LEFT);
            $chart_xAxis[$hour_label] = $hour_label . ':00';
            $chart_data[__('Pengunjung').' ('.$labels[0].')'][$hour_label] = $data1[$h]['visitors'];
            $chart_data[__('Pengunjung').' ('.$labels[1].')'][$hour_label] = $data2[$h]['visitors'];
            $chart_data[__('Transaksi').' ('.$labels[0].')'][$hour_label] = $data1[$h]['transactions'];
            $chart_data[__('Transaksi').' ('.$labels[1].')'][$hour_label] = $data2[$h]['transactions'];
        }
        $chart['xAxis'] = $chart_xAxis; $chart['data'] = $chart_data; $chart['title'] = strip_tags($report_title); $_SESSION['chart'] = $chart;

        echo '<div class="printable-area">';
        echo '<h2>'.$page_title.'</h2>';
        echo '<div class="mb-3">' . $report_title;
        echo '<div class="btn-group non-printable float-right"> <a class="s-btn btn btn-default printReport" onclick="window.print()" href="#">' . __('Cetak Halaman Ini') . '</a>
        <a class="s-btn btn btn-default notAJAX openPopUp" href="' . MWB . 'reporting/pop_chart.php" width="800" height="530">' . __('Tampilkan dalam Grafik') . '</a></div></div>' . "\n";
        
        ?>
        <div class="summary-card-container">
            <div class="summary-card bg-card-1"><h5><?=__('Total Pengunjung')?></h5><div class="comparison-value"><?=$labels[0]?>: <strong><?=$totals['v1']?></strong></div><div class="comparison-value"><?=$labels[1]?>: <strong><?=$totals['v2']?></strong></div></div>
            <div class="summary-card bg-card-2"><h5><?=__('Total Transaksi')?></h5><div class="comparison-value"><?=$labels[0]?>: <strong><?=$totals['t1']?></strong></div><div class="comparison-value"><?=$labels[1]?>: <strong><?=$totals['t2']?></strong></div></div>
            <div class="summary-card bg-card-3"><h5><?=__('Jam Puncak Pengunjung')?></h5><div class="comparison-value"><?=$labels[0]?>: <strong><?=($peak_v1_hour != -1) ? str_pad($peak_v1_hour, 2, '0', STR_PAD_LEFT) . ':00' : 'N/A'?></strong></div><div class="comparison-value"><?=$labels[1]?>: <strong><?=($peak_v2_hour != -1) ? str_pad($peak_v2_hour, 2, '0', STR_PAD_LEFT) . ':00' : 'N/A'?></strong></div></div>
            <div class="summary-card bg-card-4"><h5><?=__('Jam Puncak Transaksi')?></h5><div class="comparison-value"><?=$labels[0]?>: <strong><?=($peak_t1_hour != -1) ? str_pad($peak_t1_hour, 2, '0', STR_PAD_LEFT) . ':00' : 'N/A'?></strong></div><div class="comparison-value"><?=$labels[1]?>: <strong><?=($peak_t2_hour != -1) ? str_pad($peak_t2_hour, 2, '0', STR_PAD_LEFT) . ':00' : 'N/A'?></strong></div></div>
        </div>
        <?php
        
        echo '<table class="s-table table-sm table-bordered">';
        echo '<tr class="dataListHeaderPrinted"><th rowspan="2" class="align-middle">'.__('Jam').'</th><th colspan="4">'.__('Pengunjung').'</th><th colspan="4">'.__('Transaksi').'</th></tr>';
        echo '<tr class="dataListHeaderPrinted"><th style="width: 60px;">'.$labels[0].'</th><th>'.__('Aktivitas').'</th><th style="width: 60px;">'.$labels[1].'</th><th>'.__('Aktivitas').'</th><th style="width: 60px;">'.$labels[0].'</th><th>'.__('Aktivitas').'</th><th style="width: 60px;">'.$labels[1].'</th><th>'.__('Aktivitas').'</th></tr>';
        
        for ($h=0; $h<24; $h++) {
            $v_bar1 = ($max_v1 > 0) ? ($data1[$h]['visitors'] / $max_v1) * 100 : 0;
            $v_bar2 = ($max_v2 > 0) ? ($data2[$h]['visitors'] / $max_v2) * 100 : 0;
            $t_bar1 = ($max_t1 > 0) ? ($data1[$h]['transactions'] / $max_t1) * 100 : 0;
            $t_bar2 = ($max_t2 > 0) ? ($data2[$h]['transactions'] / $max_t2) * 100 : 0;

            echo '<tr>';
            echo '<td>'.str_pad($h, 2, '0', STR_PAD_LEFT) . ':00</td>';
            
            echo "<td>{$data1[$h]['visitors']}</td>";
            echo "<td><div class=\"progress-bar-container\"><div class=\"progress-bar-value bg-visitors\" style=\"width: {$v_bar1}%;\"></div></div></td>";
            
            echo "<td>{$data2[$h]['visitors']}</td>";
            echo "<td><div class=\"progress-bar-container\"><div class=\"progress-bar-value bg-visitors\" style=\"width: {$v_bar2}%;\"></div></div></td>";

            echo "<td>{$data1[$h]['transactions']}</td>";
            echo "<td><div class=\"progress-bar-container\"><div class=\"progress-bar-value bg-transactions\" style=\"width: {$t_bar1}%;\"></div></div></td>";

            echo "<td>{$data2[$h]['transactions']}</td>";
            echo "<td><div class=\"progress-bar-container\"><div class=\"progress-bar-value bg-transactions\" style=\"width: {$t_bar2}%;\"></div></div></td>";
            
            echo '</tr>';
        }
        
        echo '<tr class="table-warning font-weight-bold">';
        echo '<td>'.__('Total').'</td>';
        echo '<td colspan="2">'.$totals['v1'].'</td>';
        echo '<td colspan="2">'.$totals['v2'].'</td>';
        echo '<td colspan="2">'.$totals['t1'].'</td>';
        echo '<td colspan="2">'.$totals['t2'].'</td>';
        echo '</tr>';

    } else { // Laporan Periode Tunggal
        $range = $_GET['range'] ?? '';
        $today = date('Y-m-d');
        $is_in_progress = false;
        $in_progress_text = '';
        switch ($range) {
            case 'today': $startDate = $untilDate = $today; $report_title = __('Laporan untuk Hari Ini') . ' (' . $startDate . ')'; break;
            case 'yesterday': $startDate = $untilDate = date('Y-m-d', strtotime('-1 day')); $report_title = __('Laporan untuk Kemarin') . ' (' . $startDate . ')'; break;
            case 'this_week': $startDate = date('Y-m-d', strtotime('monday this week')); $end_this_week_theory = date('Y-m-d', strtotime('sunday this week')); $untilDate = ($end_this_week_theory > $today) ? $today : $end_this_week_theory; if ($end_this_week_theory > $today) { $is_in_progress = true; $in_progress_text = __('catatan: minggu ini belum berakhir'); } $report_title = __('Laporan untuk Minggu Ini') . ' (' . $startDate . ' - ' . $untilDate . ')'; break;
            case 'last_week': $startDate = date('Y-m-d', strtotime('monday last week')); $untilDate = date('Y-m-d', strtotime('sunday last week')); $report_title = __('Laporan untuk Minggu Lalu') . ' (' . $startDate . ' - ' . $untilDate . ')'; break;
            case 'this_month': $startDate = date('Y-m-01'); $end_this_month_theory = date('Y-m-t'); $untilDate = ($end_this_month_theory > $today) ? $today : $end_this_month_theory; if ($end_this_month_theory > $today) { $is_in_progress = true; $in_progress_text = __('catatan: bulan ini belum berakhir'); } $report_title = __('Laporan untuk Bulan Ini') . ' (' . date('F Y') . ', ' . $startDate . ' - ' . $untilDate . ')'; break;
            case 'last_month': $startDate = date('Y-m-01', strtotime('last month')); $untilDate = date('Y-m-t', strtotime('last month')); $report_title = __('Laporan untuk Bulan Lalu') . ' (' . date('F Y', strtotime('last month')) . ')'; break;
            case 'this_year': $startDate = date('Y-01-01'); $end_this_year_theory = date('Y-12-31'); $untilDate = ($end_this_year_theory > $today) ? $today : $end_this_year_theory; if ($end_this_year_theory > $today) { $is_in_progress = true; $in_progress_text = __('catatan: tahun ini belum berakhir'); } $report_title = __('Laporan untuk Tahun Ini') . ' (' . date('Y') . ', ' . $startDate . ' - ' . $untilDate . ')'; break;
            case 'last_year': $startDate = date('Y-01-01', strtotime('last year')); $untilDate = date('Y-12-31', strtotime('last year')); $report_title = __('Laporan untuk Tahun Lalu') . ' (' . date('Y', strtotime('last year')) . ')'; break;
            default: $startDate = isset($_GET['startDate']) ? $dbs->escape_string($_GET['startDate']) : date('Y-m-d'); $untilDate = isset($_GET['untilDate']) ? $dbs->escape_string($_GET['untilDate']) : date('Y-m-d'); $report_title = str_replace(array('{start}', '{end}'), array($startDate, $untilDate), __('Laporan untuk <strong>{start}</strong> sampai <strong>{end}</strong>'));
        }
        $report_data = fetchData($dbs, $startDate, $untilDate);
        $visitor_counts = array_column($report_data, 'visitors');
        $transaction_counts = array_column($report_data, 'transactions');
        $total_visitors = array_sum($visitor_counts);
        $total_transactions = array_sum($transaction_counts);
        $max_visitors = !empty($visitor_counts) ? max($visitor_counts) : 0;
        $max_transactions = !empty($transaction_counts) ? max($transaction_counts) : 0;
        $peak_visitor_hour = ($max_visitors > 0) ? array_keys($visitor_counts, $max_visitors)[0] : -1;
        $peak_transaction_hour = ($max_transactions > 0) ? array_keys($transaction_counts, $max_transactions)[0] : -1;

        unset($_SESSION['chart']);
        $chart_xAxis = []; $chart_data[__('Pengunjung')] = []; $chart_data[__('Transaksi')] = [];
        foreach ($report_data as $hour => $data) {
            $hour_label = str_pad($hour, 2, '0', STR_PAD_LEFT);
            $chart_xAxis[$hour_label] = $hour_label . ':00';
            $chart_data[__('Pengunjung')][$hour_label] = $data['visitors'];
            $chart_data[__('Transaksi')][$hour_label] = $data['transactions'];
        }
        $chart['xAxis'] = $chart_xAxis; $chart['data'] = $chart_data; $chart['title'] = strip_tags($report_title); $_SESSION['chart'] = $chart;

        echo '<div class="printable-area">';
        echo '<h2>'.$page_title.'</h2>';
        echo '<div class="mb-3">' . $report_title;
        if ($is_in_progress) { echo ' <small class="text-warning font-italic">('.$in_progress_text.')</small>'; }
        echo '<div class="btn-group non-printable float-right"> <a class="s-btn btn btn-default printReport" onclick="window.print()" href="#">' . __('Cetak Halaman Ini') . '</a>
        <a class="s-btn btn btn-default notAJAX openPopUp" href="' . MWB . 'reporting/pop_chart.php" width="800" height="530">' . __('Tampilkan dalam Grafik') . '</a></div></div>' . "\n";
        
        ?>
        <div class="summary-card-container">
            <div class="summary-card bg-card-1"><h5><?=__('Total Pengunjung')?></h5><div class="value"><?=$total_visitors?></div></div>
            <div class="summary-card bg-card-2"><h5><?=__('Total Transaksi')?></h5><div class="value"><?=$total_transactions?></div></div>
            <div class="summary-card bg-card-3"><h5><?=__('Jam Puncak Pengunjung')?></h5><div class="value"><?=($peak_visitor_hour != -1) ? str_pad($peak_visitor_hour, 2, '0', STR_PAD_LEFT) . ':00' : 'N/A'?></div></div>
            <div class="summary-card bg-card-4"><h5><?=__('Jam Puncak Transaksi')?></h5><div class="value"><?=($peak_transaction_hour != -1) ? str_pad($peak_transaction_hour, 2, '0', STR_PAD_LEFT) . ':00' : 'N/A'?></div></div>
        </div>
        <table class="s-table table-sm table-bordered">
          <tr class="dataListHeaderPrinted"><th rowspan="2" class="align-middle"><?=__('Jam')?></th><th colspan="2"><?=__('Pengunjung')?></th><th colspan="2"><?=__('Transaksi')?></th></tr>
          <tr class="dataListHeaderPrinted"><th style="width: 50px;"><?=__('Total')?></th><th><?=__('Aktivitas')?></th><th style="width: 50px;"><?=__('Total')?></th><th><?=__('Aktivitas')?></th></tr>
        <?php
        foreach ($report_data as $hour => $data) {
            $visitor_bar_width = ($max_visitors > 0) ? ($data['visitors'] / $max_visitors) * 100 : 0;
            $transaction_bar_width = ($max_transactions > 0) ? ($data['transactions'] / $max_transactions) * 100 : 0;
            $row_class = ($hour === $peak_visitor_hour || $hour === $peak_transaction_hour) ? 'peak-hour-highlight' : '';
            echo "<tr class=\"{$row_class}\">";
            echo '<td>' . str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00</td>';
            echo "<td>{$data['visitors']}</td><td><div class=\"progress-bar-container\"><div class=\"progress-bar-value bg-visitors\" style=\"width: {$visitor_bar_width}%;\"></div></div></td>";
            echo "<td>{$data['transactions']}</td><td><div class=\"progress-bar-container\"><div class=\"progress-bar-value bg-transactions\" style=\"width: {$transaction_bar_width}%;\"></div></div></td>";
            echo '</tr>';
        } ?>
          <tr class="table-warning font-weight-bold"><td><?=__('Total')?></td><td colspan="2"><?=$total_visitors?></td><td colspan="2"><?=$total_transactions?></td></tr>
        </table>
        </div> <?php }

    $content = ob_get_clean();
    require SB . '/admin/' . $sysconf['admin_template']['dir'] . '/pop_iframe_tpl.php';
}
?>
