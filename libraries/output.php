<?php
	class output {
		private $proxy_hostname = null;
		private $quick_links = null;
		private $working_dir = null;
		private $output = "";
		private $enabled = true;

		/* Constructor
		 */
		public function __construct($proxy_hostname, $quick_links) {
			$this->proxy_hostname = $proxy_hostname;
			$this->quick_links = $quick_links;
			$this->working_dir = str_replace("/libraries", "", __DIR__);

			$this->show_file("header", array(
				"PROXY_HOSTNAME" => $this->proxy_hostname,
				"PROTOCOL"       => $_SERVER["HTTPS"] == "on" ? "https" : "http",
				"SESSION_KEY"    => SESSION_KEY));
		}

		/* Destructor
		 */
		public function __destruct() {
			if ($this->enabled == false) {
				return;
			}

			$this->show_file("footer", array("VERSION" => VERSION));

			print $this->output;
		}

		/* Show file content
		 */
		private function show_file($filename, $replace = null) {
			$output = file_get_contents($this->working_dir."/views/".$filename.".html");
			if ($output == false) {
				return;
			}

			if (is_array($replace)) {
				foreach ($replace as $key => $value) {
					$output = str_replace("{".$key."}", $value, $output);
				}
			}

			$this->output .= $output;
		}

		/* Show local file
		 */
		public function show_local_file($filename, $local_files) {
			header("Content-Type: ".$local_files[$filename]);
			header("Content-Length: ".filesize($filename));
			header("Expires: ".date("D, d M Y H:i:s", time() + (14 * 86400))." GMT");
			readfile($filename);

			$this->enabled = false;
		}

		/* Login form
		 */
		public function show_login_form($message = null) {
			header("Status: 407");

			$data = array(
				"PROTOCOL" => ($_SERVER["HTTPS"] == "on") ? "https" : "http",
				"HOSTNAME" => $_SERVER["HTTP_HOST"],
				"URI"      => $_SERVER["REQUEST_URI"]);
			$this->show_file("login", $data);

			if ($message !== null) {
				$this->show_file("error", array("MESSAGE" => $message));
			}

			$this->show_file("download");
		}

		/* URL form
		 */
		public function show_url_form($url = "", $message = null, $status = null) {
			if ($status !== null) {
				header("Status: ".$status);
			}

			$protocol = ($_SERVER["HTTPS"] == "on") ? "https" : "http";

			$this->show_file("url_form", array("PROTOCOL" => $protocol, "URL" => $url));
			if ($message !== null) {
				$this->show_file("error", array("MESSAGE" => $message));
			}

			/* Quick links
			 */
			if (is_array($this->quick_links) == false) {
				return;
			} else if (count($this->quick_links) == 0) {
				return;
			}

			$links = array();
			foreach ($this->quick_links as $text => $link) {
				list($prot,, $host, $path) = explode("/", $link, 4);
				if (is_string($text) == false) {
					$text = $host;
				}
				$link = sprintf("%s//%s.%s/%s", $prot, $host, $this->proxy_hostname, $path);

				array_push($links, sprintf("<li><a href=\"%s\">%s</a></li>\n", $link, $text));
			}

			$this->show_file("links", array("LINKS" => implode("\n", $links)));
			$this->show_file("download");
			$this->show_file("menu");
		}

		/* HTTP error message
		 */
		public function http_error($code) {
			$messages = array(
				403 => "Forbidden",
				404 => "Not Found",
				405 => "Unsupported request method",
				500 => "Internal error at remote server");


			if (($message = $messages[$code]) == null) {
				$message = "Unknown error";
			} else {
				header("Status: ".$code);
				$message = sprintf("%d - %s", $code, $message);
			}

			$this->show_file("error", array("MESSAGE" => $message));
			$this->show_file("menu");
		}

		/* Show proxy page
		 */
		public function show_page($page) {
			$php_file = "views/".$page.".php";
			if (file_exists($php_file) == false) {
				return false;
			}

			ob_start();
			include($php_file);
			$output = ob_get_clean();

			$this->output .= $output;
			$this->show_file("menu");
		}
	}
?>
