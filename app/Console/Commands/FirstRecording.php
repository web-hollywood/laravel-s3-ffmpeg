<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;
use App\Video;

class FirstRecording extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'first_recording';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Email for first recording';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // fetches newly registered users with at least one video
        $users = User::where('created_at', '>=', new \DateTime('today'))
            ->has('videos')->get();

        foreach ($users as $user) {
            \Mail::send('emails.first_recording', ['user' => $user], function($message) use ($user) {
                $message->to($user->email, $user->getFullName())
                    ->subject(config('legacysuite.first_recording_subject'));
            });
        }
    }
}
