# Mint (Mint Is Not Taskhost.exe)

Spawn, Monitor & Manage Background Task on Linux from Web GUI

## Install

```sh
composer install colgatto/mint
```

## Usage

`www/api.php`
```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Mint\ApiEngine;

//tell the manager where are located the tasks
ApiEngine::start(__DIR__ . '/../tasks');

?>
```

`www/index.php`
```php
<?php 

require_once __DIR__ . '/../vendor/autoload.php';

use Mint\WebGui;

WebGui::start();

?>
```

`tasks/example.php`
```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Mint\Task;
use Mint\Settings;

//define task info
define('TASK_NAME', 'Example Task');
define('TASK_DESCRIPTION', 'use me as template for new task');
define('TASK_PARAMS', [
	'int_param' => Settings::TYPE_INT,
	'float_param' => Settings::TYPE_FLOAT,
	'string_param' => Settings::TYPE_STRING,
	'bool_param' => Settings::TYPE_BOOL,
]);

//start the task
//use Task::cli_start(true) if you want singletone task (block multiple instances of same task)
$task = Task::cli_start();

//get task parameters
$int_param = $task->get('int_param');
$float_param = $task->get('float_param');
$string_param = $task->get('string_param');
$bool_param = $task->get('bool_param');

//log stuff
Task::log(TASK_NAME . ' Started');

Task::log('int_param: ' . $int_param);
Task::log('float_param: ' . $float_param);
Task::log('string_param: ' . $string_param);
Task::log('bool_param: ' . $bool_param);

//setup progress bar
$task->setMaxProgress(10);

//do stuff
Task::log('wait 10 seconds then exit');
for ($i=0; $i < 10; $i++) {
	try{
		Task::log( ($i+1) );
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
```