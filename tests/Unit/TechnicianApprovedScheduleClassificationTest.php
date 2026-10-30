<?php

namespace Tests\Unit;

use App\Http\Controllers\api\v1\mobile_controllers\MobileTechnicianJobController;
use Illuminate\Support\Carbon;
use ReflectionClass;
use Tests\TestCase;

class TechnicianApprovedScheduleClassificationTest extends TestCase
{
    public function test_assigned_schedule_classification_uses_approved_window_boundaries(): void
    {
        $classify = $this->classifier();
        $approvedDate = '2025-09-27';
        $timeFrom = '14:00:00';
        $timeTo = '16:00:00';

        $this->assertSame(
            'UPCOMING',
            $classify('ASSIGNED', $approvedDate, $timeFrom, $timeTo, '2025-09-27 10:00:00')
        );
        $this->assertSame(
            'UPCOMING',
            $classify('ASSIGNED', '2025-09-28', $timeFrom, $timeTo, '2025-09-27 18:00:00')
        );
        $this->assertSame(
            'SERVICE_NOW',
            $classify('ASSIGNED', $approvedDate, $timeFrom, $timeTo, '2025-09-27 14:00:00')
        );
        $this->assertSame(
            'SERVICE_NOW',
            $classify('ASSIGNED', $approvedDate, $timeFrom, $timeTo, '2025-09-27 15:00:00')
        );
        $this->assertSame(
            'SERVICE_NOW',
            $classify('ASSIGNED', $approvedDate, $timeFrom, $timeTo, '2025-09-27 16:00:00')
        );
        $this->assertSame(
            'OVERDUE',
            $classify('ASSIGNED', $approvedDate, $timeFrom, $timeTo, '2025-09-27 16:00:01')
        );
        $this->assertSame(
            'OVERDUE',
            $classify('ASSIGNED', '2025-09-26', $timeFrom, $timeTo, '2025-09-27 10:00:00')
        );
    }

    public function test_started_workflow_statuses_are_not_reclassified_by_approved_end_time(): void
    {
        $classify = $this->classifier();

        foreach (['ONGOING', 'ARRIVED', 'DONE SERVICE', 'DONE REPORT'] as $status) {
            $this->assertNull(
                $classify(
                    $status,
                    '2025-09-27',
                    '14:00:00',
                    '16:00:00',
                    '2025-09-27 17:00:00'
                ),
                "Expected {$status} to remain governed by its existing workflow status."
            );
        }
    }

    public function test_assigned_job_without_approved_window_is_not_mislabeled_as_upcoming_or_overdue(): void
    {
        $classify = $this->classifier();

        $this->assertSame(
            'SCHEDULE_MISSING',
            $classify('ASSIGNED', null, null, null, '2025-09-27 10:00:00')
        );
    }

    private function classifier(): \Closure
    {
        $controller = app(MobileTechnicianJobController::class);
        $method = (new ReflectionClass($controller))
            ->getMethod('classifyApprovedSchedule');
        $method->setAccessible(true);

        return function ($status, $date, $from, $to, $currentAt) use ($controller, $method) {
            return $method->invoke(
                $controller,
                $status,
                $date,
                $from,
                $to,
                Carbon::parse($currentAt, config('app.timezone'))
            );
        };
    }
}
