<?php

require __DIR__ . '/vendor/autoload.php';

use \LINE\LINEBot\SignatureValidator as SignatureValidator;

// load config
$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

// initiate app
$configs =  [
	'settings' => ['displayErrorDetails' => true],
];
$app = new Slim\App($configs);

/* ROUTES */
$app->get('/', function ($request, $response) {
	return "Aing Pusing!!!!!";
});

$app->post('/', function ($request, $response)
{
  // get request body and line signature header
  $body      = file_get_contents('php://input');
  $signature = $_SERVER['HTTP_X_LINE_SIGNATURE'];

  // is LINE_SIGNATURE exists in request header?
  if (empty($signature)){
    return $response->withStatus(400, 'Signature not set');
  }

  // is this request comes from LINE?
  if($_ENV['PASS_SIGNATURE'] == false && ! SignatureValidator::validateSignature($body, $_ENV['CHANNEL_SECRET'], $signature))
  {
    return $response->withStatus(400, 'Invalid signature');
  }

  // init bot
  $httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($_ENV['CHANNEL_ACCESS_TOKEN']);
  $bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $_ENV['CHANNEL_SECRET']]);

  $data = json_decode($body, true);

  foreach ($data['events'] as $event) {
    if ($event['type'] == 'message') {
      if($event['message']['type'] == 'text') {
        // send same message as reply to user
        $result = $bot->replyText($event['replyToken'],
  $event['message']['text']);
        return $result->getHTTPStatus() . ' ' . '' .
    $result->getRawBody();
      }
    }
  }
});

// $app->get('/push/{to}/{message}', function ($request, $response, $args)
// {
// 	$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($_ENV['CHANNEL_ACCESS_TOKEN']);
// 	$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $_ENV['CHANNEL_SECRET']]);

// 	$textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($args['message']);
// 	$result = $bot->pushMessage($args['to'], $textMessageBuilder);

// 	return $result->getHTTPStatus() . ' ' . $result->getRawBody();
// });

/* JUST RUN IT */
$app->run();
