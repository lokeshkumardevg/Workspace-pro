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
            ->subject("Salary Slip - {$monthName} {$this->payroll->year}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your automated Salary Slip for {$monthName} {$this->payroll->year} is attached below.")
            ->line("---")
            ->line("**Fixed Month Days:** {$this->payroll->working_days}")
            ->line("**Absent Days:** {$this->payroll->lop_days}")
            ->line("**Unpaid Leave Days:** {$this->payroll->lop_deduction}")
            ->line("**Payable Days:** {$this->payroll->present_days}")
            ->line("---")
            ->line("**Base Monthly Salary:** ₹{$this->payroll->base_salary}")
            ->line("**Bonuses:** ₹{$this->payroll->bonuses}")
            ->line("**Deductions (PF/Tax/Extra):** ₹{$this->payroll->deductions}")
            ->line("**Net Payable Salary:** ₹" . number_format($this->payroll->net_salary, 2))
            ->line("---")
            ->action('View details on Dashboard', url('/payroll'))
            ->line('Thank you!');
    }
}
