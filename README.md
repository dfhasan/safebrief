# SafeBrief

Turn an incident report into a crew-ready safety briefing in under a minute — and see who has actually read and understood it.

**Live demo:** [your-link] · demo login: `demo@safebrief.test` / `password`

![SafeBrief demo](docs/demo.gif)

## What it does
1. A manager starts a 14-day trial — no sales call.
2. They paste an incident or near-miss report.
3. AI drafts a short briefing plus a 3-question check; the manager reviews and publishes it.
4. Crew members open a personal link on their phone, read and answer.
5. The dashboard shows completion per briefing and the trial funnel.

## The metric
I instrumented the trial journey as a funnel: sign-up → first briefing → published → crew invited → first completion, with time to first value (sign-up to first crew completion). The step I'd work on first is *published → crew invited*: today the manager has to add crew members by hand before sending links, so I'd let them paste a crew list straight from the publish screen.

## How I built this with AI
- **Tool:** Claude Code, with a [`CLAUDE.md`](CLAUDE.md) that sets the rules (tenant isolation, human-in-the-loop, AI output validation, crew token access).
- **Loop:** written plan with acceptance criteria → I approved it → build in small steps with tests alongside → an independent verifier agent (which wrote no code) ran the suite, tried to break tenant isolation and token access, and removed protections to prove the tests catch it → fix its findings → re-run tests.
- **Where I didn't let AI decide:** tenant scoping, crew token access, and the rule that AI output is only ever a draft — these are fixed in `CLAUDE.md` and covered by tests that fail if the protection is removed.
- **Something AI got wrong and how I caught it:** the first version of the company scope *failed open* — a signed-in user with no company would have seen every company's records. The verifier flagged it; the scope now returns nothing in that case and a test locks it in. A crew link to an unpublished briefing also had no test until the verifier pointed it out.

## Technical decisions
- Laravel + Inertia + Vue 3, MySQL — one codebase with server-side routing and auth, but a reactive UI for the review/edit screen and the mobile crew page.
- AI behind an interface (`App\Ai\BriefingDrafter`) with a fake implementation, so tests and local dev need no API key. The real driver uses the official Anthropic PHP SDK with structured output.
- AI output is strict JSON, validated and retried once; it is always saved as a draft.
- Crew access through unguessable tokens (48 random characters), no accounts — crew are often contractors on shared or personal phones; asking them to sign up would kill completion. Links 404 unless the briefing is published, can be answered once, are rate-limited, and are sent with no-referrer / no-store headers.

## Running locally
```sh
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed       # creates the demo company and demo@safebrief.test
npm run build                    # Vite needs Node 20+ (Laragon's Node 22 works)
```
AI uses the fake drafter by default. To use Claude, set `AI_DRIVER=claude` and `ANTHROPIC_API_KEY` in `.env`.

## Tests
`php artisan test` — covers tenant isolation, crew link access, the trial gate and AI output validation (55 tests).

## What I'd do next
- SMS/WhatsApp delivery of crew links instead of copy-paste.
- Translations for multilingual crews.
- Reminders for unread briefings, and keeping completion history when a crew member is removed.
