<?php
$nginx_file_path = 'nginx-cloudflare-ip-include-file-path';

ini_set('display_errors', '0');
error_reporting(E_ALL);
if(!extension_loaded('curl')) die('PHP - cURL NOT INSTALLED OR DISABLED ON THE SERVER');

$api_url = 'https://api.cloudflare.com/client/v4/ips';
$curl = curl_init($api_url);

if($curl === false) die('PHP - FAILED TO INITIALIZE cURL');

curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json'
    ]
]);

$response = curl_exec($curl);
if($response === false) die('PHP - cURL API REQUEST FAILED, Error : '.(curl_error($curl) ?? 'NULL'));
$response_code = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if($response_code !== 200) die('PHP - INVALID RESPONSE FROM CLOUDFLARE API');
$response = (array) (@json_decode($response, true) ?? []);

if(empty($response))                 die('PHP - INVALID RESPONSE FROM CLOUDFLARE API');
if(!($response['success'] ?? false)) die('PHP - API STATUS IS FAILED, THE RESPONSE IS : '.json_encode($response, JSON_PRETTY_PRINT));

$ip_result = $response['result']      ?? [];
$ipv6_list = $ip_result['ipv6_cidrs'] ?? [];
$ipv4_list = $ip_result['ipv4_cidrs'] ?? [];

$save_data = 'set_real_ip_from '.implode(";\nset_real_ip_from ", $ipv4_list).";\n";
$save_data.= 'set_real_ip_from '.implode(";\nset_real_ip_from ", $ipv6_list).";\n";
$save_data.= "real_ip_header X-Forwarded-For;\nreal_ip_recursive on;";

if(!(@file_put_contents($nginx_file_path, $save_data) ?? false)) die('PHP - UNABLE TO SAVE FILE');
if(!(@chmod($nginx_file_path, 0600) ?? false)) die('PHP - UNABLE TO CHANGE FILE PERMISSION');
die('PHP - CLOUDFLARE IP SAVED SUCCESSFULLY');
?>
