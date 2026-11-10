---
title: Dhivehi and RTL
sidebar_position: 6
---

# Translations

Every string is in `lang/en.json` and `lang/dv.json`, used as JSON translation strings (the English text is the key). Publish them to change the wording:

```bash
php artisan vendor:publish --tag=mediapicker-translations
```

The JavaScript UIs (the picker dialog, the Blade field, the cropper, the React pages and the editor blocks) get their strings from PHP (`Javaabu\Mediapicker\Support\Strings::all()`) in the current locale, so one set of translations covers both stacks.

# RTL

The pages, pickers, cropper and blocks use logical CSS (`margin-inline-start`, `inset-inline-end`, Tailwind's `ms-`, `pe-`) only, so they mirror in right-to-left pages. File names, sizes, URLs and dimensions keep their own direction (`dir="auto"` or `dir="ltr"`). The picker dialog takes the page's direction.

# Thaana

Name, description, alt text and caption inputs type Thaana (javaabu/forms' `thaana`, stencil's `->thaana()`); the React search box does too (Onu's `searchThaana`). Block settings for alt text and captions type Thaana in the editor.

# Needs review

These translations were written without a native speaker's review. Please check them:

| English | Dhivehi | Note |
| --- | --- | --- |
| Alt text | ބަދަލު ޓެކްސްޓް | A literal "alternative text"; a clearer term may be better |
| Aspect ratio | ދިގުމިނާއި ފުޅާމިނުގެ ނިސްބަތް | Long; a shorter common term? |
| Crop / Cropped / Crops | ކްރޮޕް … | Transliterated |
| Dimensions | ސައިޒު (ޕިކްސެލް) | |
| Free (crop preset) | މިނެއް ނެތް | "No fixed size" |
| Landscape (4:3) / Portrait (3:4) | ފުޅާ / ދިގު | |
| Wide (16:9) | ވައިޑް (16:9) | Transliterated |
| Square | ހަތަރެސްކަން | |
| Loop | ތަކުރާރުކުރޭ | |
| Muted | އަޑު ނުލާ | |
| From / To (date filters) | މި ތާރީޚުން / މި ތާރީޚާ ހަމައަށް | |
| Only mine | އަހަރެން އަޕްލޯޑް ކުރި އެއްޗެހި | |
| Rotate left / right | ވާތަށް / ކަނާތަށް އަނބުރާ | Not shown in the UIs yet |
