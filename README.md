# Mint (Mint Is Not Taskhost.exe)

Spawn, Monitor & Manage Background Task on Linux from Web GUI

### Task Monitor
![taskMonitor](https://i.imgur.com/TqONuCl.png)

### Task List
![taskList](https://i.imgur.com/Dbfoza9.png)

## Install

```sh
composer require colgatto/mint
```

Make sure Mint directory has the right privileges, if php can't create file inside it, Mint doesn't work

Run something like this from your project directory to give Mint the right privilages

```sh
sudo chown www-data:www-data -R ./vendor/colgatto/mint
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
//use Task::cli_start(true) if you want singleton task (block multiple instances of same task)
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
	Task::log( ($i+1) );
	$task->incProgress(); //increment progress bar by 1
	sleep(1); //wait
}

//terminate task
$task->terminate();

?>
```