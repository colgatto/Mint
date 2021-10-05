<?php 

namespace Mint;

class ApiEngine{

	function __construct($location = 'tasks') {
		$this->taskLocation($location);
	}

	public function taskLocation($location){
		$this->location = $location;
	}

	private static function post($k){
		return (isset($_POST[$k]) && !empty($_POST[$k])) ? $_POST[$k] : null;
	}
	private static function get($k){
		return (isset($_GET[$k]) && !empty($_GET[$k])) ? $_GET[$k] : null;
	}
	private static function require_post($k){
		$d = ApiEngine::post($k);
		if(is_null($d)) ApiEngine::no($k . ' required!');
		return $d;
	}
	private static function require_get($k){
		$d = ApiEngine::get($k);
		if(is_null($d))	ApiEngine::no($k . ' required!');
		return $d;
	}
	private static function ok($data = 'done'){
		header('Content-type: application/json; charset=utf-8');
		die(json_encode([
			'error' => false,
			'response' => $data,
		], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
	}
	private static function no($data = 'error'){
		header('Content-type: application/json; charset=utf-8');
		die(json_encode([
			'error' => true,
			'response' => $data,
		], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
	}

	public static function start($location){
		if(!isset($_REQUEST['action'])) die('action required');
		switch($_REQUEST['action']){
			case 'startMonitorServer':
				ApiEngine::startMonitorServer();
			case 'run':
				ApiEngine::runTask($location);
			case 'kill':
				ApiEngine::killTask();
			case 'remove':
				ApiEngine::removeTask();
			case 'taskList':
				ApiEngine::taskList($location);
		}
	}

	//API ENDPOINT
	private static function startMonitorServer(){
		try{
			$t = new Task('TaskManagerServer', __DIR__ . '/../Tasks');
			$t->run();
		}catch(Exception $e){
			if($e->getCode() == 745001) ApiEngine::no($e->getMessage());
		}
		ApiEngine::ok();
	}

	private static function runTask($location){
		$taskName = ApiEngine::require_get('taskName');
		$params = ApiEngine::get('params');
		if(is_null($params)) $params = '[]';
		try{
			$t = new Task($taskName, $location, json_decode($params) );
			$t->run();
		}catch(Exception $e){
			if($e->getCode() == 745001) ApiEngine::no('Task "' . $taskName . '" not found in "' . $location . '');
		}
		ApiEngine::ok();
	}

	private static function killTask(){
		$id = ApiEngine::require_get('id');
		$t = new Task(intval($id));
		$t->kill();
		ApiEngine::ok();
	}

	private static function removeTask(){
		$id = ApiEngine::require_get('id');
		$t = new Task(intval($id));
		$t->remove();
		ApiEngine::ok();
	}

	private static function taskList($location){
		$tasks = array_values( array_diff( scandir($location), ['.', '..'] ) );
		$res = [];
		for ($i=0, $l = count($tasks); $i < $l; $i++) {
			$task = $tasks[$i];
			$path = realpath($location . '/' . $task);
			$taskInfo = json_decode(exec('php ' . $path . ' --info'), true);
			$taskInfo['path'] = $path;
			$taskInfo['filename'] = $task;
			$taskInfo['task'] = preg_replace('/\.php$/', '', $task);
			//TODO aggiungi storico run errori ecc 
			array_push($res, $taskInfo);
		}
		ApiEngine::ok($res);
	}
}

?>