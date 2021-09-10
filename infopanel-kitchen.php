<?php

/*
 * Информационная панель на кухне.
 * Тут же датчик движения
 */



define('CLIENT_ID', "infopanel-kitchen");
include __DIR__."/inc/mqtt_serv.inc.php";
include __DIR__."/inc/mqtt_topics.php";
$PAUSE = 5; //пауза между запросами



//Что отображаем: 1 - время, 2 - температуру
$mode = 1;

$wildValue = array('value' => 0, 'dtm' => false, 'source' => '');

$lineOld = '';

/**
 * Данные для панели
 */
$panelData = array(
    'sys' => array(
        'dtm' => false
    ),

    'press' => $wildValue,
    'hum' => $wildValue,
    'temp' => $wildValue
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
    global $mode;
    global $MQTT_INFOPANEL_KITCHEN;

    //Системное время
    $sysDtm = new DateTime();
    if ($panelData['sys']['dtm'])
        $sysDtm = $panelData['sys']['dtm'];

    //Строка1. Позиция 1. Текущее время
    if ($panelData['sys']['dtm'])
        $dtm = $panelData['sys']['dtm']->format('Hi'); //->format('Y-m-d H:i:s');
    else
        $dtm = $sysDtm->format('Hi'); //->format('Y-m-d H:i:s');



        
//Строка1. Позиция 2.  Температура
    $temp = 'err';

    //Если значение было обновлено недавно....
    if (diffTimeIsOk($panelData['temp']['dtm'], $sysDtm)) {
        $temp = getDataSource($panelData['temp']['source']) . $panelData['temp']['value'];
        //для старого значений подставим флаг ошибки
    } else {
        $temp = "!" . $panelData['temp']['value'];
    }





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





    //Строка2. Позиция 4.  %занятости провессора + количество подключенных пользователей

    $line = '1'; 
    if( $mode == 1 ) $line .= $dtm;
    if( $mode == 2 ) $line .= $temp;
    if( $mode == 3 ) $line .= $press;
    $line = str_pad($line, 5, " ", STR_PAD_RIGHT);
    
    if ($line != $lineOld) {
        echo "Строка '" . $line . "'          \n";
        mqtt_write( $MQTT_INFOPANEL_KITCHEN['INFOPANEL_VAL'] , $line);
    }
            
    $mode = $mode + 1;
    if( $mode > 3 ) $mode = 1;


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
     //echo "Указываем, что требуется принудительное обновления значений всем программам\n";
     //mqtt_write('kv152/pogoda-force-update', '1');
      mqtt_write($MQTT_INFOPANEL_KITCHEN['WATCHDOG'], time());
    }
    $firstTime = false;
     if (!mqtt_loop()) continue;
    
    echo "Начали опрос\n";
    while (true) {
        //формируем строку для панели
        updateInfoPanelString();
        if (!mqtt_loop()) break;
        echo "                   ".($infopanelVisual?"O":"*")."Инфопанель\r";
        mqtt_write($MQTT_INFOPANEL_KITCHEN['WATCHDOG'], time());
        $infopanelVisual = 1-$infopanelVisual;
       
        if (!sleep_my($PAUSE)) break;
    }

    mqtt_close();


    echo "                     Бесконечный цикл\r";
    //sleep(50);
}
