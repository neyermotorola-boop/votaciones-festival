<?php

namespace App\Notifications;

use App\Models\Voto;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Comprobante enviado al votante después de registrar su voto. */
class VotoRegistrado extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Voto $voto) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->voto->loadMissing('votacion.categoria', 'opcion');
        $votacion = $this->voto->votacion;

        return (new MailMessage)
            ->subject('Voto registrado: '.$votacion->titulo)
            ->greeting('¡Gracias por votar, '.$notifiable->name.'!')
            ->line('Tu voto quedó registrado correctamente.')
            ->line('**Categoría:** '.$votacion->categoria->nombre)
            ->line('**Votación:** '.$votacion->titulo)
            ->line('**Opción elegida:** '.$this->voto->opcion->nombre)
            ->line('**Fecha:** '.$this->voto->created_at->format('d/m/Y H:i'))
            ->line('**Comprobante:** #'.str_pad((string) $this->voto->id, 6, '0', STR_PAD_LEFT))
            ->action('Ver la votación', route('votacion', $votacion))
            ->line('Si no reconoces este voto, cambia tu contraseña y avisa a la organización.')
            ->salutation('Fundación Festival de la Leyenda Vallenata');
    }
}
