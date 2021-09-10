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
include __DIR__."/inc/mqtt_topics.php";
$PAUSE = 60; //пауза между запросами

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
    global $MQTT_METEO_TOPICS_WEATHER;
    global $MQTT_METEO_TOPICS_YANDEX;
    
    if (strpos($message->topic, 'weather') !== false)
        echo $message->topic, " ", $message->payload, "\n";

    if ($message->topic == 'sys/dtm')
        $webData['sys']['dtm'] = str2dtm($message->payload);


    if ($message->topic == $MQTT_METEO_TOPICS_WEATHER['WINDDIR_VAL'])
        $webData['winddir']['value'] = str2int($message->payload);
    if ($message->topic == $MQTT_METEO_TOPICS_WEATHER['WINDDIR_DTM'])
        $webData['winddir']['dtm'] = str2dtm($message->payload);

    if ($message->topic == $MQTT_METEO_TOPICS_WEATHER['WIND_VAL'])
        $webData['wind']['value'] = str2int($message->payload);
    if ($message->topic == $MQTT_METEO_TOPICS_WEATHER['WIND_DTM'])
        $webData['wind']['dtm'] = str2dtm($message->payload);

    if ($message->topic == $MQTT_METEO_TOPICS_WEATHER['PRESS_VAL'])
        $webData['press']['value'] = str2int($message->payload);
    if ($message->topic == $MQTT_METEO_TOPICS_WEATHER['PRESS_DTM'])
        $webData['press']['dtm'] = str2dtm($message->payload);

    if ($message->topic == $MQTT_METEO_TOPICS_WEATHER['HUM_VAL'])
        $webData['hum']['value'] = str2int($message->payload);
    if ($message->topic == $MQTT_METEO_TOPICS_WEATHER['HUM_DTM'])
        $webData['hum']['dtm'] = str2dtm($message->payload);

    if ($message->topic == $MQTT_METEO_TOPICS_WEATHER['TEMP_VAL'])
        $webData['temp']['value'] = str2int($message->payload);
    if ($message->topic == $MQTT_METEO_TOPICS_WEATHER['TEMP_DTM'])
        $webData['temp']['dtm'] = str2dtm($message->payload);

    if ($message->topic == $MQTT_METEO_TOPICS_WEATHER['KPINDEX_VAL'])
        $webData['kpindex']['value'] = str2int($message->payload);

    if ($message->topic == $MQTT_METEO_TOPICS_WEATHER['KPINDEX_DTM'])
        $webData['kpindex']['dtm'] = str2dtm($message->payload);


    if ($message->topic == $MQTT_METEO_TOPICS_YANDEX['TEMP_DTM'])
        $webData['ytemp']['dtm'] = str2dtm($message->payload);
    if ($message->topic == $MQTT_METEO_TOPICS_YANDEX['TEMP_VAL'])
        $webData['ytemp']['value'] = str2int($message->payload);


    if ($message->topic == $MQTT_METEO_TOPICS_YANDEX['PRESS_VAL'])
        $webData['ypress']['value'] = str2int($message->payload);
    if ($message->topic == $MQTT_METEO_TOPICS_YANDEX['PRESS_DTM'])
        $webData['ypress']['dtm'] = str2dtm($message->payload);

    if ($message->topic == $MQTT_METEO_TOPICS_YANDEX['WINDSPEED_VAL'])
        $webData['ywind']['value'] = str2int($message->payload);
    if ($message->topic == $MQTT_METEO_TOPICS_YANDEX['WINDSPEED_DTM'])
        $webData['ywind']['dtm'] = str2dtm($message->payload);

    if ($message->topic == $MQTT_METEO_TOPICS_YANDEX['WINDDIR_VAL'])
        $webData['ywinddir']['value'] = $message->payload;
    if ($message->topic == $MQTT_METEO_TOPICS_YANDEX['WINDDIR_DTM'])
        $webData['ywinddir']['dtm'] = str2dtm($message->payload);

    if ($message->topic == $MQTT_METEO_TOPICS_YANDEX['DAYNAME_VAL'])
        $webData['yday_name']['value'] = $message->payload;
    if ($message->topic == $MQTT_METEO_TOPICS_YANDEX['DAYNAME_DTM'])
        $webData['yday_name']['dtm'] = str2dtm($message->payload);

    if ($message->topic == $MQTT_METEO_TOPICS_YANDEX['TYPE_VAL'])
        $webData['ytype']['value'] = $message->payload;
    if ($message->topic == $MQTT_METEO_TOPICS_YANDEX['TYPE_DTM'])
        $webData['ytype']['dtm'] = str2dtm($message->payload);

    if ($message->topic == $MQTT_METEO_TOPICS_YANDEX['CODE_VAL'])
        $webData['ycode']['value'] = $message->payload;
    if ($message->topic == $MQTT_METEO_TOPICS_YANDEX['CODE_DTM'])
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
      mqtt_write(FORCE_UPDATE, '1');        
      mqtt_write($MQTT_WEB['WATCHDOG'], time());
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
        mqtt_write($MQTT_WEB['WATCHDOG'], time());
        $infopanelVisual = 1-$infopanelVisual;
       
        if (!sleep_my($PAUSE)) break;
    }

    mqtt_close();


    echo "                     Бесконечный цикл\r";
    //sleep(50);
}

?>
