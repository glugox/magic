<?php

use Glugox\Ai\AiManager;
use Glugox\Ai\Drivers\PrismDriver;
use Glugox\Ai\Facades\Ai;
use Glugox\Ai\Request\AiRequestBuilder;
use Prism\Prism\Prism;
use Prism\Prism\Testing\TextResponseFake;

/**
 * Test suite for AI agent functionality.
 */
it('returns a response object for a prompt', function () {

    // Set fake Prism
    $fake = Prism::fake([
        TextResponseFake::make()
            ->withText('Paris'),
    ]);

    $response = Ai::ask(
        AiRequestBuilder::make()
            ->driver(new PrismDriver)
            ->text('What is the capital of France?')
            ->build()
    );

    // The first strict check: it returns an object with text()
    expect($response)->toBeObject()
        ->and($response->text())->toBeString();
});

/**
 * Test that different prompts yield different correct answers.
 */
it('returns correct answers for different prompts', function () {

    // Set fake Prism
    $fake = Prism::fake([
        TextResponseFake::make()
            ->withText('Paris'),
        TextResponseFake::make()
            ->withText('4'),
        TextResponseFake::make()
            ->withText('Berlin'),
    ]);

    $promptsAndExpected = [
        'What is the capital of France?' => 'Paris',
        'What is 2 + 2?' => '4',
        'What is the capital of Germany?' => 'Berlin',
    ];

    foreach ($promptsAndExpected as $prompt => $expected) {
        $response = Ai::ask(
            AiRequestBuilder::make()
                ->driver(new PrismDriver)
                ->text($prompt)
                ->build()
        );

        expect($response->text())->toBe($expected);
    }
});

/**
 * Test that the AiManager correctly uses the PrismDriver.
 */
it('asks PrismDriver and returns response', function () {

    // Set fake Prism
    $fake = Prism::fake([
        TextResponseFake::make()
            ->withText('4'),
    ]);

    $ai = new AiManager;

    $response = $ai->ask(
        AiRequestBuilder::make()
            ->driver(new PrismDriver)
            ->text('What is 2 + 2?')
            ->build()
    );

    expect($response)->toBeObject()
        ->and($response->text())->toBe('4');
});
