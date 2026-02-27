<?php
namespace CMS\Core;

class DatabaseSessionHandler implements \SessionHandlerInterface {
    private $db;

    public function open($path, $name): bool {
        $this->db = Database::getInstance();
        return true;
    }

    public function close(): bool {
        return true;
    }

    #[\ReturnTypeWillChange]
    public function read($id): string|false {
        $stmt = $this->db->query("SELECT data FROM pa_sessions WHERE id = :id", ['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $row['data'] : '';
    }

    public function write($id, $data): bool {
        $this->db->query("REPLACE INTO pa_sessions (id, data, last_accessed) VALUES (:id, :data, :time)", [
            'id' => $id,
            'data' => $data,
            'time' => time()
        ]);
        return true;
    }

    public function destroy($id): bool {
        $this->db->query("DELETE FROM pa_sessions WHERE id = :id", ['id' => $id]);
        return true;
    }

    public function gc($max_lifetime): int|false {
        $old = time() - $max_lifetime;
        $this->db->query("DELETE FROM pa_sessions WHERE last_accessed < :old", ['old' => $old]);
        return true;
    }
}