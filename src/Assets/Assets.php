<?php

namespace Javaabu\Mediapicker\Assets;

use Javaabu\Manifest\Package;

/**
 * The package's frontend assets, registered with javaabu/manifest
 */
class Assets
{
    public const string PACKAGE = 'mediapicker';

    /** The Blade picker field and the picker dialog */
    public const string PICKER = 'mediapicker::picker';

    /** The Blade cropper on the media edit page */
    public const string CROPPER = 'mediapicker::cropper';

    /** The image, gallery, file and video blocks for javaabu/block-editor */
    public const string BLOCKS = 'mediapicker::blocks';

    public const string TIPTAP = '^3.32.3';

    public static function register(Package $package): void
    {
        $package
            ->js('js')
            ->pages('js/Pages')
            ->dist('../dist')
            ->script('picker', 'js/blade/picker.ts')
            ->script('cropper', 'js/blade/cropper.ts')
            ->script('blocks', 'js/blocks/index.ts')
            ->npm([
                '@javaabu/forms' => 'github:Javaabu/react-forms#next-gen',
                '@javaabu/onu' => '^0.2.1',
                'cropperjs' => '^1.6.2',
                // @javaabu/onu/media (ImageCropper) loads these when it's imported
                'react-easy-crop' => '^6.2.4',
                'signature_pad' => '^5.1.4',
                'media-chrome' => '^4.19.3',
                'yet-another-react-lightbox' => '^3.32.2',
                'uqr' => '^0.1.3',
                '@tiptap/core' => self::TIPTAP,
            ], [
                'react' => '^18.3 || ^19.0',
                'react-dom' => '^18.3 || ^19.0',
                '@inertiajs/react' => '^2.0 || ^3.0',
            ]);
    }
}
