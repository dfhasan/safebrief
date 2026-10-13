# SafeBrief — rules for AI agents

Laravel 13 + Inertia + Vue 3 (Breeze), MySQL in dev (Laragon), SQLite in tests.
A manager turns an incident report into a crew briefing with a 3-question check; crew read and answer via personal token links.

## Non-negotiable rules

1. **Tenant isolation.** Every company-owned model (`Briefing`, `CrewMember`, `CrewLink`) uses `App\Models\Concerns\BelongsToCompany`.
   - The global scope must fail closed: a signed-in user without a company sees nothing.
   - Never call `withoutGlobalScope('company')` outside the crew token path (`CrewBriefingController`).
   - Validation rules that reference other records (`exists:`) must be scoped to the company.
   - Models without the trait (`Question`, `FunnelEvent`) are only reached through a scoped parent or filtered by `company_id` explicitly.
2. **Human in the loop.** AI output is always saved as a `draft`. Only a manager action (`BriefingController::publish`) publishes. No code path may auto-publish.
3. **AI output validation.** The model must return strict JSON. `App\Ai\BriefingDraftService` validates it, retries once with the rejection reason, then throws `InvalidDraftException`. Do not loosen the validation to make a model "pass".
4. **AI behind an interface.** Code depends on `App\Ai\BriefingDrafter`. `FakeBriefingDrafter` is bound by default; `ClaudeBriefingDrafter` is used only when `AI_DRIVER=claude` and `ANTHROPIC_API_KEY` are set. Tests and local dev must never need an API key or the network.
5. **Crew access.** Crew have no accounts; the 48-char random token is the credential.
   - Unknown tokens and links to unpublished briefings return 404.
   - `correct_index` is never sent to the browser before the crew member submits.
   - A link can be completed once (atomic `whereNull('completed_at')` update).
   - Crew routes keep `NoReferrer` (no referrer, no caching) and rate limits.
6. **Trial gate.** App routes sit behind the `trial` middleware (`EnsureTrialActive`, 14 days from sign-up). Crew links must keep working after a trial expires.
7. **Funnel.** Record steps with `App\Support\Funnel::record()` (once per company): `signed_up → first_briefing → published → crew_invited → first_completion`.

8. **English + Arabic.** Every UI string goes through `$t('English text')` (Vue) or `__('English text')` (PHP), with the Arabic in `lang/ar.json` (validation in `lang/ar/*.php`). Keep `:placeholders` identical — `LocaleTest` checks this. Use logical Tailwind classes (`ms-`, `pe-`, `start-`, `text-start`) so RTL works, and put `dir="auto"` on elements that show user-written text.
9. **Setup trail.** `App\Support\Onboarding` decides the manager's next step (crew → report → publish → invite → first completion); `StepTrail.vue` shows it. New activation steps go there, not in ad-hoc banners.

## Working agreement

- Plan first, then small diffs. Write or update a test with every behaviour change.
- `php artisan test` must pass before anything is called done. For security-relevant changes, prove the test fails when the protection is removed.
- Never commit secrets; `.env` stays out of git.

## Commands

```sh
php artisan test                 # full suite (SQLite in-memory)
php artisan migrate --seed       # demo data: demo@safebrief.test / password
npm run build                    # needs Node 22: export PATH="/c/laragon/bin/nodejs/node-v22:$PATH"
```
