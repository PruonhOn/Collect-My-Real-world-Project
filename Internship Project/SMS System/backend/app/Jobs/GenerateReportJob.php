<?php

namespace App\Jobs;

use App\Models\Report;
use App\Services\NotificationService;
use App\Services\ReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    /**
     * Create a new job instance.
     */
    public function __construct(public readonly Report $report) {}

    public function handle(ReportService $reports, NotificationService $notifications): void
    {
        try {
            $reports->generate($this->report);
        } catch (\Throwable $e) {
            $this->report->status = 'failed';
            $this->report->save();

            if ($this->report->generatedBy !== null) {
                $notifications->notify(
                    $this->report->generatedBy,
                    'report.failed',
                    'Report generation failed',
                    "Your {$this->report->type} report could not be generated.",
                );
            }

            throw $e;
        }
    }
}
