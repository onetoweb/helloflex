<?php

require 'vendor/autoload.php';

session_start();

use Onetoweb\HelloFlex\Client;
use Onetoweb\HelloFlex\Token;

// client parameters
$clientId = 'client_id';
$clientSecret = 'client_secret';

// setup client
$client = new Client($clientId, $clientSecret);

// set token callback to store token
$client->setUpdateTokenCallback(function(Token $token) {
    
    $_SESSION['token'] = [
        'accessToken' => $token->getAccessToken(),
        'expires' => $token->getExpires(),
    ];
    
});

// load token from storage
if (isset($_SESSION['token'])) {
    
    $token = new Token(
        $_SESSION['token']['accessToken'],
        $_SESSION['token']['expires']
    );
    
    $client->setToken($token);
    
}

// get jobs
$jobs = $client->get('/api/jobs');

// get total count header from last request 
$totalCount = $client->getTotalCount();

// get job
$jobsId = 'jobs_id';
$job = $client->get("/api/jobs/$jobsId");

// get jobs
$publicJobs = $client->get('/api/publicjobs');

// get agencies
$agencies = $client->get('/api/agencies');

// get employers
$employers = $client->get('/api/employers', [
    'skip' => 0,
    'take' => 10
]);

// candidates
$candidates = $client->get('/api/candidates');
