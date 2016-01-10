<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Download extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->load->model('MovieQuotes_Service');
	}

	// route default to /downloads/all
	public function index() {
		$this->all();
	}

	/**
	* List all quotes.
	* /downloads/all
	*/
	public function all() {
		$view = [];
		$quotes = [];

		try {
			// fetch all the quotes.
			$quotes = $this->MovieQuotes_Service->fetch();

			// Setup headers to send a plain text download
			header('Content-Type: text/plain');
			header('Content-Disposition: attachment; filename="movie.quotes.txt"');

			// Loop through each quote and render.
			for ($i = 0; $i < count($quotes); $i++) {

				// no html sanitization needed as we output text/plain
				$quoteView = [
					'quote' => $quotes[$i]['content'],
					'film' => $quotes[$i]['film'],
					'character' => $quotes[$i]['character'],
					'actor' => $quotes[$i]['actor'],
				];

				// render
				if ($i == 0)
					$this->load->view('download/firstQuote', $quoteView);
				else
					$this->load->view('download/sequentialQuote', $quoteView);
			}
		} catch (\Exception $e) {
			// An error happend show normal html site.
			$view['error'] = 'Unknown error fetching quotes.';
			$this->load->view('header', $view);
			$this->load->view('footer', $view);
		}
	}

	/**
	* View a single quote by id 
	* /download/quote/:id
	*/
	public function quote($id) {
		$view = [];
		$quote = [];

		$id = $id + 0;

		try {
			// find quote
			$quote = $this->MovieQuotes_Service->find($id);

			// We found the quote so lets render it.
			if (count($quote) != 0) {
				// no html sanitization needed as we output text/plain
				$quoteView = [
					'quote' => $quote['content'],
					'film' => $quote['film'],
					'character' => $quote['character'],
					'actor' => $quote['actor'],
				];

				// Give our text files unique filenames
				$id = $quote['id']+0;

				// Setup headers to send a plain text download
				header('Content-Type: text/plain');
				header('Content-Disposition: attachment; filename="movie.quote.'.$id.'.txt"');

				// render
				$this->load->view('download/firstQuote', $quoteView);
			}

			// return before we render normal html site.
			return;
		} catch (\MovieQuotesNotFoundException $e) { // an error happend, render normal html site.
			$view['error'] = 'Quote not found.';
		} catch (\Exception $e) {
			$view['error'] = 'Unknown error fetching quotes.';
		}

		$this->load->view('header', $view);
		$this->load->view('footer', $view);
	}
}
