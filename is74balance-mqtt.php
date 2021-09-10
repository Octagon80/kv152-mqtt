<?php

/*
 * Система 152. 
 * Получение данные о балансе интернет
 */


define('CLIENT_ID', "is74");
include __DIR__ . "/inc/mqtt_serv.inc.php";
include __DIR__ . "/inc/mqtt_topics.php";
include __DIR__ . "/inc/get_url_page.php";
include __DIR__."/inc/mqtt_topics.php";
$PAUSE = 900; //пауза между запросами

$MQTT_TOPIC = "kv152/is74/#";


$mqttData = array(
    'balance' => 0,
    'update_balance' => false
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
    global $MQTT_IS74;
    global $forceUpdate;

    if ($message->topic == $MQTT_IS74['CMD_UPDATE_BALANCE'])
        $mqttData['update_balance'] = str2int($message->payload) > 0;

    if ($message->topic == $MQTT_IS74['FORCE_UPDATE']){
        $forceUpdate = ( (int) $message->payload == 1);
         echo "Получили сигнал об принудительом обновлении значений " . $message->payload . " \n";
        
    }    

    
    return( true );
}


function updateBalance() {
    global $MQTT_IS74;
    //Время замера
    $t = new DateTime();
    $DTM = $t->format('Y-m-d H:i:s');
    unset($t);

    $url = "https://ooointersvyaz2.lk.is74.ru/balance";

    $dom = new DOMDocument();

    //load the html
    // set error level
    $internalErrors = libxml_use_internal_errors(true);
    $html = $dom->loadHTML(getPage($url));
// Restore error level
    libxml_use_internal_errors($internalErrors);

    $dom->preserveWhiteSpace = false;

    //Получить баланс по classname
    $classname = "summ"; //"current-balance-num";
    $finder = new DomXPath($dom);
    $node = $finder->query("//div[@class='$classname']")->item(0);
    $BalanseInfo = $node->textContent;


    $BalanseStr = '0.0';
    $lst = explode(" ", $BalanseInfo);
    foreach ($lst as $item) {
        if (empty($item))
            continue;
        preg_match_all('/([0-9]*)(\,)([0-9]*)/m', $item, $matches, PREG_SET_ORDER, 0);
        if (isset($matches[0][0])) {
            $BalanseStr = $matches[0][0];
            //echo "Нашли баланс ".$BalanseStr." \r\n";
        }
    }

    echo "Определили баланс на счету IS74 ".$BalanseStr." руб. \r\n";

    mqtt_write($MQTT_IS74['BALANCE_VAL'], $BalanseStr);
    mqtt_write($MQTT_IS74['BALANCE_DTM'], $DTM);

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

    mqtt_connect($MQTT_TOPIC);
    if (!mqtt_loop()) {
        if (!$clientIsConnected)
            die('Проблема при подключении к серверу');
        continue;
    }
    if ($firstTime)
        mqtt_write($MQTT_IS74['WATCHDOG'], time());
    $firstTime = false;
    if (!mqtt_loop()) {
        if (!$clientIsConnected)
            die('Проблема при подключении к серверу');
        continue;
    }

    echo "Начали опрос\n";
    while (true) {
        //принудительно обновляем баланс (но редко)
        
        $mqttData['update_balance'] = true;
        
        //формируем строку для панели
        if ($mqttData['update_balance']) {
            updateBalance();
            $mqttData['update_balance'] = false;
        }
        if (!mqtt_loop())
            break;
        echo "                   " . ($infopanelVisual ? "O" : "*") . "is74 баланс\r";
        mqtt_write($MQTT_IS74['WATCHDOG'], time());
        $infopanelVisual = 1 - $infopanelVisual;

        
        //Принудительное обноление
    if ( $forceUpdate > 0 ){
        $mqttData['update_balance'] = true;
        $forceUpdate = 0;
    }    
        
        
        if (!sleep_my($PAUSE))
            break;
    }

    mqtt_close();


    echo "                     Бесконечный цикл\r";
    //sleep(50);
}
