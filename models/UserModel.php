<?php
/** User model — stub for multi-tenancy. Not used in UI yet. */
class UserModel extends Model
{
    protected string $table = 'users';

    public function findByEmail(string $email): ?array
    {
        return $this->db->queryOne('SELECT * FROM users WHERE email = ?', [$email]);
    }

    public function create(string $email, string $password, string $name, string $role = 'manager'): int
    {
        return $this->db->insert('users', [
            'email'     => $email,
            'password'  => password_hash($password, PASSWORD_BCRYPT),
            'name'      => $name,
            'role'      => $role,
            'is_active' => 1,
        ]);
    }

    public function changePassword(int $id, string $password): void
    {
        $this->db->update('users', ['password' => password_hash($password, PASSWORD_BCRYPT)], 'id = ?', [$id]);
    }
}
