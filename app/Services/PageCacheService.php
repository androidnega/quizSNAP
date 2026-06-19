<?php

namespace App\Services;

use App\Models\ClassGroup;
use App\Models\ClassGroupStudent;
use App\Models\Course;
use App\Models\ExamCalendar;
use App\Models\Quiz;
use App\Models\QuizSession;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class PageCacheService
{
    public const VERSION_KEY = 'pagecache:version';

    public const LANDING_PUBLIC_KEY = 'pagecache:landing:public';

    public const ADMIN_OVERVIEW_KEY = 'pagecache:admin:overview';

    public function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }

    public function bumpVersion(): void
    {
        $next = $this->version() + 1;
        Cache::forever(self::VERSION_KEY, $next);
        Cache::forget(self::LANDING_PUBLIC_KEY);
        Cache::forget(self::ADMIN_OVERVIEW_KEY);
    }

    /**
     * Cached public landing settings (hero, branding). Safe for guests and logged-in students.
     *
     * @return array{
     *   appName: string,
     *   institutionName: ?string,
     *   landingHeroImage: ?string,
     *   landingHeroEnabled: bool,
     *   landingShowQuizToken: bool
     * }
     */
    public function landingPublicData(): array
    {
        $ttl = config('page-cache.landing_public_ttl', 900);
        $version = $this->version();

        return Cache::remember(self::LANDING_PUBLIC_KEY.':v'.$version, $ttl, function () {
            $keys = [
                Setting::KEY_APP_NAME,
                Setting::KEY_INSTITUTION_NAME,
                Setting::KEY_LANDING_HERO_IMAGE,
                Setting::KEY_LANDING_HERO_ENABLED,
                Setting::KEY_LANDING_SHOW_QUIZ_TOKEN,
            ];
            $settings = Setting::getMany($keys, [
                Setting::KEY_LANDING_HERO_ENABLED => '1',
                Setting::KEY_LANDING_SHOW_QUIZ_TOKEN => '0',
            ]);

            return [
                'appName' => $settings[Setting::KEY_APP_NAME] ?? config('app.name', 'QuizSnap'),
                'institutionName' => $settings[Setting::KEY_INSTITUTION_NAME] ?? null,
                'landingHeroImage' => $settings[Setting::KEY_LANDING_HERO_IMAGE] ?? null,
                'landingHeroEnabled' => ($settings[Setting::KEY_LANDING_HERO_ENABLED] ?? '1') === '1',
                'landingShowQuizToken' => ($settings[Setting::KEY_LANDING_SHOW_QUIZ_TOKEN] ?? '0') === '1',
            ];
        });
    }

    /**
     * Super-admin dashboard aggregate counts.
     *
     * @return array<string, int>
     */
    public function adminOverviewStats(): array
    {
        $ttl = config('page-cache.admin_overview_ttl', 120);
        $version = $this->version();

        return Cache::remember(self::ADMIN_OVERVIEW_KEY.':v'.$version, $ttl, function () {
            $sessionsWithResult = QuizSession::whereNotNull('ended_at')->whereHas('result')->count();

            return [
                'users' => User::count(),
                'courses' => Course::count(),
                'class_groups' => ClassGroup::count(),
                'students' => Student::count(),
                'quizzes' => Quiz::count(),
                'sessions' => $sessionsWithResult,
                'results' => $sessionsWithResult,
            ];
        });
    }

    /**
     * Coordinator dashboard stats scoped to the coordinator.
     *
     * @return array<string, int>
     */
    public function coordinatorStats(User $user): array
    {
        $ttl = config('page-cache.coordinator_stats_ttl', 120);
        $classGroupIds = $user->classGroupIds();
        $cacheKey = 'pagecache:coordinator:'.$user->id.':'.md5(json_encode($classGroupIds));

        return Cache::remember($cacheKey, $ttl, function () use ($user, $classGroupIds) {
            $classGroupsCount = empty($classGroupIds)
                ? 0
                : ClassGroup::whereIn('id', $classGroupIds)->count();

            $courseIds = $user->assignedCourseIds();
            $coursesCount = empty($courseIds)
                ? 0
                : Course::whereIn('id', $courseIds)->where('is_archived', false)->count();

            $examinersCount = $user->examinersInScope()->count();

            $examCalendarCount = empty($classGroupIds)
                ? 0
                : ExamCalendar::whereIn('class_group_id', $classGroupIds)->count();

            $studentsCount = empty($classGroupIds)
                ? 0
                : ClassGroupStudent::whereIn('class_group_id', $classGroupIds)->distinct()->count('index_number');

            return [
                'class_groups' => $classGroupsCount,
                'courses' => $coursesCount,
                'examiners' => $examinersCount,
                'exam_calendar' => $examCalendarCount,
                'students' => $studentsCount,
            ];
        });
    }

    /**
     * Student class-group resolution (index page hot path).
     *
     * @return array{classGroupIds: array<int>, classGroupIdsKey: string}
     */
    public function studentClassGroupIds(string $indexNumber): array
    {
        $normalized = trim($indexNumber);
        if ($normalized === '') {
            return ['classGroupIds' => [], 'classGroupIdsKey' => 'empty'];
        }

        $ttl = config('page-cache.student_class_groups_ttl', 120);
        $cacheKey = 'pagecache:student:cg:'.hash('sha256', $normalized);

        $classGroupIds = Cache::remember($cacheKey, $ttl, function () use ($normalized) {
            return ClassGroupStudent::allByIndexNumber($normalized)
                ->pluck('class_group_id')
                ->unique()
                ->filter()
                ->values()
                ->all();
        });

        return [
            'classGroupIds' => $classGroupIds,
            'classGroupIdsKey' => md5(json_encode($classGroupIds)),
        ];
    }

    public function forgetStudentClassGroups(string $indexNumber): void
    {
        $normalized = trim($indexNumber);
        if ($normalized !== '') {
            Cache::forget('pagecache:student:cg:'.hash('sha256', $normalized));
        }
    }

    public static function landingSettingKeys(): array
    {
        return [
            Setting::KEY_APP_NAME,
            Setting::KEY_INSTITUTION_NAME,
            Setting::KEY_LANDING_HERO_IMAGE,
            Setting::KEY_LANDING_HERO_ENABLED,
            Setting::KEY_LANDING_SHOW_QUIZ_TOKEN,
        ];
    }
}
