Proxy
=====
This is a webproxy script written in PHP by Hugo Leisink <hugo@leisink.net>.

Installation
------------
- Copy all files to a suitable location.
- Make the webserver rewrite *all* requests to index.php.
- Make this proxy available via both HTTP and HTTPS.
- Add a wildcard to the hostname you use for this proxy website and use it
  as an alias in your webserver configuration. For example, if you choose
  proxy.domain.net as the hostname for this proxy, make *.proxy.domain.net
  an alias for it.
