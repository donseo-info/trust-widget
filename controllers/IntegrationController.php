<?php
class IntegrationController extends Controller
{
    private IntegrationModel $integrations;
    private SiteModel        $sites;

    public function __construct()
    {
        parent::__construct();
        $this->integrations = new IntegrationModel();
        $this->sites        = new SiteModel();
    }

    /** GET /sites/{site_id}/integrations */
    public function index(array $params = []): void
    {
        Auth::requireAuth();
        $site = $this->requireSite((int)($params['site_id'] ?? 0));
        $this->render('admin/integrations/index', [
            'site'         => $site,
            'integrations' => $this->integrations->allForSite($site['id']),
        ]);
    }

    /** POST /sites/{site_id}/integrations — save one type */
    public function save(array $params = []): void
    {
        Auth::requireAuth();
        $this->requireCsrf();
        $site   = $this->requireSite((int)($params['site_id'] ?? 0));
        $type   = $this->post('type');
        $active = !empty($_POST['is_active']);

        if (!in_array($type, IntegrationModel::TYPES, true)) {
            $this->json(['ok' => false, 'error' => 'invalid type']);
        }

        $config = $this->buildConfig($type);
        $this->integrations->save($site['id'], $type, $config, $active);
        $this->json(['ok' => true]);
    }

    /** POST /sites/{site_id}/integrations/test-telegram */
    public function testTelegram(array $params = []): void
    {
        Auth::requireAuth();
        $this->requireCsrf();
        $token  = trim($_POST['tg_token']  ?? '');
        $chatId = trim($_POST['tg_chat_id'] ?? '');

        if (!$token || !$chatId) {
            $this->json(['ok' => false, 'error' => 'Токен и Chat ID обязательны']);
        }

        $result = $this->sendTelegramMessage($token, $chatId, '✅ Trust Widget: тестовое сообщение. Интеграция работает!');
        $this->json($result);
    }

    /** POST /sites/{site_id}/integrations/test-bitrix */
    public function testBitrix(array $params = []): void
    {
        Auth::requireAuth();
        $this->requireCsrf();
        $webhook = trim($_POST['b24_webhook'] ?? '');

        if (!$webhook) {
            $this->json(['ok' => false, 'error' => 'Webhook обязателен']);
        }

        $fields = [
            'TITLE'     => 'Trust Widget TEST',
            'PHONE'     => [['VALUE' => '+70000000000', 'VALUE_TYPE' => 'WORK']],
            'SOURCE_ID' => 'WEBFORM',
            'COMMENTS'  => 'Тестовый лид из админки Trust Widget',
        ];
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => rtrim($webhook, '/') . '/crm.lead.add.json',
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_POSTFIELDS     => http_build_query(['fields' => $fields]),
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            $this->json(['ok' => false, 'error' => $err]);
        }
        $res = json_decode($raw, true);
        $ok  = isset($res['result']) && $res['result'];
        $this->json(['ok' => $ok, 'error' => $res['error_description'] ?? ($res['error'] ?? null)]);
    }

    // ─── Private ─────────────────────────────────────────────────────────────

    private function buildConfig(string $type): array
    {
        return match ($type) {
            'telegram' => [
                'tg_token'   => trim($_POST['tg_token']   ?? ''),
                'tg_chat_id' => trim($_POST['tg_chat_id'] ?? ''),
            ],
            'bitrix24' => [
                'b24_webhook'      => trim($_POST['b24_webhook'] ?? ''),
                'b24_custom_fields' => $this->extractCustomFields(),
            ],
            'yandex_metrika' => [
                'counter_id'    => preg_replace('/[^0-9]/', '', $_POST['ym_counter_id'] ?? ''),
                'goal_open'     => trim($_POST['ym_goal_open']     ?? ''), // попап: показ
                'goal_lead'     => trim($_POST['ym_goal_lead']     ?? ''), // попап: заявка
                'goal_callback' => trim($_POST['ym_goal_callback'] ?? ''), // звонок: заявка
            ],
            default => [],
        };
    }

    private function extractCustomFields(): array
    {
        $keys = $_POST['cf_key']   ?? [];
        $vals = $_POST['cf_value'] ?? [];
        $out  = [];
        foreach ($keys as $i => $key) {
            $key = trim($key);
            $val = trim($vals[$i] ?? '');
            if ($key !== '') {
                $out[$key] = $val;
            }
        }
        return $out;
    }

    private function sendTelegramMessage(string $token, string $chatId, string $text): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => 'https://api.telegram.org/bot' . $token . '/sendMessage',
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_POSTFIELDS     => ['chat_id' => $chatId, 'text' => $text],
        ]);
        $res = json_decode(curl_exec($ch), true);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) return ['ok' => false, 'error' => $err];
        return ['ok' => (bool)($res['ok'] ?? false), 'error' => $res['description'] ?? null];
    }

    private function requireSite(int $id): array
    {
        $site = $this->sites->find($id);
        if (!$site) $this->abort(404, 'Site not found');
        return $site;
    }
}
