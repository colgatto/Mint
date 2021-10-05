<?php 

namespace Mint;

class Settings{

	public const TYPE_INT = 'INT';
	public const TYPE_FLOAT = 'FLOAT';
	public const TYPE_STRING = 'STRING';
	public const TYPE_BOOL = 'BOOL';
/*
private static function normalizeList($list){
	$l = count($list);
	if(array_keys($list) !== range(0, $l - 1)) return $list;
	$nList = [];
	for ($i=0; $i < $l; $i++) { 
		$vv = $list[$i];
		$nList[$vv] = $vv;
	}
	return $nList;
}

public static function TYPE_LIST($list){
	$l = count($list);
	if($l == 0) return 'L[]';
	$list = Settings::normalizeList($list);
	$data = [];
	for ($i=0, $l = count($list); $i < $l; $i++) {
		array_push($data, '"' . str_replace('"','\\"', strval($list[$i]) ) . '"');
	}
	return 'L' . json_encode($list);
}
*/
}

?>