<?php
set_time_limit(120);
require 'gpt_service.php';
require 'hard_filters.php';

$OPENAI_API_KEY = 'OPEN_API_KEY';

if ($_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
    die("Upload error");
}

$resumeJson = json_decode(file_get_contents($_FILES['resume']['tmp_name']), true);

// Parse nested JSON fields in JDs
$jdsRaw = json_decode(file_get_contents(__DIR__ . '/jds.json'), true);
$jds = [];

foreach ($jdsRaw as $item) {
    $item['basic_data'] = json_decode($item['basic_data'], true);
    $item['profile_data'] = json_decode($item['profile_data'], true);
    $item['misc_data'] = json_decode($item['misc_data'], true);
    $jds[] = $item;
}

// Hard filters
$filteredJDs = array_filter($jds, fn($jd) => passesHardFilters($resumeJson, $jd));

echo "<h2>Top Matching Jobs</h2>";
echo "<p>Total JDs loaded: " . count($jds) . "</p>";
echo "<p>JDs after hard filtering: " . count($filteredJDs) . "</p>";

$matchingJobs = [];

foreach ($filteredJDs as $jd) {
    $gptResponse = matchWithGPT($resumeJson, $jd, $OPENAI_API_KEY);
    //echo "<pre>GPT Response Raw:\n$gptResponse</pre>"; // Debug output

    $parsed = json_decode($gptResponse, true);
    $score = $parsed['score'] ?? null;

    if ($score !== null && $score > 40) {
        $jd['score'] = $score;
        $jd['reason'] = $parsed['reason'] ?? 'No reason provided.';
        $matchingJobs[] = $jd;
    }
}

// Sort by score in DESCENDING order
usort($matchingJobs, fn($a, $b) => $b['score'] <=> $a['score']);

foreach ($matchingJobs as $jd) {
    $id = $jd['id'] ?? 'N/A';
    $title = $jd['basic_data']['job_title'] ?? $jd['profile_name'];
    $company = $jd['misc_data']['company_name'] ?? 'Unknown Company';
    $location = $jd['basic_data']['location'] ?? 'Not Mentioned';
    $experience = $jd['profile_data']['experience'] ?? 'Not specified';
    $aboutRole = $jd['basic_data']['about_role'] ?? 'No role description.';
    $jdUrl = $jd['misc_data']['jd_source_URL'] ?? '#';
    $score = $jd['score'];
    $reason = $jd['reason'];

    echo "<div style='border:1px solid #ccc; padding:10px; margin-bottom:15px;'>";
    echo "<h3>[{$id}] {$title} at {$company}</h3>";
    echo "<p><strong>Location:</strong> {$location}</p>";
    echo "<p><strong>Score:</strong> {$score}</p>";
    echo "<p><strong>Experience:</strong> {$experience}</p>";
    echo "<p><strong>Reason:</strong> {$reason}</p>";
    echo "<p><strong>About Role:</strong> {$aboutRole}</p>";
    echo "<p><a href='{$jdUrl}' target='_blank'>View Job Posting</a></p>";
    echo "</div>";
}
