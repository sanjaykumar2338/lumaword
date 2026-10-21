<?php

namespace App;

enum DailyActivityType: string
{
    case DevotionalRead = 'devotional_read';
    case ReadingPlanCompleted = 'reading_plan_completed';
    case SermonStudy = 'sermon_study';
}
