<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use OpenAI;
use OpenAI\Client;
use OpenAI\Exceptions\ErrorException;

class AutoMod
{
    /** @var Client|\OpenAI\Testing\ClientFake */
    protected $client;

    /** @var array<string> */
    public array $texts = [];

    /** @var array<UploadedFile|string> */
    public array $images = [];

    protected bool $passed = false;

    /**
     * @param  Client|\OpenAI\Testing\ClientFake  $client
     */
    protected function __construct($client)
    {
        $this->client = $client;
    }

    /**
     * @param  Client|\OpenAI\Testing\ClientFake|null  $client
     */
    public static function build(?string $apiKey = null, $client = null): self
    {
        if ($client !== null) {
            return new self($client);
        }

        // Check if a client is bound in the container (useful for testing)
        if (app()->bound(Client::class)) {
            return new self(app(Client::class));
        }

        $apiKey = $apiKey ?? config('services.openai.api_key');

        if (! $apiKey) {
            throw new \RuntimeException('OpenAI API key is not configured. Please set OPENAI_API_KEY in your .env file.');
        }

        $openAiClient = OpenAI::client($apiKey);

        return new self($openAiClient);
    }

    public function text(?string $text): self
    {
        if ($text !== null && $text !== '') {
            $this->texts[] = $text;
        }

        return $this;
    }

    /**
     * @param  UploadedFile|string  $image
     */
    public function image($image): self
    {
        if ($image !== null) {
            $this->images[] = $image;
        }

        return $this;
    }

    /**
     * @param  array<UploadedFile|string>  $images
     */
    public function images(array $images): self
    {
        foreach ($images as $image) {
            $this->image($image);
        }

        return $this;
    }

    public function getInput(): array
    {
        $input = [];

        // Add text inputs
        foreach ($this->texts as $text) {
            $input[] = [
                'type' => 'text',
                'text' => $text,
            ];
        }

        // Add image inputs (base64 encoded)
        foreach ($this->images as $image) {
            $imageData = $this->getImageDataUri($image);
            $input[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => $imageData,
                ],
            ];
        }

        return $input;
    }

    /**
     * @return array{passed: bool, flagged_texts: array<int, array{text: string, categories: array<string, bool>, category_scores: array<string, float>}>, flagged_images: array<int, array{image: string, categories: array<string, bool>, category_scores: array<string, float>}>}
     */
    public function validate(): array
    {
        $flaggedTexts = [];
        $flaggedImages = [];

        $input = $this->getInput();

        if (empty($input)) {
            $this->passed = true;

            return [
                'passed' => $this->passed,
                'flagged_texts' => [],
                'flagged_images' => [],
            ];
        }

        // Validate all content in a single API call
        try {
            $response = $this->client->moderations()->create([
                'model' => 'omni-moderation-latest',
                'input' => $input,
            ]);

            $textIndex = 0;
            $imageIndex = 0;

            foreach ($response->results as $index => $result) {
                $inputItem = $input[$index];
                $isText = $inputItem['type'] === 'text';

                $categories = [];
                $categoryScores = [];

                if ($result->flagged) {
                    foreach ($result->categories as $category) {
                        $categories[$category->category->value] = $category->violated;
                        $categoryScores[$category->category->value] = $category->score;
                    }

                    if ($isText) {
                        $flaggedTexts[] = [
                            'text' => $this->texts[$textIndex],
                            'categories' => $categories,
                            'category_scores' => $categoryScores,
                        ];
                    } else {
                        $flaggedImages[] = [
                            'image' => $this->getImageIdentifier($this->images[$imageIndex]),
                            'categories' => $categories,
                            'category_scores' => $categoryScores,
                        ];
                    }
                }

                // Always increment the right index, flagged or not
                if ($isText) {
                    $textIndex++;
                } else {
                    $imageIndex++;
                }
            }
        } catch (ErrorException $e) {
            // Log error but don't fail validation if API is unavailable
            Log::warning('OpenAI moderation API error: '.$e->getMessage());
        }

        $this->passed = count($flaggedTexts) === 0 && count($flaggedImages) === 0;

        return [
            'passed' => $this->passed,
            'flagged_texts' => $flaggedTexts,
            'flagged_images' => $flaggedImages,
        ];
    }

    /**
     * @param  UploadedFile|string  $image
     */
    protected function getImageIdentifier($image): string
    {
        if ($image instanceof UploadedFile) {
            return $image->getClientOriginalName();
        }

        return (string) $image;
    }

    /**
     * Convert image to base64 data URI
     *
     * @param  UploadedFile|string  $image
     */
    protected function getImageDataUri($image): string
    {
        $imagePath = $this->getImagePath($image);
        $imageContent = file_get_contents($imagePath);
        $base64 = base64_encode($imageContent);

        // Determine MIME type
        $mimeType = $this->getImageMimeType($image);

        return "data:{$mimeType};base64,{$base64}";
    }

    /**
     * Get the file path for an image
     *
     * @param  UploadedFile|string  $image
     */
    protected function getImagePath($image): string
    {
        if ($image instanceof UploadedFile) {
            return $image->getRealPath();
        }

        if (is_file($image)) {
            return $image;
        }

        throw new \RuntimeException("Image file not found: {$image}");
    }

    /**
     * Get MIME type for an image
     *
     * @param  UploadedFile|string  $image
     */
    protected function getImageMimeType($image): string
    {
        if ($image instanceof UploadedFile) {
            $mimeType = $image->getMimeType();
            if ($mimeType) {
                return $mimeType;
            }
        }

        $imagePath = $this->getImagePath($image);
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $imagePath);
        finfo_close($finfo);

        return $mimeType ?: 'image/jpeg';
    }

    public function passes(): bool
    {
        return $this->passed;
    }

    public function fails(): bool
    {
        return ! $this->passed;
    }
}
