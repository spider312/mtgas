#!/bin/php
<?php

include 'lib.php' ;
include 'includes/db.php' ;
include 'includes/ranking.php' ;

ranking_to_file('ranking/week.json', 'WEEK') ;
ranking_to_file('ranking/month.json', 'MONTH') ;
ranking_to_file('ranking/year.json', 'YEAR') ;
ranking_to_file('ranking/all.json', 'YEAR', 10) ;
