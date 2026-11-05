<?php

namespace Javaabu\Mediapicker\Tests\Unit;

use Javaabu\Mediapicker\Support\Strings;
use Javaabu\Mediapicker\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TranslationsTest extends TestCase
{
    /**
     * @return array<string, string>
     */
    protected function lang(string $locale): array
    {
        return json_decode(file_get_contents(__DIR__ . "/../../lang/{$locale}.json"), true);
    }

    /**
     * Every string the package translates: __() and trans_choice() in the code and views,
     * the UI strings, permission descriptions, block titles and config labels
     *
     * @return array<int, string>
     */
    protected function usedStrings(): array
    {
        $root = __DIR__ . '/../..';
        $files = [
            ...glob("{$root}/src/{,*/,*/*/,*/*/*/}*.php", GLOB_BRACE),
            ...glob("{$root}/resources/views/{,*/,*/*/}*.blade.php", GLOB_BRACE),
        ];

        $strings = array_values(Strings::STRINGS);

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            preg_match_all("/(?:__|trans_choice)\\('((?:[^'\\\\]|\\\\.)*)'/", $contents, $matches);
            preg_match_all("/protected string \\\$(?:title|summary) = '((?:[^'\\\\]|\\\\.)*)'/", $contents, $blocks);
            preg_match_all("/'(?:view|edit|delete)(?:_others)?_media' => '((?:[^'\\\\]|\\\\.)*)'/", $contents, $permissions);

            $strings = [...$strings, ...array_map('stripslashes', [...$matches[1], ...$blocks[1], ...$permissions[1]])];
        }

        foreach (config('mediapicker.crop_presets') as $preset) {
            $strings[] = $preset['label'];
        }

        foreach (array_keys(config('mediapicker.file_types')) as $type) {
            $strings[] = str($type)->headline()->toString();
        }

        return array_values(array_unique(array_filter($strings)));
    }

    #[Test]
    public function every_string_has_english_and_dhivehi(): void
    {
        $en = $this->lang('en');
        $dv = $this->lang('dv');

        foreach ($this->usedStrings() as $string) {
            $this->assertArrayHasKey($string, $en, "lang/en.json is missing \"{$string}\"");
            $this->assertArrayHasKey($string, $dv, "lang/dv.json is missing \"{$string}\"");
        }

        $this->assertEquals(array_keys($en), array_keys($dv));
    }

    #[Test]
    public function the_dhivehi_strings_are_in_thaana_and_keep_their_placeholders(): void
    {
        foreach ($this->lang('dv') as $english => $dhivehi) {
            $this->assertMatchesRegularExpression('/[\x{0780}-\x{07BF}]/u', $dhivehi, "\"{$english}\" isn't translated");

            preg_match_all('/:[a-z_]+/', $english, $placeholders);

            foreach ($placeholders[0] as $placeholder) {
                $this->assertStringContainsString($placeholder, $dhivehi, "\"{$english}\" lost {$placeholder}");
            }
        }
    }

    #[Test]
    public function the_typescript_copy_of_the_ui_strings_matches(): void
    {
        $typescript = json_decode(file_get_contents(__DIR__ . '/../../resources/js/strings.en.json'), true);

        $this->assertEquals(Strings::STRINGS, $typescript);
    }

    #[Test]
    public function the_ui_strings_are_translated(): void
    {
        app()->setLocale('dv');

        $this->assertEquals('މީޑިއާ ލައިބްރަރީ', Strings::all()['media_library']);
        $this->assertEquals(':count ހޮވިފައި', Strings::all()['selected']);
    }
}
