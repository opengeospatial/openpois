<?php

// Enter the path that the oauth library is in relation to the php file
require_once('../lib/OAuth.php');

// Query Yelp for data
$unsigned_url = 'http://api.yelp.com/v2/search?';

// Set your Yelp API keys here
$consumer_key = 'zFYlwI-9sK5rxEJt9vv5IQ';
$consumer_secret = 'xY8bV3--wPRQ6xhcLBIJ3bvs1Sw';
$token = 'HgNMYmklgNb1YqcYNgHHb1eaUSwIn9iY';
$token_secret = 'Y-fvqT6U29PwbqrfpTgtP8Fwt_w';

// Token object built using the OAuth library
$token = new OAuthToken($token, $token_secret);

// Consumer object built using the OAuth library
$consumer = new OAuthConsumer($consumer_key, $consumer_secret);

// Yelp uses HMAC SHA1 encoding
$signature_method = new OAuthSignatureMethod_HMAC_SHA1();

// Build OAuth Request using the OAuth PHP library. Uses the consumer and token object created above.
$unsigned_url .= 'bounds=42.55,-71|40.60,-70.95';
$oauthrequest = OAuthRequest::from_consumer_and_token($consumer, $token, 'GET', $unsigned_url);

// Sign the request
$oauthrequest->sign_request($signature_method, $consumer, $token);

// Get the signed URL
$signed_url = $oauthrequest->to_url();

// Send Yelp API Call
$ch = curl_init($signed_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, 0);
$data = curl_exec($ch); // Yelp response
curl_close($ch);

// Handle Yelp response data
$response = json_decode($data);

// Print it for debugging
print_r($response);

?>
