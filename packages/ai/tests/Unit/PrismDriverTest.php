<?php

use Glugox\Ai\AiResponse;
use Glugox\Ai\Facades\Ai;
use Prism\Prism\Prism;
use Prism\Prism\Testing\TextResponseFake;

it('uses PrismDriver to ask a prompt via PrismPHP', function () {
    // Arrange: create a mock of the Prism text interface
    $fakeResponse = TextResponseFake::make()
        ->withText('4');

    // Set up the fake
    $fake = Prism::fake([$fakeResponse]);

    // Act
    $response = Ai::ask('What is 2 + 2?');

    // Assert
    expect($response)->toBeInstanceOf(AiResponse::class)
        ->and($response->text())->toBe('4');
});
