<?php

define( "FORCE_UPDATE",'kv152/pogoda-force-update');

$MQTT_METEO_SENSORS_TOPICS = array(
'WATCHDOG'    =>"sysinfo/watchdog/meteo_my_mqtt",    
'METEO_MY_WATCHDOG'    =>"kv152/pogoda/watchdog",
'MOTION'         =>"kv152/pogoda/motion",
'TEMP_VAL'       =>"kv152/pogoda/temp/value",
'TEMP_TIME'      =>"kv152/pogoda/temp/time",
'HUM_VAL'        =>"kv152/pogoda/hum/value",
'HUM_TIME'       =>"kv152/pogoda/hum/time",
'BMP_PRESS_VAL'  =>"kv152/pogoda/press/value",
'BMP_PRESS_TIME' =>"kv152/pogoda/press/time",
'BMP_TEMP_VAL'   =>"kv152/pogoda/temp2/value", /*температура внутри*/
'BMP_TEMP_TIME'  =>"kv152/pogoda/temp2/time",
'IN'             =>"kv152/pogoda/value"
);


/**
 * Погодные показания, используемые в системе кв152.
 * Для каждого параметра указывается значение, источник данных и время получения
 */
$MQTT_METEO_TOPICS_WEATHER = array(
    
'WATCHDOG'         => 'sysinfo/watchdog/infopanel',
'NAROD_WATCHDOG'   => 'sysinfo/watchdog/meteo_narod',

    
//Температура улицы для системы 152, включая указания источника данных и время обновления
'TEMP_VAL'  =>     'weather/temp/value',
'TEMP_SRC'  =>     'weather/temp/source',
'TEMP_DTM'  =>     'weather/temp/dtm',
//Влажность улицы для системы 152, включая указания источника данных и время обновления
'HUM_VAL'  =>      'weather/hum/value',
'HUM_SRC'  =>      'weather/hum/source',
'HUM_DTM'  =>      'weather/hum/dtm',
//Давление улицы для системы 152, включая указания источника данных и время обновления
'PRESS_VAL'  =>    'weather/press/value',
'PRESS_SRC'  =>    'weather/press/source',
'PRESS_DTM'  =>    'weather/press/dtm',
//Сила ветра улицы для системы 152, включая указания источника данных и время обновления
'WIND_VAL'  =>     'weather/wind/value',
'WIND_SRC'  =>     'weather/wind/source',
'WIND_DTM'  =>     'weather/wind/dtm',
//Напрвление ветра улицы для системы 152, включая указания источника данных и время обновления
'WINDDIR_VAL'  =>  'weather/winddir/value',
'WINDDIR_SRC'  =>  'weather/winddir/source',
'WINDDIR_DTM'  =>  'weather/winddir/dtm',
//Балл геомагнитной активности для системы 152, включая указания источника данных и время обновления
'KPINDEX_VAL'  =>  'weather/kpindex/value',
'KPINDEX_SRC'  =>  'weather/kpindex/source',
'KPINDEX_DTM'  =>  'weather/kpindex/dtm'
);


/**
 * Параметры температуры, полученные из yandex
 */
$MQTT_METEO_TOPICS_YANDEX = array(
'WATCHDOG'  => 'sysinfo/watchdog/meteo_yandex',
    //температура
'TEMP_DTM' => 'weather/yandex/temp/dtm', 
'TEMP_VAL' =>'weather/yandex/temp/value', 
            //давление
'PRESS_VAL' => 'weather/yandex/press/value',
'PRESS_DTM' =>'weather/yandex/press/dtm',
            //Сила ветра, ед.изм. м/c
'WINDSPEED_VAL' => 'weather/yandex/wind/value',
'WINDSPEED_DTM' => 'weather/yandex/wind/dtm', 
            //Направление ветра
'WINDDIR_VAL' => 'weather/yandex/winddir/value',
'WINDDIR_DTM' => 'weather/yandex/winddir/dtm',
            //время суток
'DAYNAME_VAL' => 'weather/yandex/day_name/value',
'DAYNAME_DTM' => 'weather/yandex/day_name/dtm',
            //описание погоды
'TYPE_VAL' => 'weather/yandex/type/value',
'TYPE_DTM' => 'weather/yandex/type/dtm', 
            //описание погоды - англ
'CODE_VAL' => 'weather/yandex/code/value',
'CODE_DTM' => 'weather/yandex/code/dtm'
          
);
        
        
//Баланс на Интерсвязи
$MQTT_IS74 = array( 
    'CMD_UPDATE_BALANCE'  => 'kv152/is74/update_balance',
    'WATCHDOG' => 'sysinfo/watchdog/is74',
    'BALANCE_VAL' => 'kv152/is74/balance_val',
    'BALANCE_DTM' => 'kv152/is74/balance_dtm',
    'FORCE_UPDATE' => FORCE_UPDATE
);    


$MQTT_INFOPANEL = array( 
    'WATCHDOG' => 'sysinfo/watchdog/infopanel',
    'INFOPANEL_VAL' => 'kv152/infopanel'
   /* ,'INFOPANEL_DTM' => 'kv152/infopanel_dtm',
    'FORCE_UPDATE' => FORCE_UPDATE*/
); 


$MQTT_INFOPANEL_KITCHEN = array( 
    'WATCHDOG' => 'sysinfo/watchdog/infopanel-kitchen',
    'INFOPANEL_VAL' => 'kv152/kitchen/value'
); 
