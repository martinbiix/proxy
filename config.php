<?php
	$_CONFIG = array(

	/* Proxy hostname
	 *
	 * Hostname of this proxy website. Set manually if the proxy isn't working correctly.
	 */
	"proxy_hostname" => $_SERVER["SERVER_NAME"],

	/* Access codes
	 *
	 * An array that contains access codes for using this proxy.
	 */
	"access_codes" => array(),

	/* Free access
	 *
	 * Defines which websites can be visited without an access code.
	 */
	"free_access" => array(),

	/* No authentication required
	 *
	 * An array that contains IP addresses from where no access code is required.
	 */
	"no_authentication" => array(),

	/* Quick links
	 *
	 * An array with URL's for the Quick Links sections
	 * Example: $quick_links = array("The Pirate Bay" => "http://thepiratebay.sx/");
	 */
	"quick_links" => array(
		"The Pirate Bay" => "http://thepiratebay.se/"),

	/* Forwarding proxy
	 *
	 * Use this other proxy (Tor) to handle all requests.
	 # Example: "forwarding_proxy" => "http://localhost:3128"
	 */
	#"forwarding_proxy" => "socks://localhost:9050",

	/* Private browsing
	 *
	 * An array that contains hostnames for which cookies are always dropped.
	 */
	"private_browsing" => array(
		"www.google.com", "google.com",
		"www.google.nl", "google.nl",
		"www.facebook.com", "facebook.com"),

	/* Access control
	 */
	"whitelist" => array(),
	"blacklist" => array()

	);
?>
