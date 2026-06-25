<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('var')
    ->exclude('vendor')
    ->exclude('public')
    ->exclude('node_modules');

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
    ])
    ->setFinder($finder);
