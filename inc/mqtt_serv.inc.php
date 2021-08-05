<?php
$USE_SSL = true;

include_once __DIR__.'/../cfg/userpass.php';

global $NEED_LOG;
if( 
   (isset($NEED_LOG) && $NEED_LOG)
  || !isset($NEED_LOG)
 ){
  include "/var/www/class/class.log.php";
  //см first include_once __DIR__."/class.log.php";
} 

define('BROKER', $HOST);
if( $USE_SSL ) define('PORT', 8883);
else define('PORT', 1883);
 
$client = false;
$forceUpdate = 0;
$clientIsConnected = false;

$mqttConnectFirstTime = true;
$mqttSubscribeAddress= "#";

    $logmqtt = new Logging();
    $logmqtt->lfile("/tmp/".CLIENT_ID.".log");
    if( $logmqtt !== false ) $logmqtt->lwrite(CLIENT_ID.". Начало работы.");

/**
 * 
 * @global boolean $client
 * @global boolean $clientIsConnected
 * @global Logging $logmqtt
 */   
function mqtt_reconnect() {
    global $client;
    global $clientIsConnected;
    global $logmqtt;
    echo "Переподключение\n";
    mqtt_close();
    
    
    $client->connect(BROKER, PORT, 60);
    $clientIsConnected = true;
}





/**
 * 
 * @global boolean $client
 * @global string $mqttSubscribeAddress
 * @global Logging $logmqtt
 * @param type $code
 * @param type $message
 * @return type
 */
function mqtt_connectCB( $code, $message) {
    global $client;
    global $mqttSubscribeAddress;
    global $logmqtt;

        if ($code == 0) {
            echo " подключились к брокеру успешно '" . $message . "' и подписались на '$mqttSubscribeAddress' \n";
            if( !empty($mqttSubscribeAddress) )
            $client->subscribe($mqttSubscribeAddress, 0);
            $clientIsConnected = true;
        } else {
            echo " Ошибка подключения к брокеру '" . $message . "'\n";
            if( $logmqttlog !== false ) $logmqtt->lwrite("Ошибка подключения к брокеру '" . $message . "'");
            die('');
        }
    
    return( true );
}




/**
 * Запись параметра в MQTT сервер
 * @param type $topic
 * @param type $value
 */
function mqtt_connect( $subscribeAddress ) {
    global $client;
    global $clientIsConnected;
    global $mqttConnectFirstTime;
    global $mqttSubscribeAddress;
    global $logmqtt;
    global $USE_SSL;
    global $LOGIN;
    global $PASSWORD;

    
    $mqttSubscribeAddress = $subscribeAddress;
    
   if( !$mqttConnectFirstTime ) {
       mqtt_reconnect();
       return( true );
   }   
    //Делаем всегда коннект $mqttConnectFirstTime = false;
    
   echo "Подключаемся как '".CLIENT_ID."'...";
    $client = new Mosquitto\Client(CLIENT_ID);

    //Создание обработчика подключения к брокеру
    $client->onConnect('mqtt_connectCB');



    //Создание обработчика сообщени от брокера
    $client->onMessage( 'mqttProcessMesssage' );


if( $USE_SSL ){
    $client->setTlsCertificates('cert/ca.crt', 'cert/mosquitto_client.crt', 'cert/mosquitto_client.key', null);
    $client->setTlsOptions(Mosquitto\Client::SSL_VERIFY_PEER  ,"tlsv1.2",null);
}
    $client->setCredentials($LOGIN,$PASSWORD );
    $client->connect(BROKER, PORT, 60);
    //$client->onPublish('mqtt_publish');
    return( true );
}





/**
 * 
 * @global boolean $client
 * @global Logging $logmqtt
 */
function mqtt_publish() {
    global $client;
    global $logmqtt;
    echo "Сообщение опубликовано \n";
}



/**
 * 
 * @global boolean $client
 * @global Logging $logmqtt
 * @return type
 */
function mqtt_loop() {
    global $client;
    global $logmqtt;
    try {
        $client->loop(2000);//число - сетевой таймаут
    } catch (Exception $e) {
        echo 'Исключение в mqtt_loop: ', $e->getMessage(), "\n";
        if( $logmqtt !== false ) $logmqtt->lwrite("Исключение в mqtt_loop '" . $e->getMessage() . "'");
        if( strpos($e->getMessage(), "client is not currently connected") !== false ||
            strpos($e->getMessage(), "connection was lost") !== false 
          ){
            echo "...потеря связи\n";
            $clientIsConnected = false;
        }
        return(false);
    }
    return(true);
}


/**
 * Новая пауза с учетом MQTT
 * При долгой паузе, без вызова loop, mqtt сервер рвет связь.
 * В новой паузе такого не будет
 * @param type $pause
 * @return type
 */
function sleep_my($pause) {
    global $forceUpdate;
    for ($i = 1; $i <= $pause; $i++) {
        if (!mqtt_loop())
            return( false );
        echo ($pause - $i) . " (forceUpdate=$forceUpdate)\r";
        sleep(1);
        if ($forceUpdate) {
            $forceUpdate = 0;
            break;
        }
    }
    return(true);
}




/** Версия 2019-12-05
 * Запись параметра в MQTT сервер
 * @param type $topic
 * @param type $value
 */
function mqtt_write($topic, $value) {
    global $client;
    global $debug;
    global $logmqtt;
    if ($debug > 0)
        echo "function mqtt_write('".$topic."', '".$value."')\n";
    if (!mqtt_loop())   return(false);
    try {
        $client->publish($topic, $value, 0, false);
    } catch (Exception $e) {
        echo 'Исключение в mqtt_write: ', $e->getMessage(), "\n";
        if( $logmqtt !== false ) $logmqtt->lwrite("Исключение в mqtt_write '" . $e->getMessage() . "'");
        if( strpos($e->getMessage(), "client is not currently connected") !== false ||
            strpos($e->getMessage(), "connection was lost") !== false 
          ){
            echo "...потеря связи\n";
            $clientIsConnected = false;
        }
        return(false);
    }    
   if (!mqtt_loop())return(false); 


        
//sleep_my(2);
    return( true );
}


/**
 * 
 * @global boolean $client
 * @global boolean $clientIsConnected
 * @global Logging $logmqtt
 * @return type
 */
function mqtt_close() {
    global $client;
    global $clientIsConnected;
    global $logmqtt;
    
    if ($clientIsConnected)
    try {
        $client->disconnect();
    } catch (Exception $e) {
        echo 'Исключение в mqtt_close: ', $e->getMessage(), "\n";
        if( $logmqtt !== false ) $logmqtt->lwrite("Исключение в mqtt_close '" . $e->getMessage() . "'");
        return(false);
    }    
    $clientIsConnected = false;
    unset($client);
    
    $logmqtt->lclose();
}

