<?php

require_once 'vendor/autoload.php';

use Symfony\Component\HttpClient\HttpClient;

const BASE_URL = "https://www.duolingo.com";
const API_URL = "https://d2.duolingo.com/api/1";

$options = getopt('', ['login:','password:','learning:','native:','output:']);
if (
    !array_key_exists('login', $options) ||
    !array_key_exists('password', $options) ||
    !array_key_exists('learning', $options) ||
    !array_key_exists('native', $options)
) {
    echo <<<USAGE
Usage: $argv[0] [options]

    --login     Duolingo email
    --password  Duolingo password
    --learning  Language you are learning 
                Short language code: en for english, es for spanish, etc.
    --native    Your native language
                Short language code: en for english, es for spanish, etc.
    --output    Optional: output format csv (default) or json

USAGE;
    exit(1);
}

$login = $options['login'];
$password = $options['password'];
$source = $options['learning'];
$dest = $options['native'];
$output = $options['output'] ?? 'csv';

/**
 * This function combines the array representing a word (with details)
 * plus the array of translations to a line in our output file.
 */
function transformToCsv(array $item, ?array $translate): array {
    return [
        strtolower($item['word_string'].'<br/>('.$item['pos'].(!empty($item['infinitive'])?' : '.$item['infinitive']:'').')'),
        strtolower($translate === null ? '??' : implode('<br/>', $translate)),
    ];
}

/**
 * This function combines the array representing a word (with details)
 * plus the array of translations to an entry in our final json.
 */
function transformToJson(array $item, ?array $translate): array {
    $translate = ($translate ?? ['??']);
    $translation = array_shift($translate);
    return [
        'word' => $item['word_string'],
        'translation' => $translation,
        'details' =>
            (!empty($translate) ? 'Also: ' . implode(' / ', $translate) . ' ' : '').
            '('.$item['pos'].(!empty($item['infinitive'])?': '.$item['infinitive']:'').')',
    ];
}

$client = HttpClient::create();
$response = $client->request('POST', BASE_URL.'/login', [
    'json' => ["login" => $login, "password" => $password]
]);
$jwt = $response->getHeaders()['jwt'][0];

$client = HttpClient::create();
$response = $client->request('GET', BASE_URL.'/vocabulary/overview', ['auth_bearer' => $jwt]);
$vocabulary = json_decode($response->getContent(), true);
if (empty($vocabulary)) {
    throw new Exception('Fatal error when getting /vocabulary/overview');
}

/*
$vocabulary['vocab_overview'] = array(14) {
    ["strength_bars"] => int(3)
    ["infinitive"] => NULL
    ["normalized_string"] => string(4) "lima"
    ["pos"] => string(11) "Proper noun"
    ["last_practiced_ms"] => int(1649942894000)
    ["skill"] => string(7) "Bases 1"
    ["related_lexemes"] => array(0) {}
    ["last_practiced"] => string(20) "2022-04-14T13:28:14Z"
    ["strength"] => float(0.599888)
    ["skill_url_title"] => string(8) "Basics-1"
    ["gender"] => string(8) "Feminine"
    ["id"] => string(32) "224ae1dd3b5069b088af09691a705aad"
    ["lexeme_id"] => string(32) "224ae1dd3b5069b088af09691a705aad"
    ["word_string"] => string(4) "Lima"
}
*/

$words = [];
$details = [];

foreach ($vocabulary['vocab_overview'] as $vocabItem) {
    $words[] = $vocabItem['word_string'];
    $details[$vocabItem['word_string']] = $vocabItem;
}

// Those are filters rules that applies well to spanish, you may want to adapt this part
// The goal is to reduce the list by removing same words in their different forms (plurals, feminines, etc.)

// Rule 1: remove words that are too small (1 char) and words that contains uppercase letters (Proper nouns)
$words = array_filter($words, fn ($item) => mb_strlen($item) > 1 && mb_strtolower($item) === $item);

// Rule 2: remove plurals
$words = array_filter($words, fn ($item) => !(
    str_ends_with($item, 's') &&
    in_array(substr($item, 0, -1), $words)
));

// Rule 3: remove feminine
$words = array_filter($words, fn ($item) => !(
    str_ends_with($item, 'a') &&
    in_array(substr($item, 0, -1).'o', $words)
));

sort($words);

$client = HttpClient::create();
$tokens = json_encode($words);
$response = $client->request('GET', API_URL . '/dictionary/hints/'.$source.'/'.$dest.'?tokens='.$tokens, ['auth_bearer' => $jwt]);
$translate = json_decode($response->getContent(), true);
if (empty($translate)) {
    throw new Exception('Fatal error when getting /dictionary/hints');
}


$fileDescriptor = fopen('php://stdout', 'w');
if ($output === 'json') {
    $final = array_map(fn ($item) => transformToJson($details[$item], $translate[$item]), $words);
    $data = json_encode($final);
    fputs($fileDescriptor, $data);
} else {
    $final = array_map(fn ($item) => transformToCsv($details[$item], $translate[$item]), $words);
    foreach ($final as $line) {
        fputcsv($fileDescriptor, $line);
    }

}
fclose($fileDescriptor);
