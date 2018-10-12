<?php

// expected URI examples:
//            "agenda/speaker/slavey"
//            "agenda/event/docker"
//            "agenda/time/1539820300"

// $uri = explode('/', $_SERVER['REQUEST_URI']);
// $action = $uri[1];
// $filter = $uri[2];

$action = $_GET['action'];
$filter = $_GET['filter'];

$agenda = json_decode(file_get_contents('zendcon.json'), true);

$result = array();

switch ($action) {
	case 'event':
	case 'speaker':
        $index = file("{$action}_index.txt",FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        // ideally, here I should be filtering out regex-unsafe strings
		$matches = preg_grep('/' . $filter . '/i', $index);
		foreach ($matches as $value) {
			$k = explode('|', $value)[1];
			$result[$k] = $agenda[$k];
		}
		break;

    case 'time':
        if ( !is_numeric($filter) || $filter < 1539619200 || $filter > 1539942780) {
            $result['error'] = "Timestamp '$filter' is outside the conference time or not valid UNIX time";
            $result['mood'] = ":-/";
            break;
        }
        $index = json_decode(file_get_contents('time_array.json'), true);
        date_default_timezone_set('America/Los_Angeles');
        $matches = array_filter($index, function($value){
			global $filter;
            if ($value['start'] <= $filter && $value['end'] >= $filter) {
                return true;
            }
            return false;
        });
		foreach ($matches as $k => $v) {
			$result[$k] = $agenda[$k];
		}
		break;

	default:
		$result['error'] = "Action '$action' is invalid";
		$result['mood'] = ":-(";
}

header("Content-Type: application/json");
echo json_encode($result, JSON_PRETTY_PRINT);
