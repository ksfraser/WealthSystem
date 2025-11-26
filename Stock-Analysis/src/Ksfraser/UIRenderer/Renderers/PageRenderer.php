<?php

namespace Ksfraser\UIRenderer\Renderers;

use Ksfraser\UIRenderer\Contracts\RendererInterface;
use Ksfraser\UIRenderer\Contracts\ComponentInterface;
use Ksfraser\UIRenderer\Providers\CssProvider;

/**
 * Page Renderer - Renders complete HTML pages
 * Follows Open/Closed Principle for extensibility
 */
class PageRenderer implements RendererInterface {
    /** @var string */
    private $title;
    /** @var ComponentInterface */
    private $navigation;
    /** @var array */
    private $components;
    /** @var array */
    private $options;
    
    public function __construct($title, ComponentInterface $navigation, $components = [], $options = []) {
        $this->title = $title;
        $this->navigation = $navigation;
        $this->components = $components;
        $this->options = array_merge([
            'theme' => 'default',
            'lang' => 'en',
            'dir' => 'ltr', // RTL support
            'charset' => 'UTF-8',
            'viewport' => 'width=device-width, initial-scale=1.0',
            'meta' => [],
            'additionalCss' => '',
            'additionalJs' => ''
        ], $options);
    }
    
    public function render() {
        $metaTags = $this->renderMetaTags();
        $styles = $this->renderStyles();
        $navigationHtml = $this->navigation->toHtml();
        $contentHtml = $this->renderComponents();
        $scripts = $this->renderScripts();
        
        $lang = isset($this->options['lang']) ? htmlspecialchars($this->options['lang'], ENT_QUOTES, 'UTF-8') : 'en';
        $dir = isset($this->options['dir']) ? htmlspecialchars($this->options['dir'], ENT_QUOTES, 'UTF-8') : 'ltr';
        return "<!DOCTYPE html>
<html lang='{$lang}' dir='{$dir}'>
<head>
    <meta charset='{$this->options['charset']}'>
    <meta name='viewport' content='{$this->options['viewport']}'>
    <title>{$this->title}</title>
    {$metaTags}
    {$styles}
</head>
<body>
    {$navigationHtml}
    <div class='container' role='main'>
        {$contentHtml}
    </div>
    {$scripts}
</body>
</html>";
    }
    
    private function renderMetaTags() {
        $meta = [];
        foreach ($this->options['meta'] as $name => $content) {
            $meta[] = "<meta name='{$name}' content='{$content}'>";
        }
        return implode("\n    ", $meta);
    }
    
    private function renderStyles() {
        $css = CssProvider::getThemeStyles($this->options['theme']);
        $additionalCss = $this->options['additionalCss'];
        
        return "<style>\n{$css}\n{$additionalCss}\n</style>";
    }
    
    private function renderComponents() {
        if (empty($this->components)) {
            return '<div class="card info"><h3>Welcome</h3><p>No content available.</p></div>';
        }
        
        $html = [];
        foreach ($this->components as $component) {
            if ($component instanceof ComponentInterface) {
                $html[] = $component->toHtml();
            } elseif (is_string($component)) {
                $html[] = $component;
            }
        }
        
        return implode("\n", $html);
    }
    
    private function renderScripts() {
        $additionalJs = $this->options['additionalJs'];
        
        if (empty($additionalJs)) {
            return '';
        }
        
        return "<script>\n{$additionalJs}\n</script>";
    }
}
