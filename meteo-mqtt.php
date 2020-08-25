#!/usr/bin/php
<?php
/**
 * Новая версия для MQTT
 *  Получить текущую температуру, давление, влажность, ветер...
 * и отправить параметры в MQTT сервер
 */
$forceUpdate = 0;
define('CLIENT_ID', "meteo-mqtt");
include __DIR__."/inc/mqtt_serv.inc.php";
$PAUSE = 60; //пауза между запросами
$MQTT_INFOPANEL_WATCHDOG = 'sysinfo/watchdog/infopanel';



global $NARODMON_ID;
global $NARODMIN_APIKEY;
global $NARODMON_MYSITE;

$SIMILATE = false;



include(__DIR__ . "/inc/get-kp-index.php");
include(__DIR__ . "/inc/yandex.pogoda.php");
$debug = 5; //1;

$MQTT_METEO_YANDEX_WATCHDOG = 'sysinfo/watchdog/meteo_yandex';
$MQTT_METEO_NAROD_WATCHDOG = 'sysinfo/watchdog/meteo_narod';

$PAUSE = 5 * 60; //мин. пауза между запросами


$SIG_handler = function () {
    mqtt_close();
    die('Останов');
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
    global $forceUpdate;
    if (strpos($message->topic, 'weather') !== false)
        echo $message->topic, " ", $message->payload, "\n";

    if ($message->topic == 'kv152/pogoda-force-update') {
        echo "Получили сигнал об принудительом обновлении значений " . $message->payload . " \n";
        $forceUpdate = ( (int) $message->payload == 1);
    }
}



function handler(){
    global $NARODMON_ID;
    global $NARODMIN_APIKEY;
    global $NARODMON_MYSITE;
    global $SIMILATE;
    global $debug;
    global $MQTT_METEO_NAROD_WATCHDOG;

  
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
            //Получим погоду из Яндекс
            $yandexPogoda = WetherYandex($debug);
            //температура
            mqtt_write('weather/yandex/temp/dtm', $DTM);
            mqtt_write('weather/yandex/temp/value', $yandexPogoda['temperature']);
            //давление
            mqtt_write('weather/yandex/press/value', $yandexPogoda['pressure']);
            mqtt_write('weather/yandex/press/dtm', $DTM);
            //Сила ветра, ед.изм. м/c
            mqtt_write('weather/yandex/wind/value', $yandexPogoda['wind_speed']);
            mqtt_write('weather/yandex/wind/dtm', $DTM);
            //Направление ветра
            mqtt_write('weather/yandex/winddir/value', $yandexPogoda['wind_direction']);
            mqtt_write('weather/yandex/winddir/dtm', $DTM);
            //время суток
            mqtt_write('weather/yandex/day_name/value', $yandexPogoda['day_name']);
            mqtt_write('weather/yandex/day_name/dtm', $DTM);
            //описание погоды
            mqtt_write('weather/yandex/type/value', $yandexPogoda['weather_type']);
            mqtt_write('weather/yandex/type/dtm', $DTM);
            //описание погоды - англ
            mqtt_write('weather/yandex/code/value', $yandexPogoda['weather_code']);
            mqtt_write('weather/yandex/code/dtm', $DTM);

            mqtt_write($MQTT_METEO_YANDEX_WATCHDOG, time());
            //Получим погоду из Народного мониторинга
            $uuid = md5($NARODMON_MYSITE);
            
            //2 запрос данных
            $request = array('cmd' => 'sensorsOnDevice',
                'id' => $NARODMON_ID, //идентификатор датчика
                'uuid' => $uuid,
                'api_key' => $NARODMIN_APIKEY,
                'lang' => 'ru');
            if ($ch = curl_init('http://narodmon.ru/api')) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_USERAGENT, $NARODMON_MYSITE);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));
                $reply = curl_exec($ch);
                curl_close($ch);
            }

            if ($ch === false) {
                echo "ОШИБКА подключения к Narodmon\r\n";
            }
        }
        if ($debug > 3) {
            echo "Ответ от Narodmon:";
            //echo implode(" ", $reply);
            echo "-------------------------\r\n";
            echo print_r($reply, true);
            echo "\r\n";
        }



        $OtherSensor = array('p' => -32768, 'h' => -32768, 't' => -32768, 'wind' => -32768, 'winddir' => -32768);


        if (!$ch or ! $reply or empty($reply)) {
            //exit('Connect error');
        } else {
            $data = json_decode($reply, true);
            if ((count($data) == 0) || ( isset($data['errno']) )) {
                if ($debug > 0)
                    echo "От Народмон получены ошибочные данные " . $reply . " \n";


            } else {

                if ($debug > 3) {
                    echo "От Narodmon получены данные ";
                    echo print_r($data, true);
                    echo "\r\n";
                }

                if (!$data or ! is_array($data)) {
                    if ($debug > 2)
                        echo('<B>Wrong data</B><BR>');
                } else {
                    mqtt_write($MQTT_METEO_NAROD_WATCHDOG, time());
                    foreach ($data['sensors'] as $S) {
                        if ($S['type'] == 3)
                            $OtherSensor['p'] = $S['value'];
                        if ($S['type'] == 2)
                            $OtherSensor['h'] = $S['value'];
                        if ($S['type'] == 1)
                            $OtherSensor['t'] = $S['value'];
                        if ($S['type'] == 4)
                            $OtherSensor['wind'] = $S['value'];
                        if ($S['type'] == 5)
                            $OtherSensor['winddir'] = $S['value'];
                    }

                    if ($debug > 2) {
                        echo "Данные по сенсорам";
                        echo print_r($OtherSensor, true);
                        echo "\r\n";
                    }
                }
            }
        }





        if ($OtherSensor['t'] > -32768) {
            mqtt_write('weather/temp/value', $OtherSensor['t']);
            mqtt_write('weather/temp/source', 'народмон');
            mqtt_write('weather/temp/dtm', $DTM);
        } else {
            if ($debug > 2)
                echo "Показания по темпераутре нородмон недостоверные, не записывем\n";

            //температура от яндекс
            if (is_numeric($yandexPogoda['temperature'])) {
                if ($debug > 2)
                    echo "Берем показания по темпераутре от яндекс\n";
                mqtt_write('weather/temp/value', $yandexPogoda['temperature']);
                mqtt_write('weather/temp/source', 'яндекс');
                mqtt_write('weather/temp/dtm', $DTM);
            }
        }



        if ($OtherSensor['h'] > -1) {
            mqtt_write('weather/hum/value', $OtherSensor['h']);
            mqtt_write('weather/hum/source', 'народмон');
            mqtt_write('weather/hum/dtm', $DTM);
        } else {
            if ($debug > 2)
                echo "Показания по влажности народмон нулевые, не записывем\n";
        }

        if ($OtherSensor['p'] > -1) {
            mqtt_write('weather/press/value', $OtherSensor['p']);
            mqtt_write('weather/press/source', 'народмон');
            mqtt_write('weather/press/dtm', $DTM);
        } else {
            if ($debug > 2)
                echo "Показания по давлению народмон нулевые, не записывем\n";
            if (is_numeric($yandexPogoda['pressure'])) {
                if ($debug > 2)
                    echo "Берем показания по давлению от яндекс\n";
                mqtt_write('weather/press/value', $yandexPogoda['pressure']);
                mqtt_write('weather/press/source', 'яндекс');
                mqtt_write('weather/press/dtm', $DTM);
            }
        }



//Сила ветра, ед.изм. м/c
        if ($OtherSensor['wind'] > -1) {
            mqtt_write('weather/wind/value', $OtherSensor['wind']);
            mqtt_write('weather/wind/source', 'народмон');
            mqtt_write('weather/wind/dtm', $DTM);
        } else {
            if ($debug > 2)
                echo "Показания по сила ветра народмон, не записывем\n";
            if (is_numeric($yandexPogoda['wind_speed'])) {
                if ($debug > 2)
                    echo "Берем показания по сила ветра от яндекс\n";
                mqtt_write('weather/wind/value', $yandexPogoda['wind_speed']);
                mqtt_write('weather/wind/source', 'яндекс');
                mqtt_write('weather/wind/dtm', $DTM);
            }
        }


//направление ветра, ед.изм. град.
        if ($OtherSensor['winddir'] > -1) {
            mqtt_write('weather/winddir/value', $OtherSensor['winddir']);
            mqtt_write('weather/winddir/source', 'народмон');
            mqtt_write('weather/winddir/dtm', $DTM);
        } else {
            if ($debug > 2)
                echo "Показания по направлению ветра, не записывем\n";
        }

        $kpindex = getKpIndex();
        mqtt_write('weather/kpindex/value', $kpindex[0]);
        mqtt_write('weather/kpindex/source', 'noaa.gov');        
        mqtt_write('weather/kpindex/dtm', $DTM);

  
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

        handler();
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

