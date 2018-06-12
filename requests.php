<?php
	function curlGetRequest($url, $proxyIp = false, $proxyUser = false, $proxyPass = false) {
	  //$proxy = '185.181.245.182:3001';
	  //$proxyauth = 'BDNPVu:V0ApdI2D1';

	  $ch = curl_init();
	  curl_setopt($ch, CURLOPT_URL, $url); // URL for CURL call

	  if($proxyIp != false) {
		  curl_setopt($ch, CURLOPT_PROXY, $proxyIp); // PROXY details with port
		  curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5); // If expected to call with specific PROXY type
	  }

	  if($proxyUser != false && $proxyPass != false) {
	  	curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyUser.':'.$proxyPass);   // Use if proxy have username and password
	  }

	  //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  // If url has redirects then go to the final redirected URL.
	  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  // Do not outputting it out directly on screen.
	  curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.90 Safari/537.36');

	  $output = curl_exec($ch);
	  $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	  $rurl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);

	  if($output === false) {
	    echo "Error Number:".curl_errno($ch)."<br>";
	    echo "Error String:".curl_error($ch);
	  }

	  curl_close($ch);

	  return ['code'=> $httpcode, 'response' => $output, 'redirect_url' => $rurl];
	}

	function addAvitoAds($url) {
		global $db;

		$ad = new AvitoAd($url);
		$adId = $ad->getIdByUrl();

		if($ad->isClosed() === false) {

			$rows = $db->prepare('SELECT id FROM avito_adv WHERE avito_id = ?');
			$rows->execute([$adId]);
			$indb = $rows->fetchColumn();
			
			if(empty($indb)){
				$rows = $db->prepare('INSERT INTO avito_adv (`avito_id`, `city_id`, `title`, `datatime_creation`, `datatime_update`, `status_id`, `category_url`) VALUES (?,?,?,?,?,?,?)');
				$rows->execute([$adId, getCityByName($ad->getCityName()), $ad->getTitle(), '', '', 1, $ad->getCategory()['href']]);
				sleep(rand(1,2));
				statsUpdateAvitoAd($db->lastInsertId());
			}

		} else {
			$rows = $db->prepare('SELECT id FROM avito_adv WHERE avito_id = ?');
			$rows->execute([$adId]);
			$indb = $rows->fetchColumn();
			
			if(empty($indb)){
				$rows = $db->prepare('INSERT INTO avito_adv (`avito_id`, `city_id`, `title`, `datatime_creation`, `datatime_update`, `status_id`) VALUES (?,?,?,?,?,?)');
				$rows->execute([$adId, 0, '', '', '', 2]);
			}

		}

		return $rows;
	}

	function statsUpdateAvitoAd($id) {
		global $db;

		$info = getAvitoAdInfoById($id);
		$adAvito = new AvitoAd('https://www.avito.ru/'.$info->avito_id);

		$currStatus = $db->prepare('SELECT status_id FROM avito_adv WHERE id = ?');
		$currStatus->execute([$id]);
		$currStatus = $currStatus->fetchColumn();

		if($adAvito->isClosed() === false) {

			$rows = $db->prepare('SELECT id FROM avito_stats WHERE avito_adv_id = ? AND DATE(datatime) = CURDATE() ORDER BY id DESC LIMIT 1');
			$rows->execute([$id]);
			$today_stats_id = $rows->fetchColumn();

			$position = getAvitoAdPosition('https://www.avito.ru'.$info->category_url, $info->avito_id);

			if(!empty($today_stats_id)) {
				$rows = $db->prepare('UPDATE avito_stats SET datatime = ?, views = ?, position = ? WHERE id = ?');
				$rows->execute([get_current_datetime(), $adAvito->getViews(), $position, $today_stats_id]);				
			} else {
				$rows = $db->prepare('INSERT INTO avito_stats (`datatime`, `avito_adv_id`, `views`, `position`) VALUES (?,?,?,?)');
				$rows->execute([get_current_datetime(), $id, $adAvito->getViews(), $position]);				
			}

			if($currStatus == 2) {
				$rows = $db->prepare('UPDATE avito_adv SET status_id = 1, title = ?, city_id = ?, category_url = ? WHERE id = ?');
				$rows->execute([$adAvito->getTitle(), getCityByName($adAvito->getCityName()), $adAvito->getCategory()['href'], $id]);
			}

		} else {
			if($currStatus == 1) {
				$rows = $db->prepare('UPDATE avito_adv SET status_id = 2 WHERE id = ?');
				$rows->execute([$id]);
			}
		}
	}

	function getAvitoAdInfoById($id) {
		global $db;

		$adId = $db->prepare('SELECT * FROM avito_adv WHERE id = ?');
		$adId->execute([$id]);

		return $adId->fetch(PDO::FETCH_OBJ);
	}

	function getAvitoAdPosition($category_href, $ad_id) {
		$position = false;
		$nextPage = $category_href;
		$allNum = 0;

		do {

			if($nextPage != "" && strpos($nextPage, 'avito.ru') === false) {
				$nextPage = 'https://www.avito.ru'.$nextPage;
			}

			$cat = curlGetRequest($nextPage);

			if($cat['code'] != 302 && $cat['code'] != 404) {
				$document = phpQuery::newDocument($cat['response']);
			} else {
				return false;
			}

			$urls = $document->find('.item-description-title-link');

			$urls_c = count($urls);

			$allNum += $urls_c;

			for($i = 0; $i < $urls_c; $i++) {
				$find_id = strpos($urls->eq($i)->attr('href'), '_'.$ad_id);

				if($find_id != false) {
					$position = $i;
					break;
				}
			}

			if($position == false) {
				$nextPage = $document->find('.js-pagination-next')->attr('href');
				sleep(rand(1,2));
			}else{
				break;
			}

		}while($nextPage != "");

		if($position != false) {
			$position = $position+$allNum-$urls_c;
		}

		return $position;
	}


	function getAvitoAds() {
		global $db;

		$rows = $db->prepare('SELECT * FROM avito_adv ORDER BY status_id ASC,city_id DESC ');
		$rows->execute();
		$all_rows = $rows->fetchAll(PDO::FETCH_OBJ);
		return $all_rows;
	}

	function getAvitoAdViews($id) {
		global $db;

		$rows = $db->prepare('SELECT views, position FROM avito_stats WHERE avito_adv_id = ? ORDER BY id DESC LIMIT 1');
		$rows->execute([$id]);
		return $rows->fetch(PDO::FETCH_OBJ);
	}