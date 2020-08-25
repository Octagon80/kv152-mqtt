<?php

/*
 * Система 152. 
 * Получение данные о балансе интернет
 */


define('CLIENT_ID', "is74");
include __DIR__."/inc/mqtt_serv.inc.php";
$PAUSE = 560; //пауза между запросами
$MQTT_INFOPANEL_WATCHDOG = 'sysinfo/watchdog/is74';
$MQTT_TOPIC = "kv152/is74/#";


$mqttData = array(
    'balance'=>0,
    'update_balance'=>false
);

/**
 * Конвертирование строки в целое
 * @param srting $str
 * @return int
 */
function str2int($str) {
    return( (int) $str );
}


/**
 * Обработчик сообщений от брокера
 * @global Mosquitto\Client $client
 * @global type $mqttData
 * @param type $message
 */
function mqttProcessMesssage($message) {
    global $client;
    global $mqttData;

    if ($message->topic == 'kv152/is74/update_balance')
        $mqttData['update_balance'] = str2int($message->payload) > 0 ;
     
     return( true );
}



 function updateBalance(){
     echo "Узнать баланс интернет";
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

    mqtt_connect( $MQTT_TOPIC );
    if (!mqtt_loop()){
         if( !$clientIsConnected ) die('Проблема при подключении к серверу');
         continue;
     }
    if( $firstTime )  mqtt_write($MQTT_INFOPANEL_WATCHDOG, time());
    $firstTime = false;
     if (!mqtt_loop()){
         if( !$clientIsConnected ) die('Проблема при подключении к серверу');
         continue;
     }
    
    echo "Начали опрос\n";
    while (true) {
        //формируем строку для панели
        if( $mqttData['update_balance'] ){
            updateBalance();
            $mqttData['update_balance'] = false;
        }   
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
