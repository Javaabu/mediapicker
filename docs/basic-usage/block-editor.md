---
title: Block editor blocks
sidebar_position: 6
---

The package adds four blocks to [javaabu/block-editor](https://github.com/Javaabu/block-editor) that pick from the media library:

| Block | Title | |
| --- | --- | --- |
| `image` | Image | Replaces the core URL image block. Alt text, caption and link in its settings |
| `gallery` | Gallery | Several images in a grid of 1–6 columns, with a caption |
| `file` | File | A download link with the file's name and size |
| `mediaVideo` | Video file | An uploaded video, with controls, autoplay, loop and muted. The core `embed` block keeps YouTube and Vimeo |

They're registered when the package boots (turn them off with `'blocks' => false` in `config/mediapicker.php`), and the `default` profile (`['*']`) offers them. Add them to your own profiles by name:

```php
BlockEditor::profile('article', ['paragraph', 'heading', 'image', 'gallery', 'file', 'mediaVideo']);
```

# In the editor

Inserting a block opens the library dialog; the chosen media fills it. Each block has a **Change** button to pick again. The picker uses the library of the signed-in user's guard; set another with `Javaabu\Mediapicker\Blocks\MediaBlock::$library = 'admin.media'` in a service provider.

The editor requires the blocks' script (`mediapicker::blocks`) through each block's `$script`. In Blade, manifest adds it with the editor. In React, import it once where you use the editor:

```tsx
import "@javaabu/mediapicker/blocks"
```

# What's stored

Each block keeps the media id and a snapshot, so content renders even without the library:

```json
{"type": "image", "attrs": {"mediaId": 12, "src": "/storage/12/conversions/beach-mediapicker-large.jpg", "width": 1200, "height": 800, "alt": "ބީޗް", "caption": null, "link": null}}
{"type": "gallery", "attrs": {"items": [{"id": 12, "src": "…", "thumb": "…", "alt": "…", "width": 1200, "height": 800}], "columns": 3, "caption": null}}
{"type": "file", "attrs": {"mediaId": 15, "href": "…", "fileName": "report.pdf", "size": "2.4 MB", "mimeType": "application/pdf", "label": "Annual report"}}
{"type": "mediaVideo", "attrs": {"mediaId": 20, "src": "…", "mimeType": "video/mp4", "caption": null, "controls": true, "autoplay": false, "loop": false, "muted": false}}
```

Validating content with `BlockContent` checks the media ids with `LibraryMedia`: editors can only use media they may see, of the right type. Unsafe URLs are dropped when content is sanitized.

# Rendering

Blade (`<x-block-editor::content>`) looks the media up again, so pages show current URLs and alt text; when the media is gone, the snapshot is used. The views are `mediapicker::blocks.image`, `gallery`, `file` and `media-video` (publish them to change the markup). React's `<BlockContent>` renders the same markup from the snapshot, with the blocks' `render` components (`ImageRender`, `GalleryRender`, `FileRender`, `VideoRender`). The styles (`.mp-block-*`) come with the package's stylesheet.
