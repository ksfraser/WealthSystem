<?php
// src/CLI/ExportGraphCommand.php
// Symfony Console command for technical analysis graph export

namespace Ksfraser\CLI;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportGraphCommand extends Command
{
    protected static $defaultName = 'export:graph';

    protected function configure()
    {
        $this
            ->setDescription('Export technical analysis graph with multiple indicators (PNG/SVG)')
            ->addOption('input', null, InputOption::VALUE_REQUIRED, 'Input CSV file with data')
            ->addOption('indicators', null, InputOption::VALUE_REQUIRED, 'Comma-separated list of indicators to plot')
            ->addOption('output', null, InputOption::VALUE_REQUIRED, 'Output PNG/SVG file')
            ->addOption('format', null, InputOption::VALUE_OPTIONAL, 'Output format: png or svg', 'png');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputFile = $input->getOption('input');
        $indicators = $input->getOption('indicators');
        $outputFile = $input->getOption('output');
        $format = $input->getOption('format');

        if (!$inputFile || !$indicators || !$outputFile) {
            $output->writeln('<error>Missing required options. Use --help for usage.</error>');
            return Command::FAILURE;
        }

        $data = $this->readCsv($inputFile);
        $indicatorsArr = array_map('trim', explode(',', $indicators));
        $this->plotGraph($data, $indicatorsArr, $outputFile, $format);
        $output->writeln("Graph exported to $outputFile");
        return Command::SUCCESS;
    }

    private function readCsv($file)
    {
        $rows = [];
        if (($h = fopen($file, 'r')) !== false) {
            $header = fgetcsv($h);
            while (($row = fgetcsv($h)) !== false) {
                $rows[] = array_combine($header, $row);
            }
            fclose($h);
        }
        return $rows;
    }

    private function scaleSeries($values)
    {
        $min = min($values);
        $max = max($values);
        if ($max == $min) {
            return array_fill(0, count($values), 0.5);
        }
        $scaled = [];
        foreach ($values as $v) {
            $scaled[] = 0.2 + 0.6 * (($v - $min) / ($max - $min));
        }
        return $scaled;
    }

    private function plotGraph($data, $indicators, $output, $format = 'png')
    {
        $width = 1000;
        $height = 600;
        $margin = 80;
        $img = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($img, 255,255,255);
        $black = imagecolorallocate($img, 0,0,0);
        $colors = [
            imagecolorallocate($img, 52, 152, 219), // blue
            imagecolorallocate($img, 231, 76, 60), // red
            imagecolorallocate($img, 46, 204, 113), // green
            imagecolorallocate($img, 155, 89, 182), // purple
            imagecolorallocate($img, 241, 196, 15), // yellow
            imagecolorallocate($img, 230, 126, 34), // orange
        ];
        imagefilledrectangle($img, 0, 0, $width, $height, $white);

        // Axes
        imageline($img, $margin, $margin, $margin, $height-$margin, $black);
        imageline($img, $margin, $height-$margin, $width-$margin, $height-$margin, $black);

        // Dates
        $dates = array_column($data, 'Date');
        $n = count($dates);
        if ($n < 2) {
            throw new \RuntimeException('Not enough data to plot.');
        }
        // X scale
        $xstep = ($width - 2*$margin) / ($n-1);
        $xvals = [];
        for ($i=0; $i<$n; $i++) {
            $xvals[] = $margin + $i*$xstep;
        }

        // Plot each indicator
        foreach ($indicators as $idx => $ind) {
            $vals = array_map(function($row) use ($ind) { return isset($row[$ind]) ? floatval($row[$ind]) : null; }, $data);
            if (in_array(null, $vals, true)) continue; // skip if missing data
            $scaled = $this->scaleSeries($vals);
            $color = $colors[$idx % count($colors)];
            // Draw line
            for ($i=1; $i<$n; $i++) {
                $y1 = $margin + (1.0 - $scaled[$i-1]) * ($height - 2*$margin);
                $y2 = $margin + (1.0 - $scaled[$i]) * ($height - 2*$margin);
                imageline($img, $xvals[$i-1], $y1, $xvals[$i], $y2, $color);
            }
            // Label
            imagestring($img, 5, $width-$margin+10, $margin+20*$idx, $ind, $color);
        }
        // Optionally plot Close price as black line
        if (isset($data[0]['Close'])) {
            $vals = array_map(function($row) { return floatval($row['Close']); }, $data);
            $scaled = $this->scaleSeries($vals);
            for ($i=1; $i<$n; $i++) {
                $y1 = $margin + (1.0 - $scaled[$i-1]) * ($height - 2*$margin);
                $y2 = $margin + (1.0 - $scaled[$i]) * ($height - 2*$margin);
                imageline($img, $xvals[$i-1], $y1, $xvals[$i], $y2, $black);
            }
            imagestring($img, 5, $width-$margin+10, $margin+20*count($indicators), 'Close', $black);
        }
        // Title
        imagestring($img, 5, $width/2-100, 20, 'Technical Analysis Indicators (Scaled)', $black);
        // Save
        if ($format === 'png') {
            imagepng($img, $output);
        } elseif ($format === 'svg' && function_exists('imagegd2')) {
            imagepng($img, $output); // fallback
        } else {
            imagepng($img, $output);
        }
        imagedestroy($img);
    }
}
