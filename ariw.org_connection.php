<?php
function ariw_fetch($lug) {

if (!function_exists('simplexml_load_file')) {echo "Error: simple_xml_extension not installed."; return null; }

if ($lug != 'slux') {
	$cache = WP_PLUGIN_DIR . "/biofoo_plugin/ariw_cache/$lug.amf.xml";
	//echo "Cache: $cache";
	if (file_exists($cache) and (time() - filemtime($cache) < 60 * 60 * 24)) { 		// is file cached?
		@$xml = simplexml_load_file($cache);
		//echo "<li>Cached file found from time:$time and filetime(cache): " . filemtime($cache) . "</li>";
		
	} elseif (file_exists("ftp://ariw.org/lib/$lug.amf.xml")) {			// try simple connection
		@$xml = simplexml_load_file("ftp://ariw.org/lib/$lug.amf.xml");
		@file_put_contents($cache, $xml->asXML());
		//echo "<li>Data loadet simple way, cache written</li>";
	}

	if (!$xml) { 										// try advanced connection
		@$conn = ftp_connect("ariw.org");
		$file = "$lug.amf.xml";
		@ftp_login($conn, "anonymous", "your@email.com");
		@ftp_pasv($conn, true);
		@ftp_chdir($conn, "/lib");
		$result = ftp_get($conn, $cache, $file, FTP_BINARY);
		echo ($result) ? "" : "Error: ftp_get did't work or ariw.org is not reachable";
		ftp_close($conn);
		@$xml = ($result) ? simplexml_load_file($cache) : null;
	}
		
	if ($xml != null) {
		echo '<select size="1" id="instslux" onchange="fill_fields()">';
		echo "\n<option selected='selected'>(choose organization)</option>";
		foreach ($xml->organization as $org) {
			$slug = $org->homepage;
			echo "\n<option value='$slug'>{$org->name}</option>";
		}
		echo '</select>';	
	} else {										//error
		echo "Error: unable to reach ftp://ariw.org/lib/$lug.amf.xml";
	}
} else { 											//countrylist
	include_once(WP_PLUGIN_DIR . '/wp4labs/countrylist.php');
}

return $xml;

}
?>
