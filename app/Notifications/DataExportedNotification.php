<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\User;

class DataExportedNotification extends Notification
{
    private $exporterName;
    private $exportType;
    private $filePath;

    public function __construct(string $exporterName, string $exportType, string $filePath = null)
    {
        $this->exporterName = $exporterName;
        $this->exportType = $exportType;
        $this->filePath = $filePath;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $bccList = ['dev.clientg@gmail.com'];
        
        // Exclude Chris from Attendance export notifications
        if ($this->exportType !== 'Attendance') {
            $bccList[] = 'chris@wheedletechnologies.ai';
        }

        $mail = (new MailMessage)
            ->bcc($bccList)
            ->subject('📥 System Data Exported: ' . $this->exportType)
            ->greeting('Hello Admin,')
            ->line('A data export operation was just performed in the system.')
            ->line('**Export Details:**')
            ->line('- **Module:** ' . $this->exportType)
            ->line('- **Exported By:** ' . $this->exporterName)
            ->line('- **Date/Time:** ' . now()->format('d M Y, h:i A'))
            ->line('The exported file is attached for your reference.')
            ->line('Please ensure this data export complies with company policies.')
            ->line('Thank you!');

        if ($this->filePath && file_exists($this->filePath)) {
            $mail->attach($this->filePath);
        }

        return $mail;
    }

    public function toArray($notifiable): array
    {
        return [
            'message' => $this->exporterName . ' exported ' . $this->exportType . ' data.',
            'type' => 'data_exported',
        ];
    }
}
