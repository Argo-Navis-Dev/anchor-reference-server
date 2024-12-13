<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\Sep12CustomerResource\Actions;

use App\Filament\Resources\Sep12CustomerResource;
use App\Filament\Resources\Sep12CustomerResource\Helper\Sep12CustomerResourceHelper;
use App\Models\Sep12Customer;
use Filament\Tables\Actions\ViewAction;
use Illuminate\Support\Facades\Log;

use function json_encode;

/**
 * Defines the edit SEP-12 customer action.
 */
class ViewSep12Customer extends ViewAction
{
    protected static string $resource = Sep12CustomerResource::class;

    /**
     * Sets up the view.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->mutateRecordDataUsing(function (Sep12Customer $record, array $data): array {
            Log::debug(
                'Preparing data for view action.',
                ['context' => 'sep12_ui', 'data' => json_encode($data), 'record' => json_encode($record)],
            );
            Sep12CustomerResourceHelper::populateCustomerFieldsBeforeFormLoad($data, $record);
            Log::debug(
                'Data prepared for view action.',
                ['context' => 'sep12_ui', 'data' => json_encode($data)],
            );

            return $data;
        });
    }
}
