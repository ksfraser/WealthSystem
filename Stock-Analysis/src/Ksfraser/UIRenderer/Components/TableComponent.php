<?php

namespace Ksfraser\UIRenderer\Components;

use Ksfraser\UIRenderer\Contracts\ComponentInterface;

/**
 * Table Component - Renders data tables
 */
class TableComponent implements ComponentInterface {
    /** @var array */
    private $data;
    /** @var array */
    private $headers;
    /** @var array */
    private $options;
    
    public function __construct($data = [], $headers = [], $options = []) {
        $this->data = $data;
        $this->headers = $headers;
        $this->options = array_merge([
            'striped' => true,
            'hover' => true,
            'responsive' => true,
            'class' => ''
        ], $options);
    }
    
    public function toHtml() {
        if (empty($this->data)) {
            return '<p><em>No data available.</em></p>';
        }
        
        $tableClass = 'table';
        if ($this->options['striped']) $tableClass .= ' table-striped';
        if ($this->options['class']) $tableClass .= ' ' . $this->options['class'];
        
        $idAttribute = isset($this->options['id']) ? ' id="' . htmlspecialchars($this->options['id']) . '"' : '';
        
        $headerHtml = $this->renderHeaders();
        $bodyHtml = $this->renderBody();
        
        $tableHtml = "
        <table class='{$tableClass}'{$idAttribute}>
            {$headerHtml}
            <tbody>
                {$bodyHtml}
            </tbody>
        </table>";
        
        if ($this->options['responsive']) {
            return "<div class='table-container'>{$tableHtml}</div>";
        }
        
        return $tableHtml;
    }
    
    private function renderHeaders() {
        if (empty($this->headers) && !empty($this->data)) {
            $this->headers = array_keys($this->data[0]);
        }
        
        if (empty($this->headers)) {
            return '';
        }
        
        $headerCells = array_map(function($header) {
            return '<th>' . htmlspecialchars($header) . '</th>';
        }, $this->headers);
        
        return '<thead><tr>' . implode('', $headerCells) . '</tr></thead>';
    }
    
    private function renderBody() {
        $rows = [];
        foreach ($this->data as $row) {
            $cells = [];
            foreach ($this->headers ?: array_keys($row) as $key) {
                $value = isset($row[$key]) ? $row[$key] : '';
                $cells[] = '<td>' . htmlspecialchars($value) . '</td>';
            }
            $rows[] = '<tr>' . implode('', $cells) . '</tr>';
        }
        
        return implode('', $rows);
    }
}
