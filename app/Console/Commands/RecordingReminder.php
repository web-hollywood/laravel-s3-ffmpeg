<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;
use App\Video;

class RecordingReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recording_reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send recording reminder every 24 hours';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // fetches not new users who have uploaded at least 1 video in last 24 hours
        $users = User::where('created_at', '<=', new \DateTime('today'))
            ->whereHas('videos', function ($query) {
                $query->where('created_at', '>=', new \DateTime('today'));
            })
            ->with('videosCountLastDay')
            ->get();

        foreach ($users as $user) {
            \Mail::send('emails.recording_reminder', ['user' => $user, 'video_count' => $user->videosCountLastDay ], function($message) use ($user) {
                $message->to($user->email, $user->getFullName())
                    ->subject(config('legacysuite.recording_reminder_subject'));
            });
        }
    }
}
