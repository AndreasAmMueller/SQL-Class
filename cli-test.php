#!/usr/bin/env php
<?php
require_once __DIR__.'/sql.class.php';

$sql = SQL::MySQL("root", "root", "tt-stuttgart");
$sql->setLocales("de_DE");

//file_put_contents(__DIR__.'/dump.sql', $sql->getDump());

echo $sql->restoreDump(file_get_contents(__DIR__.'/dump.sql'), true);

?>
