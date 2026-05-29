<?php
/**
 * Model — thin base class. Each model holds a reference to Database.
 * Models are NOT ActiveRecord — they are plain repositories that return arrays.
 */
abstract class Model
{
    protected Database $db;
    protected string $table = '';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /** Find single row by primary key. */
    public function find(int $id): ?array
    {
        return $this->db->queryOne("SELECT * FROM `{$this->table}` WHERE id = ?", [$id]);
    }

    /** Find all rows, optionally ordered. */
    public function all(string $orderBy = 'id ASC'): array
    {
        return $this->db->query("SELECT * FROM `{$this->table}` ORDER BY {$orderBy}");
    }

    /** Delete by primary key. Returns true if row was deleted. */
    public function destroy(int $id): bool
    {
        return $this->db->delete($this->table, 'id = ?', [$id]) > 0;
    }
}
