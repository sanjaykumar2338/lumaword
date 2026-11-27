<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\SermonCompanionDevotionalDayResource;
use App\Http\Resources\V1\SermonCompanionDevotionalPlanResource;
use App\Models\SermonCompanion;
use App\Models\SermonCompanionDevotionalDay;
use App\Models\SermonCompanionDevotionalPlan;
use App\Models\SermonCompanionProgress;
use App\Services\BibleReaderService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class SermonCompanionDevotionalController extends Controller
{
    public function store(Request $request, SermonCompanion $sermonCompanion, BibleReaderService $reader): SermonCompanionDevotionalPlanResource
    {
        abort_unless($sermonCompanion->user_id === $request->user()->id, 403);
        $days = $request->validate(['days_count' => ['required', 'in:5,7']])['days_count'];
        $plan = $sermonCompanion->devotionalPlans()->firstOrCreate(['days_count' => $days], ['user_id' => $request->user()->id, 'status' => 'generating']);
        if ($plan->days()->count() === 0) {
            $reference = collect($sermonCompanion->scripture_references ?? [])->first();
            if (! is_string($reference)) {
                throw ValidationException::withMessages(['scripture_references' => 'Add at least one verified Scripture reference before generating a devotional.']);
            }
            $parsed = $reader->parseReference($reference);
            if ($parsed === null) {
                throw ValidationException::withMessages(['scripture_references' => 'The Scripture reference could not be verified.']);
            }
            $translation = (string) config('bible.default_translation');
            $book = $reader->resolveBookId($translation, $parsed['book']);
            $passage = $reader->passage($translation, $book, $parsed['chapter'], $parsed['verse_start'], $parsed['verse_end']);
            $text = collect($passage['verses'])->map(fn (array $verse): string => $verse['number'].' '.$verse['text'])->implode("\n");
            $sermonCompanion->memoryCards()->firstOrCreate(['reference' => $passage['book']['name'].' '.$passage['chapter'].':'.$passage['verse_start'].'-'.$passage['verse_end'], 'translation' => $passage['translation']['abbreviation']], ['verse_text' => $text, 'fill_in_blank' => ['text' => preg_replace('/\b\w+\b/', '____', $text, 1)], 'correct_answers' => [$text]]);
            for ($day = 1; $day <= $days; $day++) {
                $plan->days()->create(['day_number' => $day, 'title' => "Day {$day}: ".$sermonCompanion->title, 'theme' => $sermonCompanion->key_takeaways[$day - 1] ?? 'Living out the sermon theme', 'scripture_reference' => $passage['book']['name'].' '.$passage['chapter'].':'.$passage['verse_start'].'-'.$passage['verse_end'], 'translation' => $passage['translation']['abbreviation'], 'scripture_text' => $text, 'reflection' => 'Reflect on the sermon notes and how this Scripture speaks to today.', 'reflection_question' => 'What is one faithful response you can make today?', 'practical_application' => 'Choose one specific action that puts this teaching into practice.', 'prayer' => 'God, help me live this truth with faithfulness today.']);
            }
            $plan->update(['status' => 'completed', 'generated_at' => now()]);
            SermonCompanionProgress::create(['sermon_companion_devotional_plan_id' => $plan->id, 'user_id' => $request->user()->id, 'current_day' => 1, 'completed_days' => [], 'last_activity_at' => now()]);
        }

        return new SermonCompanionDevotionalPlanResource($plan->refresh()->load(['days', 'progress']));
    }

    public function days(Request $request, SermonCompanionDevotionalPlan $plan): SermonCompanionDevotionalDayResource|AnonymousResourceCollection
    {
        abort_unless($plan->user_id === $request->user()->id, 403);

        return SermonCompanionDevotionalDayResource::collection($plan->days()->orderBy('day_number')->get());
    }

    public function complete(Request $request, SermonCompanionDevotionalDay $day): SermonCompanionDevotionalDayResource
    {
        $plan = $day->plan;
        abort_unless($plan->user_id === $request->user()->id, 403);
        $day->update(['completed_at' => now()]);
        $completed = $plan->days()->whereNotNull('completed_at')->pluck('day_number')->sort()->values()->all();
        $progress = $plan->progress()->firstOrCreate(['user_id' => $request->user()->id], ['completed_days' => []]);
        $progress->update(['completed_days' => $completed, 'current_day' => min($plan->days_count, count($completed) + 1), 'completion_percentage' => (int) round(count($completed) / $plan->days_count * 100), 'last_activity_at' => now(), 'completed_at' => count($completed) === $plan->days_count ? now() : null]);

        return new SermonCompanionDevotionalDayResource($day->refresh());
    }
}
