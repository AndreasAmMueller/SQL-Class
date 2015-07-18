#!/usr/bin/env php
<?php
require_once __DIR__.'/sql.class.php';
use AMWD\SQL as SQL;

$sql = SQL::MySQL("root", "root", "test");
$sql->setLocales("de_DE");

//file_put_contents(__DIR__.'/dump.sql', $sql->getDump());
//echo $sql->restoreDump(file_get_contents(__DIR__.'/dump.sql'), true);

$sql->open();
$query = "INSERT INTO test (name) VALUES('blubb')";
$sql->query($query);
echo "Last inserted ID: ".$sql->insert_id();
echo PHP_EOL;
$sql->close();

?>
