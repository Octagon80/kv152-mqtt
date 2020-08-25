<?php

  $sysinfo = array(
      'cpu'=>array(
          //температура процессора
          't'=>-32768,
          //температура ядер
          'core1t'=>-32768,
          'core2t'=>-32768,
          'core3t'=>-32768,
          'core4t'=>-32768,
          //скорость вентилятора
          'fan_speed'=>-32768
      ),
      'hdd1'=>array(
          //температура винчестера 1
          't'=>-32768,
      ),
      'who'=>''
      ); 
  
 function getCpu(){
$stat1 = file('/proc/stat'); 
sleep(1); 
$stat2 = file('/proc/stat'); 
$info1 = explode(" ", preg_replace("!cpu +!", "", $stat1[0])); 
$info2 = explode(" ", preg_replace("!cpu +!", "", $stat2[0])); 
$dif = array(); 
$dif['user'] = $info2[0] - $info1[0]; 
$dif['nice'] = $info2[1] - $info1[1]; 
$dif['sys'] = $info2[2] - $info1[2]; 
$dif['idle'] = $info2[3] - $info1[3]; 
$total = array_sum($dif); 
$cpu = array(); 
foreach($dif as $x=>$y) $cpu[$x] = round($y / $total * 100, 1);     
   return($cpu);  
 } 
  
  
function getSysinfo($debug){
 global $sysinfo;
  
 $ResSensors = shell_exec("sensors");

if( $debug > 3) echo $ResSensors;
$listStrings = explode("\n", $ResSensors);
if( $debug > 3) print_r($listStrings);
foreach($listStrings as $string){
   // if( empty($string) ) return true;
    $reVal = '/(.*):(.*)\((.*)\)/m';
    $reName = '/(.*):(.*)/m';

    
    if( $debug > 3) echo "Анализ '$string'\n";

    preg_match_all($reName, $string, $matches, PREG_SET_ORDER, 0);
    if( isset($matches[0][2]) ){
	//Это тип параметра параметра
	$paramType  = trim($matches[0][1]);
	$paramName = trim($matches[0][2]);
	if( $debug > 3) echo "$paramType = $paramName\n";
    }



    preg_match_all($reVal, $string, $matches, PREG_SET_ORDER, 0);
    if( isset($matches[0][3]) ){
	//Это значение параметра
	$valName  = trim($matches[0][1]);
	$valValue = trim($matches[0][2]);
	$valRange = trim($matches[0][3]);
	if( $debug > 3) echo "$valName = $valValue, $valRange\n";
        
        if( $valName == 'CPU Temperature') $sysinfo['cpu']['t'] = $valValue;
        if( $valName == 'temp1') $sysinfo['cpu']['t'] = $valValue;
        if( $valName == 'Core 0') $sysinfo['cpu']['core1t'] = $valValue;
        if( $valName == 'Core 1') $sysinfo['cpu']['core2t'] = $valValue;
    }
}

 $sysinfo['who'] = shell_exec("who"); 
 
$sysinfo['cpu']['load'] = sys_getloadavg();
 
if( $debug > 1) echo __FUNCTION__." результат " .print_r($sysinfo, true)."\n";
  return $sysinfo;
}
?>