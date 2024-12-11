<?php

	require 'dirpath.php';
	require $dirpath . 'vendor/autoload.php';
	require $dirpath . 'core/token.php';

	ini_set('precision', 14);
	$epoch = microtime(true)*1000;
	$token = generateToken($epoch);

	Header('Location: ../process/?action=getMobilockDevices&tokenEpoch=' . $epoch . '&token=' . $token);
?>
