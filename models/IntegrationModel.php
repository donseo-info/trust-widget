<?php
class IntegrationModel extends Model
{
    protected string $table = 'site_integrations';

    const TYPES = ['telegram', 'bitrix24', 'yandex_metrika'];

    /** Get all integrations for a site, keyed by type. */
    public function allForSite(int $siteId): array
    {
        $rows   = $this->db->query(
            'SELECT type, config, is_active FROM site_integrations WHERE site_id = ?',
            [$siteId]
        );
        $result = array_fill_keys(self::TYPES, ['config' => [], 'is_active' => 0]);
        foreach ($rows as $row) {
            $result[$row['type']] = [
                'config'    => json_decode($row['config'], true) ?: [],
                'is_active' => (int)$row['is_active'],
            ];
        }
        return $result;
    }

    /** Get single integration config. */
    public function getConfig(int $siteId, string $type): array
    {
        $row = $this->db->queryOne(
            'SELECT config FROM site_integrations WHERE site_id = ? AND type = ?',
            [$siteId, $type]
        );
        return $row ? (json_decode($row['config'], true) ?: []) : [];
    }

    /** Save (upsert) integration. */
    public function save(int $siteId, string $type, array $config, bool $active): void
    {
        $json = json_encode($config, JSON_UNESCAPED_UNICODE);
        if ($this->db->exists('site_integrations', 'site_id = ? AND type = ?', [$siteId, $type])) {
            $this->db->update('site_integrations',
                ['config' => $json, 'is_active' => (int)$active],
                'site_id = ? AND type = ?',
                [$siteId, $type]
            );
        } else {
            $this->db->insert('site_integrations', [
                'site_id'   => $siteId,
                'type'      => $type,
                'config'    => $json,
                'is_active' => (int)$active,
            ]);
        }
    }

    /** Active telegram config (token + chat_id) or null. */
    public function getTelegram(int $siteId): ?array
    {
        $row = $this->db->queryOne(
            'SELECT config FROM site_integrations WHERE site_id = ? AND type = ? AND is_active = 1',
            [$siteId, 'telegram']
        );
        if (!$row) return null;
        $cfg = json_decode($row['config'], true) ?: [];
        return (!empty($cfg['tg_token']) && !empty($cfg['tg_chat_id'])) ? $cfg : null;
    }

    /** Active Bitrix24 webhook or null. */
    public function getBitrix24(int $siteId): ?array
    {
        $row = $this->db->queryOne(
            'SELECT config FROM site_integrations WHERE site_id = ? AND type = ? AND is_active = 1',
            [$siteId, 'bitrix24']
        );
        if (!$row) return null;
        $cfg = json_decode($row['config'], true) ?: [];
        return !empty($cfg['b24_webhook']) ? $cfg : null;
    }

    /** Active Yandex.Metrika config or null. */
    public function getMetrika(int $siteId): ?array
    {
        $row = $this->db->queryOne(
            'SELECT config FROM site_integrations WHERE site_id = ? AND type = ? AND is_active = 1',
            [$siteId, 'yandex_metrika']
        );
        if (!$row) return null;
        $cfg = json_decode($row['config'], true) ?: [];
        return !empty($cfg['counter_id']) ? $cfg : null;
    }
}
