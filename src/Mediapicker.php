<?php

namespace Javaabu\Mediapicker;

use Javaabu\Mediapicker\Contracts\Attachment;

class Mediapicker
{
    /**
     * @return class-string<Attachment>
     */
    public static function attachmentModel(): string
    {
        return config('mediapicker.attachment_model');
    }

    /**
     * @return class-string<Attachment>
     */
    /*public static function attachmentModel(): string
    {
        return config('mediapicker.attachment_model');
    }*/

    /**
     * Register the admin routes
     */
    /*public static function registerRoutes(
        string $url = '/stats/time-series',
        string $index_name = 'stats.time-series.index',
        string $export_name = 'stats.time-series.export',
        array  $middleware = ['stats.view-time-series']
    )
    {
        Route::get($url, [TimeSeriesStatsController::class, 'index'])
            ->name($index_name)
            ->middleware($middleware);

        Route::post($url, [TimeSeriesStatsController::class, 'export'])
            ->name($export_name)
            ->middleware($middleware);
    }*/
}
