<?php
/*
	A simple http client, written in php with no external dependencies.

	Supports:
	* Cookies
	* SSL
	* Content-Encoding: gzip
	* Transfer-Encoding: chunked
	* Proxies
	* Basic authentication

	RiscyDevel
*/

class HttpClientException extends Exception {
	
}

class HttpClient {
	public $socket=false;
	public $domain;
	public $port=80;
	public $timeout = 5;
	public $ssl=false;
	public $proxyIp=false;
	public $proxyPort=80;
	public $debug = false;

	public $headers=array();
	public $cookies=array();
	public $dumpToFile = false;

	function __construct($domain, $port=80) {
		$this->domain=$domain;
		$this->port=$port;
		$this->headerss=array();
		$this->firefoxId();
	}

	function chromeId() {
		$this->headers['Accept']='text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8';
		$this->headers['Accept-Encoding']='gzip, deflate, sdch';
		$this->headers['Accept-Language']='en-US,en;q=0.8';
		//$this->headers['Cache-Control']='max-age=0';
		$this->headers['Connection']='keep-alive';
		$this->headers['Connection']='close';
		$this->headers['User-Agent']='Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.155 Safari/537.36';
	}

	function firefoxId() {
		$this->headers['User-Agent']='Mozilla/5.0 (Windows NT 6.1; WOW64; rv:33.0) Gecko/20100101 Firefox/33.0';
		$this->headers['Accept']='text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
		$this->headers['Accept-Language']='en-US,en;q=0.5';
		$this->headers['Accept-Encoding']='gzip, deflate';
		$this->headers['Connection']='keep-alive';
		//$this->headers['Connection']='close';
		//$this->headers['Cache-Control']='max-age=0';
	}

	function enableSsl() {
		$this->ssl=true;
		if ($this->port == 80)
			$this->port = 443;
	}

	function disableSsl() {
		$this->ssl=false;
		if ($this->port == 443)
			$this->port = 80;
	}

	function getHeader($key) {
		return $this->headers[$key];
	}

	function setHeader($key, $value) {
		$this->headers[$key] = $value;
	}

	function getCookie($key) {
		return $this->cookies[$key];
	}

	function setCookie($key, $value) {
		$this->cookies[$key] = $value;
	}

	function setAuthBasic($username, $password) {
		$this->headers['Authorization']='Basic '.base64_encode($username.':'.$password);
	}

	// $http->setProxy(false); // to disable
	function setProxy($ip, $port = 80) {
		$this->proxyIp = $ip;
		$this->proxyPort = $port;
	}

	function clean() {
		$this->cookies=array();
		$this->headers=array();
		$this->firefoxId();
	}

	function sleep() {
		usleep(1200000+mt_rand(0, 2000000));
	}

	public function connect() {
		if ($this->proxyIp == false) {
			if ($this->ssl) {
				$this->socket=@stream_socket_client('tls://'.$this->domain.':'.$this->port, $errorCode, $errorString, $this->timeout);
			} else {
				$this->socket=@stream_socket_client('tcp://'.$this->domain.':'.$this->port, $errorCode, $errorString, $this->timeout);
			}
		} else {
			$this->socket=@stream_socket_client('tcp://'.$this->proxyIp.':'.$this->proxyPort, $errorCode, $errorString, $this->timeout);

			if ($this->ssl) {
				$connect = 'CONNECT '.$this->domain.':'.$this->port." HTTP/1.1\r\n";
				$connect .= 'Host: '.$this->domain."\r\n";
				$connect .= "\r\n";
				fwrite($this->socket, $connect);
				do {
					$tmp=trim(fgets($this->socket));
				} while($tmp != false);

				stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
			}
		}

		if (!$this->socket) {
			throw new \HttpClientException('Connection timed out.', 1);
		}

		stream_set_timeout($this->socket, $this->timeout);
	}

	public function get($path, $query=null) {
		$this->connect();

		$request = '';

		$queryPart = '';
		if ($query != null) {
			$queryPart = '?';

			if (is_array($query)) {
				foreach ($query as $key => $value) {
					if ($queryPart != '')
						$queryPart .= '&';

					$queryPart .= rawurlencode($key).'='.rawurlencode($value);
				}
			} else {
				$queryPart .= $query;
			}
		}

		if ($this->proxyIp == false || ($this->proxyIp != false && $this->ssl)) {
			$request .= 'GET '.$path.$queryPart." HTTP/1.1\r\n";
		} else {
			$request .= 'GET '.$this->domain.$path.$queryPart." HTTP/1.1\r\n";
		}

		$request .= 'Host: '.$this->domain."\r\n";

		foreach ($this->headers as $key => $value) {
			$request .= $key.': '.$value."\r\n";
		}

		if (count($this->cookies) > 0) {
			$request .= 'Cookie:';
			foreach ($this->cookies as $key => $value) {
				$request .= ' '.$key.'='.$value;
			}
			$request .= "\r\n";
		}

		$request .= "\r\n";

		if ($this->debug) {
			echo "Request\r\n";
			echo $request;
		}

		fwrite($this->socket, $request);

		$buffer = $this->recive();

		if ($this->ssl) {
			$this->headers['Referer'] = 'https://'.$this->domain.$path;
		} else {
			$this->header['Referer'] = 'http://'.$this->domain.$path;
		}

		return $buffer;
	}

	function formDataToMultipart($data) {
		$dataBlock=implode(' ', $data);
		$c = 0;

		do {
			$boundry = '---'.md5(uniqid($c++, true));
		} while (strpos($dataBlock, $boundry) !== false);

		$dataBlock = $boundry."\r\n";
		$dataBlock .= implode($boundry."\r\n", $data);
		$dataBlock .= $boundry.'--';

		$header = 'Content-Type: multipart/form-data; boundary='.substr($boundry, 2)."\r\n";
		$header .= 'Content-Length: '.(strlen($dataBlock)+4)."\r\n\r\n";
		$header .= $dataBlock;

		return $header;
	}

	function formDataFile($name, $filename, $type) {
		$value=file_get_contents($filename);
		$filename=basename($filename);

		$header = 'Content-Disposition: form-data; name="'.$name.'"; filename="'.$filename.'"'."\r\n";
		$header .= 'Content-Type: '.$type."\r\n\r\n";
		$header .= $value."\r\n";

		return $header;
	}

	function formDataField($name, $value) {
		$header = 'Content-Disposition: form-data; name="'.$name.'"'."\r\n\r\n";
		$header .= $value."\r\n";

		return $header;
	}

	public function post($path, $query=null) {
		$this->connect();

		$request = '';

		if ($this->proxyIp == false || ($this->proxyIp != false && $this->ssl)) {
			$request .= 'POST '.$path." HTTP/1.1\r\n";
		} else {
			$request .= 'POST '.$this->domain.$path." HTTP/1.1\r\n";
		}

		$request .= 'Host: '.$this->domain."\r\n";

		foreach ($this->headers as $key => $value) {
			$request .= $key.': '.$value."\r\n";
		}

		if (count($this->cookies) > 0) {
			$request .= 'Cookie:';
			foreach ($this->cookies as $key => $value) {
				$request .= ' '.$key.'='.$value;
			}
			$request .= "\r\n";
		}

		if ($query != null) {
			if (is_array($query)) {
				$queryPart = '';

				foreach ($query as $key => $value) {
					if ($queryPart != '')
						$queryPart .= '&';

					$queryPart .= rawurlencode($key).'='.rawurlencode($value);
				}

				$request .= 'Content-Length: '.strlen($queryPart)."\r\n";
				$request .= 'Content-Type: application/x-www-form-urlencoded'."\r\n\r\n";
				$request .= $queryPart."\r\n";
			} else {
				$request .= $query."\r\n";
			}
		}

		$request .= "\r\n";

		if ($this->debug) {
			echo "Request\r\n";
			echo $request;
		}

		fwrite($this->socket, $request);

		$buffer = $this->recive();

		if ($this->ssl) {
			$this->headers['Referer'] = 'https://'.$this->domain.$path;
		} else {
			$this->header['Referer'] = 'http://'.$this->domain.$path;
		}

		return $buffer;
	}

	public function recive() {
		$buffer = array('data'=>'', 'headers'=>'');

		$state = 0;
		$chunked = false;
		$gziped = false;
		$bytes = 0;
		$gzipHeader = 0;

		$fp = null;
		if ($this->dumpToFile != false) {
			$fp = fopen($this->dumpToFile, 'w');
		}

		$lastRecived = time();
		while (!feof($this->socket)) {
			$data = stream_get_line($this->socket, ($chunked == false && $bytes > 0?$bytes:8192), "\r\n")."\r\n";

			if ($data === false) {
				$info = stream_get_meta_data($this->socket);
				if ($info['timed_out']) {
					throw new \HttpClientException('Connection closed. Data timed out.', 2);
				}

				if (time() - $lastRecived > $this->timeout) {
					throw new \HttpClientException('Connection closed. Data timed out.', 3);
				}
			} else {
				$lastRecived = time();
			}

			if ($state == 0) {
				if ($data == "\r\n") {
					$state++;

					if (preg_match("/^Transfer-Encoding:[ ]+chunked[\r\n ]+$/im", $buffer['headers']) == 1) {
						$chunked = true;
					} else if (preg_match("/^Content-Length:[ ]+([0-9]+)/im", $buffer['headers'], $match)==1) {
						$bytes = $match[1];
					}

					if (preg_match('/^Content-Encoding:[ ]+gzip[\r\n ]+$/im', $buffer['headers'])==1) {
						$gziped = true;
					}
				} else {
					$buffer['headers'] .= $data;
				}
			} else {
				if (!$chunked) {
					$bytes -= strlen($data);

					if ($this->dumpToFile != false) {
						if ($gziped && $state == 2) {
							if ($gzipHeader+strlen($data) >= 10) {
								$data = substr($data, 10-$gzipHeader);
								$fltr = stream_filter_append($fp, 'zlib.inflate', STREAM_FILTER_WRITE, -1);
								$state++;
								fwrite($fp, $data);
							} else {
								$gzipHeader+=strlen($data);
							}
						} else {
							fwrite($fp, $data);
						}
					} else {
						$buffer['data'] .= $data;
					}

					if ($bytes <= 0) {
						break;
					}
				} else {
					if ($bytes <= 0) { // read chunk header
						if (strlen($data) > 2) {
							$chunkHeader = trim($data);

							if (strpos($chunkHeader, ';') !== false)
								$chunkHeader = substr($chunkHeader, 0, strpos($chunkHeader, ';'));

							$bytes = hexdec(trim($chunkHeader));

							if ($bytes == 0) {
								break;
							}
						}
					} else {
						$bytes -= strlen($data);

						if ($bytes < 0) {
							$data = substr($data, 0, $bytes);
						}

						if ($this->dumpToFile != false) {
							if ($gziped && $state == 2) {
								if ($gzipHeader+strlen($data) >= 10) {
									$data = substr($data, 10-$gzipHeader);
									$fltr = stream_filter_append($fp, 'zlib.inflate', STREAM_FILTER_WRITE, -1);
									$state++;
									fwrite($fp, $data);
								} else {
									$gzipHeader+=strlen($data);
								}
							} else {
								fwrite($fp, $data);
							}
						} else {
							$buffer['data'] .= $data;
						}
					}
				}
			}

			$info = stream_get_meta_data($this->socket);
			if ($info['timed_out']) {
				throw new \HttpClientException('Data timed out.', 4);
			}
		}

		fclose($this->socket);

		if ($this->dumpToFile != false) {
			fclose($fp);
			$this->dumpToFile = false;
		} else if ($gziped) {
			$buffer['data'] = gzinflate(substr($buffer['data'],10));
		}

		$this->cookieCheck($buffer['headers']);

		return $buffer;
	}

	public function cookieCheck($headers) {
		preg_match_all('/^Set-Cookie:[ ]+([0-9a-zA-Z+-_]+)=([^;]+);/im', $headers, $cookies);

		if (is_array($cookies[1])) {
			for ($i=0; $i<count($cookies[1]); $i++) {
				$this->cookies[$cookies[1][$i]]=$cookies[2][$i].';';
			}
		}
	}
}