#!/usr/bin/php
<?php
/**
 *  Получить системную информацию об компьютере
 * и отправить параметры в MQTT сервер
 */
include __DIR__ . "/inc/sysinfo.php";
define('CLIENT_ID', "dexp-sysinfo");
include __DIR__."/inc/mqtt_serv.inc.php";
$PAUSE = 30; //пауза между запросами
$MQTT_INFOPANEL_WATCHDOG = 'sysinfo/watchdog/dexp-sysinfo';



$WATCHDOG = array(
    'INFOPANELOLD' => array(
        'ALARM' => false,
        'TIMEOUT' => 10 *  60,
        'TIMEOLD' => 0,
        'MQTT' => 'sysinfo/watchdog/infopanelold'
    ),
    'INFOPANEL' => array(
        'ALARM' => false,
        'TIMEOUT' => 10 * 60,
        'TIMEOLD' => 0,
        'MQTT' => 'sysinfo/watchdog/infopanel'
    ),
    'DB' => array(
        'ALARM' => false,
        'TIMEOUT' => 5 * 60,
        'TIMEOLD' => 0,
        'MQTT' => 'sysinfo/watchdog/db'
    ),
    'METEO_YANDEX' => array(
        'ALARM' => false,
        'TIMEOUT' => 10 * 60,
        'TIMEOLD' => 0,
        'MQTT' => 'sysinfo/watchdog/meteo_yandex'
    ),
    'METEO_NAROD' => array(
        'ALARM' => false,
        'TIMEOUT' => 15 * 60,
        'TIMEOLD' => 0,
        'MQTT' => 'sysinfo/watchdog/meteo_narod'
    ),
    'ARDUINO' => array(
        'ALARM' => false,
        'TIMEOUT' => 10 * 60,
        'TIMEOLD' => 0,
        'MQTT' => 'sysinfo/watchdog/arduino'
    )
);


function sendAlarmText($title, $text) {
    echo "!!!!! " . $title . "  " . $text . "\n";
    shell_exec("echo '" . $text . "' | mutt -s '" . $title . "' -- kv152motion@mail.ru");
    $DTM = date("Y-m-d H:i:s");
    $fd = fopen('/tmp/sysinfo-mqtt.log', 'a');
    if (!$fd)
        return(false);
    // Записываем $somecontent в наш открытый файл.
    if (fwrite($fd, $DTM . "::" . $title . "::" . $text."\n") === FALSE) {
        fclose($fd);
        return(false);
    }
    fclose($fd);
    return( true );
}

function checkTimeOut($name, $title, $text) {
    global $WATCHDOG;

    $time = time() - $WATCHDOG[$name]['TIMEOLD']; 
    
    //echo $name." if ((".$time." > ".$WATCHDOG[$name]['TIMEOUT']." ) && !".($WATCHDOG[$name]['ALARM']?"true":"false").") ...\n";
    //Если время задержки превышено и ранее не отправляли сообщение....
    if (($time > $WATCHDOG[$name]['TIMEOUT'] ) && !$WATCHDOG[$name]['ALARM']) {
        //отправляем сообщение о неработающей БД
        $WATCHDOG[$name]['ALARM'] = true; //сделаем так, чтобы сообщение было отправлено 1 раз
        sendAlarmText($title, $text);
    }

    //Если время задержки превышено и уже отправляли сообщение....
    if (($time > $WATCHDOG[$name]['TIMEOUT'] ) && $WATCHDOG[$name]['ALARM']) {
        //ничего не делаем, ждем сброса ошибки превышение времени обновления данных
    }

    //Если время задержки в номер и ранее была ошибка
    if (($time < $WATCHDOG[$name]['TIMEOUT'] ) && $WATCHDOG[$name]['ALARM']) {
        //сброс ошибки 
        $WATCHDOG[$name]['ALARM'] = false;
        sendAlarmText($title, "ИСПРАВЛЕНО ".$text);
    }
    return( true );
}

/**
 * Обработчик сообщений от брокера
 * @global Mosquitto\Client $client
 * @global type $panelData
 * @param type $message
 */
function mqttProcessMesssage($message) {
    global $client;
    global $panelData;
    global $forceUpdate;
    global $WATCHDOG;
    if (strpos($message->topic, 'weather') !== false)
        echo $message->topic, " ", $message->payload, "\n";


    if (strpos($message->topic, $WATCHDOG['DB']['MQTT']) !== false) {
        echo "Мониторинг работы БД " . date("Y-m-d H:i:s", $message->payload) . "\n";
        $WATCHDOG['DB']['TIMEOLD'] = (int) $message->payload;
    }
    checkTimeOut('DB', 'Watchdog БД', 'Не работает БД');


    if (strpos($message->topic, $WATCHDOG['METEO_YANDEX']['MQTT']) !== false) {
        echo "Мониторинг работы Погода Яндекс " . date("Y-m-d H:i:s", $message->payload) . "\n";
        $WATCHDOG['METEO_YANDEX']['TIMEOLD'] = (int) $message->payload;
    }
    checkTimeOut('METEO_YANDEX', 'Watchdog Погода Я', 'Не работает получение погоды от яндекс');


    if (strpos($message->topic, $WATCHDOG['METEO_NAROD']['MQTT']) !== false) {
        echo "Мониторинг работы Погода Народмон " . date("Y-m-d H:i:s", $message->payload) . "\n";
        $WATCHDOG['METEO_NAROD']['TIMEOLD'] = (int) $message->payload;
    }
    checkTimeOut('METEO_NAROD', 'Watchdog Погода Narod', 'Не работает получение погоды от народмон');


    if (strpos($message->topic, $WATCHDOG['ARDUINO']['MQTT']) !== false) {
        echo "Мониторинг работы Arduino " . date("Y-m-d H:i:s", $message->payload) . "\n";
        $WATCHDOG['ARDUINO']['TIMEOLD'] = (int) $message->payload;
    }
    checkTimeOut('ARDUINO', 'Watchdog Arduino', 'Не работает Arduino');


    if (strpos($message->topic, $WATCHDOG['INFOPANELOLD']['MQTT']) !== false) {
        echo "Мониторинг обновления данных для старой информационной панели " . date("Y-m-d H:i:s", $message->payload) . "\n";
        $WATCHDOG['INFOPANELOLD']['TIMEOLD'] = (int) $message->payload;
    }
    checkTimeOut('INFOPANELOLD', 'Watchdog InfoPanelOld', 'Не работает обновления данных для старой информационной панели');



    if (strpos($message->topic, $WATCHDOG['INFOPANEL']['MQTT']) !== false) {
        echo "Мониторинг обновления данных для инфопанели " . date("Y-m-d H:i:s", $message->payload) . "\n";
        $WATCHDOG['INFOPANEL']['TIMEOLD'] = (int) $message->payload;
    }
    checkTimeOut('INFOPANEL', 'Watchdog InfoPanel', 'Не работает обновления данных для информационной панели');

    return( true );
}





/* * ******************************************************************************
 * Начало программы
 */

$infopanelVisual = 0;

$firstTime = true;
echo "Начали\n";
//Бесконечный цикл
while (true) {

    mqtt_connect( "#" );
    if (!mqtt_loop()) continue;
    if( $firstTime ){
    echo "Указываем, что требуется принудительное обновления значений всем программам\n";
    mqtt_write('kv152/pogoda-force-update', '1');
      mqtt_write($MQTT_INFOPANEL_WATCHDOG, time());
    }
    $firstTime = false;
     if (!mqtt_loop()) continue;
    
    echo "Начали опрос\n";
    while (true) {

        ///Время замера
        $t = new DateTime();
        $DTM = $t->format('Y-m-d H:i:s');
        unset($t);
        $sysinfo = getSysinfo($debug);
        if ($debug > 2) {
            echo "sysinfo=";
            print_r($sysinfo);
        }
        mqtt_write('sysinfo/dexp/cpu/t', $sysinfo['cpu']['t']);
        mqtt_write('sysinfo/dexp/cpu/core1t', $sysinfo['cpu']['core1t']);
        mqtt_write('sysinfo/dexp/cpu/core2t', $sysinfo['cpu']['core2t']);
        mqtt_write('sysinfo/dexp/cpu/load/avarage', $sysinfo['cpu']['load']['0']);
        mqtt_write('sysinfo/dexp/cpu/load/core1', $sysinfo['cpu']['load']['1']);
        mqtt_write('sysinfo/dexp/cpu/load/core2', $sysinfo['cpu']['load']['2']);
        mqtt_write('sysinfo/dexp/hdd1/t', $sysinfo['hdd1']['t']);
        
        
        if (!mqtt_loop()) break;
        echo "                   ".($infopanelVisual?"O":"*")."Инфопанель\r";
        mqtt_write($MQTT_INFOPANEL_WATCHDOG, time());
        $infopanelVisual = 1-$infopanelVisual;
       
        if (!sleep_my($PAUSE)) break;
    }

    mqtt_close();


    echo "                     Бесконечный цикл\r";
    //sleep(50);
}
