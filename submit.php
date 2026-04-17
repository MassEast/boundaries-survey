<?php
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

$questionIds = [];
for ($i = 1; $i <= 55; $i++) {
    $questionIds[] = sprintf('q%02d', $i);
}

for ($i = 1; $i <= 55; $i++) {
    $questionIds[] = sprintf('d%02d', $i);
}

$row = [];
foreach ($questionIds as $questionId) {
    $value = $_POST[$questionId] ?? '';
    if (!is_string($value)) {
        $row[] = '';
        continue;
    }

    if (str_starts_with($questionId, 'q')) {
        $row[] = in_array($value, ['1', '2', '3', '4', '5'], true) ? $value : '';
        continue;
    }

    $row[] = in_array($value, ['0', '1'], true) ? $value : '0';
}

$csvPath = __DIR__ . '/../../../data/survey.csv';
$dirPath = dirname($csvPath);

if (!is_dir($dirPath) && !mkdir($dirPath, 0775, true) && !is_dir($dirPath)) {
    http_response_code(500);
    echo 'Unable to create data directory.';
    exit;
}

$handle = fopen($csvPath, 'c+');
if ($handle === false) {
    http_response_code(500);
    echo 'Unable to open survey data file.';
    exit;
}

$writeOk = false;

if (flock($handle, LOCK_EX)) {
    $stats = fstat($handle);
    $isNewFile = $stats !== false && (int) ($stats['size'] ?? 0) === 0;

    fseek($handle, 0, SEEK_END);

    if ($isNewFile) {
        $headerResult = fputcsv($handle, $questionIds);
        if ($headerResult === false) {
            flock($handle, LOCK_UN);
            fclose($handle);
            http_response_code(500);
            echo 'Unable to write survey header.';
            exit;
        }
    }

    $rowResult = fputcsv($handle, $row);
    $writeOk = $rowResult !== false;

    fflush($handle);
    flock($handle, LOCK_UN);
}

fclose($handle);

if (!$writeOk) {
    http_response_code(500);
    echo 'Unable to save survey response.';
    exit;
}

header('Location: thankyou.html');
exit;
