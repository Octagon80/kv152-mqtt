<?php

/* 
 * Получение строки состояния с сайта http://edds74.ru/
 * В частности об отмене занятий
 * 
 * @return array
 *            'school'=>true/false  true==идем в школу, false=есть основания, что не идем, смотри сам вручную
 *            'msg'=>string  строка исходного сообщения
 */
$DEBUG = false;

include __DIR__ . "/get_url_page.php";

function translit($string) 
  { 
    $table = array( 
                'А' => 'A', 
                'Б' => 'B', 
                'В' => 'V', 
                'Г' => 'G', 
                'Д' => 'D', 
                'Е' => 'E', 
                'Ё' => 'YO', 
                'Ж' => 'ZH', 
                'З' => 'Z', 
                'И' => 'I', 
                'Й' => 'J', 
                'К' => 'K', 
                'Л' => 'L', 
                'М' => 'M', 
                'Н' => 'N', 
                'О' => 'O', 
                'П' => 'P', 
                'Р' => 'R', 
                'С' => 'S', 
                'Т' => 'T', 
                'У' => 'U', 
                'Ф' => 'F', 
                'Х' => 'H', 
                'Ц' => 'C', 
                'Ч' => 'CH', 
                'Ш' => 'SH', 
                'Щ' => 'CSH', 
                'Ь' => '', 
                'Ы' => 'Y', 
                'Ъ' => '', 
                'Э' => 'E', 
                'Ю' => 'YU', 
                'Я' => 'YA', 
 
                'а' => 'a', 
                'б' => 'b', 
                'в' => 'v', 
                'г' => 'g', 
                'д' => 'd', 
                'е' => 'e', 
                'ё' => 'yo', 
                'ж' => 'zh', 
                'з' => 'z', 
                'и' => 'i', 
                'й' => 'j', 
                'к' => 'k', 
                'л' => 'l', 
                'м' => 'm', 
                'н' => 'n', 
                'о' => 'o', 
                'п' => 'p', 
                'р' => 'r', 
                'с' => 's', 
                'т' => 't', 
                'у' => 'u', 
                'ф' => 'f', 
                'х' => 'h', 
                'ц' => 'c', 
                'ч' => 'ch', 
                'ш' => 'sh', 
                'щ' => 'csh', 
                'ь' => '', 
                'ы' => 'y', 
                'ъ' => '', 
                'э' => 'e', 
                'ю' => 'yu', 
                'я' => 'ya',
                ' ' => ' ' 
    ); 
 
    $output = str_replace( 
        array_keys($table), 
        array_values($table),$string 
    ); 
 
    return $output; 
}
    
    
function getEdds74(){
    global $DEBUG;
    $curTime = time();
    
    $msgOld = false;
    if( file_exists('/tmp/edds74.txt') ){
        if( $DEBUG ) echo __FUNCTION__."Есть файл \r\n";
        $msgOld = file('/tmp/edds74.txt');
    }
    if( isset($msgOld[1]) ){
      $timeOld = intval( $msgOld[0] );
      $strmsg = $msgOld[1];
      if( $DEBUG ) echo __FUNCTION__."Прочитали из файла ".$timeOld." '".$strmsg."' \r\n";
    }else{
      if( $DEBUG ) echo __FUNCTION__."Нет данных в файле \r\n";  
      $timeOld = 0;
      $strmsg = "";        
    }  
    //Если между реальными запросами к сайту, прошлло более N минут,
    //произвести повторный запрос
    if( ($curTime - $timeOld)  > 15 * 60 ){
        if( $DEBUG ) echo __FUNCTION__."Обращение к интернет \r\n";
        
        
$context = stream_context_create(array(
    "ssl"=>array(
        /*"cafile" => "/home/clue/server/prg/cert/php_curl_cacert.pem",*/
        "verify_peer"=> false,
        "verify_peer_name"=> false
    )
));        
        
         $url = "https://edds.gov74.ru/";
         $conteudosite = file_get_contents($url, false, $context);         
    if( !$conteudosite ) {
          file_put_contents ( '/tmp/edds74.txt' , $curTime."\n" );
          file_put_contents ( '/tmp/edds74.txt' , "get web error\n", FILE_APPEND );
        }else{ 
        
         $dom = new DOMDocument();
         $internalErrors = libxml_use_internal_errors(true);
         //////////////////////$html = $dom->loadHTML(getPage($url));
         $html = $dom->loadHTML($conteudosite);
         
         $dom->preserveWhiteSpace = false;
        //Получить бегущую строку
        $classname = "blue-text-block"; //"current-balance-num";
        $finder = new DomXPath($dom);
        $node = $finder->query("//div[@class='$classname']")->item(0);
        if( is_null( $node ) ){
          file_put_contents ( '/tmp/edds74.txt' , $curTime."\n" );
          file_put_contents ( '/tmp/edds74.txt' , "Html tag find error\n", FILE_APPEND );
        }else{
          $strmsg = trim($node->textContent);
          file_put_contents ( '/tmp/edds74.txt' , $curTime."\n" );
          file_put_contents ( '/tmp/edds74.txt' , $strmsg."\n", FILE_APPEND );
        }         
    }     
        
    }

//Отмена занятий
$school = true;
if( strpos($strmsg, "отменяются занятия в школах") ) $school = false;
   

//Убираем двойные пробелы
$strmsg = preg_replace('|\s+|', ' ', $strmsg); 
   
$result = array(
    'school'=>$school,
    'msg'   => translit($strmsg) 
);
return( $result );
}

 if( $DEBUG ) {
$msg = getEdds74();

print_r($msg);
 }