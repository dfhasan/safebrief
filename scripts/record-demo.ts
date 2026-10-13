/**
 * Records the SafeBrief demo with Playwright and turns it into docs/demo.gif.
 *
 *   npm run demo:gif            (Node 22+, ffmpeg on PATH or in $FFMPEG)
 *
 * Expects the app at DEMO_URL (default http://localhost:8000) with freshly seeded demo data
 * (php artisan migrate:fresh --seed) and AI_DRIVER=fake, so no API key is needed.
 *
 * The manager is recorded in a 1280x720 desktop window and the crew member in a 390x844 phone
 * window. Both videos run at the same time, so the script notes when the phone part starts and
 * ends, and ffmpeg stitches: desktop (until the link is copied) -> phone -> desktop dashboard.
 */
import { chromium, type Page } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';

const BASE = process.env.DEMO_URL ?? 'http://localhost:8000';
const ROOT = path.resolve(import.meta.dirname, '..');
const VIDEO_DIR = path.join(ROOT, 'docs', 'video');
const GIF = path.join(ROOT, 'docs', 'demo.gif');
const FFMPEG = process.env.FFMPEG ?? 'ffmpeg';
const MAX_GIF_BYTES = 8_000_000; // "under 8 MB" in decimal megabytes
const BRAND_NAVY = '0x15233B';

const REPORT =
    'Near miss, level 2 scaffold, east elevation. A labourer stepped back onto the working platform ' +
    'to collect a bucket and almost fell 4 metres: the top guardrail on that bay had been removed for ' +
    'a delivery and was never put back. A colleague grabbed his harness. No injury. The scaffold tag ' +
    'still showed "safe to use" and nobody had checked the bay after the delivery.';
const TITLE = 'Missing guardrail on the level 2 scaffold';
const CREW = ['Ali Hassan', 'Maria Lopez'];
// Answers the crew member picks (the fake AI always writes the same three questions).
const ANSWERS = ['Stop work and make the area safe', 'The same shift', 'PPE and isolation'];

const pause = (ms = 1000) => new Promise((resolve) => setTimeout(resolve, ms));
const seconds = (from: number, to: number) => Math.max(0, (to - from) / 1000);

/** Slightly larger text so it stays readable once the GIF is scaled down. */
const LARGER_TEXT = `addEventListener('DOMContentLoaded', () => { document.documentElement.style.fontSize = '112.5%'; });`;

async function smoothScroll(page: Page, top: number, ms = 900) {
    await page.evaluate((y) => window.scrollTo({ top: y, behavior: 'smooth' }), top);
    await pause(ms);
}

async function record() {
    // A PHP error page also returns 200, so look for the Inertia root element.
    const html = await fetch(`${BASE}/login`).then((r) => (r.ok ? r.text() : '')).catch(() => '');
    if (!html.includes('id="app"')) {
        throw new Error(`SafeBrief is not reachable at ${BASE}. Start it (php artisan serve) with seeded demo data first.`);
    }

    fs.rmSync(VIDEO_DIR, { recursive: true, force: true });
    fs.mkdirSync(VIDEO_DIR, { recursive: true });

    const browser = await chromium.launch();

    // ---- Manager, desktop -------------------------------------------------------------------
    const desk = await browser.newContext({
        viewport: { width: 1280, height: 720 },
        recordVideo: { dir: VIDEO_DIR, size: { width: 1280, height: 720 } },
    });
    await desk.grantPermissions(['clipboard-read', 'clipboard-write'], { origin: BASE });
    await desk.addInitScript(LARGER_TEXT);
    const page = await desk.newPage();
    const deskStart = Date.now();
    page.on('dialog', (dialog) => dialog.accept()); // "Publish this briefing?"

    // a) Log in
    await page.goto(`${BASE}/login`);
    const deskReady = Date.now();
    await pause(800);
    await page.getByLabel('Email', { exact: true }).pressSequentially('demo@safebrief.test', { delay: 35 });
    await page.getByLabel('Password', { exact: true }).pressSequentially('password', { delay: 50 });
    await pause(400);
    await page.getByRole('button', { name: 'Log in' }).click();
    await page.waitForURL('**/dashboard');
    await pause(1200);

    // b) New briefing from a near-miss report
    await page.getByRole('link', { name: 'New briefing' }).first().click();
    await page.waitForURL('**/briefings/create');
    await pause(700);
    await page.getByLabel('Incident or near-miss report').fill(REPORT);
    await pause(1300);
    await page.getByRole('button', { name: 'Draft briefing' }).click();
    await page.waitForURL(/\/briefings\/\d+$/);
    await page.getByText('This is an AI draft.').waitFor();
    await pause(1500);

    // c) Tidy the title, then publish
    const title = page.getByLabel('Title', { exact: true });
    await title.click();
    await title.press('Control+A');
    await title.pressSequentially(TITLE, { delay: 30 });
    await pause(700);
    await page.getByRole('button', { name: 'Publish', exact: true }).click();
    await page.getByText('Published. Now invite your crew.').waitFor();
    await pause(1200);

    // d) Links for two crew members, copy one
    for (const name of CREW) {
        await page.getByLabel(name, { exact: true }).check();
        await pause(350);
    }
    await page.getByRole('button', { name: 'Create links' }).click();
    await page.getByText('Not opened').first().waitFor();
    await pause(900);
    await page.getByRole('button', { name: 'Copy link' }).first().click();
    await page.getByRole('button', { name: 'Copied' }).waitFor();
    const crewUrl = await page.evaluate(() => navigator.clipboard.readText());
    if (!crewUrl.includes('/b/')) throw new Error(`Clipboard did not contain a crew link: ${crewUrl}`);
    await pause(1000);
    const phoneFrom = Date.now();

    // ---- Crew member, phone -----------------------------------------------------------------
    const phone = await browser.newContext({
        viewport: { width: 390, height: 844 },
        isMobile: true,
        hasTouch: true,
        deviceScaleFactor: 2,
        recordVideo: { dir: VIDEO_DIR, size: { width: 390, height: 844 } },
    });
    const mobile = await phone.newPage();
    const phoneStart = Date.now();

    // e) Read, answer, see the score
    await mobile.goto(crewUrl);
    await mobile.getByText('Quick check').waitFor();
    const phoneReady = Date.now();
    await pause(1600);
    await smoothScroll(mobile, 320, 1300);
    for (const answer of ANSWERS) {
        const option = mobile.getByLabel(answer, { exact: true });
        await option.evaluate((el) => el.scrollIntoView({ behavior: 'smooth', block: 'center' }));
        await pause(600);
        await option.check();
        await pause(500);
    }
    await mobile.getByRole('button', { name: 'Submit answers' }).click();
    const score = mobile.getByText(/You scored/);
    await score.waitFor();
    await score.evaluate((el) => el.scrollIntoView({ behavior: 'smooth', block: 'center' }));
    await pause(2200);
    const phoneUntil = Date.now();

    // ---- f) Back to the manager's dashboard ---------------------------------------------------
    const deskBack = Date.now();
    await page.getByRole('link', { name: 'Dashboard', exact: true }).first().click();
    await page.waitForURL('**/dashboard');
    await pause(1500);
    await smoothScroll(page, 260, 1200);
    await pause(2000);
    const deskUntil = Date.now();

    const deskVideo = page.video()!;
    const phoneVideo = mobile.video()!;
    await phone.close();
    await desk.close();
    await browser.close();

    return {
        deskVideo: await deskVideo.path(),
        phoneVideo: await phoneVideo.path(),
        // Cut points, in seconds from the start of each video.
        desk1: [seconds(deskStart, deskReady) - 0.2, seconds(deskStart, phoneFrom)],
        phone: [seconds(phoneStart, phoneReady) - 0.2, seconds(phoneStart, phoneUntil)],
        desk2: [seconds(deskStart, deskBack), seconds(deskStart, deskUntil)],
    };
}

function toGif(clips: Awaited<ReturnType<typeof record>>, fps: number, width: number) {
    const cut = ([from, to]: number[], input: string, extra: string, label: string) =>
        `[${input}]trim=start=${Math.max(0, from).toFixed(2)}:end=${to.toFixed(2)},setpts=PTS-STARTPTS,fps=${fps},${extra}setsar=1[${label}]`;

    const height = Math.round((width * 9) / 16 / 2) * 2;
    const filter = [
        `[0:v]split[d1][d2]`,
        cut(clips.desk1, 'd1', `scale=${width}:${height}:flags=lanczos,`, 'a'),
        // Phone: fit to the 16:9 frame height, centred on the brand navy.
        cut(clips.phone, '1:v', `scale=-2:${height}:flags=lanczos,pad=${width}:${height}:(ow-iw)/2:0:color=${BRAND_NAVY},`, 'b'),
        cut(clips.desk2, 'd2', `scale=${width}:${height}:flags=lanczos,`, 'c'),
        `[a][b][c]concat=n=3:v=1:a=0,split[s0][s1]`,
        `[s0]palettegen=max_colors=128:stats_mode=diff[p]`,
        `[s1][p]paletteuse=dither=bayer:bayer_scale=5:diff_mode=rectangle`,
    ].join(';');

    execFileSync(FFMPEG, ['-y', '-loglevel', 'error', '-i', clips.deskVideo, '-i', clips.phoneVideo, '-filter_complex', filter, '-loop', '0', GIF], {
        stdio: 'inherit',
    });
    return fs.statSync(GIF).size;
}

const clips = await record();
const length = clips.desk1[1] - clips.desk1[0] + (clips.phone[1] - clips.phone[0]) + (clips.desk2[1] - clips.desk2[0]);
console.log(`Recorded ${length.toFixed(1)}s of demo -> ${path.relative(ROOT, VIDEO_DIR)}`);

for (const [fps, width] of [
    [12, 960],
    [10, 960],
    [10, 800],
    [8, 800],
]) {
    const size = toGif(clips, fps, width);
    console.log(`demo.gif at ${fps} fps, ${width}px: ${(size / 1_000_000).toFixed(2)} MB`);
    if (size <= MAX_GIF_BYTES) break;
}
