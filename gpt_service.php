<?php
require 'vendor/autoload.php';
use GuzzleHttp\Client;

function matchWithGPT($resume, $jd, $apiKey) {
    $prompt = "You are a resume screening assistant. Compare the following resume and job description. Score it from 0 to 100 and explain the result.\n\nResume:\n" .
              json_encode($resume, JSON_PRETTY_PRINT) . "\n\nJob Description:\n" .
              $jd['description'] . "\n\nReturn this JSON format:\n{\n  \"score\": number,\n  \"reason\": string\n}";

    $client = new Client(['base_uri' => 'https://api.openai.com/v1/']);
    $response = $client->post('chat/completions', [
        'headers' => [
            'Authorization' => "Bearer $apiKey",
            'Content-Type'  => 'application/json',
        ],
        'json' => [
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.3,
        ],
    ]);

    $data = json_decode($response->getBody(), true);
    return $data['choices'][0]['message']['content'] ?? '{}';
}
