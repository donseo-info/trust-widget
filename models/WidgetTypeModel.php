<?php
class WidgetTypeModel extends Model
{
    protected string $table = 'widget_types';

    public function findBySlug(string $slug): ?array
    {
        return $this->db->queryOne('SELECT * FROM widget_types WHERE slug = ?', [$slug]);
    }

    public function allActive(): array
    {
        return $this->db->query('SELECT * FROM widget_types WHERE is_active = 1 ORDER BY sort_order');
    }
}
