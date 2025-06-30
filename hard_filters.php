<?php
function passesHardFilters($resume, $jd) {
    $eduOk = stripos($resume['education'], $jd['required_education']) !== false;
    $expOk = (int)$resume['experience_years'] >= (int)$jd['min_experience'];
    $certOk = empty($jd['required_cert']) || in_array($jd['required_cert'], $resume['certifications']);
    return $eduOk && $expOk && $certOk;
}
