<?php

it('validates config', function () {

    /** @var Glugox\Magic\Support\Config\Config $config */
    $config = $this->createConfigFromFile('callcenter.json');

    // Act
    $config->validate();
})->throwsNoExceptions();
