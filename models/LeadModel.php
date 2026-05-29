<?php
class LeadModel extends Model
{
    protected string $table = 'leads';

    /**
     * Save a lead.
     *
     * @param int    $siteId
     * @param int    $widgetTypeId
     * @param string $phone        Already cleaned digits, re-stored as-is
     * @param array  $extra        Widget-specific data (variant, messenger, etc.)
     * @param array  $ctx          page_url, referrer, utms, ip, user_agent, ym_client_id, trigger_type
     */
    public function create(int $siteId, int $widgetTypeId, string $phone, array $extra, array $ctx): int
    {
        return $this->db->insert('leads', [
            'site_id'        => $siteId,
            'widget_type_id' => $widgetTypeId,
            'phone'          => $phone,
            'extra'          => json_encode($extra, JSON_UNESCAPED_UNICODE),
            'page_url'       => $ctx['page_url']    ?? '',
            'referrer'       => $ctx['referrer']    ?? '',
            'utm_source'     => $ctx['utm_source']  ?? '',
            'utm_medium'     => $ctx['utm_medium']  ?? '',
            'utm_campaign'   => $ctx['utm_campaign'] ?? '',
            'utm_content'    => $ctx['utm_content'] ?? '',
            'utm_term'       => $ctx['utm_term']    ?? '',
            'ip'             => $ctx['ip']          ?? '',
            'user_agent'     => $ctx['user_agent']  ?? '',
            'ym_client_id'   => $ctx['ym_client_id'] ?? '',
            'trigger_type'   => $ctx['trigger_type'] ?? 'manual',
        ]);
    }

    /** Paginated list for admin. */
    public function paginate(int $siteId = 0, int $page = 1, int $perPage = 25, string $widgetSlug = ''): array
    {
        $offset = ($page - 1) * $perPage;

        $conds  = ['1=1'];
        $params = [];
        if ($siteId)     { $conds[] = 'l.site_id = ?';  $params[] = $siteId; }
        if ($widgetSlug) { $conds[] = 'wt.slug = ?';    $params[] = $widgetSlug; }
        $where = 'WHERE ' . implode(' AND ', $conds);

        $total = (int)$this->db->queryScalar(
            "SELECT COUNT(*) FROM leads l JOIN widget_types wt ON wt.id = l.widget_type_id {$where}",
            $params
        );

        $rows = $this->db->query(
            "SELECT l.*, s.name AS site_name, wt.name AS widget_name, wt.slug AS widget_slug,
                    (SELECT COUNT(*) FROM leads l2
                     WHERE l2.phone = l.phone AND l2.site_id = l.site_id AND l2.id < l.id) > 0 AS is_duplicate
             FROM leads l
             JOIN sites s ON s.id = l.site_id
             JOIN widget_types wt ON wt.id = l.widget_type_id
             {$where}
             ORDER BY l.created_at DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );

        return ['rows' => $rows, 'total' => $total, 'pages' => max(1, (int)ceil($total / $perPage))];
    }

    /** Check if phone already submitted for this site. Returns previous lead id or 0. */
    public function findDuplicate(int $siteId, string $phone): int
    {
        return (int)$this->db->queryScalar(
            'SELECT id FROM leads WHERE site_id = ? AND phone = ? ORDER BY id ASC LIMIT 1',
            [$siteId, $phone]
        );
    }

    /** Total leads for a site. */
    public function countBySite(int $siteId): int
    {
        return $this->db->count('leads', 'site_id = ?', [$siteId]);
    }

    /** Leads today for a site. */
    public function countTodayBySite(int $siteId): int
    {
        return (int)$this->db->queryScalar(
            'SELECT COUNT(*) FROM leads WHERE site_id = ? AND DATE(created_at) = CURDATE()',
            [$siteId]
        );
    }
}
