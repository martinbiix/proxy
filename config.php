<?php
	/* Proxy hostname
	 *
	 * Hostname of this proxy website. Set manually if the proxy isn't working correctly.
	 */
	$proxy_hostname = $_SERVER["SERVER_NAME"];

	/* Access codes
	 *
	 * An array that contains access codes for using this proxy.
	 */
	$access_codes = array();

	/* No authentication required
	 *
	 * An array that contains IP addresses from where no access code is required.
	 */
	$no_authentication = array();

	/* Quick links
	 *
	 * An array with URL's for the Quick Links sections
	 */
	$quick_links = array(
		"The Pirate Bay" => "http://thepiratebay.se/");

	/* Private browsing
	 *
	 * An array that contains hostnames for which cookies are always dropped.
	 */
	$private_browsing = array(
		"www.google.com", "google.com",
		"www.facebook.com", "facebook.com");

	/* Access control
	 *
	 * Control what websites can be visited.
	 */
	$whitelist = array();
	$blacklist = array();
?>
