<?php
	/* Proxy bootstrap
	 *
	 * Written by Hugo Leisink <hugo@leisink.net>
	 */

	class bootstrap {
		private $proxy_hostname = null;
		private $access_codes = null;
		private $no_authentication = null;
		private $whitelist = null;
		private $blacklist = null;
		private $user_input = null;
		private $hostname = null;

		/* Constructor
		 *
		 * INPUT:  string proxy hostname, array access codes, array no authentication, array whitelist, array blacklist
		 * OUTPUT: -
		 * ERROR:  -
		 */
		public function __construct($proxy_hostname, $access_codes, $no_authentication, $whitelist, $blacklist) {
			$this->proxy_hostname = $proxy_hostname;
			$this->access_codes = $access_codes;
			$this->no_authentication = $no_authentication;
			$this->whitelist = $whitelist;
			$this->blacklist = $blacklist;
		}

		/* Magic method get
		 *
		 * INPUT:  string key
		 * OUTPUT: mixed value
		 * ERROR:  null
		 */
		public function __get($key) {
			switch ($key) {
				case "user_input": return $this->user_input;
				case "hostname": return $this->hostname;
			}

			return null;
		}

		/* Execute bootstrap procedure
		 *
		 * INPUT:  -
		 * OUTPUT: integer result
		 * ERROR:  -
		 */
		public function execute() {
			/* Block searchbots
			 */
			$search_bots = array("Googlebot", "bingbot");
			foreach ($search_bots as $bot) {
				if (strpos($_SERVER["HTTP_USER_AGENT"], $bot) !== false) {
					return 403;
				}
			}

			/* Authentication
			 */
			if (count($this->access_codes) > 0) {
				if (in_array($_SESSION["access_code"], $this->access_codes)) {
					// User already logged in
				} else if (in_array($_POST["access_code"], $this->access_codes)) {
					$_SESSION["access_code"] = $_POST["access_code"];
				} else if (in_array($_SERVER["REMOTE_ADDR"], $this->no_authentication) == false) {
					return LOGIN_REQUIRED;
				}
			}

			/* User input
			 */
			$url = $_SERVER["HTTP_HOST"];
			$url_len = strlen($url);
			$proxy_len = strlen($this->proxy_hostname);
			$host_len = $url_len - $proxy_len;

			if ($url_len < $proxy_len) {
				return INTERNAL_ERROR;
			} else if (substr($url, $host_len) != $this->proxy_hostname) {
				return INTERNAL_ERROR;
			} else {
				$this->hostname = rtrim(substr($url, 0, $host_len), ".");
			}

			if ($host_len == 0) {
				return NO_USER_INPUT;
			}

			$this->user_input = $this->hostname;
			if ($_SERVER["REQUEST_URI"] != "/") {
				$this->user_input .= $_SERVER["REQUEST_URI"];
			}

			/* Access control
			 */
			if (count($this->whitelist) > 0) {
				if (in_array($this->hostname, $this->whitelist) == false) {
					return FORBIDDEN_HOSTNAME;
				}
			}

			if (in_array($this->hostname, $this->blacklist)) {
				return FORBIDDEN_HOSTNAME;
			}

			return 0;
		}
	}
?>
