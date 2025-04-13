<?php

namespace Javaabu\Mediapicker\FileRemover;

use Exception;
use Illuminate\Support\Collection;
use Javaabu\Mediapicker\Contracts\Attachment;
use Javaabu\Mediapicker\Mediapicker;
use Javaabu\Mediapicker\UrlGenerator\AttachmentUrlGeneratorFactory;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\FileRemover\DefaultFileRemover;
use Spatie\MediaLibrary\Support\UrlGenerator\UrlGenerator;

class AttachmentFileRemover extends DefaultFileRemover
{
    public function removeAllFiles(Media $media): void
    {

        if ($media->conversions_disk && $media->disk !== $media->conversions_disk) {
            $this->removeFromConversionsDirectory($media, $media->conversions_disk);
        }

        $this->removeFromConversionsDirectory($media, $media->disk);
    }

    public function removeFromConversionsDirectory(Media $media, string $disk): void
    {
        $conversionsDirectory = $this->mediaFileSystem->getMediaDirectory($media, 'conversions');

        $remover = $this;

        collect([$conversionsDirectory])
            ->each(function (string $directory) use ($media, $disk, $remover) {
                try {
                    $allFilePaths = $this->filesystem->disk($disk)->allFiles($directory);
                    $conversionsFilePaths = $this->getAttachmentConversionFilePaths($media) ?: [];

                    $imagePaths = array_intersect($allFilePaths, $conversionsFilePaths);
                    foreach ($imagePaths as $imagePath) {
                        $this->filesystem->disk($disk)->delete($imagePath);
                    }

                    if (! $this->filesystem->disk($disk)->allFiles($directory)) {
                        $this->filesystem->disk($disk)->deleteDirectory($directory);
                    }
                } catch (Exception $exception) {
                    report($exception);
                }
            });
    }

    protected function getPathRelativeToRoot(Attachment $attachment, string $conversionName = ''): string
    {
        return $this->getUrlGenerator($attachment, $conversionName)->getPathRelativeToRoot();
    }

    public function getUrlGenerator(Attachment $attachment, string $conversionName): UrlGenerator
    {
        return AttachmentUrlGeneratorFactory::createForAttachment($attachment, $conversionName);
    }

    protected function getAttachmentConversionFilePaths(Media $media): array
    {
       $attachment_class = Mediapicker::attachmentModel();

       $attachments = $attachment_class::where('media_id', $media->getKey())
           ->distinct('model_type')
           ->get();

        $conversionsFilePaths = [];
        $remover = $this;

       /** @var Attachment $attachment */
        foreach ($attachments as $attachment) {
            $conversions = array_merge($attachment->getMediaConversionNames());
            $attachment->media = $media;

            $paths = array_map(
                static fn (string $conversion) => $remover->getPathRelativeToRoot($attachment, $conversion),
                $conversions,
            );

            $conversionsFilePaths = array_merge($conversionsFilePaths, $paths);
       }

       return $conversionsFilePaths;
    }
}
