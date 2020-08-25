<?php

/*
 * Система КВ152. Формирование данных для web на основе данных mqtt.
 * Данная программа:
 * 1. должна постоянно работать
 * 2. подписывается к нужным для web данным
 * 3. формирует json-файлы, которые будут считываться из web
 */

$forceUpdate = 0;
$DEBUG = 1;
$webFileName = '/var/www/mqtt/web-mqtt.json';
define('CLIENT_ID', "web-mqtt");
//include_once(__DIR__ . "/inc/php_serial.class.php");
include __DIR__."/inc/mqtt_serv.inc.php";
$PAUSE = 60; //пауза между запросами
$MQTT_INFOPANEL_WATCHDOG = 'sysinfo/watchdog/web-mqtt';
//Данные для web
$webData = array();




/**
 * Конвертирование строки с датой временем в объект
 * @param type $str
 */
function str2dtm($str) {
    return( DateTime::createFromFormat('Y-m-d H:i:s', $str) );
}

/**
 * Конвертирование строки в целое
 * @param srting $str
 * @return int
 */
function str2int($str) {
    return( (int) $str );
}

/**
 * Конвертирование строки в действительное число
 * @param srting $str
 * @return float
 */
function str2float($str) {
    return( (float) $str );
}

/**
 * Обработчик сообщений от брокера
 * @global Mosquitto\Client $client
 * @global type $webData
 * @param type $message
 */
function mqttProcessMesssage($message) {
    global $client;
    global $webData;
    if (strpos($message->topic, 'weather') !== false)
        echo $message->topic, " ", $message->payload, "\n";

    if ($message->topic == 'sys/dtm')
        $webData['sys']['dtm'] = str2dtm($message->payload);


    if ($message->topic == 'weather/winddir/value')
        $webData['winddir']['value'] = str2int($message->payload);
    if ($message->topic == 'weather/winddir/dtm')
        $webData['winddir']['dtm'] = str2dtm($message->payload);

    if ($message->topic == 'weather/wind/value')
        $webData['wind']['value'] = str2int($message->payload);
    if ($message->topic == 'weather/wind/dtm')
        $webData['wind']['dtm'] = str2dtm($message->payload);

    if ($message->topic == 'weather/press/value')
        $webData['press']['value'] = str2int($message->payload);
    if ($message->topic == 'weather/press/dtm')
        $webData['press']['dtm'] = str2dtm($message->payload);

    if ($message->topic == 'weather/hum/value')
        $webData['hum']['value'] = str2int($message->payload);
    if ($message->topic == 'weather/hum/dtm')
        $webData['hum']['dtm'] = str2dtm($message->payload);

    if ($message->topic == 'weather/temp/value')
        $webData['temp']['value'] = str2int($message->payload);
    if ($message->topic == 'weather/temp/dtm')
        $webData['temp']['dtm'] = str2dtm($message->payload);

    if ($message->topic == 'weather/kpindex/value')
        $webData['kpindex']['value'] = str2int($message->payload);

    if ($message->topic == 'weather/kpindex/dtm')
        $webData['kpindex']['dtm'] = str2dtm($message->payload);


    if ($message->topic == 'weather/yandex/temp/dtm')
        $webData['ytemp']['dtm'] = str2dtm($message->payload);
    if ($message->topic == 'weather/yandex/temp/value')
        $webData['ytemp']['value'] = str2int($message->payload);


    if ($message->topic == 'weather/yandex/press/value')
        $webData['ypress']['value'] = str2int($message->payload);
    if ($message->topic == 'weather/yandex/press/dtm')
        $webData['ypress']['dtm'] = str2dtm($message->payload);

    if ($message->topic == 'weather/yandex/wind/value')
        $webData['ywind']['value'] = str2int($message->payload);
    if ($message->topic == 'weather/yandex/wind/dtm')
        $webData['ywind']['dtm'] = str2dtm($message->payload);

    if ($message->topic == 'weather/yandex/winddir/value')
        $webData['ywinddir']['value'] = $message->payload;
    if ($message->topic == 'weather/yandex/winddir/dtm')
        $webData['ywinddir']['dtm'] = str2dtm($message->payload);

    if ($message->topic == 'weather/yandex/day_name/value')
        $webData['yday_name']['value'] = $message->payload;
    if ($message->topic == 'weather/yandex/day_name/dtm')
        $webData['yday_name']['dtm'] = str2dtm($message->payload);

    if ($message->topic == 'weather/yandex/type/value')
        $webData['ytype']['value'] = $message->payload;
    if ($message->topic == 'weather/yandex/type/dtm')
        $webData['ytype']['dtm'] = str2dtm($message->payload);

    if ($message->topic == 'weather/yandex/code/value')
        $webData['ycode']['value'] = $message->payload;
    if ($message->topic == 'weather/yandex/code/dtm')
        $webData['ycode']['dtm'] = str2dtm($message->payload);
}

function updateWebData() {
    global $webData;
    global $webFileName;

    file_put_contents($webFileName, json_encode($webData));
    return( true );
}


  
///////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////
global $clientIsConnected;
$firstTime = true;
echo "Начали\n";
//Бесконечный цикл
while (true) {

    mqtt_connect( "#" );

    if (!mqtt_loop()){
         if( !$clientIsConnected ) die('Проблема при подключении к серверу');
         continue;
     }
    if( $firstTime ){
    echo "Указываем, что требуется принудительное обновления значений всем программам\n";
      mqtt_write('kv152/pogoda-force-update', '1');        
      mqtt_write($MQTT_INFOPANEL_WATCHDOG, time());
    }
    $firstTime = false;
     if (!mqtt_loop()){
         if( !$clientIsConnected ) die('Проблема при подключении к серверу');
         continue;
     }
    
    echo "Начали опрос\n";
    $infopanelVisual = 0;
    while (true) {

        updateWebData();
        if (!mqtt_loop()) break;
        echo "                   ".($infopanelVisual?"O":"*")."БД\r";
        mqtt_write($MQTT_INFOPANEL_WATCHDOG, time());
        $infopanelVisual = 1-$infopanelVisual;
       
        if (!sleep_my($PAUSE)) break;
    }

    mqtt_close();


    echo "                     Бесконечный цикл\r";
    //sleep(50);
}

?>
