<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MovieQuotesNotFoundException extends \Exception {
}

class MovieQuotes_Service extends \CI_Model {
	private $serviceDomain = 'movie-quotes.herokuapp.com';
	private $serviceApiLocation = '/api/v1';

	private $httpClient;

	public function __construct() {
		$this->httpClient = new HttpClient($this->serviceDomain);
	}

	/**
	* Fetch all qutoes from our movie quotes service.
	*
	* @return returns an array with our movie quotes.
	*/
	public function fetch() {
		$tryCount = 0;
		while ($tryCount < 2) {
			try {
				$response = $this->httpClient->get($this->serviceApiLocation.'/quotes');
				$tryCount = 2;
				if ($response['data'] == '') {
					throw new \Exception('Unknown error, while fetching results from movie quotes service.');
				}
			} catch (\HttpClientException $e) {
				// eat first exception and then retry.
				$tryCount++;
				if ($tryCount >= 2) {
					throw $e;
				}
			}
		}

		$results = json_decode($response['data'], true);
		if ($results == null) {
			throw new \Exception('JSON decoding error, while fetching results from movie quotes service.');
		}

		return $results;
	}

	/**
	* Finds a specific quote from our movie quotes service and returns that quote.
	*
	* @param int id An id to a specific quote.
	* @return Returns an associative array with our movie quote.
	*/
	public function find($id) {
		$id = $id + 0;

		$tryCount = 0;
		while ($tryCount < 2) {
			try {
				$response = $this->httpClient->get($this->serviceApiLocation.'/quotes/'.$id);
				$tryCount = 2;
				if ($response['data'] == '') {
					throw new \Exception('Unknown error, while finding result from movie quotes service.');
				}
			} catch (\HttpClientException $e) {
				// eat first exception and then retry.
				$tryCount++;
				if ($tryCount >= 2) {
					throw $e;
				}
			}
		}

		$results = json_decode($response['data'], true);
		if ($results == null) {
			if (stripos($response['data'], 'Couldn\'t find Quote') === false) {
				throw new \Exception('JSON decoding error, while finding result from movie quotes service.');
			} else {
				throw new \MovieQuotesNotFoundException('Couldn\'t find Quote with that id.');
			}
		}

		return $results;
	}

	/**
	* Finds a random quote from our movie quotes service and returns that quote.
	*
	* @return Returns an associative array with our movie quote.
	*/
	public function findRandom() {
		$tryCount = 0;
		while ($tryCount < 2) {
			try {
				$response = $this->httpClient->get($this->serviceApiLocation.'/quotes/random');
				$tryCount = 2;
				if ($response['data'] == '') {
					throw new \Exception('Unknown error, while finding random result from movie quotes service.');
				}
			} catch (\HttpClientException $e) {
				// eat first exception and then retry.
				$tryCount++;
				if ($tryCount >= 2) {
					throw $e;
				}
			}
		}

		$results = json_decode($response['data'], true);
		if ($results == null) {
			throw new \Exception('JSON decoding error, while finding random result from movie quotes service.');
		}

		return $results;
	}
}