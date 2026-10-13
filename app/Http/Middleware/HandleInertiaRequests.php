<?php

namespace App\Http\Middleware;

use App\Support\Onboarding;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'trial' => fn () => ($company = $request->user()?->company) ? [
                'company' => $company->name,
                'daysLeft' => $company->trialDaysLeft(),
                'active' => $company->trialActive(),
                'endsAt' => $company->trial_ends_at->toIso8601String(),
                'contactEmail' => config('services.safebrief.contact_email'),
            ] : null,
            'flash' => [
                // Breeze uses slugs like "profile-updated" that its forms show themselves; only pass sentences.
                'status' => fn () => preg_match('/^[a-z-]+$/', (string) $status = $request->session()->get('status'))
                    ? null
                    : $status,
            ],
            'locale' => fn () => app()->getLocale(),
            'dir' => fn () => in_array(app()->getLocale(), SetLocale::RTL, true) ? 'rtl' : 'ltr',
            'translations' => fn () => $this->translations(app()->getLocale()),
            'onboarding' => fn () => ($company = $request->user()?->company) && $company->trialActive() ? Onboarding::for($company) : null,
        ];
    }

    /**
     * UI strings for the current locale, keyed by their English text (lang/{locale}.json).
     *
     * @return array<string, string>
     */
    private function translations(string $locale): array
    {
        $file = lang_path("{$locale}.json");

        return is_file($file) ? json_decode(file_get_contents($file), true) : [];
    }
}
