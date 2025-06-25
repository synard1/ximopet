<?php

namespace App\Mail\Alert;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GenericAlert extends Mailable
{
    use Queueable, SerializesModels;

    public $alertData;

    /**
     * Create a new message instance.
     */
    public function __construct(array $alertData)
    {
        $this->alertData = $alertData;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = $this->alertData['title'] ?? 'System Alert';
        $level = $this->alertData['level'] ?? 'info';

        // Add level to subject for clarity
        $levelPrefix = [
            'critical' => '[CRITICAL]',
            'error' => '[ERROR]',
            'warning' => '[WARNING]',
            'info' => '[INFO]'
        ];

        $fullSubject = ($levelPrefix[$level] ?? '[ALERT]') . ' ' . $subject;

        return $this->subject($fullSubject)
            ->view('emails.alerts.generic')
            ->with([
                'alertData' => $this->alertData,
                'level' => $level,
                'title' => $subject,
                'message' => $this->alertData['message'] ?? 'No message provided',
                'data' => $this->alertData['data'] ?? [],
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ]);
    }
}
