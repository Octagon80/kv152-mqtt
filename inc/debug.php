<?php

/* 
 * Некоторая централизованная схема отладочных сообщений
 * 
 * include "debug.php"
 * global $DEBUG_CFG;
 * $DEBUG = $DEBUG_CFG["LEVEL"]["NONE"];     //нет отладочных сообщений
 * $DEBUG = $DEBUG_CFG["LEVEL"]["ERROR"];    //только ошибочные сообщения
 * $DEBUG = $DEBUG_CFG["LEVEL"]["MSG"];      //предыдущий уровень + просто сообщения
 * $DEBUG = $DEBUG_CFG["LEVEL"]["VERBOSE"];  //предыдущий уровень + подробные сообщения
 * $DEBUG = $DEBUG_CFG["LEVEL"]["DEBUG"];    //предыдущий уровень + отладочные сообщения
 */

$DEBUG_CFG = array(
     "LEVEL"=>array(
         "NONE"=>0      //нет отладочных сообщений
         ,"ERROR"=>1    //только ошибочные сообщения
         ,"MSG"=>2      //предыдущий уровень + просто сообщения
         ,"VERBOSE"=>3  //предыдущий уровень + подробные сообщения
         ,"DEBUG"=>4    //предыдущий уровень + отладочные сообщени
     ),
    "TYPE"=> 0 /* 0 - консольный вариан, 1-web-вариант*/
);    


/**
 * Сообщение
 * @param int    $level
 * @param string $point метоположение сообщения
 * @param string $msg
 */
function DEBUG_MSG($level, $point, $msg){
    global $DEBUG_CFG; 
    if( $level == $DEBUG_CFG["LEVEL"]["NONE"] ) return( false );
    
    $time = @date('[Y-m-d:H:i:s]');
    
    $strout = "";
    if( $DEBUG_CFG["TYPE"]  == 1 ) $strout .= "<B>$time</B> <I>$point</I>";
    if( $DEBUG_CFG["TYPE"]  == 0 ) $strout .= "$time|$point|";
    
    if( $level == $DEBUG_CFG["LEVEL"]["ERROR"] ){
       if( $DEBUG_CFG["TYPE"]  == 0 ) echo $strout.$msg."\n";
       if( $DEBUG_CFG["TYPE"]  == 1 ) echo $strout." <FONT color='red'>'$msg'</FONT><BR>";
    }
    
    if( $level == $DEBUG_CFG["LEVEL"]["MSG"] ){
        if( $DEBUG_CFG["TYPE"]  == 0 ) echo $strout.$msg."\n";
        if( $DEBUG_CFG["TYPE"]  == 1 )  echo $strout." <FONT color='green'>'$msg'</FONT><BR>";
    }

    if( $level == $DEBUG_CFG["LEVEL"]["VERBOSE"] ){
        if( $DEBUG_CFG["TYPE"]  == 0 ) echo $strout.$msg."\n";
        if( $DEBUG_CFG["TYPE"]  == 1 )  echo $strout." <FONT color='c0c0c0'>'$msg'</FONT><BR>";
    }

    if( $level == $DEBUG_CFG["LEVEL"]["DEBUG"] ){
        if( $DEBUG_CFG["TYPE"]  == 0 ) echo $strout.$msg."\n";
        if( $DEBUG_CFG["TYPE"]  == 1 )  echo $strout." <FONT color='e0e0e0'>'$msg'</FONT><BR>";
    }
    
    return( true );
}

