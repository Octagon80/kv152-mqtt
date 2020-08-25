<?php

/*
 * Программа получает параметры из брокера mqtt
 * и записывает в базу данных
 */
$NEED_LOG = false;
define('CLIENT_ID', "db");
include_once("/var/www/inc/first.inc.php");
include __DIR__."/inc/mqtt_serv.inc.php";
$debug = 1;
$PAUSE = 60; //пауза между запросами

//Таймер работы программы записи данных в БД.
//Значение топика - время UNIX (сек)
$MQTT_INFOPANEL_WATCHDOG = 'sysinfo/watchdog/db';



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
 * Вызов выполнения запроса SQL в БД
 * @global int $debug
 * @param string $title заголовок для отладочного сообщения
 * @param string $sql запрос
 * @return type
 */
function db_write($title, $sql) {
    global $debug;
    global $MQTT_INFOPANEL_WATCHDOG;

    if ($debug > 0)
        echo "db_write " . $title . "  " . $sql . "\n";
    $result = SqlExec($sql, $ErrMsg);
  
    if ($result === false) {
        if ($debug > 0)
            echo "Ошибка 1 записи в БД " . $title . "  " . $ErrMsg . "\n";
    }else {
       // mqtt_write($MQTT_INFOPANEL_WATCHDOG, time());
    }
    return( $result );
};





/**
 * Обработчик сообщений от брокера
 * @global Mosquitto\Client $client
 * @global type $panelData
 * @param type $message
 */
function mqttProcessMesssage($message) {
    global $client;
    global $panelData;


    /*
      Параметр point
      1 Не определено
      2 Комната - детская
      3 Комната - коридор
      5 Комп - Samsung
      6 Комп - Сервер
      4 Улица
      7 Комната - спальная
      8 Комната - зал
      9 Кладовка

     */
//Время замера
    $DTM = DateTimes2Str(new DateTime());

    //echo $message->topic, "\n", $message->payload, "\n\n";
    if ($message->topic == 'sys/dtm')
        $panelData['sys']['dtm'] = str2dtm($message->payload);


    $numb_pos = 9;
    //if( $message->topic == 'weather/winddir/dtm')   $panelData['winddir']['dtm']   = str2dtm($message->payload);
    //if( $message->topic == 'weather/winddir/value') $panelData['winddir']['value'] = str2int( $message->payload );
    if ($message->topic == 'weather/winddir/value') {
        $sql = "SELECT add_params('$DTM." . sprintf('%03d', $numb_pos) . "', 4,17,  " . $message->payload . ", 11);";
        db_write('Улица. Направление ветра.', $sql);
    }


    $numb_pos = 8;
    //if( $message->topic == 'weather/wind/value') $panelData['wind']['value'] = str2int( $message->payload );
    //if( $message->topic == 'weather/wind/dtm')   $panelData['wind']['dtm']   = str2dtm($message->payload);
    if ($message->topic == 'weather/wind/value') {
        $sql = "SELECT add_params('$DTM." . sprintf('%03d', $numb_pos) . "', 4,16,  " . $message->payload . ", 10);";
        db_write('Улица. ветер.', $sql);
    }

    $numb_pos = 7;
    //if( $message->topic == 'weather/press/value') $panelData['press']['value'] = str2int( $message->payload );
    //if( $message->topic == 'weather/press/dtm')   $panelData['press']['dtm']   = str2dtm($message->payload);
    if ($message->topic == 'weather/press/value') {
        $sql = "SELECT add_params('$DTM." . sprintf('%03d', $numb_pos) . "', 4,9,  " . $message->payload . ", 6);";
        db_write('Улица. Давление.', $sql);
    }


    $numb_pos = 6;
    //if( $message->topic == 'weather/hum/value') $panelData['hum']['value'] = str2int( $message->payload );
    //if( $message->topic == 'weather/hum/dtm')   $panelData['hum']['dtm']   = str2dtm($message->payload);
    if ($message->topic == 'weather/hum/value') {
        $sql = "SELECT add_params('$DTM." . sprintf('%03d', $numb_pos) . "', 4,7,  " . $message->payload . ", 3);";
        db_write('Улица. Влажность.', $sql);
    }


    $numb_pos = 5;
    //if( $message->topic == 'weather/temp/value') $panelData['temp']['value'] = str2int( $message->payload );
    //if( $message->topic == 'weather/temp/dtm')   $panelData['temp']['dtm']   = str2dtm($message->payload);
    if ($message->topic == 'weather/temp/value') {
        $sql = "SELECT add_params('$DTM." . sprintf('%03d', $numb_pos) . "', 4,6, " . $message->payload . ", 2);";
        db_write('Улица. Температура.', $sql);
    }


    $numb_pos = 100;
    //if( $message->topic == 'weather/kpindex/value') $panelData['kpindex']['value'] = str2int( $message->payload );
    //if( $message->topic == 'weather/kpindex/dtm')   $panelData['kpindex']['dtm']   = str2dtm($message->payload);
    if ($message->topic == 'weather/kpindex/value') {
        $sql = "SELECT add_params('$DTM." . sprintf('%03d', $numb_pos) . "', 4,20, " . $message->payload . ", 2);";
        db_write('Улица. kp-index.', $sql);
    }

    $numb_pos = 1;
    if ($message->topic == 'arduino/weight1') {
        $data = array('point' => '9', 'unit' => '7', 'subject' => 10, 'value' => $message->payload);
        $sql = "SELECT add_params('$DTM." . sprintf('%03d', $numb_pos) . "'::character varying, " . $data['point'] . "::bigint," . $data['subject'] . "::bigint, " . $data['value'] . "::real, " . $data['unit'] . "::bigint);";
        $numb_pos = $numb_pos + 1;
        db_write('Вес 1', $sql);
    }
    $numb_pos = 2;
    if ($message->topic == 'arduino/weight2') {
        $data = array('point' => '9', 'unit' => '7', 'subject' => 11, 'value' => $message->payload);
        $sql = "SELECT add_params('$DTM." . sprintf('%03d', $numb_pos) . "'::character varying, " . $data['point'] . "::bigint," . $data['subject'] . "::bigint, " . $data['value'] . "::real, " . $data['unit'] . "::bigint);";
        $numb_pos = $numb_pos + 1;
        db_write('Вес 2', $sql);
    }

    $numb_pos = 3;
    if ($message->topic == 'arduino/Temperature') {
        $data = array('point' => '9', 'unit' => '2', 'subject' => 6, 'value' => $message->payload);

        $sql = "SELECT add_params('$DTM." . sprintf('%03d', $numb_pos) . "'::character varying, " . $data['point'] . "::bigint," . $data['subject'] . "::bigint, " . $data['value'] . "::real, " . $data['unit'] . "::bigint);";
        $numb_pos = $numb_pos + 1;
        db_write('Кладовка.Температура', $sql);
    }

    $numb_pos = 4;
    if ($message->topic == 'arduino/Humidity') {
        $data = array('point' => '9', 'unit' => '3', 'subject' => 7, 'value' => $message->payload);

        $sql = "SELECT add_params('$DTM." . sprintf('%03d', $numb_pos) . "'::character varying, " . $data['point'] . "::bigint," . $data['subject'] . "::bigint, " . $data['value'] . "::real, " . $data['unit'] . "::bigint);";
        $numb_pos = $numb_pos + 1;
        db_write('Кладовка.Влажность', $sql);
    }


    $numb_pos = 5;
    if ($message->topic == 'arduino/gauss') {
        $data = array('point' => '9', 'unit' => '9', 'subject' => 15, 'value' => $message->payload);

        $sql = "SELECT add_params('$DTM." . sprintf('%03d', $numb_pos) . "'::character varying, " . $data['point'] . "::bigint," . $data['subject'] . "::bigint, " . $data['value'] . "::real, " . $data['unit'] . "::bigint);";
        $numb_pos = $numb_pos + 1;
        db_write('Кладовка.Гаусс', $sql);
    }


    return( true );
}

    
///////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////
$infopanelVisual = 0;
dbConnect(false);
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
      mqtt_write($MQTT_INFOPANEL_WATCHDOG, time());
    }
    $firstTime = false;
     if (!mqtt_loop()){
         if( !$clientIsConnected ) die('Проблема при подключении к серверу');
         continue;
     }
    
    echo "Начали опрос\n";
    while (true) {

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
