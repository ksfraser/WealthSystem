<?php
use PHPUnit\Framework\TestCase;

class TradeLogDAOApiTest extends TestCase {
    private $baseRestUrl = 'http://localhost:8000/web_ui/portfolio_rest_api.php';
    private $baseSoapUrl = 'http://localhost:8000/web_ui/portfolio_soap_service.php';

    public function testRestTradeLogRead() {
        $type = 'micro';
        $url = $this->baseRestUrl . '?action=readTradeLog&type=' . urlencode($type);
        $response = file_get_contents($url);
        $this->assertNotFalse($response, 'REST API did not return a response');
        $data = json_decode($response, true);
        $this->assertIsArray($data, 'REST API did not return valid JSON');
    $this->assertArrayHasKey('data', $data, 'REST API missing data key');
    }

    public function testSoapTradeLogRead() {
        $client = new SoapClient(null, [
            'location' => $this->baseSoapUrl,
            'uri' => 'http://localhost/web_ui/portfolio_soap_service',
            'trace' => 1,
            'exceptions' => 1
        ]);
        $result = $client->__soapCall('readTradeLog', ['micro']);
        $this->assertIsArray($result, 'SOAP API did not return an array');
    }
}
