<?php
class WidgetController extends Controller
{
    private SiteModel       $sites;
    private SiteWidgetModel $siteWidgets;
    private WidgetTypeModel $widgetTypes;

    public function __construct()
    {
        parent::__construct();
        $this->sites       = new SiteModel();
        $this->siteWidgets = new SiteWidgetModel();
        $this->widgetTypes = new WidgetTypeModel();
    }

    /** GET /sites/{site_id}/widgets/{slug} */
    public function edit(array $params = []): void
    {
        Auth::requireAuth();
        $site = $this->requireSite((int)($params['site_id'] ?? 0));
        $type = $this->requireWidgetType($params['slug'] ?? '');
        $config = $this->siteWidgets->getConfig($site['id'], $type['id']);

        $view  = 'admin/widgets/' . str_replace('_', '-', $type['slug']);
        $this->render($view, [
            'site'   => $site,
            'type'   => $type,
            'config' => $config,
        ]);
    }

    /** POST /sites/{site_id}/widgets/{slug} — save config */
    public function save(array $params = []): void
    {
        Auth::requireAuth();
        $this->requireCsrf();
        $site   = $this->requireSite((int)($params['site_id'] ?? 0));
        $type   = $this->requireWidgetType($params['slug'] ?? '');

        $config = $this->buildConfig($type['slug']);
        $this->siteWidgets->saveConfig($site['id'], $type['id'], $config);

        if ($this->isAjax()) {
            $this->json(['ok' => true]);
        }
        $this->redirect('/sites/' . $site['id'] . '/widgets/' . $type['slug'] . '?saved=1');
    }

    /** POST /sites/{site_id}/widgets/{slug}/toggle */
    public function toggle(array $params = []): void
    {
        Auth::requireAuth();
        $this->requireCsrf();
        $site   = $this->requireSite((int)($params['site_id'] ?? 0));
        $type   = $this->requireWidgetType($params['slug'] ?? '');
        $active = $this->siteWidgets->toggle($site['id'], $type['id']);
        $this->json(['ok' => true, 'active' => $active]);
    }

    // ─── Config builders per widget type ─────────────────────────────────────

    private function buildConfig(string $slug): array
    {
        return match ($slug) {
            'exit_popup' => $this->buildExitPopupConfig(),
            'callback'   => $this->buildCallbackConfig(),
            default      => [],
        };
    }

    private function buildExitPopupConfig(): array
    {
        return [
            'variants_enabled' => array_filter(['A', 'B', 'C'], fn($v) => !empty($_POST['variant_' . $v])),
            'variants'         => [
                'A' => $this->extractVariantConfig('A'),
                'B' => $this->extractVariantConfig('B'),
                'C' => $this->extractVariantConfig('C'),
            ],
        ];
    }

    private function extractVariantConfig(string $v): array
    {
        $p = fn(string $key) => strip_tags(trim($_POST[$v . '_' . $key] ?? ''));
        return [
            'headline'    => $p('headline'),
            'subtext'     => $p('subtext'),
            'btn_label'   => $p('btn_label'),
            'color_bg'    => preg_replace('/[^#a-fA-F0-9]/', '', $_POST[$v . '_color_bg'] ?? '#1a1a2e'),
            'color_btn'   => preg_replace('/[^#a-fA-F0-9]/', '', $_POST[$v . '_color_btn'] ?? '#e02020'),
            'timer'       => max(30, min(600, (int)($_POST[$v . '_timer'] ?? 300))),
            'messengers'  => array_values(array_intersect(
                (array)($_POST[$v . '_messengers'] ?? []),
                ['tg', 'wa', 'mx']
            )),
        ];
    }

    private function buildCallbackConfig(): array
    {
        $color     = fn(string $k, string $d) => preg_replace('/[^#a-fA-F0-9]/', '', $_POST[$k] ?? $d);
        $str       = fn(string $k, string $d) => strip_tags(trim($_POST[$k] ?? $d));
        $int       = fn(string $k, int $min, int $max, int $d) => max($min, min($max, (int)($_POST[$k] ?? $d)));
        $bool      = fn(string $k) => !empty($_POST[$k]) ? 1 : 0;

        return [
            'button_color'       => $color('button_color', '#25c16f'),
            'pulse_color'        => $color('pulse_color',  '25c16f'),
            'position'           => in_array($_POST['position'] ?? '', ['left', 'right']) ? $_POST['position'] : 'right',
            'bottom_offset'      => $int('bottom_offset', 0, 200, 30),
            'side_offset'        => $int('side_offset',   0, 200, 30),
            'title'              => $str('title',          'Перезвоним за 30 секунд'),
            'subtitle'           => $str('subtitle',       'Оставьте номер — мы сами позвоним'),
            'success_text'       => $str('success_text',   'Спасибо! Перезвоним в течение 30 секунд.'),
            'submit_btn_text'    => $str('submit_btn_text','Перезвоните мне'),
            'badge_text'         => $str('badge_text',     'Перезвонить?'),
            'privacy_url'        => filter_var($_POST['privacy_url'] ?? '', FILTER_SANITIZE_URL),
            'privacy_text'       => $str('privacy_text',   'Политика конфиденциальности'),
            'show_delay'         => $int('show_delay',     0, 120, 5),
            'auto_open'          => $bool('auto_open'),
            'auto_open_scroll'   => (float)number_format(max(0, min(1, (float)($_POST['auto_open_scroll'] ?? 75) / 100)), 2),
            'auto_open_time'     => $int('auto_open_time', 5, 600, 30),
            'auto_open_title'    => $str('auto_open_title',    'Остались вопросы?'),
            'auto_open_subtitle' => $str('auto_open_subtitle', 'Наш специалист проконсультирует вас бесплатно'),
        ];
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    private function requireSite(int $id): array
    {
        $site = $this->sites->find($id);
        if (!$site) $this->abort(404, 'Site not found');
        return $site;
    }

    private function requireWidgetType(string $slug): array
    {
        $type = $this->widgetTypes->findBySlug($slug);
        if (!$type) $this->abort(404, 'Widget type not found');
        return $type;
    }
}
