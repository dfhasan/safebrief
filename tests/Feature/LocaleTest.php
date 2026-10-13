<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_english_is_the_default_and_left_to_right(): void
    {
        $this->get('/login')
            ->assertSee('dir="ltr"', false)
            ->assertInertia(fn (Assert $page) => $page->where('locale', 'en')->where('dir', 'ltr')->where('translations.:count drafts to review|one', ':count draft to review'));
    }

    public function test_switching_to_arabic_sets_rtl_and_sends_translations(): void
    {
        $this->from('/login')->get('/locale/ar')->assertRedirect('/login');

        $this->get('/login')
            ->assertSee('lang="ar" dir="rtl"', false)
            ->assertInertia(fn (Assert $page) => $page
                ->where('locale', 'ar')
                ->where('dir', 'rtl')
                ->where('translations.Log in', 'تسجيل الدخول'));
    }

    public function test_unsupported_locale_is_rejected(): void
    {
        $this->get('/locale/xx')->assertNotFound();
        $this->get('/login')->assertInertia(fn (Assert $page) => $page->where('locale', 'en'));
    }

    public function test_validation_and_flash_messages_are_translated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->withSession(['locale' => 'ar'])
            ->post('/briefings', ['incident_text' => ''])
            ->assertSessionHasErrors(['incident_text' => 'حقل تقرير الحادث مطلوب.']);

        $this->actingAs($user)->withSession(['locale' => 'ar'])
            ->post('/crew', ['name' => 'Ana'])
            ->assertSessionHas('status', 'تمت إضافة فرد الطاقم.');
    }

    public function test_every_arabic_translation_keeps_its_placeholders(): void
    {
        foreach (['ar', 'en'] as $locale) {
            foreach (json_decode(file_get_contents(lang_path("{$locale}.json")), true) as $key => $translated) {
                preg_match_all('/:[a-z]+/', $key, $expected);
                preg_match_all('/:[a-z]+/', $translated, $actual);

                if (str_contains($key, '|')) {
                    // Plural forms may spell the number out (Arabic "يومان" = two days), so they can drop a placeholder.
                    $this->assertEmpty(array_diff($actual[0], $expected[0]), "Unknown placeholder in {$locale} \"{$key}\"");

                    continue;
                }

                sort($expected[0]);
                sort($actual[0]);
                $this->assertSame($expected[0], $actual[0], "Placeholders differ in {$locale} \"{$key}\"");
            }
        }
    }
}
