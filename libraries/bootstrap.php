<?php
	/* Proxy bootstrap
	 *
	 * Written by Hugo Leisink <hugo@leisink.net>
	 */

	class bootstrap {
		private $config = null;

		/* Constructor
		 *
		 * INPUT:  array configration
		 * OUTPUT: -
		 * ERROR:  -
		 */
		public function __construct($configuration) {
			$this->config = $configuration;
		}

		/* Magic method get
		 *
		 * INPUT:  string key
		 * OUTPUT: mixed value
		 * ERROR:  null
		 */
		public function __get($key) {
			switch ($key) {
				case "user_input": return $this->config["user_input"];
				case "hostname": return $this->config["hostname"];
			}

			return null;
		}

		private function hostname_in_list($hostname, $list) {
			foreach ($list as $item) {
				if ($item[0] == "*") {
					$item = substr($item, 1);
					if (substr($hostname, -strlen($item)) == $item) {
						return true;
					}
				} else if ($hostname == $item) {
					return true;
				}
			}

			return false;
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

			/* User input
			 */
			$url = $_SERVER["HTTP_HOST"];
			$url_len = strlen($url);
			$proxy_len = strlen($this->config["proxy_hostname"]);
			$host_len = $url_len - $proxy_len;

			if ($url_len < $proxy_len) {
				return INTERNAL_ERROR;
			} else if (substr($url, $host_len) != $this->config["proxy_hostname"]) {
				return INTERNAL_ERROR;
			} else {
				$this->config["hostname"] = rtrim(substr($url, 0, $host_len), ".");
			}

			/* Authentication
			 */
			if (count($this->config["access_codes"]) > 0) {
				if ($this->hostname_in_list($this->config["hostname"], $this->config["free_access"]) == false) {
					if (in_array($_SESSION["access_code"], $this->config["access_codes"])) {
						// User already logged in
						$_SERVER["REQUEST_METHOD"] = "GET";
					} else if (in_array($_POST["access_code"], $this->config["access_codes"])) {
						$_SESSION["access_code"] = $_POST["access_code"];
						$_SERVER["REQUEST_METHOD"] = "GET";
					} else if (in_array($_SERVER["REMOTE_ADDR"], $this->config["no_authentication"]) == false) {
						return LOGIN_REQUIRED;
					}
				}
			}

			if ($host_len == 0) {
				return NO_USER_INPUT;
			}

			$this->config["user_input"] = $this->config["hostname"];
			if ($_SERVER["REQUEST_URI"] != "/") {
				$this->config["user_input"] .= $_SERVER["REQUEST_URI"];
			}

			/* Access control
			 */
			if (count($this->config["whitelist"]) > 0) {
				if ($this->hostname_in_list($this->config["hostname"], $this->config["whitelist"]) == false) {
					return FORBIDDEN_HOSTNAME;
				}
			}

			if ($this->hostname_in_list($this->config["hostname"], $this->config["blacklist"])) {
				return FORBIDDEN_HOSTNAME;
			}

			return 0;
		}
	}
?>
