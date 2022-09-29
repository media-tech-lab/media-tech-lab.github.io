#!/usr/local/bin/php
<?php
class ProgressBarService {
    public $batches = [];

    public function __construct($batches) {
        $this->batches = $batches;
        if (!mkdir('build', 0755) && !is_dir('build')) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', 'build'));
        }
        if (!mkdir('build/images', 0755) && !is_dir('build/images')) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', 'build/images'));
        }
    }
    public function render()
    {
        $now = new \DateTimeImmutable();
        foreach ($this->batches as $batch) {
            if ($now < $batch['start']) {
                $progress = 0;
                $remainingDays = 0;
            } else {
                $allDays = (int)$batch['end']->diff($batch['start'])->format("%a)");
                $currentDays = (int)$now->diff($batch['start'])->format('%a');
                $remainingDays = $allDays - $currentDays;
                $progress = $currentDays / ($allDays / 100);
            }
            $this->renderProgressBarImage($batch, $progress, $remainingDays);
            echo $batch['name'] . ': ' . $progress . "\n";
        }
    }

    public function renderProgressBarImage($batch, $progress, $remainingDays) {
        $canvasWidth = 200;
        $canvasHeight = 65;
        $dpiFactor = 2;
        $canvas = imagecreatetruecolor($canvasWidth*$dpiFactor, $canvasHeight*$dpiFactor);
        imageantialias($canvas, true);

        // Allocate colors
        $green = imagecolorallocate($canvas, 4, 252, 109);
        $greenDark = imagecolorallocate($canvas, 19, 138, 62);
        $darkGrey = imagecolorallocate($canvas, 0, 34, 10);
        $white = imagecolorallocate($canvas, 255, 255, 255);

        // Set background
        imagefilledrectangle($canvas, 0, 0,  $canvasWidth*$dpiFactor, $canvasHeight*$dpiFactor, $white);
        imagerectangle($canvas, 0, 0, $canvasWidth*$dpiFactor-1, $canvasHeight*$dpiFactor-1, $green);
        $fontRegular = 'fonts/Share-Regular.ttf';
        $fontBold = 'fonts/Share-Bold.ttf';

        // Build remaining days text
        $remainingDaysText = '';
        if ($remainingDays > 0) {
            $remainingDaysText = ' - noch ' . $remainingDays . ' Tage.';
        }

        // Add header
        imagettftext($canvas, 10*$dpiFactor, 0, 10*$dpiFactor, 20*$dpiFactor, $darkGrey, $fontBold, strtoupper($batch['name']) . ' - Statusa:');
        imagettftext($canvas, 7*$dpiFactor, 0, 10*$dpiFactor, 55*$dpiFactor, $darkGrey, $fontRegular, $batch['start']->format('d.m.Y') . ' bis ' . $batch['end']->format('d.m.Y') . $remainingDaysText);

        // Draw three rectangles each with its own color
        $spacing = 0;
        $width = 10*$dpiFactor;
        $blocks = 12;
        $blockIndex = $blocks/100;
        $rectangleWith = 10;
        $rectangleIndex = $rectangleWith/100;
        for($i = 1; $i<=$blocks; $i++) {
            $blockProgress = $i / $blockIndex;
            if ($progress > $blockProgress) {
                // Paint half filled boxes
                if ((int) floor($progress * $blockIndex) === $i) {
                    $fillWidth = ceil(($progress * $blockIndex - $i) * $rectangleWith);
                    imagefilledrectangle($canvas, ($i * 10 * $dpiFactor) + $spacing, 30 * $dpiFactor, ((($i - 1) * 10 * $dpiFactor) + $fillWidth * $dpiFactor) + $spacing + $width, 40 * $dpiFactor, $green);
                } else {
                    imagefilledrectangle($canvas, ($i * 10 * $dpiFactor) + $spacing, 30 * $dpiFactor, ($i * 10 * $dpiFactor) + $spacing + $width, 40 * $dpiFactor, $green);
                }
            }
            imagerectangle($canvas, ($i * 10 *$dpiFactor) + $spacing, 30*$dpiFactor, ($i * 10*$dpiFactor) + $spacing + $width, 40*$dpiFactor, $greenDark);
            $spacing = $spacing + (3 * $dpiFactor);
        }
        imagepng($canvas, 'build/images/' . $batch['slug'] . '.png');
        imagedestroy($canvas);
    }
}
include('batches.php');
$progressBarService = new ProgressBarService($batches);
$progressBarService->render();
