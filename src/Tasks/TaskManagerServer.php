<?php 

require_once __DIR__ . '/../../../../autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Mint\Task;

$task = Task::cli_start();

//send error to task log
function __custom_task_error_handler($errno, $errstr, $errfile, $errline){
	global $task;
	Task::log('PHP ERROR ' . $errfile . ':' . $errline);
	Task::log('(' . $errno . ') ' . $errstr);
	$task->terminate('error');
	exit();
}
set_error_handler('__custom_task_error_handler');

$task->setMaxProgress(0);

class TaskManagerServer implements MessageComponentInterface {	
	public function __construct() {
		$this->clients = new \SplObjectStorage;
	}
	public function onOpen(ConnectionInterface $conn) {
		Task::log('Connection ' . $conn->resourceId . ' open');
		$this->clients->attach($conn);
	}
	public function onClose(ConnectionInterface $conn) {
		$this->clients->detach($conn);
		Task::log('Connection ' . $conn->resourceId . ' closed');
	}
	public function onError(ConnectionInterface $conn, \Exception $e) {
		global $task;
		Task::log('An error has occurred: ' . $e->getMessage());
		$conn->close();
		$task->terminate('error');
	}
	public function onMessage(ConnectionInterface $from, $msg) {
		if($msg == 'ping') $from->send('pong');
	}
}

$tm = new TaskManagerServer();

$server = IoServer::factory( new HttpServer( new WsServer( $tm ) ), 8087 );

$server->loop->addPeriodicTimer(2, function () use ($server, $tm) {

	if( $tm->clients->count() == 0 ) return;

	$storagePath = __DIR__ . '/../../storage_task';
	$taskDirs = preg_grep('/^([^.])/', scandir($storagePath));
	$stat = [];
	foreach ($taskDirs as $td) {
		$statusPath = $storagePath . '/' . $td . '/status.json';
		$outputPath = $storagePath . '/' . $td . '/output.log';
		$isFile = is_file($statusPath);
		if($isFile){
			$s = json_decode(file_get_contents($statusPath), true);
			$s['output'] = is_file($outputPath) ? file_get_contents($outputPath) : '';
			array_push($stat, $s);
		}
	}
	
	$stat = base64_encode(json_encode($stat, true));

	foreach ($tm->clients as $client) {
		$client->send($stat);
	}
	
});

$server->run();

?>