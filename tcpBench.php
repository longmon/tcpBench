<?php

class tcpBench
{
	protected $worker = [];

	protected $table = NULL;

	public function __construct( $service, $connect_num = 100, $worker_num = 1)
	{
		//udp://192.168.200.19:10001
		list($serv,$ip) = explode("://", $service);
		list($host,$port) = explode(":", $ip);
		$this->host = $host;
		$this->port = $port?$port:($serv=='https'?443:80);
		$this->serv = $serv;
		if($connect_num<$worker_num){
			$worker_num = $connect_num;
		}
		$this->connect_num = $connect_num;
		$this->worker_num = $worker_num;

		//swoole_process::daemon();

		swoole_set_process_name("Tcp Benchmark Test");
				
		printf("tcp Benchmark test running ......\n\n {$serv}://{$host}:{$port}\n 连接数:{$connect_num},工作进程:{$worker_num}\n\n");

		$this->table = new swoole_table($worker_num);
		$this->table->column("fail", swoole_table::TYPE_INT, 4);
		$this->table->column("suc", swoole_table::TYPE_INT, 4);
		$this->table->create();
	}

	public function setData($data)
	{
		if( !$data )
		{
			$data = "hello world";
		}
		$this->data = $data;
		return $this;
	}

	public function run()
	{
		$startTime = microtime(true);
		for ($i=0; $i < $this->worker_num; $i++) { 
			$proc = new swoole_process(function(swoole_process $worker){
				$worker->name("    |-Tcp Benchmark worker");
				$func = $this->serv;
				$this->$func($worker);
			}, false, false);
			$pid = $proc->start();
			$this->worker[$pid] = $proc;
		}

		for(;;)
		{
			if( count($this->worker) ){
				$ret = swoole_process::wait();
				if( $ret ){
					if( isset($this->worker[$ret['pid']]) ){
						unset($this->worker[$ret['pid']]);
					}
				}
			}else{
				$endTime = microtime(true);
				$useTime = ($endTime-$startTime);
				$useTime = round($useTime,3);
				$fail = $suc = 0;
				foreach ($this->table as $pid => $arr) {
					$fail += $arr['fail'];
					$suc += $arr['suc'];
				}
				$failpercent = round(($fail/($fail+$suc))*100,3);
				echo "测试结束！用时:{$useTime}s, 成功：{$suc}, 失败：{$fail}, 失败占比：{$failpercent}%\n\n";
				exit;
			}
		}
	}

	protected function udp(swoole_process $worker)
	{
		$pid = posix_getpid();
		$num = intval($this->connect_num/$this->worker_num);
		$client = new swoole_client(SWOOLE_SOCK_UDP);
		if(!$client->connect($this->host, $this->port, 1, 1)){
			$this->table->incr($pid, "fail", $num );
			exit;
		}
		for($i=0;$i<$num;$i++){
			if( !$client->send($this->data) ){
				$this->table->incr($pid,"fail");
			}else{
				$this->table->incr($pid,"suc");
			}
			if( $i % 4 == 0 ){
				usleep(10000);
			}
		}
		
	}

	protected function tcp()
	{}

	public static function help()
	{
		echo "Usage:   tcpBench -[cn] scheme://ip:port\n         -n: 总连接数\n         -c: 工作进程数，也可认为是并发数\nsample: tcpBench -c 10 -n 10000 -t 300 udp://192.168.200.19:10001\n";
	}
}
