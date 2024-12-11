<?php

$env_HerokuSystemPath = $dirpath = getenv('env_HerokuSystemPath');

// if it is locally, main dir is 2 levels up with respect to current directory
if(strcasecmp(getenv('env_InHerokuRun'), "Y")!=0) {
    $dirpath=__DIR__.'/../../';
}
?>