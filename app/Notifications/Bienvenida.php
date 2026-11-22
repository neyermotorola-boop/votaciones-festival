<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Correo de bienvenida al crear una cuenta de votante. */
class Bienvenida extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('¡Bienvenido a las votaciones del Festival!')
            ->greeting('¡Hola, '.$notifiable->name.'!')
            ->line('Tu cuenta en el sistema de votaciones del Festival de la Leyenda Vallenata quedó creada.')
            ->line('Ya puedes elegir tus favoritos en las votaciones abiertas.')
            ->action('Ir a votar', route('home'))
            ->line('Si no creaste esta cuenta, ignora este mensaje.')
            ->salutation('Fundación Festival de la Leyenda Vallenata');
    }
}
