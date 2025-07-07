<?php

function normalizeDegree($str) {
    $str = strtolower($str);
    $map = [
        'bachelor of technology' => 'btech',
        'b.tech' => 'btech',
        'btech' => 'btech',
        'b.tech.' => 'btech',
        'b.tech- computer science and engineering' => 'btech in cs',
        'bachelor of engineering' => 'be',
        'b.e.' => 'be',
        'be' => 'be',
        'bachelor of computer applications' => 'bca',
        'b.c.a' => 'bca',
        'bca' => 'bca',
        'bachelor of science' => 'bsc',
        'b.sc.' => 'bsc',
        'bachelor of commerce' => 'bcom',
        'b.com' => 'bcom',
        'bachelor of arts' => 'ba',
        'b.a.' => 'ba',
        'bachelor of business administration' => 'bba',
        'b.b.a.' => 'bba',
        'hotel management' => 'hm',
        'bachelor of management studies' => 'bms',
        'bms' => 'bms',
        'bachelor of financial & investment analysis' => 'bfia',
        'bachelor of design' => 'bdes',
        'b.des' => 'bdes',
        'bachelor of architecture' => 'barch',
        'b.arch' => 'barch',

        'master of computer applications' => 'mca',
        'm.c.a' => 'mca',
        'mca' => 'mca',
        'master of technology' => 'mtech',
        'm.tech' => 'mtech',
        'mtech' => 'mtech',
        'm.tech.' => 'mtech',
        'master of business administration' => 'mba',
        'm.b.a' => 'mba',
        'mba' => 'mba',
        'master of science' => 'msc',
        'm.sc.' => 'msc',
        'msc' => 'msc',
        'master of arts' => 'ma',
        'm.a.' => 'ma',
        'ma' => 'ma',
        'master of commerce' => 'mcom',
        'm.com' => 'mcom',
        'mcom' => 'mcom',
        'master of design' => 'mdes',
        'm.des' => 'mdes',
    ];
    foreach ($map as $key => $val) {
        if (str_contains($str, $key)) return $val;
    }
    return preg_replace('/[^a-z]/', '', $str); // fallback normalization
}

function calculateExperienceYears($experienceEntries) {
    $totalMonths = 0;

    foreach ($experienceEntries as $exp) {
        $start = DateTime::createFromFormat('m Y', $exp['start'] ?? '');
        $end = DateTime::createFromFormat('m Y', $exp['end'] ?? '') ?: new DateTime(); // handle "Present"

        if ($start && $end && $start <= $end) {
            $interval = $start->diff($end);
            $months = ($interval->y * 12) + $interval->m;
            $totalMonths += $months;
        }
    }

    return round($totalMonths / 12, 2); // return total experience in years
}

function passesHardFilters($resume, $jd) {
    // Normalize degrees
    $resumeDegrees = array_map(
        fn($edu) => normalizeDegree($edu['degree'] ?? ''), 
        $resume['education'] ?? []
    );
    $jdDegrees = array_map(
        fn($q) => normalizeDegree($q['label'] ?? ''), 
        $jd['profile_data']['qualifications'] ?? []
    );

    // Degree match check
    $eduOk = false;
    foreach ($resumeDegrees as $rdeg) {
        foreach ($jdDegrees as $jdeg) {
            if ($rdeg === $jdeg) {
                $eduOk = true;
                break;
            }
        }
        if ($eduOk) break;
    }

    // Experience check from date ranges
    $resumeExp = calculateExperienceYears($resume['experience'] ?? []);
    $jdExp = (float)($jd['profile_data']['experience'] ?? 0);
    $expOk = $resumeExp >= $jdExp;

    // Certification check
    $resumeCerts = array_map('strtolower', $resume['certifications'] ?? []);
    $requiredCerts = array_map(
        fn($cert) => strtolower($cert['label'] ?? ''), 
        $jd['profile_data']['certifications'] ?? []
    );

    $certOk = true;
    foreach ($requiredCerts as $req) {
        if (!in_array($req, $resumeCerts)) {
            $certOk = false;
            break;
        }
    }

    // Final hard filter check
    return $eduOk && $expOk && $certOk;
}
