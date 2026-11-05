# mediapicker: notes for AI coding agents

`javaabu/mediapicker` is a Laravel package: a media library on spatie/laravel-medialibrary v11, attachments of library media to other models with their own (fingerprinted) conversions, a non-destructive cropper, library pages and picker fields in Blade (javaabu/forms v2) and Inertia React (@javaabu/forms, Onu), a stencil field, javaabu/block-editor blocks, and permissions through javaabu/permissions. It's a Composer package that also ships TypeScript (imported by apps as `@javaabu/mediapicker` through javaabu/manifest) and a prebuilt `dist/` for the Blade scripts.

## Layout

| Path | What |
| --- | --- |
| `src/Mediapicker.php` | Models, URLs, `pickerConfig()`, `image()` |
| `src/Routing/MediapickerRoutes.php` | `Route::mediapicker()` |
| `src/Http/` | `MediaController`, the requests, `MediaResource` |
| `src/Policies/MediaPolicy.php` | Abilities over javaabu/permissions' `ResourcePolicy` |
| `src/Models/` | `Media`, `Translatable\Media`, `Attachment`, observers |
| `src/Concerns/`, `src/Contracts/` | `OwnsMedia`/`MediaOwner`, `InteractsWithAttachments`/`HasAttachments` |
| `src/AttachmentCollections/` | `MediaAdder`, the repository, exceptions |
| `src/Conversions/` | `AttachmentConversion` (fingerprints), the manipulator, actions, metadata, stale-file cleaning |
| `src/Cropping/`, `src/Events/`, `src/Listeners/` | Crops and their regeneration |
| `src/Views/` | `ViewRenderer` (Blade or Inertia), the `picker` and `image` components |
| `src/Stencil/` | `MediaPickerField`, `MediaDetailsForm` |
| `src/Blocks/` | The block-editor blocks |
| `src/Support/` | `FileTypes`, `CropPresets`, `PickerConfig`, `ImageData`, `Strings`, `Translations`, `Icons` |
| `resources/views/` | Blade pages, components, stencil and block views |
| `resources/js/` | `Pages/Media/*`, `react/`, `picker/` (framework-free dialog and Blade field), `cropper/`, `blocks/`, `blade/` (entries), `api.ts` |
| `resources/css/mediapicker.css` | Every style, logical properties only |
| `dist/` | The Blade entries, built and **committed**. CI fails if it's stale |
| `lang/` | `en.json`, `dv.json` |
| `tests/` | PHPUnit (`Unit`, `Feature`, `Http`, `Translatable`) and Vitest (`tests/js`) |
| `docs`, `llms.txt`, `resources/boost` | Docs, the index for assistants, the Boost guideline and skill |

## Rules

- Follow the Javaabu PHP guidelines (`vendor/javaabu/boost-javaabu-guidelines`): snake_case variables and properties, camelCase methods, `#[Test]` methods named `it_…`, early returns, no `else`.
- Never depend on javaabu/helpers or javaabu/settings v1 (CI checks it). javaabu/translatable is optional: reference its classes only behind `Support\Translations`; `tests/Translatable` needs it, and CI runs the rest without it.
- Every user-facing string goes through `__()` with an entry in `lang/en.json` and `lang/dv.json` (a test checks). UI strings for JS are in `Support\Strings::STRINGS`; after changing them, regenerate `resources/js/strings.en.json` (a test compares them).
- Logical CSS and Tailwind only (`ms-`, `pe-`, `inset-inline-*`); tests use Thaana for user-facing behaviour.
- Attachment conversion files must stay under `conversions/attachments` and keep their fingerprint in the name.
- After changing `resources/js/blade`, `resources/js/picker`, `resources/js/cropper`, `resources/js/blocks` or `resources/css`, run `pnpm build` and commit `dist/`. After changing a block's PHP attributes, run `MEDIAPICKER_UPDATE_FIXTURES=1 vendor/bin/phpunit --filter JsFixtureTest`.
- Every change needs tests, docs (linked from `llms.txt`), the Boost skill if it changes usage, and a line in `CHANGELOG.md`.

## Commands

```bash
composer install && pnpm install      # pnpm 10.28 (packageManager)
composer lint && composer test        # Pint and PHPUnit (both suites)
vendor/bin/phpunit --testsuite "Test Suite"   # without the translatable tests
pnpm lint && pnpm format:check && pnpm typecheck && pnpm test
pnpm build                            # dist/
```
