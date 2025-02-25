<div class="form-group">
    <label class="form-label {{ $label ? '' : 'd-none' }}" @style('font-weight: bold;')>{{ $label }}
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
        let tomSelectSettings = {
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
        };

        if ($wire.create_load_component || $wire.create_event) {
            tomSelectSettings.create = (input) => {
                if ($wire.create_event) {
                    $wire.$dispatch($wire.create_event, {
                        text: input
                    });
                    return false;
                }

                $wire.$dispatch($wire.create_event ?? 'loadComponent', {
                    action: '{{ isset($create_load[0]) ? $create_load[0] : '' }}',
                    component: '{{ isset($create_load[1]) ? $create_load[1] : '' }}',
                    data: {
                        text: input,
                        field_name: '{{ $name }}',
                        extra: $wire.create_load
                    }
                });
                return false;
            };
        }

        const selectTom = new TomSelect(document.getElementById("{{ $select_id }}_select"), tomSelectSettings);


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
            Livewire.on('tom_select_set_reset', (event) => {
                // Check if event[0] is an array
                if (Array.isArray(event[0])) {
                    if (event[0].length === 0) {
                        // Clear all fields if event[0] is an empty array
                        console.log("Empty array detected. Clearing all fields...");
                        selectTom.clear();
                        selectTom.setValue(null);
                        errorSpan.style.display = "none";
                    } else if (event[0].includes('{{ $name }}')) {
                        // Perform logic for specific '{{ $name }}' key
                        console.log(`Key '{{ $name }}' found. Clearing field...`);
                        selectTom.clear();
                        selectTom.setValue(null);
                        errorSpan.style.display = "none";
                    }
                }
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
            const selectId = document.getElementById('{{ $select_id }}_select');
            if (!selectId) {
                listeners.forEach(unregister => unregister());
            }
        });

        let initialized = false;

        Livewire.hook('component.init', ({
            component,
            cleanup
        }) => {
            if (!initialized) {
                // Ensure window.tom_select_set_value exists and is valid
                if (window.tom_select_set_value && typeof window.tom_select_set_value['{{ $name }}'] !==
                    "undefined") {
                    const tomValue = window.tom_select_set_value['{{ $name }}'];

                    if (tomValue && typeof tomValue['value'] !== "undefined") {
                        selectTom.clear();
                        errorSpan.style.display = "none";

                        if (typeof tomValue['options'] !== "undefined") {
                            selectTom.clearOptions();
                            selectTom.addOption(tomValue['options']);
                        }

                        selectTom.setValue(tomValue['value']);
                    } else if (tomValue) {
                        selectTom.clear();
                        selectTom.setValue(tomValue);
                        errorSpan.style.display = "none";
                    }

                    // Clear processed data for '{{ $name }}'
                    window.tom_select_set_value['{{ $name }}'] = null;
                }

                initialized = true; // Prevent further executions
            }
        });

        try {
            // Get reactive properties from PHP
            const reactiveProps = @json($reactive_props);

            // console.log("Reactive Properties ({{ $name }}):", reactiveProps); // Debugging

            if (Array.isArray(reactiveProps) && reactiveProps.length > 0) {
                reactiveProps.forEach(prop => {
                    $wire.$watch(prop, async function(value) {
                        // console.log(`${prop} changed:`, value);

                        const optionsData = await $wire.$call('baseMap'); // Fetch updated options

                        let previousValue = $wire.value; // Store previous selection
                        // console.log("Previous selection before update:", previousValue);

                        selectTom.clearOptions(); // Remove old options

                        if (Array.isArray(optionsData)) {
                            selectTom.addOptions(optionsData);
                        }

                        selectTom.refreshOptions(false); // Refresh options without clearing selection

                        // Restore selection if previousValue is still valid
                        setTimeout(() => {
                            if (previousValue && selectTom.options[previousValue]) {
                                selectTom.setValue(previousValue,
                                    true); // true -> Prevent event trigger
                                // console.log("Selection restored:", previousValue);
                            } else {
                                // console.warn("Previous selection is no longer valid.");
                            }
                        }, 50); // Small delay ensures options are available
                    });
                });
            } else {
                // console.warn("No reactive properties found.");
            }

            // Watch for value changes in Livewire and auto-select it
            $wire.$watch('value', async function(newValue) {
                console.log("Livewire value updated:", newValue);

                if (!newValue) {
                    // console.warn("New value is empty. Skipping selection.");
                    selectTom.clear(true);
                    return;
                }

                // Check if newValue is an array (multiple selection)
                if (Array.isArray(newValue)) {
                    // Collect all the missing values
                    const missingValues = newValue.filter(val => !selectTom.options[val]);

                    if (missingValues.length > 0) {
                        // console.warn("Missing values found, fetching new data...");

                        try {
                            // Fetch options for all missing values concurrently
                            const optionsData = await Promise.all(
                                missingValues.map(val => $wire.$call('baseMapWithId', val))
                            );

                            // Flatten the array of options (if needed)
                            const allOptions = optionsData.flat();

                            // console.log("Fetched new options:", allOptions);

                            // Add new options
                            if (Array.isArray(allOptions)) {
                                selectTom.addOptions(allOptions);
                            }

                            // Refresh options without clearing the selection
                            selectTom.refreshOptions(false);

                            // After options are refreshed, set the selected values
                            setTimeout(() => {
                                selectTom.setValue(newValue, true); // Set all values at once
                                // console.log("Auto-selected updated values:", newValue);
                            }, 100);

                        } catch (error) {
                            // console.error("Error fetching options for missing values:", error);
                        }
                    } else {
                        // If no values are missing, just set the value
                        selectTom.setValue(newValue, true);
                    }
                } else {
                    // Handle single value
                    if (!selectTom.options[newValue]) {
                        // console.warn("Value not found in options, fetching new data...");

                        // Fetch options since the value is missing
                        const optionsData = await $wire.$call('baseMapWithId', newValue);


                        if (Array.isArray(optionsData)) {
                            selectTom.addOptions(optionsData);
                        }

                        selectTom.refreshOptions(false); // Refresh without clearing selection

                        // After options are refreshed, set the selected value
                        setTimeout(() => {
                            if (selectTom.options[newValue]) {
                                selectTom.setValue(newValue, true);
                                // console.log("Auto-selected updated value:", newValue);
                            } else {
                                // console.error("Updated value STILL not found after fetching new options.");
                            }
                        }, 100);
                    } else {
                        // console.log("Value already exists in options. Selecting directly.");
                        selectTom.setValue(newValue, true);
                    }
                }
            });


        } catch (error) {
            console.error("Error updating select options:", error);
        }
    </script>
@endscript
