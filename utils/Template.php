<?php

namespace SurveyrWP\Utils;

use SurveyrWP\SurveyrWP;
use League\Plates\Engine;

final class Template
{
    private static ?Engine $engine = null;

    private const CACHE_PATH = SurveyrWP::PLUGIN_DIR . '/templates/cache/';
    private const VIEWS_PATH = SurveyrWP::PLUGIN_DIR . '/templates/views/';

    /**
     * Initializes the template engine.
     */
    public static function initialize(): void
    {
        if (self::$engine === null) {
            if (!is_dir(self::VIEWS_PATH)) {
                throw new \RuntimeException('Views directory does not exist: ' . self::VIEWS_PATH);
            }

            self::$engine = new Engine(self::VIEWS_PATH);
            self::$engine->setFileExtension('tpl');
        }
    }

    /**
     * Renders a view and echoes the output.
     *
     * @param string $view The view name to render.
     * @param array $data Optional data to pass to the view.
     */
    public static function render(string $view, array $data = []): void
    {
        self::initialize();
        echo self::$engine->render($view, $data);
    }

    /**
     * Renders a view and returns the output.
     *
     * @param string $view The view name to render.
     * @param array $data Optional data to pass to the view.
     * @return string Rendered output.
     */
    public static function view(string $view, array $data = []): string
    {
        self::initialize();
        return self::$engine->render($view, $data);
    }
}