<?php
$finder = PhpCsFixer\Finder::create()
    ->exclude(__DIR__ . '/vendor')
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/install')
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setLineEnding("\n")
    ->setUsingCache(false)
    ->setRules([
        '@PSR2' => true,
        'array_syntax' => ['syntax' => 'short'],
        'encoding' => true,
    ])
    ->setFinder($finder);