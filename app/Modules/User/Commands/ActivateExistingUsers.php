<?php

namespace App\Modules\User\Commands;

use App\Modules\User\Enums\UserStatusEnum;
use App\Modules\User\Models\User;
use Illuminate\Console\Command;

class ActivateExistingUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:activate-existing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to activate existing users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = User::where('status', UserStatusEnum::NEW)->get();

        foreach ($users as $user) {
            try {
                $user->status = UserStatusEnum::ACTIVE;
                $user->save();
            }
            catch (\Exception $e) {
                // Log the error but continue processing other users
                continue;
            }
        }
    }
}
