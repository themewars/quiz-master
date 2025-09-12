<x-filament-panels::page>
    @if($showProgressBar ?? false)
        <x-progress-bar />
    @endif

    {{ $this->form }}

    <x-filament-panels::form.actions
        :actions="$this->getFormActions()"
    />

    <x-filament-actions::modals />
</x-filament-panels::page>
