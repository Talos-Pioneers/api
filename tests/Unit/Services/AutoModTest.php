<?php

use App\Services\AutoMod;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use OpenAI\Client;
use OpenAI\Responses\Moderations\CreateResponse;
use OpenAI\Testing\ClientFake;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Config::set('services.openai.api_key', 'test-api-key');
});

it('can be built with static build method', function () {
    $autoMod = AutoMod::build();

    expect($autoMod)->toBeInstanceOf(AutoMod::class);
});

it('throws exception when api key is not configured', function () {
    Config::set('services.openai.api_key', null);

    expect(fn () => AutoMod::build())
        ->toThrow(RuntimeException::class, 'OpenAI API key is not configured');
});

it('can add text for moderation', function () {
    $autoMod = AutoMod::build()
        ->text('Hello world');

    expect($autoMod)->toBeInstanceOf(AutoMod::class);
});

it('can add multiple texts for moderation', function () {
    $autoMod = AutoMod::build()
        ->text('First text')
        ->text('Second text');

    expect($autoMod)->toBeInstanceOf(AutoMod::class)->and($autoMod->texts)->toHaveCount(2);
    expect($autoMod->texts)->toBe([
        'First text',
        'Second text',
    ]);
});

it('ignores null or empty text', function () {
    $autoMod = AutoMod::build()
        ->text(null)
        ->text('')
        ->text('Valid text');

    expect($autoMod)->toBeInstanceOf(AutoMod::class)->and($autoMod->texts)->toHaveCount(1);
    expect($autoMod->texts)->toBe(['Valid text']);
});

it('can add single image for moderation', function () {
    $image = UploadedFile::fake()->image('test.jpg');

    $autoMod = AutoMod::build()
        ->image($image);

    expect($autoMod)->toBeInstanceOf(AutoMod::class);
});

it('can add multiple images for moderation', function () {
    $image1 = UploadedFile::fake()->image('test1.jpg');
    $image2 = UploadedFile::fake()->image('test2.jpg');

    $autoMod = AutoMod::build()
        ->images([$image1, $image2]);

    expect($autoMod)->toBeInstanceOf(AutoMod::class);
});

it('validates images and flags inappropriate content', function () {
    $image = UploadedFile::fake()->image('test.jpg');

    $client = new ClientFake([
        CreateResponse::fake([
            'results' => [
                [
                    'flagged' => true,
                    'categories' => ['sexual' => true, 'violence' => false],
                    'category_scores' => ['sexual' => 0.9, 'violence' => 0.1],
                ],
            ],
        ]),
    ]);

    $autoMod = AutoMod::build(client: $client);

    $result = $autoMod->image($image)->validate();

    expect($result['passed'])->toBeFalse();
    expect($result['flagged_images'])->toHaveCount(1);
    expect($result['flagged_images'][0]['image'])->toBe('test.jpg');
    expect($result['flagged_images'][0]['categories']['sexual'])->toBeTrue();
});

it('validates mixed text and images together', function () {
    $image = UploadedFile::fake()->image('test.jpg');

    $client = new ClientFake([
        CreateResponse::fake([
            'results' => [
                [
                    'flagged' => false,
                    'categories' => [],
                    'category_scores' => [],
                ],
                [
                    'flagged' => true,
                    'categories' => ['hate' => true],
                    'category_scores' => ['hate' => 0.95],
                ],
            ],
        ]),
    ]);

    $autoMod = AutoMod::build(client: $client);

    $result = $autoMod
        ->text('Safe text')
        ->image($image)
        ->validate();

    expect($result['passed'])->toBeFalse();
    expect($result['flagged_texts'])->toBeEmpty();
    expect($result['flagged_images'])->toHaveCount(1);
    expect($result['flagged_images'][0]['categories']['hate'])->toBeTrue();
});

it('passes validation when no content is flagged', function () {
    $client = new ClientFake([
        CreateResponse::fake([
            'results' => [
                [
                    'flagged' => false,
                    'categories' => [],
                    'category_scores' => [],
                ],
            ],
        ]),
    ]);

    // Use build method with fake client
    $autoMod = AutoMod::build(client: $client);

    $result = $autoMod->text('Safe content')->validate();

    expect($result['passed'])->toBeTrue();
    expect($result['flagged_texts'])->toBeEmpty();
    expect($result['flagged_images'])->toBeEmpty();
});

it('fails validation when content is flagged', function () {
    $client = new ClientFake([
        CreateResponse::fake([
            'results' => [
                [
                    'flagged' => true,
                    'categories' => [
                        'hate' => true,
                        'harassment' => false,
                    ],
                    'category_scores' => [
                        'hate' => 0.95,
                        'harassment' => 0.1,
                    ],
                ],
            ],
        ]),
    ]);

    $autoMod = AutoMod::build(client: $client);

    $result = $autoMod->text('Inappropriate content')->validate();

    expect($result['passed'])->toBeFalse();
    expect($result['flagged_texts'])->toHaveCount(1);
    expect($result['flagged_texts'][0]['text'])->toBe('Inappropriate content');
    expect($result['flagged_texts'][0]['categories']['hate'])->toBeTrue();
});

it('passes method returns true when validation passes', function () {
    $client = new ClientFake([
        CreateResponse::fake([
            'results' => [
                [
                    'flagged' => false,
                    'categories' => [],
                    'category_scores' => [],
                ],
            ],
        ]),
    ]);

    $autoMod = AutoMod::build(client: $client);
    $autoMod->text('Safe content')->validate();

    expect($autoMod->passes())->toBeTrue();
});

it('passes method returns false when validation fails', function () {
    $client = new ClientFake([
        CreateResponse::fake([
            'results' => [
                [
                    'flagged' => true,
                    'categories' => ['hate' => true],
                    'category_scores' => ['hate' => 0.95],
                ],
            ],
        ]),
    ]);

    $autoMod = AutoMod::build(client: $client);

    expect($autoMod->text('Bad content')->passes())->toBeFalse();
});

it('fails method returns opposite of passes', function () {
    $client = new ClientFake([
        CreateResponse::fake([
            'results' => [
                [
                    'flagged' => true,
                    'categories' => ['hate' => true],
                    'category_scores' => ['hate' => 0.95],
                ],
            ],
        ]),
        CreateResponse::fake([
            'results' => [
                [
                    'flagged' => true,
                    'categories' => ['hate' => true],
                    'category_scores' => ['hate' => 0.95],
                ],
            ],
        ]),
    ]);

    $autoMod1 = AutoMod::build(client: $client);
    $autoMod2 = AutoMod::build(client: $client);

    expect($autoMod1->text('Bad content')->fails())->toBeTrue();
    expect($autoMod2->text('Bad content')->passes())->toBeFalse();
});

it('validates multiple texts independently', function () {
    $client = new ClientFake([
        CreateResponse::fake([
            'results' => [
                [
                    'flagged' => false,
                    'categories' => [],
                    'category_scores' => [],
                ],
                [
                    'flagged' => true,
                    'categories' => ['hate' => true],
                    'category_scores' => ['hate' => 0.9],
                ],
            ],
        ]),
    ]);

    $autoMod = AutoMod::build(client: $client);

    $result = $autoMod
        ->text('Safe content')
        ->text('Bad content')
        ->validate();

    expect($result['passed'])->toBeFalse();
    expect($result['flagged_texts'])->toHaveCount(1);
    expect($result['flagged_texts'][0]['text'])->toBe('Bad content');
});

it('handles api errors gracefully', function () {
    $response = new \GuzzleHttp\Psr7\Response(500, [], json_encode([
        'error' => [
            'message' => 'API error',
            'type' => 'invalid_request_error',
            'code' => null,
        ],
    ]));

    $client = new ClientFake([
        new \OpenAI\Exceptions\ErrorException([
            'message' => 'API error',
            'type' => 'invalid_request_error',
            'code' => null,
        ], $response),
    ]);

    $autoMod = AutoMod::build(client: $client);

    // Should not throw exception, but log warning
    $result = $autoMod->text('Some content')->validate();

    expect($result['passed'])->toBeTrue();
    expect($result['flagged_texts'])->toBeEmpty();
});

it('supports fluent chaining', function () {
    $image = UploadedFile::fake()->image('test.jpg');

    $autoMod = AutoMod::build()
        ->text('Title')
        ->text('Description')
        ->image($image)
        ->images([UploadedFile::fake()->image('test2.jpg')]);

    expect($autoMod)->toBeInstanceOf(AutoMod::class);
});

it('can be built with custom api key', function () {
    Config::set('services.openai.api_key', null);

    $autoMod = AutoMod::build('custom-api-key');

    expect($autoMod)->toBeInstanceOf(AutoMod::class);
});
