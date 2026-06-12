<?php

namespace App\Notifications;

use App\Models\Absence;
use App\Models\AbsenceApprovalChain;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AbsencePendingApproval extends Notification
{
    use Queueable;

    public function __construct(
        protected Absence $absence,
        protected AbsenceApprovalChain $chain
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nueva solicitud de ausencia pendiente')
            ->greeting("Hola {$notifiable->first_name},")
            ->line("El empleado **{$this->absence->user->name}** del área **{$this->absence->user->area?->name}** ha solicitado ausencia.")
            ->line("**Tipo:** {$this->absence->type->name}")
            ->line("**Período:** {$this->absence->start_datetime->format('d/m/Y')} a {$this->absence->end_datetime->format('d/m/Y')}")
            ->line("**Duración:** {$this->absence->total_days} días")
            ->action('Ver Solicitud', url("/absences/{$this->absence->id}"))
            ->line('Por favor, aprueba o rechaza en el plazo de 48 horas.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'absence_id' => $this->absence->id,
            'user_name' => $this->absence->user->name,
            'area_name' => $this->absence->user->area?->name,
            'type' => $this->absence->type->name,
            'total_days' => $this->absence->total_days,
            'message' => "Nueva solicitud de {$this->absence->user->name}",
        ];
    }
}
