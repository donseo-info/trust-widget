<?php
class SiteWidgetModel extends Model
{
    protected string $table = 'site_widgets';

    /** Get widget config for a site+type. Returns decoded config array or []. */
    public function getConfig(int $siteId, int $widgetTypeId): array
    {
        $row = $this->db->queryOne(
            'SELECT config FROM site_widgets WHERE site_id = ? AND widget_type_id = ?',
            [$siteId, $widgetTypeId]
        );
        return $row ? (json_decode($row['config'], true) ?: []) : [];
    }

    /** Save (upsert) widget config. */
    public function saveConfig(int $siteId, int $widgetTypeId, array $config): void
    {
        $json = json_encode($config, JSON_UNESCAPED_UNICODE);

        if ($this->db->exists('site_widgets', 'site_id = ? AND widget_type_id = ?', [$siteId, $widgetTypeId])) {
            $this->db->update('site_widgets', ['config' => $json, 'is_active' => 1],
                              'site_id = ? AND widget_type_id = ?', [$siteId, $widgetTypeId]);
        } else {
            $this->db->insert('site_widgets', [
                'site_id'        => $siteId,
                'widget_type_id' => $widgetTypeId,
                'config'         => $json,
                'is_active'      => 1,
            ]);
        }
    }

    /** Toggle widget is_active for a site. Returns new state. */
    public function toggle(int $siteId, int $widgetTypeId): int
    {
        $this->db->execute(
            'UPDATE site_widgets SET is_active = 1 - is_active WHERE site_id = ? AND widget_type_id = ?',
            [$siteId, $widgetTypeId]
        );
        return (int)$this->db->queryScalar(
            'SELECT is_active FROM site_widgets WHERE site_id = ? AND widget_type_id = ?',
            [$siteId, $widgetTypeId]
        );
    }

    /** All widgets with their status for a site. */
    public function allForSite(int $siteId): array
    {
        return $this->db->query(
            'SELECT wt.id, wt.slug, wt.name, wt.icon,
                    COALESCE(sw.is_active, 0)  AS is_active,
                    sw.config,
                    sw.updated_at
             FROM widget_types wt
             LEFT JOIN site_widgets sw ON sw.widget_type_id = wt.id AND sw.site_id = ?
             WHERE wt.is_active = 1
             ORDER BY wt.sort_order',
            [$siteId]
        );
    }
}
