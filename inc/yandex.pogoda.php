<?php
/**
 * Получение данных по погоде из Яндекс
 */


/**
* Получить из погода.yandex информацию о погоде
* и вернуть результат как массив
*/
function WetherYandex($debug){
  if( $debug >0) echo  __FUNCTION__." вызов \r\n";
   $res = array();
   $html ='';

   //Чтобы удаленный сайт не напрягать кучей запросов, сократим их, считывая с локального файла
   if( file_exists('/tmp/yatimeout.txt') )
            $timeout = (int)file_get_contents('/tmp/yatimeout.txt');
   else
       $timeout = 999;
   
   $timeout++;
   if( $timeout < 11 ){
      $html = file_get_contents('/tmp/yamessage.txt');
  }else{
      $html = implode( file('https://export.yandex.ru/bar/reginfo.xml?region=56') );
      file_put_contents('/tmp/yamessage.txt', $html);
      $timeout = 0;
   }
   file_put_contents('/tmp/yatimeout.txt', $timeout);
 
  if( empty( $html ) ) return( false );
 
   $pogodaXML = new SimpleXMLElement( $html );

   if( $debug >3) echo  __FUNCTION__."<PRE>".print_r($pogodaXML->weather->day, true)."</PRE>"; 

 

   //индекс времени 0-утро, 1-день, 2-вечер, 3-ночь 
   $indexDayTime = 0;
   $H = date('H');

//всегда в нулевом индексе Яндекс подставляет правильные данные
//   if( $H>=6  && $H<12 )  $indexDayTime = 0;
//   if( $H>=12 && $H<18 )  $indexDayTime = 1;
//   if( $H>=18 && $H<24 )  $indexDayTime = 2;
//   if( $H>=00  && $H<6 )  $indexDayTime = 3;
    

   $res = array( 

   //время суток
    'day_name' => (string)$pogodaXML->weather->day->day_part[ $indexDayTime ]['type']
   //описание погоды
  ,'weather_type' => (string)$pogodaXML->weather->day->day_part[ $indexDayTime ]->weather_type

  //ветер
      ,'wind_speed' => (float)$pogodaXML->weather->day->day_part[ $indexDayTime ]->wind_speed
      ,'wind_direction' => (string)$pogodaXML->weather->day->day_part[ $indexDayTime ]->wind_direction
   //температура
     ,'temperature'=> (int)$pogodaXML->weather->day->day_part[ $indexDayTime ]->temperature
//давление
    ,'pressure'=> (int)$pogodaXML->weather->day->day_part[ $indexDayTime ]->pressure
//описание погоды - англ
    ,'weather_code'=> (string)$pogodaXML->weather->day->day_part[ $indexDayTime ]->weather_code
);


  if( $debug > 2) echo  __FUNCTION__."<PRE>".print_r($res,true)."</PRE>";
  return( $res );
}

