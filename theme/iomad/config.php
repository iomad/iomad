<?php

$THEME->name = 'iomad';
$THEME->parents = array('standard','base');
$THEME->sheets = array(
    'core',     /** Must come first**/
    'admin',
    'blocks',
    'calendar',
    'course',
    'user',
    'dock',
    'grade',
    'message',
    'modules',
    'question',
    'css3',      /** Sets up CSS 3 + browser specific styles **/
    'iomad'  /** Iomad specific styles **/
);
$THEME->enable_dock = true;
