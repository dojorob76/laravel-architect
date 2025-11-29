<?php

use tests\TestCase;

uses(TestCase::class)->in('Feature');

// Custom expectations
expect()->extend('toBeValidPhpFile', function () {
    $content = file_get_contents($this->value);

    expect($content)->toStartWith('<?php');

    $output = [];
    $returnVar = 0;
    exec("php -l {$this->value} 2>&1", $output, $returnVar);

    expect($returnVar)->toBe(0, 'File should have valid PHP syntax');

    return $this;
});

expect()->extend('toHaveMethod', function (string $method) {
    $content = file_get_contents($this->value);

    expect($content)->toContain("function {$method}(");

    return $this;
});

expect()->extend('toImplementInterface', function (string $interface) {
    $content = file_get_contents($this->value);

    expect($content)->toContain("implements {$interface}");

    return $this;
});

expect()->extend('toExtendClass', function (string $class) {
    $content = file_get_contents($this->value);

    expect($content)->toContain("extends {$class}");

    return $this;
});
