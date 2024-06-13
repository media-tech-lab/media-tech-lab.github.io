<?php
require 'vendor/autoload.php';

$projectKeys = [
    'mtl' => [
      'title' => 'Media Tech Lab',
      'url' => 'https://media-tech-lab.com'
      ],
    'promptmage' => [
       'title' => 'PromptMage',
       'url' => 'https://github.com/tsterbak/promptmage'
    ],
    'pulsespotter' => [
        'title' => 'PulseSpotter',
        'url' => 'https://github.com/levrone1987/PulseSpotter'
    ],
    'redaktool' => [
        'title' => 'Redaktool',
        'url' => 'https://github.com/kyr0/redaktool'
    ],
    'tinqta' => [
        'title' => 'tinqta',
        'url' => 'https://github.com/bleeptrack/tinqta'
    ],
];

// Generate changelog
$files = glob('content/**/*.md', GLOB_BRACE);
$files = array_reverse($files);
$parsedown = new Parsedown();

$htmlContent = '';

foreach ($files as $file) {
    $htmlContent .= '<hr>';
    $markdownContent = file_get_contents($file);

    // Get the parent directory name and parse it into a DateTime object
    $dirName = basename(dirname($file));
    list ($dirName, $projectKey) = explode('-', $dirName, 2);
    $date = DateTime::createFromFormat('YmdHi', $dirName);

    // Format the DateTime object to a human-readable format
    $formattedDate = date_format($date, 'd.m.Y H:i');

    // Prepend the formatted date to the content
    $content = $parsedown->text($markdownContent);
    $content = str_replace('<img src="', '<img src="' . $dirName .'-'. $projectKey . '/', $content);
    $htmlContent .= '<div class="changelog-entry">   
            <a href="' . $projectKeys[$projectKey]['url'] . '" target="_blank" class="project">' . $projectKeys[$projectKey]['title'] . '</a> - 
            <span class="date">' . $formattedDate . '</span>' .
        $content.'</div>';
}

$layout = file_get_contents('resources/layout/changelog.html');
$finalHtml = str_replace('###CONTENT###', $htmlContent, $layout);

file_put_contents('build/changelog/index.html', $finalHtml);

// Copy CSS
$source = 'resources/css/style.css';
$destination = 'build/changelog/css/style.css';
if (!is_dir(dirname($destination))) {
    if (!mkdir($concurrentDirectory = dirname($destination), 0777, true) && !is_dir($concurrentDirectory)) {
        throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
    }
}

if (!copy($source, $destination)) {
    echo "failed to copy $source...\n";
}

// Copy images
$imageFiles = glob('content/**/*.{jpg,png,webp}', GLOB_BRACE);

foreach ($imageFiles as $source) {
    $destination = str_replace('content', 'build/changelog', $source);

    if (!is_dir(dirname($destination))) {
        if (!mkdir($concurrentDirectory = dirname($destination), 0777, true) && !is_dir($concurrentDirectory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
    }

    if (!copy($source, $destination)) {
        echo "failed to copy $source...\n";
    }
}