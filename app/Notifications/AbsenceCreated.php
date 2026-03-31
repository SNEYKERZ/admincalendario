<?php

namespace App\Notifications;

use App\Models\Absence;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AbsenceCreated extends Notification
{
    use Queueable;

    public function __construct(
        public Absence $absence
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): \Illuminate\Notifications\Messages\MailMessage
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('Nueva solicitud de ausencia')
            ->line("El empleado {$this->absence->user->name} ha solicitado una ausencia.")
            ->line("Tipo: {$this->absence->type->name}")
            ->line("Desde: {$this->absence->start_datetime->format('d/m/Y')}")
            ->line("Hasta: {$this->absence->end_datetime->format('d/m/Y')}")
            ->line("Días: {$this->absence->total_days}")
            ->action('Ver detalles', url('/dashboard'))
            ->line('Por favor, revisa la solicitud en el sistema.');
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Nueva solicitud de ausencia',
            'message' => "{$this->absence->user->name} solicitó {$this->absence->type->name}",
            'absence_id' => $this->absence->id,
            'type' => 'absence_created',
        ];
    }
}
