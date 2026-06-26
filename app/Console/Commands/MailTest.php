<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Live mail diagnostics. Prints the EFFECTIVE mail config (so a silent
 * MAIL_MAILER=log is obvious), reports the queue state (queued mailables don't
 * send without a worker), then sends a real test email and surfaces the exact
 * SMTP error if it fails.
 *
 *   php artisan mail:test you@example.com
 */
class MailTest extends Command
{
    protected $signature = 'mail:test {email : Address to send the test message to}';

    protected $description = 'Diagnose mail delivery: show effective config, queue state, and send a test email';

    public function handle(): int
    {
        $email = $this->argument('email');
        $password = (string) config('mail.mailers.smtp.password');

        $this->info('Effective mail configuration (from the active .env):');
        $this->table(['Setting', 'Value'], [
            ['MAIL_MAILER (mail.default)', config('mail.default')],
            ['SMTP host:port', config('mail.mailers.smtp.host').':'.config('mail.mailers.smtp.port')],
            ['SMTP username', config('mail.mailers.smtp.username') ?: '(EMPTY)'],
            ['SMTP password', $password !== '' ? '(set, '.strlen($password).' chars)' : '(EMPTY)'],
            ['SMTP scheme', config('mail.mailers.smtp.scheme') ?: '(none / STARTTLS)'],
            ['From', config('mail.from.address').' <'.config('mail.from.name').'>'],
            ['QUEUE_CONNECTION', config('queue.default')],
        ]);

        if (config('mail.default') === 'log') {
            $this->warn('MAIL_MAILER is "log" — emails are written to storage/logs/laravel.log, NOT sent.');
            $this->warn('Set MAIL_MAILER=smtp (and the SMTP credentials) in the server .env, then run: php artisan config:clear');
        }

        // Queued mailables (owner/manager welcome) need a running worker.
        try {
            $pending = DB::table('jobs')->count();
            $failed = DB::table('failed_jobs')->count();
            $this->line("Queue: pending jobs = {$pending}, failed jobs = {$failed}");

            if (config('queue.default') !== 'sync' && $pending > 0) {
                $this->warn("There are {$pending} queued job(s). Owner/manager welcome emails are queued and only send when a worker runs.");
                $this->warn('Quick fix on shared hosting: set QUEUE_CONNECTION=sync in .env (sends immediately), or run a queue worker via cron.');
            }
        } catch (\Throwable $e) {
            $this->line('Queue tables not reachable: '.$e->getMessage());
        }

        $this->newLine();
        $this->info("Sending a test email to {$email} ...");

        try {
            Mail::raw(
                'Test email from '.config('app.name').' ('.config('app.url').'). If you received this, SMTP is working.',
                function ($message) use ($email) {
                    $message->to($email)->subject('Mail test — '.config('app.name'));
                }
            );

            if (config('mail.default') === 'log') {
                $this->warn('No exception — but MAIL_MAILER=log, so it went to storage/logs/laravel.log, not to the inbox.');
            } else {
                $this->info('Sent with no exception. Check the inbox AND spam folder of '.$email.'.');
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('SEND FAILED: '.get_class($e));
            $this->error($e->getMessage());
            $this->newLine();
            $this->line('Common causes: wrong SMTP credentials (Gmail needs an App Password, not your account password →');
            $this->line('error code 535), the host blocking outbound port 587/465, or a wrong host/port.');

            return self::FAILURE;
        }
    }
}
