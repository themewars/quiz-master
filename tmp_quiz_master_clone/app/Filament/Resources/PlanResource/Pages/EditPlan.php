<?php

namespace App\Filament\Resources\PlanResource\Pages;

use App\Filament\Resources\PlanResource;
use App\Models\Plan;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPlan extends EditRecord
{
    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url($this->getResource()::getUrl('index')),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Be defensive: key may be missing from form payload
        if (!array_key_exists('assign_default', $data)) {
            $data['assign_default'] = (bool) $record->assign_default;
        }

        if (!empty($data['assign_default'])) {
            $existingDefaultPlan = Plan::where('assign_default', 1)->where('id', '!=', $record->id)->first();
            if ($existingDefaultPlan) {
                $existingDefaultPlan->update(['assign_default' => 0]);
            }
        }

        $record->update($data);

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('messages.plan.plan_updated_success');
    }

    public function getTitle(): string
    {
        return __('messages.plan.edit_plan');
    }
}
