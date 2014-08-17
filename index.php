<?php
	/* System stuff
	 */
	require("system.php");
	require("config.php");

	if (substr($_CONFIG["proxy_hostname"], 0, 2) == "*.") {
		$_CONFIG["proxy_hostname"] = substr($_CONFIG["proxy_hostname"], 2);
	}

	session_name(SESSION_KEY);
	session_set_cookie_params(0, "/", ".".$_CONFIG["proxy_hostname"]);
	session_start();
	unset($_COOKIE[SESSION_KEY]);

	/* Bootstrap
	 */
	$bootstrap = new bootstrap($_CONFIG);
	$result = $bootstrap->execute();

	if ($result == 0) {
		/* Start proxy
		 */
		if ($_SERVER["HTTPS"] == "on") {
			$proxy = new proxys($_CONFIG["proxy_hostname"], $bootstrap->hostname, $_SERVER["SERVER_PORT"]);
		} else {
			$proxy = new proxy($_CONFIG["proxy_hostname"], $bootstrap->hostname, $_SERVER["SERVER_PORT"]);
		}

		/* Other proxy
		 */
		if ($_CONFIG["forwarding_proxy"] != null) {
			list($protocol, $hostname, $port) = explode(":", $_CONFIG["forwarding_proxy"], 3);
			$hostname = trim($hostname, "/");
			$port = trim($port, "/");

			if (($hostname != "") && ($port != "")) {
				switch ($protocol) {
					case "http": $proxy->via_http_proxy($hostname, $port); break;
					case "https": $proxy->via_http_proxy($hostname, $port, true); break;
					case "socks": $proxy->via_socks_proxy($hostname, $port); break;
					default: print "Invalid forwarding proxy protocol."; return;
				}
			}
		}

		/* Private browsing
		 */
		$proxy->ignore_cookies($_CONFIG["private_browsing"]);

		/* Forward request
		 */
		$result = $proxy->forward_request($_SERVER["REQUEST_URI"]);

		if ($result == 0) {
			/* Forward successful
			 */
			return;
		}
	}

	/* Handle bootstrap or proxy result
	 */
	$output = new output($_CONFIG["proxy_hostname"], $_CONFIG["quick_links"]);
	$message = null;
	$status = null;

	switch ($result) {
		case CONNECTION_ERROR:
			$message = "Connection error.";
			break;
		case NO_USER_INPUT:
			$page = ltrim($_SERVER["REQUEST_URI"], "/");

			if ($page == "") {
				$output->show_url_form();
			} else if (in_array($page, $proxy_pages)) {
				$output->show_page($page);
			} else if (in_array($page, array_keys($local_files))) {
				$output->show_local_file($page, $local_files);
			} else {
				$output->http_error(404);
			}
			break;
		case LOGIN_REQUIRED:
			$error = isset($_POST["access_code"]) ? "Invalid login." : null;
			$output->show_login_form($error);
			break;
		case INTERNAL_ERROR:
			$message = "Internal error. Try setting proxy_hostname in config.php manually.";
			$status = 500;
			break;
		case FORBIDDEN_HOSTNAME:
			$message = "Access to that website is not allowed.";
			$status = 403;
			break;
		case LOOPBACK:
			$message = "Loopback connection not allowed.";
			$status = 508;
			break;
		case LOCAL_FILE:
			$output->show_local_file();
			break;
		case 403:
			$output->http_error($result);
			break;
		case 405:
		case 500:
			$output->http_error($result);
			$output->show_url_form();
			break;
		default:
			$message = "Something went wrong (".$result.").";
	}

	if ($message !== null) {
		$output->show_url_form($bootstrap->user_input, $message, $status);
	}
?>
