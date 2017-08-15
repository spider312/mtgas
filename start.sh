#!/bin/sh
DATE=`date +%Y-%m-%d_%H-%M`
mkdir -p log
/usr/bin/php ws_server.php 2>&1 | tee log/$DATE.log
