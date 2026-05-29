<?php
class StatsController extends Controller
{
    public function index(array $params = []): void
    {
        Auth::requireAuth();

        $db = $this->db;

        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo   = trim($_GET['date_to']   ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) $dateFrom = date('Y-m-d', strtotime('-29 days'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo))   $dateTo   = date('Y-m-d');
        if ($dateTo < $dateFrom) $dateTo = $dateFrom;

        $siteFilter = (int)($_GET['site'] ?? 0);
        $dateWhere  = "AND DATE(l.created_at) BETWEEN ? AND ?";
        $dateParams = [$dateFrom, $dateTo];
        $siteWhereQ = $siteFilter ? 'AND l.site_id = ?' : '';
        $siteParams = $siteFilter ? array_merge($dateParams, [$siteFilter]) : $dateParams;

        // Callback: by day + trigger_type (auto/manual)
        $callbackRows = $db->query(
            "SELECT DATE(l.created_at) AS day, l.trigger_type, COUNT(l.id) AS cnt
             FROM leads l
             JOIN widget_types wt ON wt.id = l.widget_type_id
             WHERE wt.slug = 'callback' {$dateWhere} {$siteWhereQ}
             GROUP BY DATE(l.created_at), l.trigger_type
             ORDER BY day DESC",
            $siteParams
        );
        $callbackDaily  = [];
        $callbackTotals = ['auto' => 0, 'manual' => 0];
        foreach ($callbackRows as $row) {
            $t = $row['trigger_type'] === 'auto' ? 'auto' : 'manual';
            $callbackDaily[$row['day']][$t] = (int)$row['cnt'];
            $callbackTotals[$t] += (int)$row['cnt'];
        }

        // Popup: by day + variant (A/B/C)
        $popupRows = $db->query(
            "SELECT DATE(l.created_at) AS day,
                    UPPER(JSON_UNQUOTE(JSON_EXTRACT(l.extra, '$.variant'))) AS variant,
                    COUNT(l.id) AS cnt
             FROM leads l
             JOIN widget_types wt ON wt.id = l.widget_type_id
             WHERE wt.slug = 'exit_popup' {$dateWhere} {$siteWhereQ}
             GROUP BY DATE(l.created_at), variant
             ORDER BY day DESC",
            $siteParams
        );
        $popupDaily  = [];
        $popupTotals = [];
        foreach ($popupRows as $row) {
            $v = $row['variant'];
            if (!in_array($v, ['A','B','C'], true)) $v = '?';
            $popupDaily[$row['day']][$v] = (int)$row['cnt'];
            $popupTotals[$v] = ($popupTotals[$v] ?? 0) + (int)$row['cnt'];
        }
        ksort($popupTotals);

        $sites = $db->query('SELECT id, name FROM sites ORDER BY name');

        $this->render('admin/stats/index', [
            'date_from'       => $dateFrom,
            'date_to'         => $dateTo,
            'site_filter'     => $siteFilter,
            'callback_daily'  => $callbackDaily,
            'callback_totals' => $callbackTotals,
            'popup_daily'     => $popupDaily,
            'popup_totals'    => $popupTotals,
            'sites'           => $sites,
        ]);
    }
}
