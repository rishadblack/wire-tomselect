<div class="form-group" id="{{ $select_id }}_group">
    @if ($label)
        <label class="form-label {{ $label_class }}" for="{{ $select_id }}_select">{{ $label }}</label>
    @endif

    <div wire:ignore
        id="{{ $select_id }}_class"
        x-data="wireTomselect(@js($tomselect_config))"
        x-on:tom_select_set_value.window="onSetValue($event)"
        x-on:tom_select_set_reset.window="onReset($event)"
        x-on:alert.window="onAlert($event)"
        x-on:{{ $select_id }}_set_value.window="onLegacySetValue($event)"
        x-on:{{ $select_id }}_set_option.window="onLegacySetOption($event)"
        x-on:{{ $select_id }}_set_reset.window="onLegacyReset()">
        <select
            x-ref="select"
            wire:model.change="value"
            id="{{ $select_id }}_select"
            class="{{ $select_id }}_class {{ $class }}"
            placeholder="{{ $placeholder ?? trim('Type to select '.$label) }}"
            @disabled($disabled)
            {{ $multiple ? 'multiple' : '' }}></select>

        <span class="invalid-feedback error_msg"
            id="{{ $select_id }}_error_msg"
            x-show="error"
            x-text="error"
            style="display: none;"></span>
    </div>
</div>
