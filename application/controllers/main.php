<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Main extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->load->model('MovieQuotes_Service');
	}

	public function index() {
		try {
			var_dump($this->MovieQuotes_Service->findRandom());
		} catch (MovieQuotesNotFoundException $e) {
			var_dump('Quote not found.');
		}
		//$this->load->view('welcome_message');
	}
}
