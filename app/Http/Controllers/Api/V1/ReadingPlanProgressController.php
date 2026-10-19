<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateReadingPlanProgressRequest;
use App\Http\Resources\V1\UserReadingPlanResource;
use App\Models\ReadingPlanDay;
use App\Models\UserReadingPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ReadingPlanProgressController extends Controller
{
    public function update(
        UpdateReadingPlanProgressRequest $request,
        UserReadingPlan $userReadingPlan,
        ReadingPlanDay $readingPlanDay,
    ): UserReadingPlanResource {
        Gate::authorize('update', $userReadingPlan);
        abort_unless($readingPlanDay->reading_plan_id === $userReadingPlan->reading_plan_id, 404);

        DB::transaction(function () use ($request, $userReadingPlan, $readingPlanDay): void {
            $enrollment = UserReadingPlan::query()->lockForUpdate()->findOrFail($userReadingPlan->id);

            if ($request->boolean('completed')) {
                $enrollment->progress()->updateOrCreate(
                    ['reading_plan_day_id' => $readingPlanDay->id],
                    ['completed_at' => now()],
                );
            } else {
                $enrollment->progress()->whereBelongsTo($readingPlanDay, 'day')->delete();
            }

            $completedDays = $enrollment->progress()->count();
            $totalDays = $enrollment->readingPlan()->value('duration_days');
            $enrollment->update([
                'completed_at' => $completedDays === $totalDays ? now() : null,
            ]);
        });

        return new UserReadingPlanResource(
            $userReadingPlan->refresh()
                ->load(['readingPlan.days', 'progress'])
                ->loadCount('progress'),
        );
    }
}
