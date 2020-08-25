<?php

/*
 * Система 152. Информационная панель.
 * Данная программа может работать где угодно.
 * Данная программа подписывается к брокеру MQTT для
 * получения нужных параметров для отображения на панеле,
 * формирует строку для отображения на экране и записывает в
 * брокер MQTT.
 * 
 * Где-то работает программа, которая будет получать здесь сформированные
 * строки и отображать на панеле.
 */


define('CLIENT_ID', "infopanel-get");
include __DIR__."/inc/mqtt_serv.inc.php";
$PAUSE = 60; //пауза между запросами
$MQTT_INFOPANEL_WATCHDOG = 'sysinfo/watchdog/infopanel';




$wildValue = array('value' => 0, 'dtm' => false, 'source' => '');

$lineOld = '';

/**
 * Данные для панели
 */
$panelData = array(
    'sys' => array(
        'dtm' => false
    ),
    'kpindex' => $wildValue,
    'winddir' => $wildValue,
    'wind' => $wildValue,
    'press' => $wildValue,
    'hum' => $wildValue,
    'temp' => $wildValue,
    'weather_type' => $wildValue
);



/**
 * Сравнить две даты. 
 * Если разнице между ними большая, вернуть false
 * @param time_obj $dtm1
 * @param time_obj $dtm2
 */
function diffTimeIsOk($dtm1, $dtm2) {
    if (!$dtm1 || !$dtm2)
        return( false );

    $diff = abs($dtm1->format('U') - $dtm2->format('U'));
    return( $diff < 20 * 60);
}

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

function getDataSource($source) {
    $source = '';
    if (strlen($source) > 0 && strcasecmp($source, 'народмон') === false)
        $source = mb_substr($source, 0, 1);
    return( $source );
}

/**
 * По сформированным данных создается строка для
 * отображения на информационной панеле
 * @global array $panelData сформированные данные для панели
 * @global string $lineOld ранее созданная строка для панели
 * @global Mosquitto\Client $client
 */
function updateInfoPanelString() {
    global $panelData;
    global $lineOld;
    global $client;

    //Системное время
    $sysDtm = new DateTime();
    if ($panelData['sys']['dtm'])
        $sysDtm = $panelData['sys']['dtm'];

    //Строка1. Позиция 1. Текущее время
    if ($panelData['sys']['dtm'])
        $dtm = $panelData['sys']['dtm']->format('H:i'); //->format('Y-m-d H:i:s');
    else
        $dtm = $sysDtm->format('H:i'); //->format('Y-m-d H:i:s');



        
//Строка1. Позиция 2.  Температура
    $temp = 'err';

    //Если значение было обновлено недавно....
    if (diffTimeIsOk($panelData['temp']['dtm'], $sysDtm)) {
        $temp = getDataSource($panelData['temp']['source']) . $panelData['temp']['value'];
        //для старого значений подставим флаг ошибки
    } else {
        $temp = "!" . $panelData['temp']['value'];
    }


    //Строка1. Позиция 3.  Скорость ветра
    $wind = 'err';

    //Если значение было обновлено недавно....
    if (diffTimeIsOk($panelData['wind']['dtm'], $sysDtm)) {
        $wind = getDataSource($panelData['wind']['source']) . $panelData['wind']['value'];
        //для старого значений подставим флаг ошибки
    } else {
        $wind = "!" . $panelData['wind']['value'];
    }


    //Строка1. Позиция 4.  Магнитная буря. Показывать только при налиции бури
    $kpindex = '';

    //Если значение было обновлено недавно....
    if (diffTimeIsOk($panelData['kpindex']['dtm'], $sysDtm))
        if ($panelData['kpindex']['value'] > 3)
        //с пробелом, чтобы визуально чсило отделялось от предыдущей ифно
            $kpindex = " " . getDataSource($panelData['kpindex']['source']) . $panelData['kpindex']['value'];



    //Строка2. Позиция 1.  Учеба в школе. Писать НЕТ. Если есть соответсвующее сообщение
    $School = '';
    //Если есть выводимая информация, добавить пробел в конец
    //Строка2. Позиция 2.  Давление
    $press = 'err';

    //Если значение было обновлено недавно....
    if (diffTimeIsOk($panelData['press']['dtm'], $sysDtm)) {

        $press = getDataSource($panelData['press']['source']) . $panelData['press']['value'];
        //для старого значений подставим флаг ошибки
    } else
        $press = "!" . $panelData['press']['value'];



    //Строка2. Позиция 3. Строка описание погоды
    $weather_type = '';

    //Если значение было обновлено недавно....
    if (diffTimeIsOk($panelData['weather_type']['dtm'], $sysDtm))
    //if ( !empty( $panelData['weather_type']['value'] ) )
    //с пробелом, чтобы визуально чсило отделялось от предыдущей ифно
        $weather_type = " " . $panelData['weather_type']['value'];
    //Упрощаем сообщение о типе погоды: важно знать, есть ли дождь
    if (strpos($weather_type, "rain") === false) {
        $weather_type = "";
    } else {
        $weather_type = ' 3OHT';
    }



    //Строка2. Позиция 4.  %занятости провессора + количество подключенных пользователей

    $line = $dtm . " " . $temp . " " . $wind . "m/c" . $kpindex . "|" . $School . $press . $weather_type;
    if ($line != $lineOld) {
        echo "Строка " . $line . "          \n";
        //$client->publish('kv152/infopanel', $line);
        mqtt_write('kv152/infopanel', $line);
    }



    $lineOld = $line;
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
    if (strpos($message->topic, 'weather') !== false)
        echo $message->topic, " ", $message->payload, "\n";

    if ($message->topic == 'sys/dtm')
        $panelData['sys']['dtm'] = str2dtm($message->payload);


    if ($message->topic == 'weather/winddir/value')
        $panelData['winddir']['value'] = str2int($message->payload);
    if ($message->topic == 'weather/winddir/dtm')
        $panelData['winddir']['dtm'] = str2dtm($message->payload);

    if ($message->topic == 'weather/wind/value')
        $panelData['wind']['value'] = str2int($message->payload);
    if ($message->topic == 'weather/wind/source')
        $panelData['wind']['source'] = $message->payload;
    if ($message->topic == 'weather/wind/dtm')
        $panelData['wind']['dtm'] = str2dtm($message->payload);

    if ($message->topic == 'weather/press/value')
        $panelData['press']['value'] = str2int($message->payload);
       
    if ($message->topic == 'weather/press/source')
        $panelData['press']['source'] = $message->payload;
    if ($message->topic == 'weather/press/dtm')
        $panelData['press']['dtm'] = str2dtm($message->payload);

    if ($message->topic == 'weather/hum/value')
        $panelData['hum']['value'] = str2int($message->payload);
    if ($message->topic == 'weather/hum/source')
        $panelData['hum']['source'] = $message->payload;
    if ($message->topic == 'weather/hum/dtm')
        $panelData['hum']['dtm'] = str2dtm($message->payload);

    if ($message->topic == 'weather/temp/value')
        $panelData['temp']['value'] = str2int($message->payload);
    if ($message->topic == 'weather/temp/source')
        $panelData['temp']['source'] = $message->payload;
    if ($message->topic == 'weather/temp/dtm')
        $panelData['temp']['dtm'] = str2dtm($message->payload);

    if ($message->topic == 'weather/kpindex/value')
        $panelData['kpindex']['value'] = str2int($message->payload);
    if ($message->topic == 'weather/kpindex/source')
        $panelData['kpindex']['source'] = $message->payload;
    if ($message->topic == 'weather/kpindex/dtm')
        $panelData['kpindex']['dtm'] = str2dtm($message->payload);


    /*
     * 
      clear — ясно.
      partly-cloudy — малооблачно.
      cloudy — облачно с прояснениями.
      overcast — пасмурно.
      partly-cloudy-and-light-rain — небольшой дождь.
      partly-cloudy-and-rain — дождь.
      overcast-and-rain — сильный дождь.
      overcast-thunderstorms-with-rain — сильный дождь, гроза.
      cloudy-and-light-rain — небольшой дождь.
      overcast-and-light-rain — небольшой дождь.
      cloudy-and-rain — дождь.
      overcast-and-wet-snow — дождь со снегом.
      partly-cloudy-and-light-snow — небольшой снег.
      partly-cloudy-and-snow — снег.
      overcast-and-snow — снегопад.
      cloudy-and-light-snow — небольшой снег.
      overcast-and-light-snow — небольшой снег.
      cloudy-and-snow — снег.
     */
    //русский if ($message->topic == 'weather/yandex/type/value')
    if ($message->topic == 'weather/yandex/code/value') //на англ.
        $panelData['weather_type']['value'] = $message->payload;
    if ($message->topic == 'weather/yandex/type/dtm')
        $panelData['weather_type']['dtm'] = str2dtm($message->payload);
    

     
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
        //формируем строку для панели
        updateInfoPanelString();
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
