<?php
class LeadController extends Controller
{
    private LeadModel $leads;

    public function __construct()
    {
        parent::__construct();
        $this->leads = new LeadModel();
    }

    /** GET /leads */
    public function index(array $params = []): void
    {
        Auth::requireAuth();
        $siteId     = (int)$this->get('site');
        $widgetSlug = trim($this->get('widget'));
        $page       = max(1, (int)$this->get('page', '1'));
        $perPage    = 25;

        $data  = $this->leads->paginate($siteId, $page, $perPage, $widgetSlug);
        $sites = $this->db->query('SELECT id, name FROM sites ORDER BY name');

        $this->render('admin/leads/index', [
            'rows'        => $data['rows'],
            'total'       => $data['total'],
            'pages'       => $data['pages'],
            'page'        => $page,
            'site_id'     => $siteId,
            'widget_slug' => $widgetSlug,
            'sites'       => $sites,
        ]);
    }

    /** POST /leads/{id}/delete */
    public function delete(array $params = []): void
    {
        Auth::requireAuth();
        $this->requireCsrf();
        $id = (int)($params['id'] ?? 0);
        $this->leads->destroy($id);
        $this->json(['ok' => true]);
    }
}
