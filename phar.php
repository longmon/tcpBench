<?php

$archive_file_name = "tcpBench.phar";
$phar = new phar(__DIR__.'/'.$archive_file_name, 0, $archive_file_name);
$phar->buildFromDirectory(__DIR__);
$phar->startBuffering();
$defaultStub = $phar->createDefaultStub("index.php.sh");
$phar->setStub($defaultStub);
$phar->stopBuffering();
$phar->delete("pack.php");
