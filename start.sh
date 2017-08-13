#!/bin/sh
DATE=`date +%Y-%m-%d_%R`
mkdir -p log
/usr/bin/php ws_server.php | tee log/$DATE.log
