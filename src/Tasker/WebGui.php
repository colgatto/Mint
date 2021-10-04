<?php 

namespace Mint;

class WebGui{

	private const STATIC_PATH = __DIR__ . '/../static/';
	private const PARTS = [
		[ 'BOOTSTRAP_CSS',	'bootstrap.min.css' ],
		[ 'STYLE',			'style.css' ],
		[ 'JQUERY',			'jquery-3.2.1.min.js' ],
		[ 'POPPER',			'popper.min.js' ],
		[ 'BOOTSTRAP_JS',	'bootstrap.min.js' ],
		[ 'TASK',			'task.js' ]
	];

	
	public static function start(){
		$action = isset($_GET['action']) ? $_GET['action'] : 'monitor';
		switch($action){
			case 'monitor':
				WebGui::taskMonitor();
			case 'list':
				WebGui::taskList();
		}
	}

    public static function taskMonitor(){
		$html = file_get_contents(__DIR__ . '/../static/taskManager.hbs');
		for ($i=0; $i < count(WebGui::PARTS); $i++) { 
			$p = WebGui::PARTS[$i];
			$html = WebGui::incPart($html, $p[0], $p[1]);
		}
		die($html);
    }

    public static function taskList(){
		$html = file_get_contents(__DIR__ . '/../static/taskList.hbs');
		for ($i=0; $i < count(WebGui::PARTS); $i++) { 
			$p = WebGui::PARTS[$i];
			$html = WebGui::incPart($html, $p[0], $p[1]);
		}
		die($html);
    }


	private static function incPart($html, $selector, $name){
		return str_replace('{{' . $selector . '}}', "\n" . file_get_contents(WebGui::STATIC_PATH . $name) . "\n", $html);
	}

}
?> 