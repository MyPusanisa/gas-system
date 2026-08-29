<?php

namespace WPTableBuilder\Utils;

use HTMLPurifier;
use HTMLPurifier_Config;

class RenderUtils
{
    public static function generate_css_string($styles)
    {
        $css_string = '';

        foreach ($styles as $key => $value) {
            if (trim($value) !== '') {
                $css_string .= $key . ': ' . $value . '; ';
            }
        }

        return esc_attr($css_string);
    }

    public static function generate_attrs_string($attrs)
    {
        $attrs_string = '';
        foreach ($attrs as $key => $value) {
            if ($value !== false) {
                $attrs_string .= $key . '="' . esc_attr($value) . '" ';
            }
        }
        return $attrs_string;
    }

    public static function get_icon($name)
    {
        $path = WPTB_PLUGIN_DIR . '/assets/icons/' . $name . '.svg';
        if (file_exists($path)) {
            return file_get_contents($path);
        }
        return '';
    }

    public static function esc_url($url)
    {
        if (!$url) {
            return '#';
        }
        return \esc_url($url);
    }

    public static function strip_xss($html)
    {
        if (!$html) {
            return '';
        }
    
        $config = HTMLPurifier_Config::createDefault();
    
        $config->set('HTML.Allowed', implode(',', [
            'b[class]', 'strong[class]', 'i[class]', 'em[class]', 'u[class]', 's[class]',
            'p[class|style]', 'br',
            'ul[class]', 'ol[class]', 'li[class]',
            'span[class]',
            'a[href|target|rel|class|style]', 
            'button[type|class]', 'div[class]',
            'iframe[src|width|height|frameborder|allowfullscreen|class]',
            'img[src|width|height|class]',
            'table[class]', 'caption[class]',
            'thead[class]', 'tbody[class]', 'tfoot[class]', 'tr[class]',
            'td[colspan|rowspan|class]', 'th[colspan|rowspan|scope|class]',
            'colgroup[span|class]', 'col[span|class]',
            'form[class]', 'input[type|class]', 'textarea[class]', 'select[class]', 'option[class]',
            'fieldset[class]', 'legend[class]',
            'hr[class]',
        ]));
    
        $config->set('URI.AllowedSchemes', [
            'http'   => true,
            'https'  => true,
            'mailto' => true,
            'tel'    => true,
        ]);

        $config->set('HTML.SafeIframe', true);
    
        $config->set('URI.SafeIframeRegexp', 
            '#^https://(www\.)?(youtube\.com/embed/|youtube-nocookie\.com/embed/)#'
        );
        
        $config->set('CSS.AllowedProperties', ['text-align', 'font-size', 'color']);

        $config->set('HTML.TargetBlank', true);

        $config->set('HTML.Forms', true);

        $cache_path = __DIR__ . '/htmlpurifier-cache';

        if (!file_exists($cache_path)) {
            @mkdir($cache_path, 0755, true);
        }

        if (!is_dir($cache_path) || !is_writable($cache_path)) {
            $cache_path = sys_get_temp_dir() . '/wptb-htmlpurifier-cache';
        }


        $config->set('Cache.SerializerPath', $cache_path);

        $config->set('HTML.DefinitionID', 'wptb-custom');
        $config->set('HTML.DefinitionRev', 1);

        if ($def = $config->maybeGetRawHTMLDefinition()) {
            $def->addElement('button', 'Inline', 'Inline', 'Common', [
                'type' => 'Enum#button,submit,reset',
            ]);
        }
    
        $purifier = new HTMLPurifier($config);
    
        return $purifier->purify($html);
    }



}