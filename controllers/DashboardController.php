<?php
class DashboardController extends Controller
{
    public function index(array $params = []): void
    {
        Auth::requireAuth();

        $db = $this->db;

        $data = [
            'total_sites'   => (int)$db->queryScalar('SELECT COUNT(*) FROM sites'),
            'active_sites'  => (int)$db->queryScalar('SELECT COUNT(*) FROM sites WHERE is_active = 1'),
            'total_leads'     => (int)$db->queryScalar('SELECT COUNT(*) FROM leads'),
            'leads_today'     => (int)$db->queryScalar('SELECT COUNT(*) FROM leads WHERE DATE(created_at) = CURDATE()'),
            'leads_yesterday' => (int)$db->queryScalar("SELECT COUNT(*) FROM leads WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)"),
            'recent_leads'  => $db->query(
                'SELECT l.*, s.name AS site_name, wt.name AS widget_name
                 FROM leads l
                 JOIN sites s  ON s.id  = l.site_id
                 JOIN widget_types wt ON wt.id = l.widget_type_id
                 ORDER BY l.created_at DESC LIMIT 10'
            ),
            'sites'         => $db->query(
                'SELECT s.*, COUNT(l.id) AS lead_count
                 FROM sites s
                 LEFT JOIN leads l ON l.site_id = s.id
                 GROUP BY s.id
                 ORDER BY s.created_at DESC'
            ),
        ];

        $this->render('admin/dashboard', $data);
    }
}
