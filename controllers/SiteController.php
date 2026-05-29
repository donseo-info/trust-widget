<?php
class SiteController extends Controller
{
    private SiteModel $sites;

    public function __construct()
    {
        parent::__construct();
        $this->sites = new SiteModel();
    }

    /** GET /sites — list */
    public function index(array $params = []): void
    {
        Auth::requireAuth();
        $this->render('admin/sites/index', [
            'sites' => $this->sites->allWithStats(),
        ]);
    }

    /** GET /sites/create */
    public function create(array $params = []): void
    {
        Auth::requireAuth();
        $this->render('admin/sites/form', ['site' => null]);
    }

    /** POST /sites — store new site */
    public function store(array $params = []): void
    {
        Auth::requireAuth();
        $this->requireCsrf();

        $name   = $this->post('name');
        $domain = $this->post('domain');

        if (!$name || !$domain) {
            if ($this->isAjax()) {
                $this->json(['ok' => false, 'error' => 'Название и домен обязательны']);
            }
            $this->redirect('/sites/create?error=1');
        }

        $id = $this->sites->create($name, $domain);

        if ($this->isAjax()) {
            $site = $this->sites->find($id);
            $this->json(['ok' => true, 'id' => $id, 'key' => $site['api_key']]);
        }

        $this->redirect('/sites/' . $id);
    }

    /** GET /sites/{id} */
    public function show(array $params = []): void
    {
        Auth::requireAuth();
        $site = $this->requireSite((int)($params['id'] ?? 0));
        $wm   = new SiteWidgetModel();
        $lm   = new LeadModel();

        $this->render('admin/sites/show', [
            'site'    => $site,
            'widgets' => $wm->allForSite($site['id']),
            'leads_count' => $lm->countBySite($site['id']),
            'leads_today' => $lm->countTodayBySite($site['id']),
        ]);
    }

    /** GET /sites/{id}/edit */
    public function edit(array $params = []): void
    {
        Auth::requireAuth();
        $site = $this->requireSite((int)($params['id'] ?? 0));
        $this->render('admin/sites/form', ['site' => $site]);
    }

    /** POST /sites/{id} — update */
    public function update(array $params = []): void
    {
        Auth::requireAuth();
        $this->requireCsrf();
        $site = $this->requireSite((int)($params['id'] ?? 0));

        $name   = $this->post('name');
        $domain = $this->post('domain');

        if ($this->isAjax()) {
            if (!$name || !$domain) {
                $this->json(['ok' => false, 'error' => 'invalid']);
            }
            $this->sites->updateInfo($site['id'], $name, $domain);
            $this->json(['ok' => true]);
        }

        if ($name && $domain) {
            $this->sites->updateInfo($site['id'], $name, $domain);
        }
        $this->redirect('/sites/' . $site['id'] . '?saved=1');
    }

    /** POST /sites/{id}/toggle */
    public function toggle(array $params = []): void
    {
        Auth::requireAuth();
        $this->requireCsrf();
        $site   = $this->requireSite((int)($params['id'] ?? 0));
        $active = $this->sites->toggle($site['id']);
        $this->json(['ok' => true, 'active' => $active]);
    }

    /** POST /sites/{id}/toggle-debug */
    public function toggleDebug(array $params = []): void
    {
        Auth::requireAuth();
        $this->requireCsrf();
        $site  = $this->requireSite((int)($params['id'] ?? 0));
        $debug = $this->sites->toggleDebug($site['id']);
        $this->json(['ok' => true, 'debug' => $debug]);
    }

    /** POST /sites/{id}/delete */
    public function delete(array $params = []): void
    {
        Auth::requireAuth();
        $this->requireCsrf();
        $site = $this->requireSite((int)($params['id'] ?? 0));
        $this->sites->deleteSite($site['id']);

        if ($this->isAjax()) {
            $this->json(['ok' => true]);
        }
        $this->redirect('/sites');
    }

    private function requireSite(int $id): array
    {
        $site = $this->sites->find($id);
        if (!$site) $this->abort(404, 'Site not found');
        return $site;
    }
}
