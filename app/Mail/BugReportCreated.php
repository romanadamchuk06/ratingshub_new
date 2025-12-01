<?php

namespace App\Mail;

use App\Models\BugReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * BUG REPORT EMAIL NOTIFICATION
 * ==============================
 *
 * Wird gesendet, wenn ein User einen neuen Bug-Report erstellt.
 * Admin erhält alle Details inkl. Link zum Admin-Panel.
 */
class BugReportCreated extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public BugReport $bugReport
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $typeLabels = [
            'bug' => '🐛 Bug',
            'feature' => '💡 Feature Request',
            'improvement' => '🔧 Verbesserung',
            'question' => '❓ Frage',
        ];

        $subject = '[RatingsHub] ' . ($typeLabels[$this->bugReport->type] ?? 'Neuer Bug-Report');

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.bug-report-created',
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
