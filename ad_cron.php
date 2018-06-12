<?php
	if(php_sapi_name() != 'cli') die;

	require 'phpQuery.php';
	require 'requests.php';
	require 'avitoAd.php';

  $db = new db(); // you db connect

  foreach (getAvitoAds() as $ad) {
		statsUpdateAvitoAd($ad->id);
		sleep(rand(6,15));
	}