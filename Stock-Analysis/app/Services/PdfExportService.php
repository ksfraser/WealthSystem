<?php

namespace App\Services;

use Mpdf\Mpdf;
use Mpdf\MpdfException;
use DateTime;

/**
 * PDF Export Service
 * 
 * Generates PDF reports for portfolio analysis using mPDF library
 * 
 * @package App\Services
 */
class PdfExportService
{
    /**
     * Generate sector analysis PDF report
     * 
     * @param int $userId User ID
     * @param array $sectorData Sector analysis data
     * @param array $chartImages Optional chart images (base64 encoded)
     * @return array PDF file data
     * @throws MpdfException
     */
    public function generateSectorAnalysisPdf(int $userId, array $sectorData, array $chartImages = []): array
    {
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'Letter',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 20,
            'margin_bottom' => 20,
            'margin_header' => 10,
            'margin_footer' => 10,
        ]);
        
        // Set document metadata
        $mpdf->SetTitle('Sector Analysis Report');
        $mpdf->SetAuthor('Portfolio Analytics System');
        $mpdf->SetCreator('Trading System');
        
        // Set header and footer
        $mpdf->SetHeader('Sector Analysis Report|User ID: ' . $userId . '|{DATE j-m-Y}');
        $mpdf->SetFooter('Page {PAGENO} of {nb}');
        
        // Build HTML content
        $html = $this->buildSectorAnalysisHtml($sectorData, $chartImages);
        
        // Write HTML to PDF
        $mpdf->WriteHTML($html);
        
        // Generate filename
        $filename = 'sector_analysis_user' . $userId . '_' . date('Ymd_His') . '.pdf';
        
        return [
            'filename' => $filename,
            'content' => $mpdf->Output('', 'S'), // Return as string
            'mime_type' => 'application/pdf',
            'generation_date' => date('Y-m-d H:i:s'),
            'has_header' => true,
            'has_footer' => true,
            'includes_charts' => !empty($chartImages),
        ];
    }
    
    /**
     * Generate index benchmark PDF report
     * 
     * @param int $userId User ID
     * @param array $benchmarkData Index benchmark data
     * @param array $chartImages Optional chart images
     * @return array PDF file data
     * @throws MpdfException
     */
    public function generateIndexBenchmarkPdf(int $userId, array $benchmarkData, array $chartImages = []): array
    {
        $mpdf = new Mpdf(['format' => 'Letter']);
        
        $mpdf->SetTitle('Index Benchmark Report');
        $mpdf->SetHeader('Index Benchmark Report|User ID: ' . $userId . '|{DATE j-m-Y}');
        $mpdf->SetFooter('Page {PAGENO} of {nb}');
        
        $html = $this->buildIndexBenchmarkHtml($benchmarkData, $chartImages);
        $mpdf->WriteHTML($html);
        
        $filename = 'index_benchmark_user' . $userId . '_' . date('Ymd_His') . '.pdf';
        
        return [
            'filename' => $filename,
            'content' => $mpdf->Output('', 'S'),
            'mime_type' => 'application/pdf',
            'generation_date' => date('Y-m-d H:i:s'),
            'has_header' => true,
            'has_footer' => true,
            'includes_charts' => !empty($chartImages),
        ];
    }
    
    /**
     * Generate advanced charts PDF report
     * 
     * @param int $userId User ID
     * @param array $chartData Chart data
     * @param array $chartImages Chart images
     * @return array PDF file data
     * @throws MpdfException
     */
    public function generateAdvancedChartsPdf(int $userId, array $chartData, array $chartImages = []): array
    {
        $mpdf = new Mpdf(['format' => 'Letter', 'orientation' => 'L']); // Landscape for charts
        
        $mpdf->SetTitle('Advanced Charts Report');
        $mpdf->SetHeader('Advanced Charts Report|User ID: ' . $userId . '|{DATE j-m-Y}');
        $mpdf->SetFooter('Page {PAGENO} of {nb}');
        
        $html = $this->buildAdvancedChartsHtml($chartData, $chartImages);
        $mpdf->WriteHTML($html);
        
        $filename = 'advanced_charts_user' . $userId . '_' . date('Ymd_His') . '.pdf';
        
        return [
            'filename' => $filename,
            'content' => $mpdf->Output('', 'S'),
            'mime_type' => 'application/pdf',
            'generation_date' => date('Y-m-d H:i:s'),
            'has_header' => true,
            'has_footer' => true,
            'includes_charts' => !empty($chartImages),
        ];
    }
    
    /**
     * Build HTML for sector analysis report
     * 
     * @param array $sectorData Sector data
     * @param array $chartImages Chart images
     * @return string HTML content
     */
    private function buildSectorAnalysisHtml(array $sectorData, array $chartImages): string
    {
        $html = '<html><head><style>
            body { font-family: Arial, sans-serif; }
            h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
            h2 { color: #34495e; margin-top: 30px; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th { background-color: #3498db; color: white; padding: 10px; text-align: left; }
            td { border: 1px solid #ddd; padding: 10px; }
            tr:nth-child(even) { background-color: #f2f2f2; }
            .metric { font-weight: bold; color: #2980b9; }
            .positive { color: #27ae60; }
            .negative { color: #e74c3c; }
            .chart-img { max-width: 100%; height: auto; margin: 20px 0; }
        </style></head><body>';
        
        $html .= '<h1>Sector Analysis Report</h1>';
        $html .= '<p><strong>Generated:</strong> ' . date('F j, Y g:i A') . '</p>';
        
        if (empty($sectorData)) {
            $html .= '<p><em>No sector data available for this report.</em></p>';
        } else {
            $html .= '<h2>Sector Allocation & Performance</h2>';
            $html .= '<table>';
            $html .= '<tr><th>Sector</th><th>Weight (%)</th><th>Return (%)</th><th>Volatility (%)</th><th>Sharpe Ratio</th></tr>';
            
            foreach ($sectorData as $sector => $data) {
                $returnClass = ($data['return'] ?? 0) >= 0 ? 'positive' : 'negative';
                $html .= '<tr>';
                $html .= '<td class="metric">' . htmlspecialchars($sector) . '</td>';
                $html .= '<td>' . number_format($data['weight'] ?? 0, 2) . '%</td>';
                $html .= '<td class="' . $returnClass . '">' . number_format($data['return'] ?? 0, 2) . '%</td>';
                $html .= '<td>' . number_format($data['volatility'] ?? 0, 2) . '%</td>';
                $html .= '<td>' . number_format($data['sharpe'] ?? 0, 2) . '</td>';
                $html .= '</tr>';
            }
            
            $html .= '</table>';
        }
        
        // Add chart images if provided
        if (!empty($chartImages)) {
            $html .= '<h2>Visualizations</h2>';
            foreach ($chartImages as $chartName => $imageData) {
                $html .= '<h3>' . ucwords(str_replace('_', ' ', $chartName)) . '</h3>';
                $html .= '<img src="' . $imageData . '" class="chart-img" />';
            }
        }
        
        $html .= '</body></html>';
        
        return $html;
    }
    
    /**
     * Build HTML for index benchmark report
     * 
     * @param array $benchmarkData Benchmark data
     * @param array $chartImages Chart images
     * @return string HTML content
     */
    private function buildIndexBenchmarkHtml(array $benchmarkData, array $chartImages): string
    {
        $html = '<html><head><style>
            body { font-family: Arial, sans-serif; }
            h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
            h2 { color: #34495e; margin-top: 30px; }
            .metrics-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0; }
            .metric-box { border: 2px solid #3498db; padding: 15px; border-radius: 5px; }
            .metric-label { font-size: 14px; color: #7f8c8d; }
            .metric-value { font-size: 24px; font-weight: bold; color: #2c3e50; margin-top: 5px; }
            .positive { color: #27ae60; }
            .negative { color: #e74c3c; }
            .chart-img { max-width: 100%; height: auto; margin: 20px 0; }
        </style></head><body>';
        
        $html .= '<h1>Index Benchmark Report</h1>';
        $html .= '<p><strong>Generated:</strong> ' . date('F j, Y g:i A') . '</p>';
        
        if (empty($benchmarkData)) {
            $html .= '<p><em>No benchmark data available for this report.</em></p>';
        } else {
            $html .= '<h2>Performance Metrics</h2>';
            $html .= '<div class="metrics-grid">';
            
            $metrics = [
                'portfolio_return' => 'Portfolio Return',
                'sp500_return' => 'S&P 500 Return',
                'outperformance' => 'Outperformance',
                'alpha' => 'Alpha',
                'beta' => 'Beta',
                'tracking_error' => 'Tracking Error',
                'information_ratio' => 'Information Ratio',
            ];
            
            foreach ($metrics as $key => $label) {
                if (isset($benchmarkData[$key])) {
                    $value = $benchmarkData[$key];
                    $valueClass = (in_array($key, ['portfolio_return', 'outperformance', 'alpha']) && $value > 0) ? 'positive' : '';
                    $valueClass = $valueClass ?: ((in_array($key, ['portfolio_return', 'outperformance', 'alpha']) && $value < 0) ? 'negative' : '');
                    
                    $html .= '<div class="metric-box">';
                    $html .= '<div class="metric-label">' . $label . '</div>';
                    $html .= '<div class="metric-value ' . $valueClass . '">' . number_format($value, 2) . '%</div>';
                    $html .= '</div>';
                }
            }
            
            $html .= '</div>';
        }
        
        // Add chart images
        if (!empty($chartImages)) {
            $html .= '<h2>Visualizations</h2>';
            foreach ($chartImages as $chartName => $imageData) {
                $html .= '<h3>' . ucwords(str_replace('_', ' ', $chartName)) . '</h3>';
                $html .= '<img src="' . $imageData . '" class="chart-img" />';
            }
        }
        
        $html .= '</body></html>';
        
        return $html;
    }
    
    /**
     * Build HTML for advanced charts report
     * 
     * @param array $chartData Chart data
     * @param array $chartImages Chart images
     * @return string HTML content
     */
    private function buildAdvancedChartsHtml(array $chartData, array $chartImages): string
    {
        $html = '<html><head><style>
            body { font-family: Arial, sans-serif; }
            h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
            h2 { color: #34495e; margin-top: 30px; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 12px; }
            th { background-color: #3498db; color: white; padding: 8px; text-align: left; }
            td { border: 1px solid #ddd; padding: 8px; }
            .chart-img { max-width: 100%; height: auto; margin: 20px 0; }
        </style></head><body>';
        
        $html .= '<h1>Advanced Charts Report</h1>';
        $html .= '<p><strong>Generated:</strong> ' . date('F j, Y g:i A') . '</p>';
        
        // Correlation data
        if (!empty($chartData['correlation'])) {
            $html .= '<h2>Sector Correlation Matrix</h2>';
            $html .= '<table>';
            $html .= '<tr><th>Sector 1</th><th>Sector 2</th><th>Correlation</th></tr>';
            foreach ($chartData['correlation'] as $row) {
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($row[0]) . '</td>';
                $html .= '<td>' . htmlspecialchars($row[1]) . '</td>';
                $html .= '<td>' . number_format($row[2], 3) . '</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
        }
        
        // Concentration data
        if (!empty($chartData['concentration'])) {
            $html .= '<h2>Portfolio Concentration (HHI)</h2>';
            $html .= '<table>';
            $html .= '<tr><th>Date</th><th>HHI Value</th><th>Risk Level</th></tr>';
            foreach ($chartData['concentration'] as $date => $hhi) {
                $riskLevel = $hhi < 1500 ? 'Low' : ($hhi < 2500 ? 'Moderate' : 'High');
                $html .= '<tr>';
                $html .= '<td>' . $date . '</td>';
                $html .= '<td>' . number_format($hhi, 0) . '</td>';
                $html .= '<td>' . $riskLevel . '</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
        }
        
        // Add chart images
        if (!empty($chartImages)) {
            $html .= '<h2>Visualizations</h2>';
            foreach ($chartImages as $chartName => $imageData) {
                $html .= '<h3>' . ucwords(str_replace('_', ' ', $chartName)) . '</h3>';
                $html .= '<img src="' . $imageData . '" class="chart-img" />';
            }
        }
        
        $html .= '</body></html>';
        
        return $html;
    }
}
