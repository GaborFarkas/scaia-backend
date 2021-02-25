<?php

// https://github.com/mcnemesis/proxy.php/blob/master/proxy.php

require_once '../admin/users/init.php';

if (!$user->canServed()) {
    http_response_code(401);
    die();
}
if (!$user->isEligible()) {
    http_response_code(403);
    die();
}

$db = DB::getInstance();
$settings = $db->query("SELECT * FROM settings")->first();

$request_headers = array( );

foreach ( $_SERVER as $key => $value ) {
    if(preg_match('/Content.Type/i', $key)){
        $content_type = explode(";", $value)[0];
        $request_headers[] = "Content-Type: ".$content_type;
        continue;
    }
    if ( substr( $key, 0, 5 ) == 'HTTP_' ) {
		$headername = str_replace( '_', ' ', substr( $key, 5 ) );
		$headername = str_replace( ' ', '-', ucwords( strtolower( $headername ) ) );
		if ( !in_array( $headername, array( 'Host', 'X-Proxy-Url' ) ) ) {
			$request_headers[] = "$headername: $value";
		}
	}
}

$request_params = $_GET;

$request_url = $_SERVER['REQUEST_SCHEME'].'://localhost'.$settings->mapserv_path;

if (Input::get('map')) {
    $request_url .= '?map='.getConfigPath($settings->mapfile_prefix, $abs_us_root).'/'.Input::get('map');
    foreach ($_GET as $key => $value) {
        if ($key !== 'map') {
            $request_url .= '&'.$key.'='.$value;
        }
    }
} else {
    http_response_code(404);
    die();
}

$ch = curl_init( $request_url );
curl_setopt( $ch, CURLOPT_HTTPHEADER, $request_headers );   // (re-)send headers
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );	 // return response
curl_setopt( $ch, CURLOPT_HEADER, true );	   // enabled response headers

//NOTE: Do not use these options for anything else but fixed endpoints on localhost!!!!!!
curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false); // enable HTTPS without SSL verification
curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false); // enable HTTPS without SSL verification

// retrieve response (headers and content)
$response = curl_exec( $ch );
curl_close( $ch );

// split response to header and content
list($response_headers, $response_content) = preg_split( '/(\r\n){2}/', $response, 2 );

// (re-)send the headers
$response_headers = preg_split( '/(\r\n){1}/', $response_headers );
foreach ( $response_headers as $key => $response_header ) {
	// Rewrite the `Location` header, so clients will also use the proxy for redirects.
	if ( preg_match( '/^Location:/', $response_header ) ) {
		list($header, $value) = preg_split( '/: /', $response_header, 2 );
		$response_header = 'Location: ' . $_SERVER['REQUEST_URI'] . '?csurl=' . $value;
	}
	if ( !preg_match( '/^(Transfer-Encoding):/', $response_header ) ) {
		header( $response_header, false );
	}
}

// finally, output the content
print( $response_content );

?>
