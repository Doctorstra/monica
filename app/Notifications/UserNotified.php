<?php

namespace App\Notifications;

use App\Models\User\User;
use App\Helpers\DateHelper;
use Illuminate\Bus\Queueable;
use App\Models\Contact\Contact;
use App\Models\Contact\Reminder;
use App\Interfaces\MailNotification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as LaravelNotification;

class UserNotified extends LaravelNotification implements ShouldQueue, MailNotification
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Reminder
     */
    public $reminder;
    /**
     * @var int|null
     */
    public $numberDaysBefore;

    /**
     * Create a new message instance.
     *
     * @param  Reminder  $reminder
     * @param  int|null  $numberDaysBefore
     * @return void
     */
    public function __construct(Reminder $reminder, ?int $numberDaysBefore)
    {
        $this->reminder = $reminder;
        $this->numberDaysBefore = $numberDaysBefore;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array
     */
    public function via()
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  User  $user
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail(User $user): MailMessage
    {
        $contact = Contact::where('account_id', $user->account_id)
            ->findOrFail($this->reminder->contact_id);

        $message = (new MailMessage)
            ->subject(trans('mail.subject_line', ['contact' => $contact->name]))
            ->greeting(trans('mail.greetings', ['username' => $user->first_name]))
            ->line(trans_choice('mail.notification_description', $this->numberDaysBefore, [
                'count' => $this->numberDaysBefore,
                'date' => DateHelper::getShortDate($this->reminder->calculateNextExpectedDate()),
            ]))
            ->line($this->reminder->title)
            ->line(trans('mail.for', ['name' => $contact->name]))
            ->action(trans('mail.footer_contact_info2', ['name' => $contact->name]), $contact->getLink());

        if (! is_null($this->reminder->description)) {
            $message = $message
                ->line(trans('mail.comment', ['comment' => $this->reminder->description]));
        }

        return $message;
    }
}
