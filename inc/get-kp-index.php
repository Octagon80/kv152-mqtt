<?php

$ERROR_RETURN = -32768;
/**
 * Вернет массив 0-число индекс

  Получить состояние магнитных бурь
  https://services.swpc.noaa.gov/text/3-day-geomag-forecast.txt
  еще
  https://services.swpc.noaa.gov/text/3-day-forecast.txt
  if(  $kpindex <  5 ) $res[1]=  "Без шторма";
  if(  $kpindex == 5 ) $res[1]=  "Слабый шторм";
  if(  $kpindex == 6 ) $res[1]=  "Средний шторм";
  if(  $kpindex == 7 ) $res[1]=  "Умеренный шторм";
  if(  $kpindex == 8 ) $res[1]=  "Сильный шторм";
  if(  $kpindex >  8 ) $res[1]=  "Очень сильный";


  1-строка
 */
function getKpIndex() {
    global $ERROR_RETURN;
    $URL_KPINDEX = "https://services.swpc.noaa.gov/text/3-day-geomag-forecast.txt";


    /*
      текущие состояние
      https://services.swpc.noaa.gov/text/3-day-geomag-forecast.txt
      http://www.pogoda.by/uv-gw/geo.php
      http://wdc.kugi.kyoto-u.ac.jp/dst_realtime/presentmonth/index.html
      http://swx.sinp.msu.ru/
     */


try{
    $arrTxtData = file($URL_KPINDEX);
}
 catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
        return($ERROR_RETURN);
    }   

//echo "<PRE>".print_r($arrTxtData, true)."</PRE>";
//$arrTxtData[16] - строка дней   "Apr 28    Apr 29    Apr 30"
//$arrTxtData[17..24] строки часов  значений по дням "00-03UT        2         2         2"

    $firstIndex = 16;

//разбор дней
    $tmp = explode("  ", $arrTxtData[$firstIndex + 0]);
    $days = array();
    $no = 1;
    foreach ($tmp as $value) {
        if (!empty(trim($value))) {
            $days[$no] = trim($value);
            $no++;
        }
    }
//echo "days<PRE>".print_r($days, true)."</PRE>";
//разбор значений
    $valueByHour = array();
    $HourLineNo = 0;
    for ($i = ($firstIndex + 1); $i <= ($firstIndex + 7); $i++) {
        //echo $arrTxtData[ $i ]."<BR>";
        $tmp = explode(" ", $arrTxtData[$i]);
        //echo "i=$i tmp<PRE>".print_r($tmp, true)."</PRE>";
        //Обработка строки-текущего часа (в строке несколько значений по дням)
        $ValuePosNo = 0;
        foreach ($tmp as $value0) {
            $value = trim($value0);
            if (!empty($value)) {
                if ($ValuePosNo == 0) {
                    //это заголовок - время
                    //инициализация 3-мя значениями
                    $valueByHour[$HourLineNo] = array('title' => $value, $days[1] => 32768, $days[2] => 32768, $days[3] => 32768);
                    $ValuePosNo = 1;
                } else {
                    //это значение по каждому дню
                    $valueByHour[$HourLineNo][$days[intval($ValuePosNo)]] = $value;
                    $ValuePosNo++;
                }
            }
        }
        //продолжим обработку на следующий час
        $HourLineNo++;
    }

//echo "valueByHour<PRE>".print_r($valueByHour, true)."</PRE>";
//Получаем индекс массива данных по текущему времени
//echo gmdate("Y-m-d H:i:s", time());
    $currentHour = gmdate("H", time());
    $indexOfCurrentHour = 0;
    if ($currentHour >= 0 && $currentHour <= 3)
        $indexOfCurrentHour = 0;
    if ($currentHour > 3 && $currentHour <= 6)
        $indexOfCurrentHour = 1;
    if ($currentHour > 6 && $currentHour <= 9)
        $indexOfCurrentHour = 2;
    if ($currentHour > 9 && $currentHour <= 12)
        $indexOfCurrentHour = 3;
    if ($currentHour > 12 && $currentHour <= 15)
        $indexOfCurrentHour = 4;
    if ($currentHour > 15 && $currentHour <= 18)
        $indexOfCurrentHour = 5;
    if ($currentHour > 18 && $currentHour <= 21)
        $indexOfCurrentHour = 6;

//echo "Текущая строка данных  ".print_r($valueByHour[$indexOfCurrentHour], true)."<BR>";

    $currentDay = gmdate("d", time());

    $kpindex = $ERROR_RETURN;
    foreach ($valueByHour[$indexOfCurrentHour] as $title => $value) {
        $tmp = explode(' ', $title);
        if (isset($tmp[1])) {
            $d = intval($tmp[1]);
            if ($currentDay == $d)
                $kpindex = $value;
        }
    }

    $res = array();

    $res[0] = $kpindex;

    $res[1] = " ";
    if ($kpindex < 5)
        $res[1] = "Без шторма";
    if ($kpindex == 5)
        $res[1] = "Слабый шторм";
    if ($kpindex == 6)
        $res[1] = "Средний шторм";
    if ($kpindex == 7)
        $res[1] = "Умеренный шторм";
    if ($kpindex == 8)
        $res[1] = "Сильный шторм";
    if ($kpindex > 8)
        $res[1] = "Очень сильный";
    /*

      Значения шкалы (G) и соотвествующие им значения Кр-индекса (Кр).	Название.	Проявления.	Широта северного сияния.
      G0 — Kp<5	Без шторма	Геомагнитная обстановка от спокойной до возмущенной (ярко выраженного влияния не замечено).	В высоких (> 65°) широтах.
      G1 — Kp= 5	Слабый шторм.	Незначительные сбои в работе энергосистем. Изменения путей миграции животных и птиц.	На географической широте Ст.Петербурга [Corrected Magnetic Latitude=56.1°]).
      G2 — Kp=6	Средний шторм.	В энергосистемах, расположенных в высоких широтах, могут происходить сбои напряжения. Длительный геомагнитный шторм может вызвать неполадки на трансформаторных подстанциях.	На географической широте г. Пскова [Corrected Magnetic Latitude=54°]).
      G3 — Kp=7	Умеренный шторм.	Возникновение перенапряжений в промышленной электросети. Ложные срабатывания автоматики. Кратковременные сбои GPS-навигации и низкочастотной радионавигации. Перебои коротковолновой связи.	На широте Риги [Corrected Magnetic Latitude=51°], Москвы [Corrected Magnetic Latitude=51.8°]).
      G4 — Kp=8	Сильный шторм.	Широкомасштабное возникновение перенапряжений в промышленной электросети. Повсеместное ложное срабатывание работе аварийных защитных систем (АЗС). Коротковолновая связь неустойчива. GPS-навигация ухудшается на несколько часов. Средневолновая радионавигация отсутствует.	На широте Минска [Corrected Magnetic Latitude=50°]).
      G5 — Kp>8	Очень сильный.	Могут возникнуть повсеместные проблемы с регулировкой напряжения промышленной электросети и проблемы в работе защитных систем (АЗС). Энергосистемы в целом и трансформаторные подстанции в частности могут полностью выйти из строя или отключиться (наведенные токи могут достигать сотен ампер). КВ-связь, GPS-навигация и средневолновая радионавигация отсутствует как вид... в общем примерно так конец света и описывали.
     */

    return( $res );
}
