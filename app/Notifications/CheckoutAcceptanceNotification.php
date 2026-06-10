<?php

namespace App\Notifications;

use App\Helpers\Helper;
use App\Models\Asset;
use App\Models\CheckoutAcceptance;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Channels\SlackWebhookChannel;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use NotificationChannels\GoogleChat\Card;
use NotificationChannels\GoogleChat\GoogleChatChannel;
use NotificationChannels\GoogleChat\GoogleChatMessage;
use NotificationChannels\GoogleChat\Section;
use NotificationChannels\GoogleChat\Widgets\KeyValue;
use NotificationChannels\MicrosoftTeams\MicrosoftTeamsChannel;
use NotificationChannels\MicrosoftTeams\MicrosoftTeamsMessage;

class CheckoutAcceptanceNotification extends Notification
{
    use Queueable;

    public $settings;
    public $item;
    public $acceptance;
    public $target;
    public $admin;

    public function __construct(
        Asset $asset,
        User $checkedOutTo,
        User $checkedOutBy,
        CheckoutAcceptance $acceptance,
        bool $sendCopy
    ) {
        $this->settings = Setting::getSettings();
        $this->item = $asset;
        $this->target = $checkedOutTo;
        $this->admin = $checkedOutBy;
        $this->acceptance = $acceptance;
        $this->sendCopy = $sendCopy;
    }

    /**
     * Delivery channels
     */
    public function via()
    {
        $notifyBy = [];

        if (
            Setting::getSettings()->webhook_selected === 'slack' ||
            Setting::getSettings()->webhook_selected === 'general'
        ) {
            $notifyBy[] = SlackWebhookChannel::class;
        }

        if (Setting::getSettings()->webhook_selected === 'microsoft') {
            $notifyBy[] = MicrosoftTeamsChannel::class;
        }

        if (Setting::getSettings()->webhook_selected === 'google') {
            $notifyBy[] = GoogleChatChannel::class;
        }

        return $notifyBy;
    }

    /**
     * Slack / general webhook payload
     */
    public function toSlack(): SlackMessage
    {
        $botname = $this->settings->webhook_botname ?: 'Snipe-Bot';
        $channel = $this->settings->webhook_channel ?: '';

        $fields = [
            trans('general.asset') => '<' . $this->item->present()->viewUrl() . '|' . $this->item->display_name . '>',
            trans('general.assigned_to') => '<' . $this->target->present()->viewUrl() . '|' . $this->target->display_name . '>',
            trans('general.administrator') => '<' . $this->admin->present()->viewUrl() . '|' . $this->admin->display_name . '>',
            'Send Copy' => $this->sendCopy ? 'true' : 'false',
        ];

        return (new SlackMessage)
            ->content(':white_check_mark: :computer: IT-Hardware angenommen')
            ->from($botname)
            ->to($channel)
            ->attachment(function ($attachment) use ($fields) {
                $attachment->title(
                    htmlspecialchars_decode($this->item->display_name),
                    $this->item->present()->viewUrl()
                )
                ->fields($fields)
                ->content('User has accepted the assigned asset.');
            });
    }

    /**
     * Microsoft Teams
     */
    public function toMicrosoftTeams()
    {
        if (!Str::contains($this->settings->webhook_endpoint, 'workflows')) {
            return MicrosoftTeamsMessage::create()
                ->to($this->settings->webhook_endpoint)
                ->type('success')
                ->title('IT-Hardware angenommen')
                ->fact('Asset', $this->item->display_name)
                ->fact('Assigned to', $this->target->display_name)
                ->fact('Approved by', $this->admin->display_name)
                ->fact('Acceptance ID', $this->acceptance->id);
        }

        return [
            'Asset Acceptance Confirmed',
            [
                'Asset' => $this->item->display_name,
                'Assigned to' => $this->target->display_name,
                'Approved by' => $this->admin->display_name,
                'Acceptance ID' => $this->acceptance->id,
            ],
        ];
    }

    /**
     * Google Chat
     */
    public function toGoogleChat()
    {
        return GoogleChatMessage::create()
            ->to($this->settings->webhook_endpoint)
            ->card(
                Card::create()
                    ->header(
                        '<strong>IT-Hardware angenommen</strong>',
                        $this->item->display_name
                    )
                    ->section(
                        Section::create(
                            KeyValue::create(
                                'Assigned to',
                                $this->target->display_name,
                                'User has accepted the asset'
                            )
                        )
                    )
            );
    }
}
