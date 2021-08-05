#!/usr/bin/php
<?php
/**
 * Получить текущую температуру, давление, влажность, ветер...
 * и отправить параметры в MQTT сервер
 */
$forceUpdate = 0;
define('CLIENT_ID', "meteo-mqtt");
include __DIR__."/inc/mqtt_serv.inc.php";
include __DIR__."/inc/mqtt_topics.php";

$PAUSE = 60; //пауза между запросами



global $NARODMON_ID;
global $NARODMIN_APIKEY;
global $NARODMON_MYSITE;
global $MQTT_METEO_SENSORS_TOPICS;
global $MQTT_METEO_TOPICS_WEATHER;
global $MQTT_METEO_TOPICS_YANDEX;

$SIMILATE = false;



include(__DIR__ . "/inc/get-kp-index.php");
include(__DIR__ . "/inc/yandex.pogoda.php");
include(__DIR__ . "/inc/balandino.php");
$debug = 5; //1;


$PAUSE = 5 * 60; //мин. пауза между запросами


$SIG_handler = function () {
    mqtt_close();
    die('Останов');
};

$MQTT_METEO_TOPICS_FORCE_UPDATE = 'kv152/pogoda-force-update';

$OtherSensor = array('p' => -32768, 'h' => -32768, 't' => -32768, 'wind' => -32768, 'winddir' => -32768);



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
    global $MQTT_METEO_TOPICS_FORCE_UPDATE;
    global $MQTT_METEO_SENSORS_TOPICS;
    global $OtherSensor;


    
    if (strpos($message->topic, 'weather') !== false)
        echo $message->topic, " ", $message->payload, "\n";

    if ($message->topic == $MQTT_METEO_TOPICS_FORCE_UPDATE ) {
        echo "Получили сигнал об принудительом обновлении значений " . $message->payload . " \n";
        $forceUpdate = ( (int) $message->payload == 1);
    }
    
    //Обрабатываем работу нашей погодной станции
    if ($message->topic == $MQTT_METEO_SENSORS_TOPICS['TEMP_VAL'] ) {
        //Температура на улице
        $OtherSensor['t'] = floatval( $message->payload );
        echo "Моя погодная станция. Температура = ".$OtherSensor['t']."\n";
    }
    if ($message->topic == $MQTT_METEO_SENSORS_TOPICS['BMP_PRESS_VAL'] ) {
        //Давление на улице
        $OtherSensor['p'] = floatval( $message->payload );
        echo "Моя погодная станция. Давление = ".$OtherSensor['p']."\n";
    }
    if ($message->topic == $MQTT_METEO_SENSORS_TOPICS['HUM_VAL'] ) {
        //Влажность на улице
        $OtherSensor['h'] = floatval( $message->payload );
        //Есть особенность прибора: иногда возвращает 998%
        //Одина раз видел значения около 68%, потом скачек в 998 и вновь 68%
        if( $OtherSensor['h'] > 100 ) $OtherSensor['h'] = 99.99;
        echo "Моя погодная станция. Влажность = ".$OtherSensor['h']."\n";
    }

    
}



function handler(){
    global $NARODMON_ID;
    global $NARODMIN_APIKEY;
    global $NARODMON_MYSITE;
    global $SIMILATE;
    global $debug;
    global $MQTT_METEO_TOPICS_WEATHER;
    global $MQTT_METEO_TOPICS_YANDEX;
    global $OtherSensor;


  
        //Время замера
        $t = new DateTime();
        $DTM = $t->format('Y-m-d H:i:s');
        unset($t);

        

                
//Получение значений от другого источника из народмон на случай
//некорректной работы моих датчиков
        $ch = false;
        $reply = false;
        if ($SIMILATE) {
            //иммитируем обращение к narod
            $epoch = "" . time();
            $wind_dir = rand(0, 360);
            $wind_speed = rand(0, 20);
            $press = rand(730, 790);
            $hum = rand(0, 100);
            $temp = rand(-32, 32);
            $reply = '{"id":15757,"mac":"","name":"CHELYABINSK-BALA","my":0,"owner":"3","location":"\u0427\u0435\u043b\u044f\u0431\u0438\u043d\u0441\u043a","distance":775.16,"liked":0,"uptime":50,"sensors":[{"id":86416,"mac":"","fav":0,"pub":1,"type":1,"name":"temp_c","value":' . $temp . ',"unit":"\u00b0","time":' . $epoch . ',"changed":' . $epoch . ',"trend":-2},{"id":94411,"mac":"","fav":0,"pub":1,"type":2,"name":"RH","value":' . $hum . ',"unit":"%","time":' . $epoch . ',"changed":' . $epoch . ',"trend":19.16},{"id":86417,"mac":"","fav":0,"pub":1,"type":3,"name":"press_qfe","value":' . $press . ',"unit":"mmHg","time":' . $epoch . ',"changed":' . $epoch . ',"trend":0},{"id":86419,"mac":"","fav":0,"pub":1,"type":4,"name":"wind_speed","value":' . $wind_speed . ',"unit":"m\/s","time":' . $epoch . ',"changed":' . $epoch . ',"trend":0},{"id":86418,"mac":"","fav":0,"pub":1,"type":5,"name":"wind_dir","value":' . $wind_dir . ',"unit":"\u0417\u0421\u0417","time":' . $epoch . ',"changed":' . $epoch . ',"trend":20}]}';
            $ch = true;
        } else {
            
        echo __FUNCTION__."______________\n";
            //Получить параметры по погоде из метеостанции в Баландино
            $BalandinoSensor = getBalandino();
        
            //Получим погоду из Яндекс
            $yandexPogoda = WetherYandex($debug);
        
            //температура
            mqtt_write($MQTT_METEO_TOPICS_YANDEX['TEMP_DTM'], $DTM);
            mqtt_write($MQTT_METEO_TOPICS_YANDEX['TEMP_VAL'], $yandexPogoda['temperature']);
            //давление
            mqtt_write($MQTT_METEO_TOPICS_YANDEX['PRESS_VAL'], $yandexPogoda['pressure']);
            mqtt_write($MQTT_METEO_TOPICS_YANDEX['PRESS_DTM'], $DTM);
            //Сила ветра, ед.изм. м/c
            mqtt_write($MQTT_METEO_TOPICS_YANDEX['WINDSPEED_VAL'], $yandexPogoda['wind_speed']);
            mqtt_write($MQTT_METEO_TOPICS_YANDEX['WINDSPEED_DTM'], $DTM);
sleep_my(10);
            //Направление ветра
            mqtt_write($MQTT_METEO_TOPICS_YANDEX['WINDDIR_VAL'], $yandexPogoda['wind_direction']);
            mqtt_write($MQTT_METEO_TOPICS_YANDEX['WINDDIR_DTM'], $DTM);
            //время суток
            mqtt_write($MQTT_METEO_TOPICS_YANDEX['DAYNAME_VAL'], $yandexPogoda['day_name']);
            mqtt_write($MQTT_METEO_TOPICS_YANDEX['DAYNAME_DTM'], $DTM);
            //описание погоды
            mqtt_write($MQTT_METEO_TOPICS_YANDEX['TYPE_VAL'], $yandexPogoda['weather_type']);
            mqtt_write($MQTT_METEO_TOPICS_YANDEX['TYPE_DTM'], $DTM);
sleep_my(10);
            //описание погоды - англ
            mqtt_write($MQTT_METEO_TOPICS_YANDEX['CODE_VAL'], $yandexPogoda['weather_code']);
            mqtt_write($MQTT_METEO_TOPICS_YANDEX['CODE_DTM'], $DTM);

            mqtt_write($MQTT_METEO_TOPICS_YANDEX['WATCHDOG'], time());
            
            sleep_my(10);


        }
        




        if ($OtherSensor['t'] > -32768) {
            mqtt_write($MQTT_METEO_TOPICS_WEATHER['TEMP_VAL'], $OtherSensor['t']);
            mqtt_write($MQTT_METEO_TOPICS_WEATHER['TEMP_SRC'], 'народмон');
            mqtt_write($MQTT_METEO_TOPICS_WEATHER['TEMP_DTM'], $DTM);
        } else {
            if ($debug > 2)
                echo "Показания по темпераутре нородмон недостоверные, не записывем\n";

            //температура от яндекс
            if (is_numeric($yandexPogoda['temperature'])) {
                if ($debug > 2)
                    echo "Берем показания по темпераутре от яндекс\n";
                mqtt_write($MQTT_METEO_TOPICS_WEATHER['TEMP_VAL'], $yandexPogoda['temperature']);
                mqtt_write($MQTT_METEO_TOPICS_WEATHER['TEMP_SRC'], 'яндекс');
                mqtt_write($MQTT_METEO_TOPICS_WEATHER['TEMP_DTM'], $DTM);
            }
        }

        sleep_my(10);

        if ($OtherSensor['h'] > -1) {
            mqtt_write($MQTT_METEO_TOPICS_WEATHER['HUM_VAL'], $OtherSensor['h']);
            mqtt_write($MQTT_METEO_TOPICS_WEATHER['HUM_SRC'], 'народмон');
            mqtt_write($MQTT_METEO_TOPICS_WEATHER['HUM_DTM'], $DTM);
        } else {
            if ($debug > 2)
                echo "Показания по влажности народмон нулевые, не записывем\n";
        }

        sleep_my(10);
        
        if ($OtherSensor['p'] > -1) {
            mqtt_write($MQTT_METEO_TOPICS_WEATHER['PRESS_VAL'], $OtherSensor['p']);
            mqtt_write($MQTT_METEO_TOPICS_WEATHER['PRESS_SRC'], 'народмон');
            mqtt_write($MQTT_METEO_TOPICS_WEATHER['PRESS_DTM'], $DTM);
        } else {
            if ($debug > 2)
                echo "Показания по давлению народмон нулевые, не записывем\n";
            if (is_numeric($yandexPogoda['pressure'])) {
                if ($debug > 2)
                    echo "Берем показания по давлению от яндекс\n";
                mqtt_write($MQTT_METEO_TOPICS_WEATHER['PRESS_VAL'], $yandexPogoda['pressure']);
                mqtt_write($MQTT_METEO_TOPICS_WEATHER['PRESS_SRC'], 'яндекс');
                mqtt_write($MQTT_METEO_TOPICS_WEATHER['PRESS_DTM'], $DTM);
            }
        }


        sleep_my(10);
        
//Сила ветра, ед.изм. м/c
        if ($OtherSensor['wind'] > -1) {
            mqtt_write($MQTT_METEO_TOPICS_WEATHER['WIND_VAL'], $OtherSensor['wind']);
            mqtt_write($MQTT_METEO_TOPICS_WEATHER['WIND_SRC'], 'народмон');
            mqtt_write($MQTT_METEO_TOPICS_WEATHER['WIND_DTM'], $DTM);
        } else 
        
        if ($BalandinoSensor['wind_speed']['value'] > -1) {
            mqtt_write($MQTT_METEO_TOPICS_WEATHER['WIND_VAL'], $BalandinoSensor['wind_speed']['value'] );
            mqtt_write($MQTT_METEO_TOPICS_WEATHER['WIND_SRC'], 'Баландино');
            mqtt_write($MQTT_METEO_TOPICS_WEATHER['WIND_DTM'], $DTM); //$BalandinoSensor['wind_speed']['dtm']
        }
        else         
        {
            if ($debug > 2)
                echo "Показания по сила ветра народмон, не записывем\n";
            if (is_numeric($yandexPogoda['wind_speed'])) {
                if ($debug > 2)
                    echo "Берем показания по сила ветра от яндекс\n";
                mqtt_write($MQTT_METEO_TOPICS_WEATHER['WIND_VAL'], $yandexPogoda['wind_speed']);
                mqtt_write($MQTT_METEO_TOPICS_WEATHER['WIND_SRC'], 'яндекс');
                mqtt_write($MQTT_METEO_TOPICS_WEATHER['WIND_DTM'], $DTM);
            }
        }


        sleep_my(10);
        
//направление ветра, ед.изм. град.
        if ($OtherSensor['winddir'] > -1) {
            mqtt_write($MQTT_METEO_TOPICS_WEATHER['WINDDIR_VAL'], $OtherSensor['winddir']);
            mqtt_write($MQTT_METEO_TOPICS_WEATHER['WINDDIR_SRC'], 'народмон');
            mqtt_write($MQTT_METEO_TOPICS_WEATHER['WINDDIR_DTM'], $DTM);
        } else {
            if ($debug > 2)
                echo "Показания по направлению ветра, не записывем\n";
        }

        sleep_my(10);
        
        $kpindex = getKpIndex();
        mqtt_write($MQTT_METEO_TOPICS_WEATHER['KPINDEX_VAL'], $kpindex[0]);
        mqtt_write($MQTT_METEO_TOPICS_WEATHER['KPINDEX_SRC'], 'noaa.gov');        
        mqtt_write($MQTT_METEO_TOPICS_WEATHER['KPINDEX_DTM'], $DTM);
        sleep_my(10);
  
}

///////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////

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
      mqtt_write($MQTT_METEO_TOPICS_WEATHER['WATCHDOG'], time());
    }
    $firstTime = false;
     if (!mqtt_loop()){
         if( !$clientIsConnected ) die('Проблема при подключении к серверу');
         continue;
     }
    
    echo "Начали опрос\n";
    $infopanelVisual = 0;
    while (true) {

        handler();
        if (!mqtt_loop()) break;
        echo "                   ".($infopanelVisual?"O":"*")."БД\r";
        mqtt_write($MQTT_METEO_TOPICS_WEATHER['WATCHDOG'], time());
        $infopanelVisual = 1-$infopanelVisual;
       
        if (!sleep_my($PAUSE)) break;
    }

    mqtt_close();


    echo "                     Бесконечный цикл\r";
    //sleep(50);
}

