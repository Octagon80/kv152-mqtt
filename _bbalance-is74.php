#!/usr/bin/php
<?php
$DEBUG=false;
//Выключаем сообщения об ошибках
error_reporting(0);


#$base_url='https://www.is74.ru';
$base_url='/tmp/balance';

$url = $base_url.'';


 // new dom object
  $dom = new DOMDocument();

  //load the html
  $html = $dom->loadHTMLFile($url);

if( $DEBUG ){ echo "<PRE>";var_dump( $html );echo "</PRE>";}

  //discard white space
  $dom->preserveWhiteSpace = false;

//Полцчить баланс по classname
$classname = "summ";//"current-balance-num";
$finder = new DomXPath( $dom );
$node = $finder->query("//div[@class='$classname']")->item(0);
//if( $DEBUG ){ echo "<PRE>";var_dump( $node );echo "</PRE>";}
$BalanseInfo = $node->textContent ;


$BalanseStr='0.0';
if( $DEBUG ){ echo "Строка информации о балансе: ".$BalanseInfo;}
$lst=explode(" ", $BalanseInfo);
if( $DEBUG ){ echo " Полученный список для анализа  ".print_r( $lst, true )." ";}
foreach($lst as $item){
if( empty($item) ) continue;

if( $DEBUG ){ echo " Анализируем строку  ".$item." ";}
preg_match_all('/([0-9]*)(\,)([0-9]*)/m', $item, $matches, PREG_SET_ORDER, 0);
if( $DEBUG ){ echo " Совпадения ".print_r( $matches[0][0], true )." ";}
if( isset($matches[0][0]) ){
  $BalanseStr = $matches[0][0];
  //echo "Нашли баланс ".$BalanseStr." \r\n";
 
}
}


//$Balanse = (float) $BalanseStr;
//echo  $Balanse;
echo  $BalanseStr;



/*
//получить сколько доплатить
$classname = "sum";
$node = $finder->query("//span[@class='$classname']")->item(0);
echo "<PRE>";var_dump( $node );echo "</PRE>";
$SummStr = $node->textContent;
$Summ = (float) $SummStr;
echo  $Summ;
*/




?>
