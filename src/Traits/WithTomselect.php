<?php

namespace Rishadblack\WireTomselect\Traits;

use Livewire\Attributes\Locked;

/**
 * Lets a parent (page, form or modal) component control its dropdowns.
 */
trait WithTomselect
{
    /** The dropdown that opened this component through a "create new option" flow. */
    #[Locked]
    public ?string $tom_select_field = null;

    /**
     * Livewire trait hook. Receives the loadComponent payload when a dropdown
     * opens this component to create a new option.
     *
     * @param  array{data?: array{text?: string, field_name?: string}}  $data
     */
    public function mountWithTomselect(array $data = []): void
    {
        $text = $data['data']['text'] ?? null;

        if (blank($text)) {
            return;
        }

        $this->tom_select_field = $data['data']['field_name'] ?? null;

        if (property_exists($this, 'name')) {
            $this->name = $text;
        }

        if (method_exists($this, 'tomSelectText')) {
            $this->tomSelectText($text);
        }
    }

    /**
     * Select a freshly created record in the dropdown that opened this component.
     * Returns true when no dropdown is waiting for a value.
     */
    public function tomSelectRemoteUpdate(string|int $id, string $name): bool
    {
        if (! $this->tom_select_field) {
            return true;
        }

        $this->tomSelectUpdate([
            $this->tom_select_field => [
                'value' => $id,
                'options' => ['id' => $id, 'name' => $name],
            ],
        ]);

        $this->tom_select_field = null;

        return false;
    }

    /**
     * Set dropdown values. Keys are dropdown names; values are either a plain value
     * or ['value' => ..., 'options' => ['id' => ..., 'name' => ...]].
     *
     * The parameter keeps its 1.x name so named-argument calls still work.
     *
     * @param  array<string, mixed>  $options
     */
    public function tomSelectUpdate(array $options): void
    {
        $this->dispatch('tom_select_set_value', fields: $options);
    }

    /**
     * Clear the given dropdowns, or every dropdown when called without arguments.
     *
     * The parameter keeps its 1.x name so named-argument calls still work.
     *
     * @param  array<int, string>|string  $options
     */
    public function tomSelectReset(array|string $options = []): void
    {
        $this->dispatch('tom_select_set_reset', fields: array_values((array) $options));
    }
}
