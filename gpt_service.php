<?php
require 'vendor/autoload.php';
use GuzzleHttp\Client;

function matchWithGPT($resume, $jd, $apiKey) {
    // Safely decode basic_data
    $jdBasicData = $jd['basic_data'];
    if (is_string($jdBasicData)) {
        $jdBasicData = json_decode($jdBasicData, true);
    }

    $jdText = json_encode($jdBasicData, JSON_PRETTY_PRINT);

    $prompt = "Compare the candidate's resume against the job description and score it from 0 to 100 based on the following internal weighted criteria:

    - Experience (40%): Count only the years of experience that are relevant to the job role. For example, if the JD asks for 2 years of Software Engineering experience, do not count unrelated experience like sales or teaching.
    - Skills (35%): Match both technical and soft skills required by the JD. Consider exact matches and strong equivalents.
    - Projects (23%): Evaluate how well the resume projects align with the job role and key responsibilities.(e.g., same domain, tools, responsibilities).
    - Certifications + Education (2%): Consider only if they are relevant to the role.

    Your task:
    1. Carefully evaluate how well the candidate's **relevant experience**, **skills**, **projects**, **certifications**, and **education** match the job description.
    2. Internally assign weights to each category as described and compute a final score out of 100.
    3. Do not give credit for unrelated experience, skills, or projects. Relevance is essential, especially for the experience section.
    4. Explain the score clearly, highlighting strengths and gaps, especially in experience, skills, and projects.

    Input:
    Resume:
    " . json_encode($resume, JSON_PRETTY_PRINT) . "

    Job Description:
    " . $jdText . "

    Return only this JSON format:
    {
      \"score\": number,
      \"reason\": \"string explanation of the score and the key matching points\"
    }";


    $client = new Client([
        'base_uri' => 'https://api.openai.com/v1/',
        'verify' => __DIR__ . '/cacert.pem',
    ]);

    $response = $client->post('chat/completions', [
        'headers' => [
            'Authorization' => "Bearer $apiKey",
            'Content-Type'  => 'application/json',
        ],
        'json' => [
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a professional resume screening assistant.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.3,
        ],
    ]);

    $data = json_decode($response->getBody(), true);
    return $data['choices'][0]['message']['content'] ?? '{}';
}
