<?php

/**
 * sql.class.php
 *
 * @author Andreas Mueller <webmaster@am-wd.de>
 * @version 1.0-20140417
 *
 * @description
 * This class tries to provide (full) support for
 * - MySQL
 * - SQLite
 * using only one function-name
 *
 * Correct syntax (no slangs) required!
 **/

@error_reporting(0);

class SQL {
	// MySQL
	private $host, $port, $user, $database;
	// SQLite
	private $file;
	// Both
	private $password, $type, $con;

	// constructor => use static functions to create object!
	public function __construct($type) {
		$this->type = $type;
	}

	// static function to create mysql-instance
	public static function MySQL($user, $password, $database, $host = 'localhost', $port = 3306) {
		class_exists('mysqli') or throw new Exception('<b>Error:</b> MySQLi Class not installed! Details <a href="http://php.net/manual/de/book.mysqli.php">here</a>');
		$self = new self('mysql');
		$self->setHost($host);
		$self->setUser($user);
		$self->setPassword($password);
		$self->setDatabase($database);
		$self->setPort($port);
		return $self;
	}

	// static function to create sqlite-instance
	public static function SQLite($file, $password = '') {
		class_exists('SQLite3') or throw new Exception('<b>Error:</b> SQLite3 Class not installed! Details <a href="http://php.net/manual/de/book.sqlite3.php">here</a>');
		$self = new self('sqlite');
		$self->setFile($file);
		$self->setPassword($password);
		return $self;
	}

	// destructor
	public function __destruct() {
		if ($con != null || $con != false) {
			$this->close();
		}
	}

	/* --- GETter and SETer ---
	------------------------ */
	public function setHost($host) {
		$this->host = $host;
	}
	public function getHost() {
		return $this->host;
	}

	public function setUser($user) {
		$this->user = $user;
	}
	public function getUser() {
		return $this->user;
	}

	public function setPassword($password) {
		$this->password = $password;
	}
	public function getPassword() {
		return $this->password;
	}

	public function setDatabase($database) {
		$this->database = $database;
	}
	public function getDatabase() {
		return $this->database;
	}

	public function setPort($port) {
		$this->port = $port;
	}
	public function getPort() {
		return $this->port;
	}

	public function setFile($file) {
		is_writable(dirname($file)) or throw new Exception('<b>Error:</b> SQLite file directory not writable');
		$this->file = $file;
	}
	public function getFile() {
		return $this->file;
	}

	/* --- Open connection ---
	----------------------- */
	public function open() {
		return ($this->type == 'sqlite') ? $this->openSQLite() : $this->openMySQL();
	}

	public function connect() {
		return $this->open();
	}

	private function openSQLite() {
		$switch = SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE;
		$c = new SQLite3($this->file, $switch, $this->password);
		if ($c->lastErrorCode()) {
			throw new Exception('Failed to open SQLite: ('.$c->lastErrorCode().') '.$c->lastErrorMsg());
			return false;
		}		
		$this->con = $c;
		return true;
	}

	private function openMySQL() {
		$c = new mysqli($this->host, $this->user, $this->password, $this->database, $this->port);
		if ($c->connect_errno) {
			throw new Exception('Failed to connect to MySQL: ('.$c->connect_errno.') '.$c->connect_error);
			return false;
		}
		$this->con = $c;
		return true;
	}

	/* --- Close connection ---
	------------------------ */
	public function close() {
		return $this->con->close();
	}

	public function disconnect() {
		return $this->close();
	}

	/* --- Query ---
	------------- */
	public function query($query) {
		return $this->con->query($query);
	}

	public function error() {
		return ($this->type == 'sqlite') ? $this->con-lastErrorMsg() : $this->con->error;
	}

	/* --- Results ---
	--------------- */
	public function fetch_array($result) {
		return ($this->type == 'sqlite') ? $result->fetchArray() : $result->fetch_array();
	}

	public function fetch_object($result) {
		if ($this->type == 'sqlite') {
			$array = $result->fetchArray();
			if (empty($array))
					return;
			
			$res = new stdClass();
			foreach ($res as $key => $val)
					$res->$key = $val;

			return $res;
		} else {
			return $result->fetch_object();
		}
	}

	public function num_rows($result) {
		if ($this->type == 'sqlite') {
			$count = 0;
			while ($row = $this->fetch_object($result))
					$count++;

			return $count;
		} else {
			return $result->num_rows;
		}
	}

	public function insert_id() {
		return ($this->type == 'sqlite') ? $this->con->lastInsertRowID() : $this->con->insert_id;
	}

	public function affected_rows() {
		return ($this->type == 'sqlite') ? $this->con->changes() : $this->con->affected_rows;
	}

	/* --- SQL Dump ---
	---------------- */
	public function getTables() {
		return ($this->type == 'sqlite') ? $this->sqTables() : $this->myTables();
	}

	private function sqTables() {
		$tables = array();
		$res = $this->query("SELECT name FROM sqlite_master WHERE type = 'table'");
		while ($row = $this->fetch_array($res)) {
			if ($row['name'] != 'sqlite_sequence')
					$tables[] = $row['name'];
		}
		return $tables;
	}

	private function myTables() {
		$tables = array();
		$res = $this->query("SHOW TABLES FROM `".$this->database."`");
		while ($row = $this->fetch_array($res)) {
			$tables[] = $row['Tables_in_'.$this->database];
		}
		return $tables;
	}

	private function getStructure($table) {
		// TODO: generate structure
	}

	private function getData($table) {
		// TODO: list data
	}

	public function getDump($part = 'structure,data', $tables = '') {
		// TODO: create full dump
	}

	public function restoreDump($dump, $returnError = false) {
		// TODO: restore full dump
	}

}
