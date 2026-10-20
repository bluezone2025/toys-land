<?php

namespace App\Console\Commands;

use App\Mail\CampaignMail;
use App\Models\EmailCampaign;
use App\Models\GuestCustomer;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class RunEmailCampaigns extends Command
{
    protected $signature = 'campaigns:run {campaign? : Send this campaign now regardless of its schedule}';

    protected $description = 'Queue recurring promotional emails that are due';

    public function handle(): int
    {
        $campaigns = $this->argument('campaign')
            ? EmailCampaign::whereKey($this->argument('campaign'))->get()
            : EmailCampaign::where('is_active', true)->whereNotNull('repeat_hours')->get()->filter->isDue();

        foreach ($campaigns as $campaign) {
            $count = static::dispatch($campaign);
            $this->info("Campaign #{$campaign->id}: queued {$count} email(s).");
        }

        return self::SUCCESS;
    }

    /**
     * Queue one email per recipient on the low-priority "newsletter" queue; the per-minute
     * worker drains it in small batches so the server is never flooded.
     */
    public static function dispatch(EmailCampaign $campaign): int
    {
        $count = 0;
        $userEmails = [];

        User::where('is_admin', false)->whereNull('unsubscribed_at')->whereNotNull('email')
            ->select(['id', 'name', 'email', 'locale'])
            ->chunkById(500, function ($users) use ($campaign, &$count, &$userEmails) {
                foreach ($users as $user) {
                    Mail::to($user->email)->queue(new CampaignMail($campaign, $user->name, 'user', $user->id, $user->locale ?? 'ar'));
                    $userEmails[strtolower($user->email)] = true;
                    $count++;
                }
            });

        if ($campaign->include_guests) {
            GuestCustomer::whereNull('unsubscribed_at')->select(['id', 'name', 'email', 'locale'])
                ->chunkById(500, function ($guests) use ($campaign, &$count, $userEmails) {
                    foreach ($guests as $guest) {
                        if (isset($userEmails[strtolower($guest->email)])) {
                            continue;
                        }
                        Mail::to($guest->email)->queue(new CampaignMail($campaign, $guest->name, 'guest', $guest->id, $guest->locale ?? 'ar'));
                        $count++;
                    }
                });
        }

        $campaign->forceFill([
            'last_run_at' => now(),
            'last_run_recipients' => $count,
            'sent_total' => $campaign->sent_total + $count,
        ])->save();

        return $count;
    }
}
