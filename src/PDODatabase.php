<?PHP

namespace zardsama\pdo;

use PDO;
use PDOStatement;
use PDOException;
use zardsama\pdo\PDOIterator;

Class PDODatabase {

	private $pdo;
    private $res;
	private $qry;
	private $errmsg;
	private $errcode;
	private $last_id;

	public function __construct($dbinfo, $charset = 'utf8mb4') {
        if (is_object($dbinfo) && get_class($dbinfo) == 'PDO') {
            $this->pdo = $dbinfo;
            $this->driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            return;
        }

        $this->driver = $dbinfo['driver'];

        switch($dbinfo['driver']) {
            case 'mysql' :
        		$dsn = $dbinfo['driver'].':host='.$dbinfo['host'].';dbname='.$dbinfo['database'].';';
                break;
            case 'oci' :
                $dsn = $dbinfo['driver'].':dbname='.$dbinfo['host'].'/'.$dbinfo['database'].';charset='.$charset;
                break;
            case 'pgsql' :
                $dsn = $dbinfo['driver'].':host='.$dbinfo['host'];
                if ($dbinfo['port']) $dsn .= ' port='.$dbinfo['port'];
                if ($dbinfo['database']) $dsn .= ' dbname='.$dbinfo['database'];
                if ($dbinfo['user']) $dsn .= ' user='.$dbinfo['user'];
                if ($dbinfo['password']) $dsn .= ' password='.$dbinfo['password'];
                break;
        }

		$username = $dbinfo['user'];
		$password = $dbinfo['password'];
		$options = array(
			PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES '.$charset,
		);

		try {
			$this->pdo = new PDO($dsn, $username, $password, $options);
		} catch(PDOException $e) {
			exit('DB connect Error : '.$e->getMessage());
		}
	}

	public function query($qry, $param = null) {
		$this->qry = $qry;
		$this->errcode = null;
		$this->errmsg = null;

		try {
            $driver = (preg_match('/^select/i', trim($qry)) == true) ? array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL) : array();
			$res = $this->pdo->prepare($this->qry, $driver);
			if($res->execute($param) == false) {
				$err = $res->errorInfo();
				$this->errcode = $err[1];
				$this->errmsg = $err[2];

				return false;
			}

            if ($this->driver != 'oci') {
    			$this->last_id = $this->pdo->lastInsertId();
            }

			$this->res = $res;
		} catch(PDOException $e) {
			exit($e->getMessage());
		}

		return $res;
	}

	public function iterator($qry, $param = null) {
		$res = $this->query($qry, $param);

		if($res instanceof PDOStatement == true) {
			return new PDOIterator($res, PDO::FETCH_ASSOC);
		}
		return false;
	}

	public function assoc($qry, $param = null) {
		$res = $this->query($qry, $param);
		if(!$res) return false;

		$data = $res->fetch(PDO::FETCH_ASSOC);
		return $data;
	}

	public function fetch($qry, $param = null) {
		$res = $this->query($qry, $param);
		if(!$res) return false;

		$data = $res->fetch(PDO::FETCH_BOTH);
		return $data;
	}

	public function row($qry, $param = null) {
		$res = $this->query($qry, $param);
		if(!$res) return false;

		$data = $res->fetchColumn(0);

		return $data;
	}

	public function loop($fetch = PDO::FETCH_ASSOC) {
		if(is_object($this->res) == false) return false;

		return $this->res->fetch($fetch);
	}

	public function getError() {
		return $this->errmsg;
	}

	public function getQry($print = false) {
		return $this->qry;
	}

    public function lastInsertId() {
        return $this->last_id;
    }

    public function rowCount($qry, $param = array()) {
        $res = $this->query($qry, $param);
        if($res instanceof PDOStatement) {
            return $res->rowCount();
        }
        return false;
    }

    public function lastRowCount() {
        if(isset($this->res) == false || $this->res instanceof PDOStatement == false) {
            return 0;
        }
        return $this->res->rowCount();
    }

}

?>