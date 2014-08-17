<?php
	/* Proxy library
	 *
	 * Written by Hugo Leisink <hugo@leisink.net>
	 */

	class proxy extends HTTP {
		private $proxy_hostname = null;
		private $ignore_cookies = array();
		private $headers_to_server = array("Accept", "Accept-Charset",
			"Accept-Language", "Referer", "User-Agent", "X-Requested-With",
			"Authorization");
		private $headers_to_client = array("Accept-Ranges", "Cache-Control",
			"Content-Type", "Content-Range", "DNT", "ETag", "Expires",
			"Last-Modified", "Location", "Pragma", "Refresh", "Set-Cookie",
			"WWW-Authenticate");

		/* Constructor
		 *
		 * INPUT:  string proxy hostname, string host[, int port]
		 * OUTPUT: -
		 * ERROR:  -
		 */
		public function __construct($proxy_hostname, $host, $port = null) {
			$this->proxy_hostname = $proxy_hostname;

			parent::__construct($host, $port);
		}

		/* Forward HTTP header from browser to server
		 */
		private function header_to_server($header) {
			$envkey = "HTTP_".str_replace("-", "_", strtoupper($header));

			if (isset($_SERVER[$envkey]) == false) {
				return;
			}

			$value = $_SERVER[$envkey];
			if ($header == "Referer") {
				$value = str_replace($_SERVER["SERVER_NAME"]."/", "", $value);
			}
			$this->add_header($header, $value);
		}

		/* Rewrite URL
		 */
		private function rewrite_url($url) {
			$url = trim($url, " '\n");
			$scheme = ($_SERVER["HTTPS"] == "on") ? "https" : "http";

			if (substr($url, 0, 2) == "//") {
				list($host, $path) = explode("/", substr($url, 2), 2);
				$new_url = sprintf("//%s.%s/%s", $host, $this->proxy_hostname, $path);
			} else if (substr($url, 0, 7) == "http://") {
				list($host, $path) = explode("/", substr($url, 7), 2);
				$new_url = sprintf("http://%s.%s/%s", $host, $this->proxy_hostname, $path);
			} else if (substr($url, 0, 8) == "https://") {
				list($host, $path) = explode("/", substr($url, 8), 2);
				$new_url = sprintf("https://%s.%s/%s", $host, $this->proxy_hostname, $path);
			} else {
				$new_url = $url;
			}

			return $new_url;
		}

		/* Fix property value
		 */
		private function rewrite_to_proxy($data, $delim_begin, $delim_end) {
			$offset = 0;

			while (($begin = strpos($data, $delim_begin, $offset)) !== false) {
				$begin += strlen($delim_begin);
				if (($end = strpos($data, $delim_end, $begin)) === false) {
					$offset = $begin;
					continue;
				}

				$first = substr($data, 0, $begin);
				$url = substr($data, $begin, $end - $begin);
				$last = substr($data, $end);

				$data = $first.$this->rewrite_url($url).$last;

				$offset = $begin + strlen($new_url) + 1;
			}

			return $data;
		}

		/* Ignore cookies for host
		 *
		 * INPUT:  mixed hostname
		 * OUTPUT: -
		 * ERROR:  -
		 */
		public function ignore_cookies($host) {
			if (is_array($host) == false) {
				array_push($this->ignore_cookies, $host);
			} else {
				$this->ignore_cookies = array_unique(array_merge($this->ignore_cookies, $host));
			}
		}

		/* Forward request to remote webserver
		 *
		 * INPUT:  string path
		 * OUTPUT: -
		 * ERROR:  -
		 */
		public function forward_request($path) {
			/* Proxy self?
			 */
			if ($this->host == $this->proxy_hostname) {
				return LOOPBACK;
			}

			/* Forward headers to server
			 */
			foreach ($this->headers_to_server as $header) {
				$this->header_to_server($header);
			}

			/* POST data
			 */
			$parts = array();
			foreach ($_POST as $key => $value) {
				array_push($parts, urlencode($key)."=".urlencode($value));
			}
			$post = implode("&", $parts);

			/* Cookies from client
			 */
			if (in_array($this->host, $this->ignore_cookies) == false) {
				foreach ($_COOKIE as $key => $value) {
					$this->add_cookie($key, $value);
				}
			}

			/* Send request to server
			 */
			switch ($_SERVER["REQUEST_METHOD"]) {
				case "GET":
					$result = $this->GET($path);
					break;
				case "POST":
					$result = $this->POST($path, $post);
					break;
				default:
					return 405;
			}

			/* Abort on error
			 */
			if ($result == false) {
				return CONNECTION_ERROR;
			} else if ($result["status"] >= 500) {
				return $result["status"];
			}

			/* Fix headers
			 */
			if (isset($result["headers"]["Location"])) {
				$result["headers"]["Location"] = $this->rewrite_url($result["headers"]["Location"]);
			}

			/* Fix header typo
			 */
			if (isset($result["headers"]["Content-type"])) {
				$result["headers"]["Content-Type"] = $result["headers"]["Content-type"];
				unset($result["headers"]["Content-type"]);
			}

			/* Fix body
			 */
			if (substr($result["headers"]["Content-Type"], 0, 9) == "text/html") {
				/* HTML
				 */
				foreach (array("action", "href", "src") as $property) {
					$result["body"] = $this->rewrite_to_proxy($result["body"], $property.'="', '"');
				}
				$result["body"] = $this->rewrite_to_proxy($result["body"], "url(", ")");
				$result["body"] = $this->rewrite_to_proxy($result["body"], "='", "'");
				$result["body"] = $this->rewrite_to_proxy($result["body"], "( '", "'");
			} else if (substr($result["headers"]["Content-Type"], 0, 8) == "text/css") {
				/* CSS
				 */
				$result["body"] = $this->rewrite_to_proxy($result["body"], "url(", ")");
			} else if ((substr($result["headers"]["Content-Type"], 0, 22) == "application/javascript") ||
			           (substr($result["headers"]["Content-Type"], 0, 24) == "application/x-javascript") ||
			           (substr($result["headers"]["Content-Type"], 0, 15) == "text/javascript")) {
				/* Javascript
				 */
				$result["body"] = $this->rewrite_to_proxy($result["body"], "='", "'");
				$result["body"] = $this->rewrite_to_proxy($result["body"], '+"', '"');
			}

			/* Send result to browser
			 */
			if ($result["status"] != 200) {
				header("Status: ".$result["status"]);
			}

			foreach ($result["headers"] as $key => $value) {
				if (in_array($key, $this->headers_to_client) || (substr($key, 2) == "X-")) {
					if ($key == "Set-Cookie") {
						if (in_array($this->host, $this->ignore_cookies)) {	
							continue;
						}
					}
					header($key.": ".$value);
				}
			}

			print $result["body"];

			if ($result["sock"] !== null) {
				while (($line = fgets($result["sock"])) !== false) {
					print $line;
				}

				fclose($result["sock"]);
				$result["sock"] = null;
			}

			return 0;
		}
	}
?>
