<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UploadCostsImputsNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $uploadError;
    public $usersCreated;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($uploadError, $usersCreated)
    {
        $this->uploadError = $uploadError;
        $this->usersCreated = $usersCreated;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('mayorazgoasesores.info@gmail.com')->subject('Proceso de envio de imputación de costes finalizado')->view('mails.mail-UploadCostsImputs-template')->with('uploadError', $this->uploadError)->with('usersCreated', $this->usersCreated);
    }
}
