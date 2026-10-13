/**
 * Records every free-trial case of SafeBrief as one captioned demo:
 * docs/demo-trial-cases.mp4 and docs/demo-trial-cases.gif.
 *
 *   npm run demo:trial          (Node 22+, ffmpeg on PATH or in $FFMPEG)
 *
 * Needs the app at DEMO_URL (default http://localhost:8000) on seeded demo data with AI_DRIVER=fake,
 * and DEMO_DB pointing at the same SQLite file, because the script moves the trial clock with
 * `php artisan tinker` to reach each state (7 days left, 3 days left, last day, ended).
 */
import { chromium, devices, type Page } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';

const BASE = process.env.DEMO_URL ?? 'http://localhost:8000';
const ROOT = path.resolve(import.meta.dirname, '..');
const DB = process.env.DEMO_DB ?? path.join(ROOT, 'database', 'demo.sqlite');
const VIDEO_DIR = path.join(ROOT, 'docs', 'video', 'trial');
const MP4 = path.join(ROOT, 'docs', 'demo-trial-cases.mp4');
const GIF = path.join(ROOT, 'docs', 'demo-trial-cases.gif');
const FFMPEG = process.env.FFMPEG ?? 'ffmpeg';
const NAVY = '0x15233B';

const stamp = Date.now();
const EMAIL = `erin+${stamp}@harbourworks.test`;
const AR_EMAIL = `sara+${stamp}@harbourworks.test`;
const PASSWORD = 'harbour-safe-2026';

const pause = (ms = 1000) => new Promise((resolve) => setTimeout(resolve, ms));
const seconds = (from: number, to: number) => Math.max(0, (to - from) / 1000);

function php(code: string) {
    return execFileSync('php', ['artisan', 'tinker', `--execute=${code}`], {
        cwd: ROOT,
        env: { ...process.env, DB_CONNECTION: 'sqlite', DB_DATABASE: DB },
    })
        .toString()
        .trim();
}
/** Move a company's trial end, found through one of its users. */
const setTrial = (email: string, when: string) =>
    php(`App\\Models\\User::where('email', '${email}')->first()->company->update(['trial_ends_at' => ${when}]);`);

/** A caption bar naming the case on screen. Re-added after full page loads. */
async function caption(page: Page, n: number | string, title: string, detail = '') {
    await page.evaluate(
        ({ n, title, detail }) => {
            document.getElementById('demo-caption')?.remove();
            const bar = document.createElement('div');
            bar.id = 'demo-caption';
            bar.setAttribute('dir', 'ltr');
            bar.style.cssText =
                'position:fixed;left:16px;right:16px;bottom:16px;z-index:9999;display:flex;gap:14px;align-items:center;' +
                'padding:12px 18px;border-radius:10px;background:rgba(21,35,59,.94);color:#fff;' +
                'font:500 17px/1.35 "IBM Plex Sans",system-ui,sans-serif;box-shadow:0 8px 24px rgba(0,0,0,.25);pointer-events:none';
            bar.innerHTML =
                `<span style="flex:none;background:#FFC20E;color:#15233B;font-weight:700;border-radius:6px;padding:3px 9px">${n}</span>` +
                `<span><b>${title}</b>${detail ? `<span style="opacity:.75"> · ${detail}</span>` : ''}</span>`;
            document.body.appendChild(bar);
        },
        { n: String(n), title, detail },
    );
}

async function signUp(page: Page, o: { company?: string; name?: string; email?: string; password?: string; confirm?: string; button?: string }) {
    await page.fill('#company_name', o.company ?? 'Harbour Works');
    await page.fill('#name', o.name ?? 'Erin Walsh');
    await page.fill('#email', '');
    await page.locator('#email').pressSequentially(o.email ?? EMAIL, { delay: 12 });
    await page.fill('#password', o.password ?? PASSWORD);
    await page.fill('#password_confirmation', o.confirm ?? o.password ?? PASSWORD);
    await pause(300);
    await Promise.all([
        page.waitForResponse((r) => r.url().endsWith('/register') && r.request().method() === 'POST'),
        page.getByRole('button', { name: o.button ?? 'Start free trial' }).click(),
    ]);
    await pause(400);
}

async function record() {
    const html = await fetch(`${BASE}/login`).then((r) => (r.ok ? r.text() : '')).catch(() => '');
    if (!html.includes('id="app"')) throw new Error(`SafeBrief is not reachable at ${BASE}.`);

    fs.rmSync(VIDEO_DIR, { recursive: true, force: true });
    fs.mkdirSync(VIDEO_DIR, { recursive: true });
    const browser = await chromium.launch();

    // ---------------- Desktop: cases 1–14 ----------------
    const desk = await browser.newContext({ viewport: { width: 1280, height: 720 }, recordVideo: { dir: VIDEO_DIR, size: { width: 1280, height: 720 } } });
    const page = await desk.newPage();
    const deskStart = Date.now();

    await page.goto(BASE);
    const deskReady = Date.now();
    await caption(page, 1, 'Visitor lands on SafeBrief', 'one button starts a 14-day free trial');
    await pause(2600);
    await page.getByRole('link', { name: 'Start your 14-day free trial' }).click();
    await page.waitForURL('**/register');
    await caption(page, 2, 'Sign-up form', 'company, name, work email, password');
    await pause(2200);

    await signUp(page, { confirm: 'something-else' });
    await caption(page, 3, 'Error: passwords do not match', 'nothing is created');
    await pause(2200);
    await signUp(page, { password: 'short' });
    await caption(page, 4, 'Error: password too short', 'at least 8 characters');
    await pause(2200);
    await signUp(page, { email: 'demo@safebrief.test' });
    await caption(page, 5, 'Error: email already registered', 'one trial per email');
    await pause(2200);

    await signUp(page, { email: `Erin+${stamp}@HarbourWorks.test` });
    await page.waitForURL('**/dashboard');
    await caption(page, 6, 'Signed up with capitals in the email', 'saved lower-case · 14 days left · step guide starts');
    await pause(3200);

    await page.goto(`${BASE}/register`);
    await page.waitForURL('**/dashboard');
    await caption(page, 7, 'Signed in and opening /register', 'sent back to the dashboard');
    await pause(2000);

    setTrial(EMAIL, 'now()->addDays(7)');
    await page.reload();
    await caption(page, 8, 'Halfway: 7 days left', 'counter in the top bar');
    await pause(2200);

    setTrial(EMAIL, 'now()->addDays(3)->subMinute()');
    await page.reload();
    await caption(page, 9, '3 days left: warning banner', 'exact end date + “Keep SafeBrief” button');
    await pause(3000);

    setTrial(EMAIL, 'now()->addHours(5)');
    await page.reload();
    await caption(page, 10, 'Last day of the trial', 'still fully usable until the end time');
    await pause(2600);

    await page.goto(`${BASE}/trial-expired`);
    await page.waitForURL('**/dashboard');
    await caption(page, 11, 'Active trial opens /trial-expired', 'sent back to the dashboard');
    await pause(2000);

    setTrial(EMAIL, 'now()->subMinute()');
    await page.reload();
    await page.waitForURL('**/trial-expired');
    await caption(page, 12, 'Trial ended', 'data kept · email button to continue · no dead-end links');
    await pause(3400);

    for (const url of ['/briefings', '/briefings/create', '/crew']) {
        await page.goto(BASE + url);
        await page.waitForURL('**/trial-expired');
        await caption(page, 13, `Ended trial tries ${url}`, 'blocked, back to the same page');
        await pause(900);
    }

    await page.goto(`${BASE}/profile`);
    await caption(page, 14, 'Profile still opens after the trial', 'settings, password, log out');
    await pause(2400);
    const phoneFrom = Date.now();

    // ---------------- Phone: cases 15–16 ----------------
    const phone = await browser.newContext({ ...devices['iPhone 13'], recordVideo: { dir: VIDEO_DIR, size: { width: 390, height: 844 } } });
    const mobile = await phone.newPage();
    const phoneStart = Date.now();
    await mobile.goto(`${BASE}/login`);
    const phoneReady = Date.now();
    await caption(mobile, 15, 'Ended trial on a phone');
    await mobile.fill('#email', EMAIL);
    await mobile.fill('#password', PASSWORD);
    await mobile.getByRole('button', { name: 'Log in' }).click();
    await mobile.waitForURL('**/trial-expired');
    await caption(mobile, 15, 'Ended trial on a phone', 'same message');
    await pause(2800);

    setTrial('demo@safebrief.test', 'now()->subMinute()');
    const token = php(
        "echo App\\Models\\CrewLink::withoutGlobalScopes()->whereNull('completed_at')->whereHas('briefing', fn ($q) => $q->withoutGlobalScopes()->where('status', 'published'))->value('token');",
    );
    await mobile.goto(`${BASE}/b/${token}`);
    await caption(mobile, 16, 'Crew link after the trial ended', 'still works for the crew');
    await pause(2200);
    await mobile.evaluate(() => window.scrollTo({ top: 700, behavior: 'smooth' }));
    await pause(2000);
    setTrial('demo@safebrief.test', 'now()->addDays(10)');
    const phoneUntil = Date.now();

    // ---------------- Desktop again: Arabic, cases 17–20 ----------------
    await page.goto(`${BASE}/locale/ar`);
    await page.getByRole('button', { name: /Erin|^Demo/ }).first().click().catch(() => {});
    await page.getByRole('button', { name: 'تسجيل الخروج' }).first().click();
    await page.waitForURL(BASE + '/');
    await page.goto(`${BASE}/register`);
    const deskBack = Date.now();
    await caption(page, 17, 'Sign-up form in Arabic', 'right-to-left');
    await pause(2400);
    await signUp(page, { company: 'Harbour Works AR', name: 'Sara', email: AR_EMAIL, password: 'short', button: 'ابدأ التجربة المجانية' });
    await caption(page, 18, 'Sign-up error in Arabic', 'validation messages are translated');
    await pause(2400);
    await page.fill('#password', PASSWORD);
    await page.fill('#password_confirmation', PASSWORD);
    await page.getByRole('button', { name: 'ابدأ التجربة المجانية' }).click();
    await page.waitForURL('**/dashboard');
    await caption(page, 19, 'Day 1 in Arabic', '14 days left · step guide');
    await pause(2600);
    setTrial(AR_EMAIL, 'now()->addDays(2)');
    await page.reload();
    await caption(page, 19, 'Two days left in Arabic', 'warning banner, correct Arabic plural');
    await pause(2600);
    setTrial(AR_EMAIL, 'now()->subMinute()');
    await page.reload();
    await page.waitForURL('**/trial-expired');
    await caption(page, 20, 'Trial ended in Arabic', 'same rules, right-to-left');
    await pause(3200);
    const deskUntil = Date.now();

    const deskVideo = page.video()!;
    const phoneVideo = mobile.video()!;
    await phone.close();
    await desk.close();
    await browser.close();

    return {
        deskVideo: await deskVideo.path(),
        phoneVideo: await phoneVideo.path(),
        desk1: [seconds(deskStart, deskReady) - 0.2, seconds(deskStart, phoneFrom)],
        phone: [seconds(phoneStart, phoneReady), seconds(phoneStart, phoneUntil)],
        desk2: [seconds(deskStart, deskBack), seconds(deskStart, deskUntil)],
    };
}

function stitch(clips: Awaited<ReturnType<typeof record>>, out: string, fps: number, width: number, gif: boolean) {
    const height = Math.round((width * 9) / 16 / 2) * 2;
    const cut = ([from, to]: number[], input: string, extra: string, label: string) =>
        `[${input}]trim=start=${Math.max(0, from).toFixed(2)}:end=${to.toFixed(2)},setpts=PTS-STARTPTS,fps=${fps},${extra}setsar=1[${label}]`;
    const parts = [
        `[0:v]split[d1][d2]`,
        cut(clips.desk1, 'd1', `scale=${width}:${height}:flags=lanczos,`, 'a'),
        cut(clips.phone, '1:v', `scale=-2:${height}:flags=lanczos,pad=${width}:${height}:(ow-iw)/2:0:color=${NAVY},`, 'b'),
        cut(clips.desk2, 'd2', `scale=${width}:${height}:flags=lanczos,`, 'c'),
    ];
    const filter = gif
        ? [...parts, `[a][b][c]concat=n=3:v=1:a=0,split[s0][s1]`, `[s0]palettegen=max_colors=128:stats_mode=diff[p]`, `[s1][p]paletteuse=dither=bayer:bayer_scale=5:diff_mode=rectangle`].join(';')
        : [...parts, `[a][b][c]concat=n=3:v=1:a=0,format=yuv420p`].join(';');
    const codec = gif ? ['-loop', '0'] : ['-c:v', 'libx264', '-crf', '26', '-preset', 'slow', '-movflags', '+faststart'];
    execFileSync(FFMPEG, ['-y', '-loglevel', 'error', '-i', clips.deskVideo, '-i', clips.phoneVideo, '-filter_complex', filter, ...codec, out], { stdio: 'inherit' });
    return fs.statSync(out).size / 1024 / 1024;
}

const clips = await record();
const length = clips.desk1[1] - clips.desk1[0] + (clips.phone[1] - clips.phone[0]) + (clips.desk2[1] - clips.desk2[0]);
console.log(`Recorded ${length.toFixed(1)}s covering all 20 trial cases`);
console.log(`demo-trial-cases.mp4: ${stitch(clips, MP4, 24, 1280, false).toFixed(2)} MB`);
console.log(`demo-trial-cases.gif: ${stitch(clips, GIF, 8, 800, true).toFixed(2)} MB`);
