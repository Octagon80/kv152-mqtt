#!/usr/bin/php5
<?PHP
//Модуль для работы с tty
include_once(__DIR__ . "/inc/php_serial.class.php");

define('CLIENT_ID', "arduino");

include __DIR__ . "/inc/mqtt_serv.inc.php";


$DEBUG = 1;

$PAUSE = 1; //пауза между запросами

$MQTT_ARDUINO_WATCHDOG = 'sysinfo/watchdog/arduino';

$serial;
$SerialString = "";
$forceUpdate = false;

$USB = isset($argv[1]) ? $USB = $argv[1] : '/dev/ttyACM0';
if ($DEBUG >= 1) {
    echo "Подключение к  $USB";
}

function SerialInit() {
    global $serial, $USB;

    // Let's start the class
    $serial = new phpSerial;

    // First we must specify the device. This works on both linux and windows (if
    // your linux serial device is /dev/ttyS0 for COM1, etc)
    $serial->deviceSet($USB);

    // We can change the baud rate, parity, length, stop bits, flow control
    $serial->confBaudRate(9600);
    $serial->confParity("none");
    $serial->confCharacterLength(8);
    $serial->confStopBits(1);
    $serial->confFlowControl("none");
}

function SerialRead() {
    global $SerialString, $serial;

    $serial->deviceOpen();
//$serial->serialflush();
    $SerialString = $serial->readPort();
    $serial->deviceClose();
    return( $SerialString);
}

function mqttProcessMesssage($message) {
    
}




////////////////////////////////////////////////////////////////////////////////////////////    
////////////////////////////////////////////////////////////////////////////////////////////

SerialInit();
$serial->deviceOpen();





$infopanelVisual = 0;
$weightIndex = 1;
$firstTime = true;
echo "Начали\n";
//Бесконечный цикл
while (true) {

    mqtt_connect( "" );
    if (!mqtt_loop())
        continue;
    if ($firstTime) {
        echo "Указываем, что требуется принудительное обновления значений всем программам\n";
        mqtt_write($MQTT_ARDUINO_WATCHDOG, time());
    }
    $firstTime = false;
    if (!mqtt_loop())
        continue;



    echo "Начали опрос\n";
    while (true) {
        //$SerialString =  SerialRead();
        $SerialString = $serial->readPort();

        if (empty($SerialString))
            continue;

        //$SerialString = rtrim( $SerialString,';' );
        $SerialString = str_replace(";", "", $SerialString);
//if ($DEBUG == 1)      echo " Получили  " . $SerialString . "\n";
       


        $ListSensors = explode("\n", $SerialString);



        foreach ($ListSensors as $Sensor) {

            $ArrParams = explode(',', $Sensor);

            if (count($ArrParams) < 3)
                continue;
            if ($ArrParams[0] == 'weight') {
                //у меня разные параметры имеют одинаковое имя. Для mqtt это неприемлемо
                $ArrParams[0] = $ArrParams[0] . $weightIndex;
                $weightIndex++;
                if ($weightIndex > 2)
                    $weightIndex = 1;
            }
            $now = date('Y-m-d H:i:s');
            mqtt_write("arduino/" . $ArrParams[0], $ArrParams[2]);
            if ($DEBUG == 1)
                echo $now . "  " . $ArrParams[0] . " " . $ArrParams[2] . "\n";
        }

        if (!mqtt_loop())
            break;
        echo "                   " . ($infopanelVisual ? "O" : "*") . "Arduino\r";
        mqtt_write($MQTT_ARDUINO_WATCHDOG, time());
        $infopanelVisual = 1 - $infopanelVisual;

        //if (!sleep_my($PAUSE))       break;
    }


    mqtt_close();


    echo "                     Бесконечный цикл\r";
    sleep($PAUSE);
}
?>
