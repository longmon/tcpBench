<?php

include __DIR__."/tcpBench.php";

$opts = getopt("c:n:");
$num = isset($opts['n'])?intval($opts['n']):10000;
$wc = isset($opts['c'])?intval($opts['c']):10;
$service = array_pop($argv);
if( $service == 'help'){
	tcpBench::help();
	exit;
}
$regExp = '/[udp|tcp|http|https]+:\/\/.+/i';
if(!preg_match($regExp, $service)){
	tcpBench::help();
	exit;
}

$data = array("api"=>"tcpBenchTest", "useTime"=>10000,"gid"=>5);
$tcpBench = new tcpBench($service, $num, $wc);
$tcpBench->setData(json_encode($data));
$tcpBench->run();
