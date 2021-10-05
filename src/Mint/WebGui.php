<?php 

namespace Mint;

class WebGui{

	private const STATIC_PATH = __DIR__ . '/../static/';
		
	public static function start(){
		$action = isset($_GET['action']) ? $_GET['action'] : 'monitor';
		switch($action){
			case 'monitor':
				self::taskMonitor();
			case 'list':
				self::taskList();
		}
	}

    public static function taskMonitor(){
		self::render(__DIR__ . '/../static/taskManager.hbs');
    }

    public static function taskList(){
		self::render(__DIR__ . '/../static/taskList.hbs');
    }

	private static function render($path){

		$html = file_get_contents($path);

		$strings = [
			[ 'SERVER_IP', $_SERVER['SERVER_ADDR'] ]
		];
		for ($i=0; $i < count($strings); $i++) { 
			$s = $strings[$i];
			$html = self::incString($html, $s[0], $s[1]);
		}

		$parts = [
			[ 'BOOTSTRAP_CSS',	'bootstrap.min.css' ],
			[ 'STYLE',			'style.css' ],
			[ 'JQUERY',			'jquery-3.2.1.min.js' ],
			[ 'POPPER',			'popper.min.js' ],
			[ 'BOOTSTRAP_JS',	'bootstrap.min.js' ],
			[ 'TASK',			'task.js' ]
		];
		
		for ($i=0; $i < count($parts); $i++) { 
			$p = $parts[$i];
			$html = self::incPart($html, $p[0], $p[1]);
		}

		die($html);
	}

	private static function incPart($html, $selector, $name){
		return str_replace('{{' . $selector . '}}', "\n" . file_get_contents(self::STATIC_PATH . $name) . "\n", $html);
	}

	private static function incString($html, $selector, $string){
		return str_replace('{{' . $selector . '}}', $string, $html);
	}

}
?> 