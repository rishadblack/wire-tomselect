<?php

namespace Rishadblack\WireTomselect\Traits;

trait WithTomselect
{
    /**
     * Initialize the tom select component with remote data.
     *
     * @param array $data
     * @return void
     */
    public function mountWithTomselect(array $data = [])
    {
        if (isset($data['data']['text']) && !empty($data['data']['text'])) {
            session()->put('tom_select_remote', $data['data']);

            if (property_exists($this, 'name')) {
                $this->name = $data['data']['text']; // Assign the value to $name
            }

            if (method_exists($this, 'tomSelectText')) {
                // Dynamically call the user-defined method and pass the text data
                $this->tomSelectText($data['data']['text']);
            }
        }
    }

    /**
     * Handle remote updates for tom select component.
     *
     * @param string|int $id
     * @param string $name
     * @return bool
     */
    public function tomSelectRemoteUpdate(string | int $id, string $name): bool
    {
        $remoteData = session()->get('tom_select_remote');
        $fieldName = $remoteData['field_name'] ?? null;

        if (!$fieldName) {
            return true; // Early exit if no field name is found
        }

        session()->forget('tom_select_remote');

        $this->tomSelectUpdate([
            $fieldName => [
                'value' => $id,
                'options' => [
                    'id' => $id,
                    'name' => $name,
                ],
            ],
        ]);

        return false;
    }

    /**
     * Update the tom select component with given options.
     *
     * @param array $options
     * @return void
     */
    public function tomSelectUpdate(array $options): void
    {
        $this->dispatch('tom_select_set_value', $options);
    }

    /**
     * Reset the tom select component for specified fields.
     *
     * @param array|string $options
     * @return void
     */
    public function tomSelectReset(array | string $options = []): void
    {
        $options = is_array($options) ? $options : [$options]; // Normalize to an array
        $this->dispatch('tom_select_set_reset', $options);
    }
}