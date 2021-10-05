<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Mint\Task;
use Mint\Settings;

define('TASK_NAME', 'Test list');
define('TASK_DESCRIPTION', 'Test list params');
define('TASK_PARAMS', [
	'wait_time' => Settings::TYPE_INT,
	'esclamation' => ['mamma mia','eccellente','d\'oh','perlapeppetta']
]);

//start the task
//use Task::cli_start(true) if you want singleton task (block multiple instances of same task)
$task = Task::cli_start();

//get task parameters
$wait_time = $task->get('wait_time');
$esclamation = $task->get('esclamation');

//log stuff
Task::log(TASK_NAME . ' Started');

//setup progress bar
$task->setMaxProgress($wait_time);

//do stuff
Task::log('wait ' . $wait_time . ' seconds then exit');
for ($i=0; $i < $wait_time; $i++) {
	try{
		Task::log($esclamation);
		//increment progress bar by 1
		$task->incProgress();
		//wait
		sleep(1);
	}catch(Exception $e){
		//terminate task with error
		$task->terminate('error');
		//terminate method can be used outside the task code (this file) so it can't trigger "die" or "exit" function
		//it is used just to tell the manager that is terminate
		//always remember to kill process just after $task->terminate() if is not at the end of file
		throw $e;
	}
}

//terminate task
$task->terminate();

?>