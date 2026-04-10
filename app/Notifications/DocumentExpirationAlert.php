<?php

namespace App\Notifications;

use App\Models\HrDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentExpirationAlert extends Notification
{
    use Queueable;

    public function __construct(
        public HrDocument $document,
        public string $alertType,
        public int $daysRemaining
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isExpired = $this->alertType === 'expired';
        $subject = $isExpired
            ? 'Documento vencido: '.$this->document->title
            : 'Documento próximo a vencer: '.$this->document->title;

        $line = $isExpired
            ? 'El documento ya se encuentra vencido y requiere revisión.'
            : "El documento vence en {$this->daysRemaining} día(s).";

        return (new MailMessage)
            ->subject($subject)
            ->line($line)
            ->line('Documento: '.$this->document->title)
            ->line('Tipo: '.$this->document->document_type)
            ->line('Archivo: '.$this->document->original_name)
            ->line('Fecha de vencimiento: '.($this->document->expires_at?->toDateString() ?? 'N/A'));
    }
}
