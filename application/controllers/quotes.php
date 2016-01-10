<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Quotes extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->load->model('MovieQuotes_Service');
	}

	// route default to /quotes/all
	public function index() {
		$this->all();
	}

	/**
	* List all quotes.
	* /quotes/all
	*/
	public function all() {
		// intialize variables.
		$view = [];
		$quotes = [];

		// fetch all quotes
		try {
			$quotes = $this->MovieQuotes_Service->fetch();
		} catch (\Exception $e) {
			$view['error'] = 'Unknown error fetching quotes.';
		}

		// render
		$this->load->view('header', $view);
		foreach ($quotes as $quote) {
			// html sanitize the quote.
			$quoteView = [
				'id' => $quote['id']+0,
				'quote' => html_escape($quote['content']),
			];

			$this->load->view('list/quote', $quoteView);
		}
		$this->load->view('footer', $view);
	}

	/**
	* View a single quote by id
	* /quotes/view/:id
	*/
	public function view($id) {
		// intialize variables.
		$view = [];
		$quote = [];

		// sanitize input id to int
		$id = $id + 0;

		// find quote
		try {
			$quote = $this->MovieQuotes_Service->find($id);
		} catch (\MovieQuotesNotFoundException $e) {
			$view['error'] = 'Quote not found.';
		} catch (\Exception $e) {
			$view['error'] = 'Unknown error fetching quotes.';
		}

		// render
		$this->load->view('header', $view);
		if (count($quote) != 0) {
			// html sanitize the quote.
			$quoteView = [
				'id' => $quote['id']+0,
				'quote' => html_escape($quote['content']),
				'film' => html_escape($quote['film']),
				'year' => html_escape($quote['year']),
				'character' => html_escape($quote['character']),
				'actor' => html_escape($quote['actor']),
			];

			$this->load->view('quote', $quoteView);
		}
		$this->load->view('footer', $view);
	}


	/**
	* View a random quote
	* /quotes/random
	*/
	public function random() {
		// intialize variables.
		$view = [];
		$quote = [];

		// find random quote
		try {
			$quote = $this->MovieQuotes_Service->findRandom();
		} catch (\Exception $e) {
			$view['error'] = 'Unknown error fetching quotes.';
		}

		// render
		$this->load->view('header', $view);
		if (count($quote) != 0) {
			// html sanitize the quote.
			$quoteView = [
				'id' => $quote['id']+0,
				'quote' => html_escape($quote['content']),
				'film' => html_escape($quote['film']),
				'year' => html_escape($quote['year']),
				'character' => html_escape($quote['character']),
				'actor' => html_escape($quote['actor']),
			];

			$this->load->view('quote', $quoteView);
		}
		$this->load->view('footer', $view);
	}
}
