<?php 

namespace Mint;

use Exception;

class Task {
	/*
	possibili valori di status:
		init:     alla creazione di un nuovo oggetto Task
		starting: alla chiamata del metodo run(), appena prima di spawnare il processo con exec
		running:  alla chiamata del metodo statico cli_start(), dev'essere la prima cosa da fare in ogni task ("tasks/*.php")
		success:  alla chiamata del metodo terminate() (senza parametri), indica che il task è terminato senza errori, dev'essere l'ultima cosa da fare in ogni task ("tasks/*.php") 
		error:    alla chiamata del metodo terminate('error'), indica che il task è terminato a causa di un errore
		killed:   alla chiamata del metodo terminate('killed'), indica che il task è stato killato a mano
	*/
	function __construct($task, $location = 'tasks', $params = []) {
		if(is_int($task)){
			$statusPath = Task::getStatusPath($task);
			$taskData = json_decode(file_get_contents($statusPath), true);
			$this->id = $task;
			$this->task_path = $taskData['task_path'];
			$this->workinkDir = $taskData['workinkDir'];
			$this->pId = $taskData['pId'];
			$this->status = $taskData['status'];
			$this->progress = $taskData['progress'];
			$this->maxProgress = $taskData['maxProgress'];
			$this->percentage = $taskData['percentage'];
			$this->start_time = $taskData['start_time'];
			$this->last_update = $taskData['last_update'];
			$this->stop_time = $taskData['stop_time'];
			$this->custom = $taskData['custom'];
			$this->params = $taskData['params'];
			return;
		}
		
		$this->task_path = realpath($location . '/' . $task . '.php');
		if(!is_file($this->task_path)) throw new Exception('Task "' . $this->task_path . '" not found!', 745001);
		
		$this->start_time = null;
		$this->stop_time = null;
		$this->last_update = null;
		$this->pId = false;
		$this->status = 'init';
		$this->progress = 0;
		$this->maxProgress = 0;
		$this->percentage = 0;
		$this->params = $params;
		$this->custom = '';
		$this->id = $this->initWDir();
		$this->logStatus();
	}

	/* PROGRESS */
	
	public function setMaxProgress($maxProgress){
		$this->maxProgress = $maxProgress;
		$this->logStatus();
	}
	public function setProgress($progress, $logStatus = true){
		$this->progress = $progress;
		$this->setPercentage();
		if($logStatus)$this->logStatus();
	}
	public function incProgress($logStatus = true){
		$this->progress++;
		$this->setPercentage();
		if($logStatus)$this->logStatus();
	}
	private function setPercentage(){
		$this->percentage = $this->maxProgress == 0 ? 0 : round( ( $this->progress * 100 ) / $this->maxProgress, 2);
		return $this->percentage;
	}
	
	/* ENGINE */
	
	public function run() {
		if($this->status == 'running') throw new Exception('task already running!');
		$this->setProgress(0, false);
		$this->status = 'starting';
		$this->start_time = date('y-m-d H:i:s');
		$this->logStatus();	
		$this->pId = exec('php ' . $this->task_path . ' ' . $this->id . ' > ' . $this->workinkDir . 'output.log 2>&1 & echo $!');
		$this->logStatus();
	}

	public function terminate($status = 'success'){
		$this->stop_time = date('y-m-d H:i:s');
		$this->status = $status;
		if($status == 'success') {
			$this->setProgress($this->maxProgress, false);
		}
		$this->logStatus();
	}

	public function kill(){
		try{
			$this->terminate('killed');
			$result = shell_exec(sprintf("kill %d", $this->pId));
			if( count(preg_split("/\n/", $result)) > 2){
				return true;
			}
		}catch(Exception $e){}
		return false;
	}

	public function remove(){
		if(is_file($this->workinkDir . 'output.log')) unlink($this->workinkDir . 'output.log');
		if(is_file($this->workinkDir . 'status.json')) unlink($this->workinkDir . 'status.json');
		rmdir($this->workinkDir);
	}

	public static function cli_start($singleton = false) {
		global $argv;
		if(php_sapi_name() != 'cli') throw new Exception('this function work only on cli environment');
		if(count($argv) < 2) throw new Exception('give me task id');
		if( in_array('-i', $argv) || in_array('--info', $argv) ){
			die(json_encode([
				'name' => ( defined('TASK_NAME') ? TASK_NAME : null ),
				'description' => ( defined('TASK_DESCRIPTION') ? TASK_DESCRIPTION : null ),
				'params' => ( defined('TASK_PARAMS') ? TASK_PARAMS : [] )
			]));
		}
		$id = intval($argv[1]);
		$t = new Task($id);
		//check if singleton not start
		if($singleton){
			$taskList = array_values(array_diff(scandir(__DIR__ . '/../../storage_task'), ['..', '.']));
			for ($i=0, $l = count($taskList); $i < $l; $i++) {
				$sPath = __DIR__ . '/../../storage_task/' . $taskList[$i] . '/status.json';
				if(is_file($sPath)){
					$status = json_decode(file_get_contents($sPath), true);
					if($id == intval($status['id'])) continue;
					if($status['task_path'] == $t->task_path && $status['status'] == 'running'){
						Task::log('Same task instance already running, Stopped');
						$t->terminate('error');
						die();
					}
				}

			}
		}
		$t->status = 'running';
		$t->logStatus();
		return $t;
	}

	/* UTILITY */

	public static function getStatusPath($id){
		$taskDir = __DIR__ . '/../../storage_task/p_' . $id;
		if(!is_dir($taskDir)) throw new Exception('task working dir not found');
		$taskStatusPath = $taskDir . '/status.json';
		if(!is_file($taskStatusPath)) throw new Exception('task status file not found');
		return $taskStatusPath;
	}

	public function isRunning(){
		try{
			$result = shell_exec(sprintf("ps %d", $this->pId));
			if( count(preg_split("/\n/", $result)) > 2){
				return true;
			}
		}catch(Exception $e){}
		return false;
	}

	public function get($p){
		if(!isset($this->params[$p])) throw new Exception('params ' . $p . ' not found!');
		if( !defined('TASK_PARAMS') || !isset(TASK_PARAMS[$p]) ){
			trigger_error('use undefined task param', E_USER_WARNING);
		}else{
			switch(TASK_PARAMS[$p]){
				case Settings::TYPE_BOOL:
					return boolval($this->params[$p]);
				case Settings::TYPE_INT:
					return intval($this->params[$p]);
				case Settings::TYPE_FLOAT:
					return floatval($this->params[$p]);
				case Settings::TYPE_STRING:
					return strval($this->params[$p]);
			}
		}
		return $this->params[$p];
	}
	
	private function initWDir(){
		$globalWorkinkDir = __DIR__ . '/../../storage_task/';
		$otherTaskId = [];
		foreach (scandir($globalWorkinkDir) as $v) {
			if(!preg_match('/^p_\d+$/', $v)) continue;
			array_push($otherTaskId, substr($v, 2));
		}
		while(in_array($id = rand(10000,99999), $otherTaskId)){}
		$workinkDir = $globalWorkinkDir . 'p_' . $id;
		mkdir($workinkDir);
		$this->workinkDir = $workinkDir . '/';
		return $id;
	}

	private function getUptime(){
		$t = time() - strtotime($this->start_time);
		$h = str_pad(floor($t / 3600), 2, "0", STR_PAD_LEFT);
		$m = str_pad(floor(($t / 60) % 60), 2, "0", STR_PAD_LEFT);
		$s = str_pad($t % 60, 2, "0", STR_PAD_LEFT);
		return "$h:$m:$s";
	}

	/* LOG */
	
	public function logStatus($custom = ''){
		if($custom != '') $this->custom = $custom;
		$this->last_update = date('y-m-d H:i:s');
		file_put_contents($this->workinkDir . 'status.json', json_encode([
			'task_path' => $this->task_path,
			'status' => $this->status,
			'uptime' => $this->getUptime(),
			'progress' => $this->progress,
			'maxProgress' => $this->maxProgress,
			'percentage' => $this->percentage,
			'pId' => $this->pId,
			'id' => $this->id,
			'workinkDir' => $this->workinkDir,
			'start_time' => $this->start_time,
			'last_update' => $this->last_update,
			'stop_time' => $this->stop_time,
			'params' => $this->params,
			'custom' => $this->custom
		], JSON_PRETTY_PRINT), LOCK_EX);
	}

	public static function log($v){
		echo '[' . date('y-m-d H:i:s') . '] ' . $v . "\n";
	}
}

?>