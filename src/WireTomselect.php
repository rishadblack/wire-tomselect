<?php

namespace Rishadblack\WireTomselect\Traits;

trait WithTomSelect
{
    public function mountWithTomSelect($data = [])
    {
        if (isset($data['data']) && isset($data['data']['text']) && !empty($data['data']['text'])) {
            session()->put('tom_select_remote', $data['data']);

            if (property_exists($this, 'name')) {
                $this->name = $data['data']['text']; // Assign the value of $name to $tom_select_remote_text
            }

            if (method_exists($this, 'tomSelectText')) {
                // Dynamically call the method and pass the data
                $this->tomSelectText($data['data']['text']);
            }
        }

    }

    public function tomSelectRemoteUpdate(string | int $id, string $name)
    {
        $remoteData = session()->get('tom_select_remote');
        $fieldName = $remoteData['field_name'] ?? null;

        if (!$fieldName) {
            return true;
        }

        session()->forget(['tom_select_remote']);

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

    public function tomSelectUpdate(array $options)
    {
        $this->dispatch('tom_select_set_value', $options);
    }
}
