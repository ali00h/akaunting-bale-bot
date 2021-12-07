<?php

namespace Modules\BaleBot\Listeners;

use App\Events\Module\SettingShowing as Event;

class ShowInSettingsPage
{
    /**
     * Handle the event.
     *
     * @param  Event $event
     * @return void
     */
    public function handle(Event $event)
    {
        $event->modules->settings['bale-bot'] = [
            'name' => 'Bale Bot',
            'description' => trans('offline-payments::general.description'),
            'url' => route('offline-payments.settings.get'),
            'icon' => 'fas fa-credit-card',
        ];

        //echo "<pre>";print_r($event->modules->settings);echo "</pre>";
        //exit();
    }
}
