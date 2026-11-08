<?php

namespace App\Mail;

use App\Models\Recordatorio;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Correo de recordatorio / aviso de cita (CU31): próxima cita, reprogramación o cancelación.
 */
class RecordatorioCitaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Recordatorio $recordatorio)
    {
    }

    public function envelope(): Envelope
    {
        $asunto = match ($this->recordatorio->tipo) {
            'Reprogramacion' => 'Su cita fue reprogramada',
            'Cancelacion' => 'Su cita fue cancelada',
            default => 'Recordatorio de su cita médica',
        };
        return new Envelope(subject: 'Clínica Vida Salud · ' . $asunto . ' ' . $this->recordatorio->cita->codigo);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.recordatorio');
    }
}
