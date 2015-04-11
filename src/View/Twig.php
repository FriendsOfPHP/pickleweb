<?php

/**
 * Slim - a micro PHP 5 framework.
 *
 * @author      Josh Lockhart
 * @author      Andrew Smith
 *
 * @link        http://www.slimframework.com
 *
 * @copyright   2013 Josh Lockhart
 *
 * @version     0.1.3
 */

namespace PickleWeb\View;

use PickleWeb\Application;

/**
 * Twig view.
 *
 * The Twig view is a custom View class that renders templates using the Twig
 * template language (http://www.twig-project.org/).
 *
 * Two fields that you, the developer, will need to change are:
 * - parserDirectory
 * - parserOptions
 */
class Twig extends \Slim\View
{
    protected $application;

    public function __construct(Application $application)
    {
        parent::__construct();

        $this->application = $application;
    }

    /**
     * @var string The path to the Twig code directory WITHOUT the trailing slash
     */
    public $parserDirectory = null;

    /**
     * @var array The options for the Twig environment, see
     *            http://www.twig-project.org/book/03-Twig-for-Developers
     */
    public $parserOptions = array();

    /**
     * @var \Twig_Extension[] The Twig extensions you want to load
     */
    public $parserExtensions = array();

    /**
     * @var \Twig_Environment The Twig environment for rendering templates.
     */
    private $parserInstance = null;

    /**
     * Render Twig Template.
     *
     * This method will output the rendered template content
     *
     * @param string $template The path to the Twig template, relative to the Twig templates directory.
     * @param null   $data
     *
     * @return string
     */
    public function render($template, $data = null)
    {
        $env = $this->getInstance();
        $parser = $env->loadTemplate($template);

        $data = array_merge($this->all(), (array) $data);

        return $parser->render($data);
    }

    /**
     * Creates new TwigEnvironment if it doesn't already exist, and returns it.
     *
     * @return \Twig_Environment
     */
    public function getInstance()
    {
        if (!$this->parserInstance) {
            $defaults = [];

            if ($this->application->getMode() === 'production') {
                $defaults['cache'] = sys_get_temp_dir().'/twig_cache';
            }

            $this->parserInstance = new \Twig_Environment(
                new \Twig_Loader_Filesystem(__DIR__.'/../../templates'),
                array_merge($defaults, $this->parserOptions)
            );
        }

        return $this->parserInstance;
    }
}
