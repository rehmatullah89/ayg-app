<?php

$env_HerokuSystemPath = $dirpath = getenv('env_HerokuSystemPath');

if(strcasecmp(getenv('env_InHerokuRun'), "Y")!=0) {
    $dirpath=__DIR__.'/../../';
}
?>