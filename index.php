<?php
	/* Local files
	 */
	$files = array(
		"tunnel.png"  => "image/png",
		"favicon.ico" => "image/x-ico",
		"robots.txt"  => "text/plain");
	foreach ($files as $file => $content_type) {
		if ($_SERVER["REQUEST_URI"] == "/".$file) {
			header("Content-Type: ".$content_type);
			header("Content-Length: ".filesize($file));
			header("Expires: ".date("D, d M Y H:i:s", time() + (14 * 86400))." GMT");
			readfile($file);
			exit;
		}
	}

	/* System stuff
	 */
	require("system.php");
	require("config.php");

	session_name(SESSION_KEY);
	session_set_cookie_params(0, "/", ".".$proxy_hostname);
	session_start();
	unset($_COOKIE[SESSION_KEY]);

	/* Bootstrap
	 */
	$bootstrap = new bootstrap($proxy_hostname, $access_codes, $no_authentication,
	                           $whitelist, $blacklist);
	$result = $bootstrap->execute();

	if ($result == 0) {
		/* Start proxy
		 */
		if ($_SERVER["HTTPS"] == "on") {
			$proxy = new proxys($proxy_hostname, $bootstrap->hostname, $_SERVER["SERVER_PORT"]);
		} else {
			$proxy = new proxy($proxy_hostname, $bootstrap->hostname, $_SERVER["SERVER_PORT"]);
		}

		/* Private browsing
		 */
		$proxy->ignore_cookies($private_browsing);

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
	$output = new output($proxy_hostname, $quick_links);
	$message = null;

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
			} else {
				$output->http_error(404);
			}
			break;
		case LOGIN_REQUIRED:
			$error = isset($_POST["access_code"]) ? "Invalid login." : null;
			$output->show_login_form($error);
			break;
		case INTERNAL_ERROR:
			$message = "Internal error. Try setting \$proxy_hostname in config.php manually.";
			break;
		case FORBIDDEN_HOSTNAME:
			$message = "Access to that website is not allowed.";
			break;
		case LOOPBACK:
			$message = "Loopback connection not allowed.";
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
		$output->show_url_form($bootstrap->user_input, $message);
	}
?>
