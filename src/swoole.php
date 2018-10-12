<?php

chdir(__DIR__);


function parseCsv($file)
{
    $fp = fopen($file, 'r');
    $data = [];

    $headings = fgetcsv($fp);

    while($row = fgetcsv($fp))
    {
        $data[] = array_combine($headings, $row);
    }

    fclose($fp);
    return $data;
}

function filter($value, $beers)
{
    return array_filter($beers, function($v) use($value) {
        return stripos($v['name'], $value) !== false || stripos($v['style'], $value) !== false;
    });
}

function filterNumber($field, $value, $beers)
{
    $min = $max = null;

    //single value?
    if(stripos($value, '-') !== false)
    {
        list($min, $max) = explode('-', $value);
    } else {
        $min = $value;
    }

    return array_filter($beers, function($v) use($field, $min, $max) {
        if(!is_null($max))
        {
            return round((float)$v[$field], 3) >= $min && round((float)$v[$field], 3) <= $max;
        }
        return round((float)$v[$field], 3) == $min;
    });
}

$beers      = parseCsv('beers.csv');
$breweries  = parseCsv('breweries.csv');

$http = new swoole_http_server("0.0.0.0", 9501);

$http->on("start", function ($server) {
    echo "Swoole http server is started at http://0.0.0.0:9501\n";
});

$http->on("request", function ($request, $response) use ($beers, $breweries) {
    $method = $request->server['request_method'];
    //$request->get['q']);

    if(isset($request->get['q']))
    {
        $beers = filter($request->get['q'], $beers);
    }

    if(isset($request->get['abv']))
    {
        $beers = filterNumber('abv', $request->get['abv'], $beers);
    }

    if(isset($request->get['ibu']))
    {
        $beers = filterNumber('ibu', $request->get['ibu'], $beers);
    }

    //merge brewer
    foreach($beers as &$beer)
    {
        unset($beer['id']);
        $breweryId = $beer['brewery_id'];
        unset($beer['brewery_id']);

        $brewery = array_values(array_filter($breweries, function($b) use ($breweryId) { return $b['id'] == $breweryId; }));
        unset($brewery['id']);
        $beer['brewery'] = $brewery[0] ?? null;
    }

    $response->header("Content-Type", "application/json");
    $response->end(json_encode($beers));
});

$http->start();
