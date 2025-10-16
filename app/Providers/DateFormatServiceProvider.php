<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class DateFormatServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
        // Custom Blade directive for US date formatting
        Blade::directive('usDate', function ($expression) {
            return "<?php echo \App\Helpers\DateFormatter::toUS($expression); ?>";
        });

        // Custom Blade directive for US date and time formatting
        Blade::directive('usDateTime', function ($expression) {
            return "<?php echo \App\Helpers\DateFormatter::toUSWithTime($expression); ?>";
        });

        // Custom Blade directive for US short date formatting
        Blade::directive('usShortDate', function ($expression) {
            return "<?php echo \App\Helpers\DateFormatter::toUSShort($expression); ?>";
        });
    }
}
