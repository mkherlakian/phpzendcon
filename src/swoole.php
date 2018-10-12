<?php

chdir(__DIR__);

$agenda     = json_decode(file_get_contents('zendcon.json'), true);
$event      = file("event_index.txt",FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$speaker    = file("speaker_index.txt",FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$times      = $index = json_decode(file_get_contents('time_array.json'), true);

$http = new swoole_http_server("0.0.0.0", 9501);

$http->on("start", function ($server) {
    echo "Swoole http server is started at http://0.0.0.0:9501\n";
});

$http->on("request", function ($request, $response) use ($agenda, $event, $speaker, $times) {
    $action = $request->get['action'];
    $filter = $request->get['filter'];

    $result = array();

    switch ($action) {
        case 'event':
        case 'speaker':
            $index = $action == 'event' ? $event : $speaker;
            // ideally, here I should be filtering out regex-unsafe strings
            //
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
            //$index = json_decode(file_get_contents('time_array.json'), true);

            $index = $times;
            $matches = array_filter($index, function($value) use($filter) {
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

    $response->header("Content-Type", "application/json");
    $response->end(json_encode($result));
});

$http->start();
