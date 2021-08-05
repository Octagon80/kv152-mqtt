<?php

/*
 * Получение погоды с сайта 
 * https://www.wunderground.com/weather/ru/chelyabinsk/USCC?cm_ven=localwx_today
 * (Баландино)
 */

//$DEBUG = true;
    
/**
 * Получить погоду
 * @global type $context
 * @return array  array( 
 *     'wind_speed'=> array(
 *                          'value' => float сила ветра в метрах в секунду, 
 *                                    false-при ошибке
 *                          'dtm' => время обновения значения epoch
 *                          'msg' => если value==false, здесь будет текст ошибки 
 *                         )
 *  )
 */
function getBalandino() {
    global $DEBUG;
    $curTime = time();
    $result = array(
        'wind_speed' => array(
            'value' => false,
            'dtm' => $curTime,
            'msg' => ''
        )
    );



    //Для исключения частого опроса Интернет, проверяем, что записывали
    //в буфер на предудущей итерации
    $msgOld = false;
    if (file_exists('/tmp/balandino.txt')) {
        if ($DEBUG)
            echo __FUNCTION__ . "Есть файл \r\n";
        $msgOld = file('/tmp/balandino.txt');
    }

    if (isset($msgOld[1])) {
        $timeOld = intval($msgOld[0]);
        $conteudosite = $msgOld[1];
        if ($DEBUG)
            echo __FUNCTION__ . "Прочитали из файла " . $timeOld . " html len=".strlen($conteudosite)." empty?=".( empty($conteudosite)?"да":"нет"). " \r\n";
    } else {
        if ($DEBUG)
            echo __FUNCTION__ . "Нет данных в файле \r\n";
        $timeOld = 0;
        $conteudosite = "";
    }


    //Условия обновления данных из Интернет
    $neeUpadateFromInternet = ($curTime - $timeOld) > 15 * 60;
    //$neeUpadateFromInternet =  $neeUpadateFromInternet || strlen($conteudosite)<10;
    
    echo __FUNCTION__." Условие обновления по времени: ".($curTime - $timeOld).">".(15 * 60)."\n";
    
    //Если между реальными запросами к сайту, прошлло более N минут,
    //произвести повторный запрос
    if (  $neeUpadateFromInternet ) {
        if ($DEBUG)
            echo __FUNCTION__ . "Обращение к интернет \r\n";

        $url = "https://www.wunderground.com/weather/ru/chelyabinsk/USCC?cm_ven=localwx_today";
            $context = stream_context_create(array(
                "http" => array(
                    "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
                ),
                "ssl" => array(
                    "verify_peer" => false,
                    "verify_peer_name" => false
                )
            ));


            $conteudosite = file_get_contents($url, false, $context);
            $conteudosite = str_replace(PHP_EOL, '', $conteudosite);
            echo __FUNCTION__."Обновили погоду баландино из Интернет\n";
            file_put_contents('/tmp/balandino.txt', $curTime . "\n");
            file_put_contents('/tmp/balandino.txt', $conteudosite, FILE_APPEND);
      }    
       /* 
        * на текущий момент $conteudosite содержит: 
        * 1. html-код из Интернет,
        * 2. html-код из файла,
        * 3. false или пусто при ошибке
        */

      
            if (!$conteudosite || empty($conteudosite) ) {
                $result['wind_speed']['value'] = false;
                $result['wind_speed']['msg'] = "Get web error";
                goto _exit;
            }
      

        // Здесь ситуация, когда $conteudosite содержит html-код    


        $dom = new DOMDocument();
        $internalErrors = libxml_use_internal_errors(true);
        $dom->loadHTML($conteudosite);

        $dom->preserveWhiteSpace = false;
        //Получить силу ветра
        $classname = "wind-speed"; //"current-balance-num";
        $finder = new DomXPath($dom);
        $node = $finder->query("//header[@class='$classname']")->item(0);
        if (is_null($node)) {
            $result['wind_speed']['value'] = false;
            $result['wind_speed']['msg'] = "Not found html tag";
        } else {
            $strmsg = trim($node->textContent);

            $mph = intval($strmsg);
            $mps = $mph * 0.44704; //мили в час конвертируем в метры в секунды
            $result['wind_speed']['value'] = round($mps,1);
            $result['wind_speed']['msg'] = "";
        }

        _exit:
        return( $result );
   }
    
   
   
//echo print_r(getBalandino(), true);