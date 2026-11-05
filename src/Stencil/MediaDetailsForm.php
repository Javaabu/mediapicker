<?php

namespace Javaabu\Mediapicker\Stencil;

use Javaabu\Mediapicker\Support\Translations;
use Javaabu\Stencil\Fields\Textarea;
use Javaabu\Stencil\Fields\TextInput;
use Javaabu\Stencil\Form;

/**
 * The details of a library media: name, description, alt text and caption. Rendered on the
 * edit page by either stack; with javaabu/translatable, one input per language.
 */
class MediaDetailsForm extends Form
{
    protected function setUp(): void
    {
        $this->submit(__('Save'));
    }

    protected function definition(): array
    {
        $translatable = Translations::supports($this->getRecord());

        return [
            TextInput::make('name')
                ->label(__('Name'))
                ->required()
                ->maxLength(255)
                ->thaana()
                ->translatable($translatable),
            Textarea::make('description')
                ->label(__('Description'))
                ->rows(3)
                ->thaana()
                ->translatable($translatable),
            TextInput::make('alt')
                ->label(__('Alt text'))
                ->help(__('Describes the image for people who can\'t see it.'))
                ->maxLength(1000)
                ->thaana()
                ->translatable($translatable),
            TextInput::make('caption')
                ->label(__('Caption'))
                ->maxLength(1000)
                ->thaana()
                ->translatable($translatable),
        ];
    }
}
