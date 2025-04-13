<?php

namespace Javaabu\Mediapicker\Conversions\Actions;

use Javaabu\Mediapicker\Contracts\Attachment;
use Javaabu\Mediapicker\Conversions\Events\AttachmentConversionHasBeenCompletedEvent;
use Javaabu\Mediapicker\Conversions\Events\AttachmentConversionWillStartEvent;
use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\Conversions\ImageGenerators\ImageGeneratorFactory;
use Spatie\MediaLibrary\MediaCollections\Filesystem;
use Spatie\MediaLibrary\ResponsiveImages\ResponsiveImageGenerator;

class PerformAttachmentConversionAction
{
    public function execute(
        Conversion $conversion,
        Attachment $attachment,
        string $copiedOriginalFile
    ): void {
        $media = $attachment->media;

        $imageGenerator = ImageGeneratorFactory::forMedia($media);

        $copiedOriginalFile = $imageGenerator->convert($copiedOriginalFile, $conversion);

        if (! $copiedOriginalFile) {
            return;
        }

        event(new AttachmentConversionWillStartEvent($attachment, $conversion, $copiedOriginalFile));

        $manipulationResult = (new PerformAttachmentManipulationsAction)->execute($attachment, $conversion, $copiedOriginalFile);

        $newFileName = $conversion->getConversionFile($media);

        $renamedFile = $this->renameInLocalDirectory($manipulationResult, $newFileName);

        if ($conversion->shouldGenerateResponsiveImages()) {
            /** @var ResponsiveImageGenerator $responsiveImageGenerator */
            $responsiveImageGenerator = app(ResponsiveImageGenerator::class);

            $responsiveImageGenerator->generateResponsiveImagesForConversion(
                $media,
                $conversion,
                $renamedFile
            );
        }

        app(Filesystem::class)->copyToMediaLibrary($renamedFile, $media, 'conversions');

        $media->markAsConversionGenerated($conversion->getName());

        event(new AttachmentConversionHasBeenCompletedEvent($attachment, $conversion));
    }

    protected function renameInLocalDirectory(
        string $fileNameWithDirectory,
        string $newFileNameWithoutDirectory
    ): string {
        $targetFile = pathinfo($fileNameWithDirectory, PATHINFO_DIRNAME).'/'.$newFileNameWithoutDirectory;

        rename($fileNameWithDirectory, $targetFile);

        return $targetFile;
    }
}
