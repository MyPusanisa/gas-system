<?php

namespace WPTableBuilder\Utils;

class AssetLoader
{


    private $cdn_host = '';
    private $dev_url = '';

    private $manifest = false;



    private static $preloads = '';
    private static $modules = '';

    private static $styles = [];

    public function __construct($cdn_host, $manifest, $hotfile)
    {
        $this->cdn_host = $cdn_host;
        if (file_exists($hotfile)) {
            $this->dev_url = file_get_contents($hotfile);
            self::$modules .= '<script type="module" src="' . $this->dev_url . '/@vite/client"></script>';
        } else {
            if ($manifest) {
                $this->manifest = json_decode(file_get_contents($manifest), true);
            }
        }
    }

    public static function backend($cdn_host, $manifest, $hotfile)
    {

        $assets = new AssetLoader($cdn_host, $manifest, $hotfile);
        
        if ($assets->dev_url) {
            self::$modules .= '<script type="module">
                import RefreshRuntime from "' . $assets->dev_url . '/@react-refresh"
                RefreshRuntime.injectIntoGlobalHook(window)
                window.$RefreshReg$ = () => {}
                window.$RefreshSig$ = () => (type) => type
                window.__vite_plugin_react_preamble_installed__ = true
                </script>';
        }

        return $assets;

    }

    public function register($file, $pre = false)
    {
        if (!$this->manifest) {
            self::$modules .= '<script type="module" src="' . $this->dev_url . '/' . $file . '"></script>';
            return;
        }
        if (!isset($this->manifest[$file])) {
            return;
        }
        $entry = $this->manifest[$file];
        if (isset($entry['has_loaded'])) {
            return;
        }
        $entry['has_loaded'] = true;
        if (isset($entry['imports'])) {
            foreach ($entry['imports'] as $import) {
                $this->register($import, true);
            }
        }

        $url = $this->cdn_host . '/dist/' . $entry['file'];

        if ($pre) {
            self::$preloads .= '<link rel="modulepreload" href="' . $url . '"/>';
        } else {
            self::$modules .= '<script type="module" src="' . $url . '?v=' . WPTB_VERSION . '"></script>';
        }
    }

    public function register_path($path)
    {
        self::$modules .= '<script type="module" src="' . $this->cdn_host . '/' . $path . '?v=' . WPTB_VERSION . '"></script>';
    }

    public function register_style($handle, $file)
    {
        $url = $this->dev_url . '/' . $file;
        if ($this->manifest) {
            $url = $this->cdn_host . '/dist/' . $this->manifest[$file]['file'];
        }
        wp_register_style($handle, $url, [], WPTB_VERSION);
        self::$styles[] = $handle;
    }

    public function register_style_path($handle, $file)
    {
        $url = $this->cdn_host . '/' . $file;
        wp_register_style($handle, $url, [], WPTB_VERSION);
        self::$styles[] = $handle;
    }

    public static function get_scripts()
    {
        return self::$preloads . self::$modules;
    }


    public static function enqueue_styles()
    {
        foreach (self::$styles as $handle) {
            wp_enqueue_style($handle);
        }
    }
}