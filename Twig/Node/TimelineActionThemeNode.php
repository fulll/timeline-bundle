<?php

namespace Spy\TimelineBundle\Twig\Node;

use Spy\TimelineBundle\Twig\Extension\TimelineExtension;
use Twig\Compiler;
use Twig\Node\Node;

class TimelineActionThemeNode extends Node
{
    public function __construct(Node $action, Node $resources, array $attributes = array(), $lineno = 0, $tag = null)
    {
        parent::__construct(array('action' => $action, 'resources' => $resources), $attributes, $lineno, $tag);
    }

    public function compile(Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write('echo $this->env->getExtension(\''.TimelineExtension::class.'\')->setTheme(')
            ->subcompile($this->getNode('action'))
            ->raw(', array(')
        ;

        foreach ($this->getNode('resources') as $resource) {
            $compiler->subcompile($resource)->raw(', ');
        }

        $compiler->raw("));\n");
    }
}
