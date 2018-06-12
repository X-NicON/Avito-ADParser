#Simple Avito Parser Ads by url AND monitoring

Require **phpQuery.php**

##Usage

	require 'phpQuery.php';
	require 'requests.php';
	require 'avitoAd.php';


	$ad = new AvitoAd($URL);

	$ad->isClosed()
	$ad->getCityName()
	$ad->getTitle()
	$ad->getPrice()
	...
	etc