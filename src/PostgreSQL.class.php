<?php

/**
 * MySQL.class.php
 *
 * (c) Andreas Mueller <webmaster@am-wd.de>
 */

namespace AMWD\SQL;
require_once __DIR__.'/SQL.class.php';

/**
 * Representing specific implementation for PostgreSQL
 *
 * @package    SQL
 * @author     Andreas Mueller <webmaster@am-wd.de>
 * @copyright  (c) 2016 Andreas Mueller
 * @license    MIT - http://am-wd.de/index.php?p=about#license
 * @link       https://bitbucket.org/BlackyPanther/sql-class
 * @version    v1.3-20160309 | stable
 */
class PostgreSQL extends SQL
{

	/**
	 * Initializes a new instance of a PostgreSQL connection.
	 *
	 * @param string $user
	 *   The database user.
	 * @param string $password
	 *   The database users password.
	 * @param string $database (optional)
	 *   The name of the database. On null all statements need a `db`.`table`.
	 * @param int $port (optional)
	 *   The portnumber for the tcp connection. (Sockets currently not supported)
	 * @param string $hostname (optional)
	 *   The hostname or ip address of the MySQL server.
	 *
	 * @throws \RuntimeException
	 *   If the driver is not available.
	 */
	function __construct($user, $password, $database = null, $port = 5432, $hostname = '127.0.0.1')
	{
		if (!in_array('pgsql', \PDO::getAvailableDrivers()))
			throw new \RuntimeException("PostgreSQL driver not found. Please install PostgreSQL support for PHP (e.g. php-pgsql)");

		parent::__construct();

		$this->user     = $user;
		$this->password = $password;
		$this->database = $database;
		$this->port     = $port;
		$this->hostname = $hostname;
		$this->encoding = 'utf8';
	}

		/**
	 * Gets the version of the database client (driver).
	 *
	 * @return string
	 *   The driver and its version number.
	 */
	public function driver_version()
	{
		return 'PostgreSQL '.$this->conn->getAttribute(\PDO::ATTR_CLIENT_VERSION);
	}

	/**
	 * Opens the connection to the database.
	 *
	 * @return bool
	 *   true on a successful connection otherwise false and the error message is set.
	 */
	public function open()
	{
		$conString = 'pgsql';
		$conString.= ':host='.$this->hostname;
		$conString.= ';port='.$this->port;
		$conString.= ';options=\'--client_encoding="'.$this->encoding.'"\'';

		if ($this->database != null)
			$conString.= ';dbname='.$this->database;

		try
		{
			$conn = new \PDO(
				$conString,
				$this->user,
				$this->password
			);
			$conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			$conn->setAttribute(\PDO::ATTR_PERSISTENT, true);

			$this->conn = $conn;
			$this->status = 'open';

			return true;
		}
		catch (\PDOException $ex)
		{
			$this->error = $ex->getMessage();
			$this->status = 'broken';

			return false;
		}
	}

	/**
	 * Gets the correct function with all elements concatenated.
	 *
	 * @param mixed[] $elements
	 *   An array list of elements to concatenate.
	 *
	 * @return string
	 */
	public function concat($elements)
	{
		if (count($elements) == 0)
			return '';

		return implode(' || ', $elements);
	}

	/**
	 * Creates a dump to reimport.
	 *
	 * @param string|string[] $part (optional)
	 *   String or string array with structure, data or structure,data.
	 * @param string|string[] $tables
	 *   String or string array with tablenames to dump.
	 *
	 * @return string
	 */
	public function get_dump($part = SQLOPT_DB_STRUCTUREANDDATA, $tables = '') {
		throw new \Exception('Not Implemented yet');
	}

	/**
	 * Gets the structure of a table.
	 *
	 * @param string $table
	 *   The name of the table.
	 *
	 * @return string
	 *   The table's structure.
	 */
	protected function get_structure($table) {
		$schema = 'public';

		$query = "WITH list AS (
	SELECT
		  n.nspname as schema
		, c.relname as table
		, a.attname AS name
		, a.attnotnull AS notnull
		, pg_catalog.format_type(a.atttypid, a.atttypmod) AS type
		, CASE
		  	WHEN p.contype = 'p' THEN 't'
		  	ELSE 'f'
		  END AS primarykey
		, CASE
		  	WHEN p.contype = 'u' THEN 't'
		  	ELSE 'f'
		  END AS uniquekey
		, CASE
		  	WHEN p.contype = 'f' THEN g.relname
		  END AS foreignkey
		, CASE
		  	WHEN p.contype = 'f' THEN p.confkey
		  END AS foreignkey_fieldnum
		, CASE
		  	WHEN a.atthasdef = 't' THEN d.adsrc
		  END AS default
	FROM
		pg_attribute a
	JOIN
		pg_class c ON c.oid = a.attrelid
	JOIN
		pg_type t ON t.oid = a.atttypid
	LEFT JOIN
		pg_attrdef d ON d.adrelid = c.oid AND d.adnum = a.attnum
	LEFT JOIN
		pg_namespace n ON n.oid = c.relnamespace
	LEFT JOIN
		pg_constraint p ON p.conrelid = c.oid AND a.attnum = ANY(p.conkey)
	LEFT JOIN
		pg_class AS g ON p.confrelid = g.oid
WHERE
	c.relkind = 'r'::char
		AND n.nspname = '".$schema."'
		AND c.relname = '".$table."'
		AND a.attnum > 0
ORDER BY
	a.attnum
)
SELECT
	  name
	, \"notnull\"
	, type
	, primarykey
	, uniquekey
	, CASE
	  	WHEN foreignkey IS NOT NULL THEN CONCAT(foreignkey, '(', column_name, ')')
	  END AS foreignkey
	, \"default\"
FROM
	list
LEFT JOIN
	information_schema.columns ON schema = table_schema
		AND foreignkey = table_name
		AND foreignkey_fieldnum[1] = ordinal_position
;";

		$close = $this->status() == 'closed';

		$this->open();

		$stmt = $this->query($query);

		$structure = 'CREATE TABLE "'.$schema.'"."'.$table.'" (';

		while ($row = $this->fetch_object($stmt))
		{
			$structure .= '	"'.$row->name.'"'
		}




		if ($close)
			$this->close();
	}
}

?>
