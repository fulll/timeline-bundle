<?php

namespace Spy\TimelineBundle\Twig\Extension;

use Spy\Timeline\Model\TimelineInterface;
use Spy\Timeline\Model\ActionInterface;
use Spy\TimelineBundle\Twig\TokenParser\TimelineActionThemeTokenParser;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Extension\AbstractExtension;
use Twig\Template;
use Twig\TemplateWrapper;
use Twig\TwigFunction;

/**
 * "timeline_render" -> renders a timeline by getting the path of twig
 * templates from config. Then, calls PATH/VERB.html.twig
 *
 * "i18n_timeline_render" -> renders timeline using locale.
 * PATH/VERB.LOCALE.html.twig if file exists
 * then falls back to PATH/VERB.DEFAULT_LOCALE.html.twig ( if set in conf )
 *
 * @author Stephane PY <py.stephane1@gmail.com>
 */
class TimelineExtension extends AbstractExtension
{
    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var array
     */
    private $config;

    /**
     * @var null|TemplateWrapper
     */
    protected $template = null;

    /**
     * @var \SplObjectStorage
     */
    protected $blocks;

    /**
     * @var \SplObjectStorage
     */
    protected $themes;

    /**
     * @var array
     */
    protected $resources;

    /**
     * @var array
     */
    protected $varStack;

    /**
     * @param Environment $twig   Twig environment
     * @param array       $config and array of configuration
     */
    public function __construct(Environment $twig, array $config, array $resources)
    {
        $this->twig      = $twig;
        $this->config    = $config;
        $this->resources = $resources;
        $this->blocks    = new \SplObjectStorage();
        $this->themes    = new \SplObjectStorage();
        $this->varStack  = [];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new TwigFunction('timeline' ,array($this, 'renderContextualTimeline'), array('is_safe' => array('html'))),
            new TwigFunction('timeline_render', array($this, 'renderTimeline'), array('is_safe' => array('html'))),
            new TwigFunction('timeline_component_render' ,array($this, 'renderActionComponent'), array('is_safe' => array('html'))),
            new TwigFunction('i18n_timeline_render', array($this, 'renderLocalizedTimeline'), array('is_safe' => array('html'))),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getTokenParsers()
    {
        return array(
            // {% timeline_action_theme timeline "Acme::components.html.twig" %}
            new TimelineActionThemeTokenParser(),
        );
    }

    protected function resolveAction(ActionInterface|TimelineInterface $entity): ActionInterface
    {
        if ($entity instanceof ActionInterface) {
            return $entity;
        }

        return $entity->getAction();
    }

    /**
     * @param object      $action    What Action to render
     * @param string|null $template  Force template path
     * @param array       $variables Additional variables to pass to templates
     *
     * @return string
     */
    public function renderTimeline($action, $template = null, array $variables = [])
    {
        $action = $this->resolveAction($action);

        if (null === $template) {
            $template = $this->getDefaultTemplate($action);
        }

        $parameters = array_merge($variables, array(
            'timeline' => $action,
        ));

        try {
            return $this->twig->render($template, $parameters);
        } catch (LoaderError $e) {
            if (null !== $this->config['fallback']) {
                return $this->twig->render($this->config['fallback'], $parameters);
            }

            throw $e;
        }
    }

    /**
     * Return an array of variables from a timeline component
     * @param object $action
     * @param string $component
     *
     * @return array
     */
    public function getComponentVariables($action, $component)
    {
        $values      = [];
        $action      = $this->resolveAction($action);
        $component   = $action->getComponent($component);
        $isComponent = is_object($component);

        $values['value'] = $isComponent ? $component->getData() : $component;
        $values['model'] = $isComponent ? $component->getModel() : null;
        $values['id']    = $isComponent ? $component->getIdentifier() : null;
        $values['text']  = $isComponent ? null : $component;

        if (!empty($values['model'])) {
            $values['normalized_model'] = strtolower(str_replace('\\', '_', $values['model']));
        }

        return $values;
    }

    /**
     * Render an action component
     *
     * @param  object $action    action
     * @param  string $component Component to render (subject, verb, etc ...)
     * @param  array  $variables Additional variables to pass to templates
     * @return string
     */
    public function renderActionComponent($action, $component, array $variables = [])
    {
        $action = $this->resolveAction($action);

        if (null === $this->template) {
            $template = reset($this->resources);
            $this->template = $this->twig->resolveTemplate($template)->unwrap();
        }

        $componentVariables = $this->getComponentVariables($action, $component);
        $componentVariables['type']   = $component;
        $componentVariables['action'] = $action;

        $custom = false;
        if (!empty($componentVariables['model'])) {
            $custom = '_'.$componentVariables['normalized_model'];
        }

        $rendering = $custom.'_'.$component.'component';
        $blocks = $this->getBlocks($action);

        if (isset($this->varStack[$rendering])) {
            $typeIndex = $this->varStack[$rendering]['typeIndex'] - 1;
            $types = $this->varStack[$rendering]['types'];
            $this->varStack[$rendering]['variables'] = array_replace_recursive($componentVariables, $variables);
        } else {
            $types = [];
            // fallback to __toString of component.
            if ($component != 'action') {
                $types[] = 'action';
            }

            $types[] = $component;

            if ($custom) {
                $types[] = $custom.'_default';
                $types[] = $custom.'_'.$component;
            }

            $typeIndex = count($types) - 1;
            $this->varStack[$rendering] = array(
                'variables' => array_replace_recursive($componentVariables, $variables),
                'types'     => $types,
            );
        }

        $twigGlobals = $this->twig->getGlobals();

        do {
            $types[$typeIndex] .= '_component';

            if (isset($blocks[$types[$typeIndex]])) {
                $this->varStack[$rendering]['typeIndex'] = $typeIndex;

                $context = array_merge($twigGlobals, $this->varStack[$rendering]['variables']);

                // we do not call renderBlock here to avoid too many nested level calls (XDebug limits the level to 100 by default)
                ob_start();
                $this->template->displayBlock($types[$typeIndex], $context, $blocks);
                $html = ob_get_clean();

                unset($this->varStack[$rendering]);

                return $html;
            }
        } while (--$typeIndex >= 0);

        throw new \Exception(sprintf(
            'Unable to render the action component as none of the following blocks exist: "%s".',
            implode('", "', array_reverse($types))
        ));
    }

    /**
     * Returns the blocks used to render the view.
     *
     * Templates are looked for in the configured resources
     *
     * @param object $action
     *
     * @return array An array of string, Template or TemplateWrapper instances
     */
    protected function getBlocks($action)
    {
        $action = $this->resolveAction($action);

        if (!$this->blocks->contains($action)) {
            $templates = $this->resources;

            if ($this->themes->contains($action)) {
                $templates = array_merge($templates, $this->themes[$action]);
            }

            $blocks = [[]];
            foreach ($templates as $template) {
                $template = $this->twig->resolveTemplate($template)->unwrap();
                $templateBlocks = [[]];
                do {
                    $templateBlocks[] = $template->getBlocks();
                } while (false !== $template = $template->getParent([]));
                $blocks[] = array_merge(...$templateBlocks);
            }
            $blocks = array_merge(...$blocks);
            $this->blocks->attach($action, $blocks);
        }

        return $this->blocks[$action];
    }

    /**
     * Returns the default template name.
     *
     * @param object $action
     *
     * @return string
     */
    public function getDefaultTemplate($action)
    {
        $action = $this->resolveAction($action);

        return vsprintf('@%s/%s.html.twig', array(
                    $this->config['path'],
                    strtolower($action->getVerb()),
                ));
    }

    /**
     * @param object      $action  What Action to render
     * @param string|null $context Template context path
     * @param string      $format  Template format
     *
     * @return string
     */
    public function renderContextualTimeline($action, $context = null, $format = 'html')
    {
        $action = $this->resolveAction($action);

        if (null === $context) {
            $template = $this->getDefaultTemplate($action);
        } else {
            $template = $this->getContextualTemplate($action, $context, $format);
        }

        $parameters = array(
            'timeline' => $action,
        );

        try {
            return $this->twig->render($template, $parameters);
        } catch (LoaderError $e) {
            if (null !== $this->config['fallback']) {
                return $this->twig->render($this->config['fallback'], $parameters);
            }

            throw $e;
        }
    }

    /**
     * Returns the contextualized template name.
     *
     * @param object $action
     * @param string $context
     * @param string $format
     *
     * @return string
     */
    public function getContextualTemplate($action, $context, $format)
    {
        $action = $this->resolveAction($action);

        return vsprintf('@%s/%s/%s.%s.twig', array(
                    $this->config['path'],
                    $context,
                    strtolower($action->getVerb()),
                    $format,
                ));
    }

    /**
     * @param object      $action    What Action to render
     * @param string|null $locale    Locale of the template
     * @param array       $variables Additional variables to pass to templates
     *
     * @return string
     */
    public function renderLocalizedTimeline($action, $locale = null, array $variables = [])
    {
        $action = $this->resolveAction($action);

        if ($locale === null) {
            $locale = $this->config['i18n_fallback'];
        }

        $template = $this->getDefaultLocalizedTemplate($action, $locale);

        $parameters = array_merge($variables, array(
            'timeline' => $action,
        ));

        try {
            return $this->twig->render($template, $parameters);
        } catch (LoaderError $e) {
            if ($locale != $this->config['i18n_fallback'] && null !== $this->config['i18n_fallback']) {
                $fallbackTemplate = $this->getDefaultLocalizedTemplate($action, $this->config['i18n_fallback']);
                try {
                    return $this->twig->render($fallbackTemplate, $parameters);
                } catch (LoaderError $e) {
                    //Let's look at the default template
                }
            }

            if (null !== $this->config['fallback']) {
                return $this->twig->render($this->config['fallback'], $parameters);
            }

            throw $e;
        }
    }

    /**
     * Returns the default template name using locale.
     *
     * @param object $action action object
     * @param string $locale which locale
     *
     * @return string
     */
    public function getDefaultLocalizedTemplate($action, $locale)
    {
        $action = $this->resolveAction($action);

        return vsprintf('@%s/%s.%s.html.twig', array(
            $this->config['path'],
            strtolower($action->getVerb()),
            $locale,
        ));
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'timeline_render';
    }

    /**
     * Store themes for a given Action
     *
     * @param object $action
     * @param array  $resources
     */
    public function setTheme($action, array $resources)
    {
        $action = $this->resolveAction($action);

        $this->themes->attach($action, $resources);
        $this->blocks->detach($action);
    }
}
