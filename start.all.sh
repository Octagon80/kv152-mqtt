#!/bin/sh


killProcByName()
{
# pid=`ps ax | grep $1 | awk 'NR==1{print $1}' | cut -d' ' -f1`
# sudo kill $pid
pkill -f $1
 #return $pid
}

cd /home/clue/server

#getPidPir
#pid_pir=$?

echo "Запуск обновления строк для отображения на информационной панеле"
killProcByName infopanel-get.php
screen -dmS infopanel php ./infopanel-get.php 

echo "Запуск временной программы для совместимости с старой электроникой обновления строк для отображения на информационной панеле"
killProcByName infopanel-get-old.php
screen -dmS infopanel-old php ./infopanel-get-old.php 

echo "Запуск получения параметров из arduino ОТМЕНЕНО"
killProcByName arduino-get-mqtt.php
screen -dmS arduino php ./arduino-get-mqtt.php  /dev/ttyACM0

echo "Запуск получения погоды"
killProcByName meteo-mqtt.php
screen -dmS pogoda php ./meteo-mqtt.php

echo "Запуск системы записи параметров в БД"
killProcByName db-mqtt.php
screen -dmS db php ./db-mqtt.php

echo "Обновление системной информации"
killProcByName sysinfo-mqtt.php
screen -dmS sysinfo php ./sysinfo-mqtt.php


echo "Обновление информации для web"
killProcByName web-mqtt.php
screen -dmS web-mqtt /usr/bin/php ./web-mqtt.php



