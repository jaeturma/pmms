<?php

namespace App\Notifications;

use App\Models\EligibilityReview;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CoachEligibilityRemarksNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly EligibilityReview $review,
        private readonly ?string $remarks,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $athlete = $this->review->athlete;
        $message = (new MailMessage)
            ->subject(__('Athlete eligibility review update'))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__('There is an eligibility review update for :athlete.', ['athlete' => $athlete->fullName()]))
            ->line(__('Current status: :status', ['status' => $this->review->status->label()]));

        if ($this->remarks !== null) {
            $message->line(__('Remarks: :remarks', ['remarks' => $this->remarks]));
        }

        return $message->action(__('Review athlete'), route('eligibility.reviews.show', $this->review));
    }
}
