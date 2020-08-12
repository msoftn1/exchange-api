<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Команда создает пользователя.
 */
class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create {name} {email} {password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create User';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if($this->checkUser()) {
            $this->error('Пользователь с таким e-mail уже существует!');

            return -1;
        }

        $token = Str::random(60);//INC4X9FMOTjjwquSE2i7s0rxNl2KwnZGwFLeLHWjgN8ps7AoXxZh639gLSm4
        $this->createUser($token);

        $this->line(sprintf('Пользователь успешно создан. Токен: %s', $token));

        return 0;
    }

    private function checkUser()
    {
        return User::query()
            ->where('email', $this->argument('email'))
            ->count();
    }

    private function createUser($token)
    {
        $user = new User();
        $user->name = $this->argument('name');
        $user->email = $this->argument('email');
        $user->password = Hash::make($this->argument('password'));
        $user->api_token = hash('sha256', $token);
        $user->save();

        return $user;
    }

}
