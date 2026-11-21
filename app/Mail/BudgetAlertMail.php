<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Collection;

class BudgetAlertMail extends Mailable
{
    use Queueable;

    public function __construct(public Collection $items, public string $periodeLabel, public string $laporanUrl)
    {
    }

    public function envelope(): Envelope
    {
        $over = $this->items->where('level', 100)->count();
        $subject = $over
            ? "Peringatan: {$over} pos melebihi budget"
            : 'Peringatan: '.$this->items->count().' pos sudah di atas 80% budget';

        return new Envelope(subject: $subject.' ('.$this->periodeLabel.')');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.budget-alert');
    }
}
