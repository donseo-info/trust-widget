<?php
class SiteModel extends Model
{
    protected string $table = 'sites';

    /** Find site by API key. Returns row or null. */
    public function findByKey(string $key): ?array
    {
        return $this->db->queryOne('SELECT * FROM sites WHERE api_key = ? AND is_active = 1', [$key]);
    }

    /** All sites with lead count. */
    public function allWithStats(): array
    {
        return $this->db->query(
            'SELECT s.*,
                    (SELECT COUNT(*) FROM leads l WHERE l.site_id = s.id) AS lead_count
             FROM sites s
             ORDER BY s.created_at DESC'
        );
    }

    /** Create new site. Returns new ID. */
    public function create(string $name, string $domain): int
    {
        $key = bin2hex(random_bytes(12));
        return $this->db->insert('sites', [
            'name'      => $name,
            'domain'    => $this->normalizeDomain($domain),
            'api_key'   => $key,
            'is_active' => 1,
        ]);
    }

    /** Update name and domain. */
    public function updateInfo(int $id, string $name, string $domain): void
    {
        $this->db->update('sites', [
            'name'   => $name,
            'domain' => $this->normalizeDomain($domain),
        ], 'id = ?', [$id]);
    }

    /** Toggle is_active. Returns new state (0 or 1). */
    public function toggle(int $id): int
    {
        $this->db->execute('UPDATE sites SET is_active = 1 - is_active WHERE id = ?', [$id]);
        return (int)$this->db->queryScalar('SELECT is_active FROM sites WHERE id = ?', [$id]);
    }

    public function toggleDebug(int $id): int
    {
        $this->db->execute('UPDATE sites SET debug_mode = 1 - debug_mode WHERE id = ?', [$id]);
        return (int)$this->db->queryScalar('SELECT debug_mode FROM sites WHERE id = ?', [$id]);
    }

    /** Delete site and all related data (FK cascade handles the rest). */
    public function deleteSite(int $id): void
    {
        $this->db->delete('sites', 'id = ?', [$id]);
    }

    /** Strip www. and lowercase. */
    private function normalizeDomain(string $domain): string
    {
        return preg_replace('/^www\./i', '', strtolower(trim($domain)));
    }
}
