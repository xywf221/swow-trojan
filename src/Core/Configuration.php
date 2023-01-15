<?php

namespace xywf221\Trojan\Core;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('');
        $rootNode = $builder->getRootNode();

        $rootNode->ignoreExtraKeys(true)->children()
            ->enumNode('run_type')
            ->isRequired()
            ->cannotBeEmpty()
            ->values(['server', 'client'])
            ->end()
            ->scalarNode('local_addr')
            ->isRequired()
            ->end()
            ->integerNode('local_port')
            ->isRequired()
            ->end()
            ->scalarNode('remote_addr')
            ->isRequired()
            ->end()
            ->integerNode('remote_port')
            ->isRequired()
            ->end()
            ->arrayNode('password')
                ->isRequired()->scalarPrototype()->end()
            ->end()
            ->append($this->tcpNode())
            ->append($this->sslNode());

        return $builder;
    }

    public function tcpNode(): ArrayNodeDefinition
    {
        $node = new ArrayNodeDefinition('tcp');
        $node->children()
            ->booleanNode('no_delay')
            ->defaultFalse()
            ->end()
            ->booleanNode('keep_alive')
            ->defaultFalse()
            ->end()
            ->integerNode('keep_alive_delay')
            ->min(0)->defaultValue(100)
            ->end()
            ->booleanNode('accept_balance')
            ->defaultFalse()
            ->end();
        return $node;
    }

    public function sslNode(): ArrayNodeDefinition
    {
        $node = new ArrayNodeDefinition('ssl');
        $node->children()
            ->scalarNode('cert')
            ->cannotBeEmpty()
            ->end()
            ->scalarNode('key')
            ->cannotBeEmpty()
            ->end()
            ->scalarNode('key_password')
            ->end();

        return $node;
    }
}