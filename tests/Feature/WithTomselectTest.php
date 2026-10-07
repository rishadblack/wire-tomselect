<?php

use Livewire\Livewire;
use Rishadblack\WireTomselect\Tests\Fixtures\OrderForm;

it('dispatches values keyed by dropdown name', function () {
    Livewire::test(OrderForm::class)
        ->call('tomSelectUpdate', ['customer_id' => ['value' => 5, 'options' => ['id' => 5, 'name' => 'Acme']]])
        ->assertDispatched('tom_select_set_value', fields: ['customer_id' => ['value' => 5, 'options' => ['id' => 5, 'name' => 'Acme']]]);
});

it('dispatches a reset for the given dropdowns', function () {
    Livewire::test(OrderForm::class)
        ->call('tomSelectReset', 'customer_id')
        ->assertDispatched('tom_select_set_reset', fields: ['customer_id']);
});

it('dispatches an empty reset to clear every dropdown', function () {
    Livewire::test(OrderForm::class)
        ->call('tomSelectReset')
        ->assertDispatched('tom_select_set_reset', fields: []);
});

it('prefills the typed text and remembers the source dropdown without the session', function () {
    $component = Livewire::test(OrderForm::class, [
        'data' => ['data' => ['text' => 'New Customer', 'field_name' => 'customer_id']],
    ]);

    $component->assertSet('name', 'New Customer')
        ->assertSet('received_text', 'New Customer')
        ->assertSet('tom_select_field', 'customer_id');

    expect(session()->has('tom_select_remote'))->toBeFalse();
});

it('selects the created record in the dropdown that opened the form', function () {
    Livewire::test(OrderForm::class, [
        'data' => ['data' => ['text' => 'New Customer', 'field_name' => 'customer_id']],
    ])
        ->call('tomSelectRemoteUpdate', 9, 'New Customer')
        ->assertReturned(false)
        ->assertDispatched('tom_select_set_value', fields: ['customer_id' => ['value' => 9, 'options' => ['id' => 9, 'name' => 'New Customer']]])
        ->assertSet('tom_select_field', null);
});

it('reports when no dropdown is waiting for a value', function () {
    Livewire::test(OrderForm::class)
        ->call('tomSelectRemoteUpdate', 9, 'New Customer')
        ->assertReturned(true)
        ->assertNotDispatched('tom_select_set_value');
});
