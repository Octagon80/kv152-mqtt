#!/bin/sh

echo "Останов системы"
pkill -f "db-mqtt.php"
pkill -f "infopanel-get.php"
pkill -f "infopanel-get-old.php"
pkill -f "arduino-get-mqtt.php"
pkill -f "meteo-mqtt.php"
pkill -f "sysinfo-mqtt.php"
pkill -f "web-mqtt.php"
pkill -f "infopanel-kitchen.php"
pkill -f "meteo-my-mqtt.php"
pkill -f "is74balance-mqtt.php"

screen -ls