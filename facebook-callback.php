<?php
// Handle Facebook OAuth redirect
$code = $_GET['code'];
// Exchange code for access token using Facebook's API
$app_id = '824411860533922';
$app_secret = 'e14de994bcebea7a74587ee0fda38655';
$redirect_uri = 'https://kwa.com.pk/facebook-callback';

$token_url = "https://graph.facebook.com/oauth/access_token?client_id=$app_id&redirect_uri=$redirect_uri&client_secret=$app_secret&code=$code";

$response = json_decode(file_get_contents($token_url), true);

if (isset($response['access_token'])) {
    // Use the access token to authenticate the user
    $access_token = $response['access_token'];
    // ...
} else {
    // Handle error
}