<?php
class PopupEventModel extends Model
{
    protected string $table = 'popup_events';

    public function recordOpen(int $siteId, string $variant, string $ymId, int $hasYm, string $url, string $referrer, string $ip): int
    {
        return $this->db->insert('popup_events', [
            'site_id'      => $siteId,
            'action'       => 'open',
            'variant'      => $variant,
            'ym_client_id' => $ymId,
            'has_ym'       => $hasYm,
            'url'          => $url,
            'referrer'     => $referrer,
            'ip'           => $ip,
        ]);
    }

    public function recordLead(int $siteId, string $variant, string $phone, string $messenger, string $ymId, int $hasYm, string $url, string $ip): int
    {
        return $this->db->insert('popup_events', [
            'site_id'      => $siteId,
            'action'       => 'lead',
            'variant'      => $variant,
            'phone'        => $phone,
            'messenger'    => $messenger,
            'ym_client_id' => $ymId,
            'has_ym'       => $hasYm,
            'url'          => $url,
            'ip'           => $ip,
        ]);
    }

    /** Check duplicate open within 1 minute by ym_client_id. */
    public function isDuplicateOpen(string $ymId): bool
    {
        if (!$ymId) return false;
        return (bool)$this->db->queryScalar(
            "SELECT 1 FROM popup_events
             WHERE action = 'open' AND ym_client_id = ?
               AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
             LIMIT 1",
            [$ymId]
        );
    }

    /** Stats per variant for a site. */
    public function statsBySite(int $siteId): array
    {
        return $this->db->query(
            "SELECT variant,
                    SUM(action = 'open')    AS opens,
                    SUM(action = 'lead')    AS leads
             FROM popup_events
             WHERE site_id = ?
             GROUP BY variant",
            [$siteId]
        );
    }

    /** Last 7 days opens + leads for chart. */
    public function chartData(int $siteId): array
    {
        return $this->db->query(
            "SELECT DATE(created_at) AS day,
                    SUM(action = 'open') AS opens,
                    SUM(action = 'lead') AS leads
             FROM popup_events
             WHERE site_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
             GROUP BY DATE(created_at)
             ORDER BY day",
            [$siteId]
        );
    }
}
