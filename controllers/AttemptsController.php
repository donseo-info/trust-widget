<?php
class AttemptsController extends Controller
{
    public function index(array $params = []): void
    {
        Auth::requireAuth();

        $db   = $this->db;
        $page = max(1, (int)$this->get('page', '1'));
        $perPage = 50;
        $offset  = ($page - 1) * $perPage;

        $total = (int)$db->queryScalar('SELECT COUNT(*) FROM loader_attempts');

        $rows = $db->query(
            'SELECT la.*, s.name AS site_name, s.domain AS site_domain
             FROM loader_attempts la
             LEFT JOIN sites s ON s.id = la.site_id
             ORDER BY la.created_at DESC
             LIMIT ? OFFSET ?',
            [$perPage, $offset]
        );

        // Group stats by request_domain
        $topDomains = $db->query(
            'SELECT request_domain, COUNT(*) AS cnt, MAX(created_at) AS last_seen
             FROM loader_attempts
             GROUP BY request_domain
             ORDER BY cnt DESC
             LIMIT 20'
        );

        $this->render('admin/attempts/index', [
            'rows'       => $rows,
            'total'      => $total,
            'page'       => $page,
            'pages'      => max(1, (int)ceil($total / $perPage)),
            'topDomains' => $topDomains,
        ]);
    }
}
