<?php
require 'gpt_service.php';
require 'hard_filters.php';

$OPENAI_API_KEY = getenv('OPENAI_API_KEY');

if ($_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
    die("Upload error");
}
$resumeJson = json_decode(file_get_contents($_FILES['resume']['tmp_name']), true);

$jds = json_decode(file_get_contents('jds.json'), true);

$filteredJDs = [];
foreach ($jds as $jd) {
    if (passesHardFilters($resumeJson, $jd)) {
        $filteredJDs[] = $jd;
    }
}

$matches = [];
foreach ($filteredJDs as $jd) {
    $gptResponse = matchWithGPT($resumeJson, $jd, $OPENAI_API_KEY);
    $parsed = json_decode($gptResponse, true);
    if ($parsed && $parsed['score'] >= 70) {
        $jd['score'] = $parsed['score'];
        $jd['reason'] = $parsed['reason'];
        $matches[] = $jd;
    }
}

usort($matches, fn($a, $b) => $b['score'] <=> $a['score']);

echo "<h2>Top Matching Jobs</h2>";
foreach ($matches as $match) {
    echo "<h3>{$match['title']} (Score: {$match['score']})</h3>";
    echo "<p>{$match['reason']}</p>";
    echo "<hr>";
}
