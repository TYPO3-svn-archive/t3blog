<?
require_once('../../lib/xmlrpc-2.2/lib/xmlrpc.inc');

function do_send_pingback($myarticle, $url, $pdebug = 0) {
	print_r($parts);

	if (!isset($parts['scheme'])) {
		print "do_send_pingback: failed to get url scheme [".$url."]<br />\n";
		return(1);
	}
	if ($parts['scheme'] != 'http') {
		print "do_send_pingback: url scheme is not http [".$url."]<br />\n";
		return(1);
	}
	if (!isset($parts['host'])) {
		print "do_send_pingback: could not get host [".$url."]<br />\n";
		return(1);
	}
	$host = $parts['host'];
	$port = 80;
	if (isset($parts['port'])) $port = $parts['port'];
	$path = "/";
	if (isset($parts['path'])) $path = $parts['path'];
	if (isset($parts['query'])) $path .="?".$parts['query'];
	if (isset($parts['fragment'])) $path .="#".$parts['fragment'];

	$fp = fsockopen($host, $port);
	fwrite($fp, "GET $path HTTP/1.0\r\nHost: $host\r\n\r\n");
	$response = "";
	while (is_resource($fp) && $fp && (!feof($fp))) {
		$response .= fread($fp, 1024);
	}
	fclose($fp);
	$lines = explode("\r\n", $response);
	foreach ($lines as $line) {
		if (ereg("X-Pingback: ", $line)) {
			list($pburl) = sscanf($line, "X-Pingback: %s");
			#print "pingback url is $pburl<br />\n";
		}
	}

	if (empty($pburl)) {
		print "Could not get pingback url from [$url].<br />\n";
		return(1);
	}
	if (!isset($parts['scheme'])) {
		print "do_send_pingback: failed to get pingback url scheme [".$pburl."]<br />\n";
		return(1);
	}
	if ($parts['scheme'] != 'http') {
		print "do_send_pingback: pingback url scheme is not http[".$pburl."]<br />\n";
		return(1);
	}
	if (!isset($parts['host'])) {
		print "do_send_pingback: could not get pingback host [".$pburl."]<br />\n";
		return(1);
	}
	$host = $parts['host'];
	$port = 80;
	if (isset($parts['port'])) $port = $parts['port'];
	$path = "/";
	if (isset($parts['path'])) $path = $parts['path'];
	if (isset($parts['query'])) $path .="?".$parts['query'];
	if (isset($parts['fragment'])) $path .="#".$parts['fragment'];

	$m = new xmlrpcmsg("pingback.ping", array(new xmlrpcval($myarticle, "string"), new xmlrpcval($url, "string")));
	$c = new xmlrpc_client($path, $host, $port);
	$c->setRequestCompression(null);
	$c->setAcceptedCompression(null);
	if ($pdebug) $c->setDebug(2);
	$r = $c->send($m);
	if (!$r->faultCode()) {
		print "Pingback to $url succeeded.<br >\n";
	} else {
		$err = "code ".$r->faultCode()." message ".$r->faultString();
		print "Pingback to $url failed with error $err.<br >\n";
	}
}

# call send_pingback() from your blog after adding a new post,
# $text will be the full text of your post
# $myurl will be the full url of your posting
function send_pingback($text, $myurl) {
	$m = array();
	preg_match_all ("/<a[^>]*href=[\"']([^\"']*)[\"'][^>]*>(.*?)<\/a>/i", $text, $m);
	$c = count($m[0]);
	for ($i = 0; $i < $c; $i++) {
		$ret = valid_url($m[1][$i]);
		if ($ret) do_send_pingback($myurl, $m[1][$i]);
	}
}
?>