<?php

/*it('validates config', function () {

    \Glugox\Magic\Validation\MagicConfigValidator::disableAutoFix();

    $config = $this->createConfigFromFile('inventory.json');
    $jsonConfigBeforeValidation = $config->toJson();

    $configWithAddedAllFks = $this->createConfigFromFile('inventory-full.json');

    // Act
    \Glugox\Magic\Validation\MagicConfigValidator::enableAutoFix();
    $config->validate();

    // Assert prepare
    $entities = $config->entities;
    $jsonConfigAfterValidation = $config->toJson();

    // Assert
    \PHPUnit\Framework\assertEquals($configWithAddedAllFks->toJson(), $jsonConfigAfterValidation);


})->throwsNoExceptions();*/
