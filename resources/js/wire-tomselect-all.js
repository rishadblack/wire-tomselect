import TomSelect from 'tom-select';

/**
 * Values dispatched through tom_select_set_value before the target dropdown
 * exists (for example while a modal is still opening). Consumed on init.
 */
const pendingValues = new Map();

const fieldsFrom = (event) => {
    const detail = event?.detail;

    if (Array.isArray(detail)) {
        return detail[0]?.fields ?? detail[0] ?? {};
    }

    return detail?.fields ?? {};
};

const toKey = (value) => String(value);

/**
 * Tom Select mutates the option objects it receives (it adds $order), so never
 * hand it Livewire's reactive data directly or the locked "data" property would
 * look dirty on the next request.
 */
const plain = (value) => (value === undefined || value === null ? [] : JSON.parse(JSON.stringify(value)));

window.addEventListener('tom_select_set_value', (event) => {
    Object.entries(fieldsFrom(event) ?? {}).forEach(([name, payload]) => {
        pendingValues.set(name, payload);
    });
});

document.addEventListener('alpine:init', () => {
    window.Alpine.data('wireTomselect', (config) => ({
        select: null,
        error: null,
        syncToken: 0,
        defaultOptions: [],
        searched: false,

        init() {
            this.defaultOptions = plain(this.$wire.data);
            this.select = new TomSelect(this.$refs.select, this.settings());

            this.select.on('change', () => {
                this.error = null;
            });

            if (config.searchable) {
                // Back to the default list once the search text is gone or the dropdown closes.
                this.select.on('type', (text) => {
                    if (!text) {
                        this.restoreDefaults();
                    }
                });
                this.select.on('dropdown_close', () => this.restoreDefaults());
            }

            this.applyValue(this.$wire.value, true);

            const pending = pendingValues.get(config.name);

            if (pending !== undefined) {
                pendingValues.delete(config.name);
                this.setFromPayload(pending);
            }

            this.$wire.$watch('value', (value) => this.syncValue(value));

            config.reactiveProps.forEach((prop) => {
                this.$wire.$watch(prop, () => {
                    this.defaultOptions = plain(this.$wire.data);
                    this.replaceOptions(this.defaultOptions);
                });
            });
        },

        destroy() {
            this.select?.destroy();
            this.select = null;
        },

        settings() {
            const settings = {
                valueField: 'id',
                labelField: 'name',
                searchField: ['name'],
                maxOptions: null,
                loadThrottle: config.loadThrottle,
                options: plain(this.$wire.data),
                plugins: config.removeButton ? ['remove_button'] : [],
                shouldLoad: (query) => config.searchable && query.length >= config.minSearchLength,
                load: (query, callback) => {
                    this.$wire.searchBuilder(query)
                        .then((options) => {
                            // Show exactly what the server matched: drop the previous
                            // options (selected ones are kept) and add the results.
                            this.searched = true;
                            this.select.clearOptions();
                            callback(options);
                        })
                        .catch(() => callback());
                },
                render: {
                    // "html" and "item_html" come from map() and are trusted server output.
                    option: (item, escape) => item.html ?? `<div class="py-1"><span class="text-muted">${escape(item.name)}</span></div>`,
                    item: (item, escape) => item.item_html ?? `<div>${escape(item.name)}</div>`,
                },
            };

            if (config.searchable) {
                // The server decides what matches (it may search columns that are not
                // part of the label), so do not filter the results again by name.
                settings.score = () => () => 1;
            }

            if (config.createEvent || config.createLoad.length) {
                settings.create = (input) => {
                    this.requestCreate(input);

                    return false;
                };
            }

            return settings;
        },

        requestCreate(text) {
            if (config.createEvent) {
                this.$wire.$dispatch(config.createEvent, { text });

                return;
            }

            this.$wire.$dispatch('loadComponent', {
                action: config.createLoad[0] ?? '',
                component: config.createLoad[1] ?? '',
                data: { text, field_name: config.name, extra: config.createLoad },
            });
        },

        hasOption(value) {
            return Object.prototype.hasOwnProperty.call(this.select.options, toKey(value));
        },

        isEmpty(value) {
            return value === null || value === undefined || value === '' || (Array.isArray(value) && value.length === 0);
        },

        applyValue(value, silent = false) {
            this.error = null;

            if (this.isEmpty(value)) {
                this.select.clear(silent);

                return;
            }

            this.select.setValue(Array.isArray(value) ? [...value] : value, silent);
        },

        /**
         * Livewire changed the value. Load any option we do not have yet, then select it.
         */
        async syncValue(value) {
            if (this.isEmpty(value)) {
                this.applyValue(value, true);

                return;
            }

            const token = ++this.syncToken;
            const values = Array.isArray(value) ? value : [value];
            let missing = values.filter((item) => !this.hasOption(item));

            // The server reloads the options in the same request when a parent
            // assigns a value, so look there before asking for them again.
            if (missing.length) {
                const wanted = missing.map(toKey);
                const loaded = plain(this.$wire.data).filter((option) => wanted.includes(toKey(option.id)));

                if (loaded.length) {
                    this.select.addOptions(loaded);
                    missing = values.filter((item) => !this.hasOption(item));
                }
            }

            if (missing.length) {
                try {
                    const options = await this.$wire.baseMapWithIds(missing);

                    // A newer value arrived while this request was in flight.
                    if (token !== this.syncToken) {
                        return;
                    }

                    if (Array.isArray(options)) {
                        this.select.addOptions(options);
                        this.select.refreshOptions(false);
                    }
                } catch (error) {
                    console.error('[wire-tomselect] Could not load options for the selected value.', error);
                }
            }

            this.applyValue(value, true);
        },

        /**
         * Put the default (unsearched) list back after a remote search.
         */
        restoreDefaults() {
            if (!this.searched) {
                return;
            }

            this.searched = false;
            this.replaceOptions(this.defaultOptions);
        },

        /**
         * Swap the option list. clearOptions() keeps selected options and forgets
         * cached searches, so retyping a term reloads.
         */
        replaceOptions(options) {
            this.select.clearOptions();

            if (Array.isArray(options)) {
                this.select.addOptions(options);
            }

            this.select.refreshOptions(false);
        },

        setFromPayload(payload) {
            if (payload && typeof payload === 'object' && !Array.isArray(payload) && 'value' in payload) {
                if (payload.options) {
                    this.select.addOption(plain(payload.options));
                }

                this.applyValue(payload.value);

                return;
            }

            this.applyValue(payload);
        },

        onSetValue(event) {
            const fields = fieldsFrom(event);

            if (fields && Object.prototype.hasOwnProperty.call(fields, config.name)) {
                pendingValues.delete(config.name);
                this.setFromPayload(fields[config.name]);
            }
        },

        onReset(event) {
            const fields = fieldsFrom(event);
            const names = Array.isArray(fields) ? fields : [];

            if (names.length === 0 || names.includes(config.name)) {
                this.applyValue(null);
            }
        },

        onAlert(event) {
            const detail = Array.isArray(event.detail) ? event.detail[0] : event.detail;
            const errors = detail?.type === 'error' ? detail?.data?.validation_errors : null;

            if (errors && errors[config.name]) {
                this.error = Array.isArray(errors[config.name]) ? errors[config.name][0] : errors[config.name];
            }
        },
    }));
});
