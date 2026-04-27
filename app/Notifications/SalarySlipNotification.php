<?php

namespace App\Notifications;

use App\Models\Payroll;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SalarySlipNotification extends Notification
{
    use Queueable;

    private $payroll;

    public function __construct(Payroll $payroll)
    {
        $this->payroll = $payroll;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $monthName = date("F", mktime(0, 0, 0, $this->payroll->month, 10));

        return (new MailMessage)
            ->subject("📄 Your Salary Slip for {$monthName} {$this->payroll->year}")
            ->greeting("Hello {$notifiable->name},")
            ->line("We are pleased to inform you that your salary slip for the month of **{$monthName} {$this->payroll->year}** has been generated.")
            ->panel(
                "* **Payable Days:** {$this->payroll->present_days} / {$this->payroll->working_days} Days\n" .
                "* **Deductions (LOP):** ₹{$this->payroll->lop_deduction}\n" .
                "* **Net Payable Salary:** ₹" . number_format($this->payroll->net_salary, 2)
            )
            ->line("**Payroll Breakdown Summarized:**")
            ->line("- **Base Salary:** ₹" . number_format($this->payroll->base_salary, 2))
            ->line("- **Bonuses/Allowance:** ₹" . number_format($this->payroll->bonuses, 2))
            ->line("- **General Deductions:** ₹" . number_format($this->payroll->deductions, 2))
            ->action('View Payroll Dashboard', url('/payroll'))
            ->line('Wishing you a productive month ahead!');
    }
}
