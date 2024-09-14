<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\Sep12FieldResource\Pages;

use App\Filament\Resources\ResourceUtil;
use App\Filament\Resources\Sep12FieldResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSep12Field extends EditRecord
{
    protected static string $resource = Sep12FieldResource::class;

    /**
     * Processes the form data model before filling it.
     *
     * @param array<array-key, mixed> $data The form data model.
     * @return array<array-key, mixed> $data The mutated form data model.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $choicesStr = $data['choices'] ?? null;
        if ($choicesStr != null) {
            $choices = array_map('trim', explode(',', $choicesStr));
            $data['choices'] = $choices;
        }

        return $data;
    }

    /**
     * Processes the form data model before saving it.
     *
     * @param array<array-key, mixed> $data The form data model.
     * @return array<array-key, mixed> $data The mutated form data model.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['choices'] =
            ResourceUtil::convertJsonArrayToCommaSeparatedString($data, 'choices');

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
