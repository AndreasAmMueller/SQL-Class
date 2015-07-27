#!/usr/bin/env php
<?php
require_once __DIR__.'/sql.class.php';
use AMWD\SQL as SQL;

$sql = SQL::MySQL("root", "root", "mysql");
$sql->setLocales("de_DE");

$sql->open();
$query = "SELECT user FROM user";
$res = $sql->query($query);

while ($row = $sql->fetch_object($res)) {
	echo $row->user.PHP_EOL;
}

$sql->close();

$sql = SQL::SQLite(__DIR__.'/test.sqlite');
echo "SQLite Version: ".$sql->getVersion();
echo PHP_EOL;

?>
