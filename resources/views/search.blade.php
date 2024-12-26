<div class="form-group">
    <label class="form-label mt-1  {{ $label ? '' : 'd-none' }}" @style('font-weight: bold;')>{{ $label }}
    </label>
    <div>
        <div wire:ignore id="{{ $select_id }}_class">
            <select type="text" wire:model.change="value" id="{{ $select_id }}_select"
                class="{{ $select_id }}_class"
                @if ($disabled == 'true') style="background-color: rgb(184, 35, 35); color:#000000; border: 1px solid #e8f2ff; padding: 0.3rem 0.75rem;"
            disabled @endif
                placeholder="{{ $placeholder ?? 'Type to select ' . $label }}"
                {{ $multiple ? 'multiple' : '' }}></select>
            <span class="invalid-feedback error_msg" id="{{ $select_id }}_error_msg"></span>
        </div>
    </div>
</div>

@script
    <script>
        const errorSpan = document.getElementById("{{ $select_id }}_error_msg");
        const listeners = [];

        const selectTom = new TomSelect(document.getElementById("{{ $select_id }}_select"), {
            valueField: $wire.value_field,
            labelField: $wire.label_field,
            searchField: $wire.search_field,
            maxOptions: $wire.max_options,
            loadThrottle: 800,
            options: $wire.data,
            load: (query, callback) => {
                if (!$wire.searchable) return callback();
                if (!query.length) return callback();
                $wire.$call('searchBuilder', query).then(data => {
                    callback(data);
                });
            },
            render: {
                option: function(item, escape) {
                    return `<div class="py-1 d-flex">
                            <div>
                                <div>
                                    <span class="text-muted"> ${ escape(item.name) }</span>
                                </div>
                            </div>
                        </div>`;
                },
                item: function(item, escape) {
                    return `<div>${ escape(item.name) }</div>`;
                }
            }
        });


        selectTom.on('change', () => {
            errorSpan.style.display = "none";
        })

        listeners.push(
            Livewire.on('{{ $select_id }}_set_option', (event) => {
                if (typeof event[0] === "undefined") {
                    selectTom.clear();
                    selectTom.clearOptions();
                    selectTom.addOption($wire.data);
                } else {
                    selectTom.clear();
                    selectTom.clearOptions();
                    selectTom.addOption(event[0]);
                }
            }))

        listeners.push(
            Livewire.on('{{ $select_id }}_set_value', (event) => {
                if (typeof event[0] === "undefined") {
                    selectTom.clear();
                    selectTom.setValue($wire.value);
                    errorSpan.style.display = "none";
                } else {
                    selectTom.clear();
                    selectTom.setValue(event[0]);
                    errorSpan.style.display = "none";
                }
            }))

        listeners.push(
            Livewire.on('tom_select_set_value', (event) => {
                if (typeof event[0]['{{ $name }}'] != "undefined") {
                    if (typeof event[0]['{{ $name }}']['value'] != "undefined") {
                        selectTom.clear();
                        errorSpan.style.display = "none";
                        if (typeof event[0]['{{ $name }}']['options'] != "undefined") {
                            selectTom.clearOptions();
                            selectTom.addOption(event[0]['{{ $name }}'][
                                'options'
                            ]);
                        }
                        selectTom.setValue(event[0]['{{ $name }}']['value']);

                    } else {
                        selectTom.clear();
                        selectTom.setValue(event[0]['{{ $name }}']);
                        errorSpan.style.display = "none";
                    }
                }
            }))

        listeners.push(
            Livewire.on('{{ $select_id }}_set_reset', () => {
                selectTom.clear();
                selectTom.setValue($wire.value);
                errorSpan.style.display = "none";
            }))

        listeners.push(
            Livewire.on('tom_select_set_reset', () => {
                selectTom.clear();
                selectTom.setValue(null);
                errorSpan.style.display = "none";
            }))

        listeners.push(
            Livewire.on('alert', (event) => {

                if (event.type == 'error') {
                    const errorDatas = event.data.validation_errors;
                    if (errorDatas) {
                        Object.keys(errorDatas).forEach(key => {
                            if (key === $wire.name) {
                                errorSpan.style.display = "inline";
                                errorSpan.innerText = errorDatas[key][0];
                            }
                        });
                    }
                }
            }))

        Livewire.hook("morph.removed", ({
            el,
            component
        }) => {
            listeners.forEach(unregister => unregister());
        });
    </script>
@endscript
