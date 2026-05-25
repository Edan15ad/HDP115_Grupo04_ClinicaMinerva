<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ResultadoLaboratorioMail extends Mailable
{
    use Queueable, SerializesModels;

    public $paciente;
    public $examen;
    public $pdfPath;

    public function __construct($paciente, $examen, $pdfPath)
    {
        $this->paciente = $paciente;
        $this->examen = $examen;
        $this->pdfPath = $pdfPath;
    }

    public function build()
    {
        $mail = $this->subject('Tus Resultados Clínicos - Clínica Minerva')
            ->view('emails.resultado');

        if ($this->pdfPath && Storage::disk('public')->exists($this->pdfPath)) {
            $mail->attach(Storage::disk('public')->path($this->pdfPath), [
                'as' => 'Resultado_' . $this->examen->codigo . '.pdf',
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }
}