<?php

/* 
 * Получение метеоданных из моего метеоприбора.
 *  и отправка в narodmon
 */
$forceUpdate = 0;
define('CLIENT_ID', "meteo-my-mqtt");
include __DIR__."/inc/mqtt_serv.inc.php";
include __DIR__."/inc/mqtt_topics.php";

$PAUSE = 10*60; //пауза между отправками показаний в narodmon


global $NARODMON_ID;
global $NARODMIN_APIKEY;
global $NARODMON_MYSITE;
global $MQTT_METEO_SENSORS_TOPICS;

$debug = 1; //1;

$MQTT_VALUES = array(
    'temp'=>false, 'hum'=>false, 'press'=>false
);





$SIG_handler = function () {
    mqtt_close();
    die('Останов');
};



function handler(){
    global $NARODMON_ID;
    global $NARODMIN_APIKEY;
    global $NARODMON_MYSITE;
    global $SIMILATE;
    global $debug;
    global $MQTT_METEO_SENSORS_TOPICS;
    global $MQTT_VALUES;

  
        //Время замера
        $t = new DateTime();
        $DTM = $t->format('Y-m-d H:i:s');
        unset($t);
        
        
        $need_send = false;  
        //MAC адрес ESP
        $strsend ="#F4-CF-A2-F0-12-80\n#";
        if( $MQTT_VALUES['temp']  !== false ) {$need_send=true; $strsend .= "T1#".$MQTT_VALUES['temp']."\n#";}
        if( $MQTT_VALUES['hum']   !== false ) {$need_send=true; $strsend .= "H1#".$MQTT_VALUES['hum']."\n#";}
        if( $MQTT_VALUES['press'] !== false ) {$need_send=true; $strsend .= "P1#".$MQTT_VALUES['press']."\n#";}
        $strsend .= "#";
      
        //Проверка, что в narodmon данные отправляли не ранее 5 минут
        //это ограничение в народмон
        $nowEpoch = time();
        if(  ! file_exists ( "/tmp/narodmon-send-time.txt" )  ) file_put_contents("/tmp/narodmon-send-time.txt", $nowEpoch );
        $oldEpoch = intval(  file("/tmp/narodmon-send-time.txt")[0] );
        
        //echo " deltaВремя = ".($nowEpoch - $oldEpoch)."\r\n";
        $need_send = $need_send && ( ($nowEpoch - $oldEpoch) > 5 * 60 );
        
      if($need_send){
            printf("Отправка данных в narodmon ".$strsend." \r\n");
            
        $fp = @fsockopen("tcp://narodmon.ru", 8283, $errno, $errstr);
       if($fp){
         fwrite($fp, $strsend);
         fclose($fp);     
         file_put_contents("/tmp/narodmon-send-time.txt", $nowEpoch );
       }else  echo "ERROR(".$errno."): ".$errstr;
         
         file_put_contents("/tmp/narodmon-send-time.txt", $nowEpoch );
      }
      
      
}

/**
 * Обработчик сообщений от брокера
 * @global Mosquitto\Client $client
 * @global type $panelData
 * @param type $message
 */
function mqttProcessMesssage($message) {
    global $MQTT_METEO_SENSORS_TOPICS;
    global $MQTT_VALUES;
    global $client;
    global $panelData;
    global $forceUpdate;
    
    $value = $message->payload;
    if (strpos($message->topic, 'weather') !== false)
        echo $message->topic, " ", $message->payload, "\n";

    if ($message->topic == 'kv152/pogoda-force-update') {
        echo "Получили сигнал об принудительом обновлении значений " . $message->payload . " \n";
        $forceUpdate = ( (int) $message->payload == 1);
    }
    
    if ($message->topic == $MQTT_METEO_SENSORS_TOPICS['MOTION']) {
       //Погодная странция зафиксировала движение
        echo "Погодная странция зафиксировала движение " . $message->payload . " \n";
    }  
    
    if ($message->topic == $MQTT_METEO_SENSORS_TOPICS['TEMP_VAL']) {
       //Значение температуры на улице
        
        echo "Значение температуры на улице " . $value . " \n";
        $MQTT_VALUES['temp'] = floatval( $value );
    }    
    if ($message->topic == $MQTT_METEO_SENSORS_TOPICS['HUM_VAL']) {
       //Значение влажности на улице
        echo "Значение влажности на улице " . $value . " \n";
         $MQTT_VALUES['hum'] = floatval( $value  );
         //Есть особенность прибора: иногда возвращает 998%
         if( $MQTT_VALUES['hum'] > 100 ) $MQTT_VALUES['hum'] = 99.99;
    }  
    if ($message->topic == $MQTT_METEO_SENSORS_TOPICS['BMP_PRESS_VAL']) {
       //Значение давления на улице
        echo "Значение давления на улице " .$value. " \n";
         $MQTT_VALUES['press'] = floatval( $value );
    }    
    if ($message->topic == $MQTT_METEO_SENSORS_TOPICS['BMP_TEMP_VAL']) {
       //Значение температуры у прибора
        echo "Значение температуры у прибора " . $value . " \n";
    }
    return true;
}



///////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////

$firstTime = true;
echo "Начали\n";
//Бесконечный цикл
while (true) {

    mqtt_connect( "kv152/pogoda/#" );

    if (!mqtt_loop()){
         if( !$clientIsConnected ) die('Проблема при подключении к серверу');
         continue;
     }
    if( $firstTime ){
      mqtt_write($MQTT_METEO_SENSORS_TOPICS['WATCHDOG'], time());
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
        mqtt_write($MQTT_METEO_SENSORS_TOPICS['WATCHDOG'], time());
        $infopanelVisual = 1-$infopanelVisual;
       
        if (!sleep_my($PAUSE)) break;
    }

    mqtt_close();


    echo "                     Бесконечный цикл\r";
    //sleep(50);
}


