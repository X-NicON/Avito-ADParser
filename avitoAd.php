<?php
class AvitoAd {
	private $document;
	private $req;
	private $adUrl;

	function __construct($url) {
		$this->adUrl = $url;

		$this->req = $this->avitoRequest($this->adUrl);

		if($this->req['code'] == 301) {
			$this->adUrl = $this->req['redirect_url'];
			$this->req = $this->avitoRequest($this->adUrl);
		}

		if($this->req['code'] != 302 && $this->req['code'] != 404) {
			$this->document = phpQuery::newDocument($this->req['response']);
		}
	}

	function isClosed() {
		if($this->req['code'] != 302 && $this->req['code'] != 404) {
			return false;
		}
		return true;
	}

	function getIdByUrl() {
		if(preg_match('/_([0-9]+)$/', $this->adUrl, $finded)) {
			return $finded[1];
		}
		return false;
	}

	function getId() {
		$id = $this->document->find('.title-info-metadata-item')->text();
		preg_match('/№ (.*),/', $id, $finded);
		return $finded[1];
	}

	function getTitle() {
		return $this->document->find('.title-info-title-text')->text();
	}

	function getPrice() {
		return $this->document->find('#price-value')->eq(0)->text();
	}

	function getViews() {
		$views = $this->document->find('.title-info-views')->text();
		return trim(preg_replace('( \(.*\))', '', $views));
	}

	function getTodayViews() {
		$views = $this->document->find('.title-info-views')->text();
		if(preg_match('/\(\+(.*)\)/', $views, $finded)) {
			return $finded[1];
		} else {
			return false;
		}
	}

	function getCityName() {
		$location = $this->document->find('.seller-info-value:last')->text();
		if(preg_match('/,\s(.*) р-н|,\s(.*)/', $location, $finded)) {
			if(!empty($finded[1])) {
				return $finded[1];
			} elseif(isset($finded[2])) {
				return $finded[2];
			}
		}
	}

	function getCategory() {
		$cat = $this->document->find('.breadcrumb-link:last');
		return ['title' => $cat->text(), 'href' => $cat->attr('href')];
	}

	private function avitoRequest($url) {
		return curlGetRequest($url);
	}
}