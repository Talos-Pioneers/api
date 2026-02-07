<?php

namespace App\Mail;

use App\Models\Blueprint;
use App\Models\BlueprintCollection;
use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public Report $report) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Report',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.report-mail',
            with: [
                'report' => $this->report,
                'url' => $this->getReportUrl(),
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

    public function getReportUrl() : string {
        return match($this->report->reportable_type) {
            Blueprint::class => config('app.frontend_url') . '/en/blueprints/' . $this->report->reportable_id,
            BlueprintCollection::class => config('app.frontend_url') . '/en/collections/' . $this->report->reportable_id,
            default => config('app.frontend_url'),
        };
    }
}
