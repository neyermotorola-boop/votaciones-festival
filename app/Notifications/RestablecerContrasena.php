<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Enlace para restablecer la contraseña (vence según config/auth.php). */
class RestablecerContrasena extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);
        $minutos = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Restablece tu contraseña')
            ->greeting('Hola, '.$notifiable->name)
            ->line('Recibimos una solicitud para restablecer la contraseña de tu cuenta.')
            ->action('Crear nueva contraseña', $url)
            ->line("Este enlace vence en {$minutos} minutos.")
            ->line('Si no hiciste esta solicitud, ignora este correo: tu contraseña no cambiará.')
            ->salutation('Fundación Festival de la Leyenda Vallenata');
    }
}
