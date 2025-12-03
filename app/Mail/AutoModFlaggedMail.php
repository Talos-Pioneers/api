<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AutoModFlaggedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param  array<int, array{text: array{text: string, label: string|null}, categories: array<string, bool>, category_scores: array<string, float>}>  $flaggedTexts
     * @param  array<int, array{image: string, categories: array<string, bool>, category_scores: array<string, float>}>  $flaggedImages
     */
    public function __construct(
        public string $contentType,
        public string $contentTitle,
        public ?User $author,
        public array $flaggedTexts,
        public array $flaggedImages,
        public ?string $reviewUrl = null
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[AutoMod Alert] Flagged {$this->contentType}: {$this->contentTitle}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.auto-mod-flagged',
            with: [
                'contentType' => $this->contentType,
                'contentTitle' => $this->contentTitle,
                'author' => $this->author,
                'flaggedTexts' => $this->flaggedTexts,
                'flaggedImages' => $this->flaggedImages,
                'reviewUrl' => $this->reviewUrl,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
