<?PHP

namespace zardsama\pdo;

Class PDOIterator implements \Iterator {
    private $iterator;

    public function __construct(\PDOStatement $stmt, $fetchMode = \PDO::FETCH_ASSOC) {
        $this->iterator = new \ArrayObject($stmt->fetchAll($fetchMode));
        $this->iterator = $this->iterator->getIterator();
    }

    function rewind() {
        $this->iterator->rewind();
    }

    function current() {
        return $this->iterator->current();
    }

    function key() {
        return $this->iterator->key();
    }

    function next() {
        $this->iterator->next();
    }

    function valid() {
        return $this->iterator->valid();
    }

    function rowCount() {
        return $this->iterator->count();
    }
}

?>