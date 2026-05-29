<?php
/**
 * Database — PDO wrapper with full CRUD, prepared statements, SQL injection protection.
 *
 * Usage:
 *   $db = Database::getInstance();
 *   $rows = $db->query('SELECT * FROM sites WHERE is_active = ?', [1]);
 *   $id   = $db->insert('sites', ['name' => 'Foo', 'domain' => 'foo.com']);
 *   $db->update('sites', ['name' => 'Bar'], 'id = ?', [1]);
 *   $db->delete('sites', 'id = ?', [1]);
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];
        $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** Run SELECT, return all rows. */
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->prepare($sql, $params);
        return $stmt->fetchAll();
    }

    /** Run SELECT, return first row or null. */
    public function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->prepare($sql, $params);
        $row  = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /** Run SELECT, return single scalar value or null. */
    public function queryScalar(string $sql, array $params = []): mixed
    {
        $stmt = $this->prepare($sql, $params);
        $row  = $stmt->fetch(PDO::FETCH_NUM);
        return $row !== false ? $row[0] : null;
    }

    /**
     * Execute INSERT. Returns last insert ID.
     *
     * @param string $table  Table name (validated against [a-z_] chars only)
     * @param array  $data   Associative array of column => value
     */
    public function insert(string $table, array $data): int
    {
        $this->assertSafeIdentifier($table);
        if (empty($data)) {
            throw new \InvalidArgumentException('Insert data cannot be empty');
        }

        $cols        = array_map([$this, 'quoteIdentifier'], array_keys($data));
        $placeholders = array_fill(0, count($data), '?');

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->quoteIdentifier($table),
            implode(', ', $cols),
            implode(', ', $placeholders)
        );

        $this->prepare($sql, array_values($data));
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Execute UPDATE. Returns number of affected rows.
     *
     * @param string $table  Table name
     * @param array  $data   Associative array of column => value to set
     * @param string $where  WHERE clause with ? placeholders (required — prevents full-table update)
     * @param array  $params Values for WHERE placeholders
     */
    public function update(string $table, array $data, string $where, array $params = []): int
    {
        $this->assertSafeIdentifier($table);
        if (empty($data)) {
            throw new \InvalidArgumentException('Update data cannot be empty');
        }
        if (trim($where) === '') {
            throw new \InvalidArgumentException('WHERE clause is required for update');
        }

        $sets = array_map(
            fn(string $col) => $this->quoteIdentifier($col) . ' = ?',
            array_keys($data)
        );

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $this->quoteIdentifier($table),
            implode(', ', $sets),
            $where
        );

        $stmt = $this->prepare($sql, array_merge(array_values($data), $params));
        return $stmt->rowCount();
    }

    /**
     * Execute DELETE. Returns number of affected rows.
     *
     * @param string $where  WHERE clause with ? placeholders (required)
     * @param array  $params Values for WHERE placeholders
     */
    public function delete(string $table, string $where, array $params = []): int
    {
        $this->assertSafeIdentifier($table);
        if (trim($where) === '') {
            throw new \InvalidArgumentException('WHERE clause is required for delete');
        }

        $sql  = sprintf('DELETE FROM %s WHERE %s', $this->quoteIdentifier($table), $where);
        $stmt = $this->prepare($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Execute arbitrary SQL (for UPDATE/DELETE/INSERT with complex logic).
     * Returns number of affected rows.
     */
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->prepare($sql, $params);
        return $stmt->rowCount();
    }

    /** Check if a row matching WHERE exists. */
    public function exists(string $table, string $where, array $params = []): bool
    {
        $this->assertSafeIdentifier($table);
        $sql = sprintf('SELECT 1 FROM %s WHERE %s LIMIT 1', $this->quoteIdentifier($table), $where);
        return $this->queryScalar($sql, $params) !== null;
    }

    /** Count rows matching WHERE (or all rows if no WHERE). */
    public function count(string $table, string $where = '', array $params = []): int
    {
        $this->assertSafeIdentifier($table);
        $sql = sprintf('SELECT COUNT(*) FROM %s', $this->quoteIdentifier($table));
        if ($where !== '') {
            $sql .= ' WHERE ' . $where;
        }
        return (int)$this->queryScalar($sql, $params);
    }

    /** Begin transaction. */
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    /** Commit transaction. */
    public function commit(): void
    {
        $this->pdo->commit();
    }

    /** Roll back transaction. */
    public function rollback(): void
    {
        $this->pdo->rollBack();
    }

    /**
     * Run $callback inside a transaction. Rolls back on exception and re-throws.
     *
     * @return mixed  Return value of $callback
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    /** Prepare and execute statement with bound parameters. Returns PDOStatement. */
    private function prepare(string $sql, array $params): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** Wrap identifier in backticks. Prevents SQL injection in identifiers. */
    private function quoteIdentifier(string $name): string
    {
        // Strip any backtick that could break out of the quoting
        return '`' . str_replace('`', '', $name) . '`';
    }

    /** Throw if table/column name contains unsafe characters. */
    private function assertSafeIdentifier(string $name): void
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            throw new \InvalidArgumentException("Unsafe SQL identifier: {$name}");
        }
    }
}
