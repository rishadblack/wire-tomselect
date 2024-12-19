# WireTomSelect - Laravel Livewire Searchable Dropdown

**WireTomSelect** is a reusable, customizable Livewire component designed for implementing searchable dropdowns with minimal effort in Laravel projects. It provides a clean interface to query data, map results, and handle user selections.

## Features

- Easy integration with Laravel Livewire.
- Configurable search, label, and value fields.
- Supports single and multiple selections.
- Customizable query logic.
- Placeholder, disabled state, and max options settings.

---

## Installation

### Step 1: Install the Package

You can install the package via Composer:

```bash
composer require rishadblack/wire-tomselect
```

### Step 2: Publish the Views

If you need to customize the default views, you can publish them:

```bash
php artisan vendor:publish --tag=wire-tomselect-views
```

This will publish the `wire-tomselect::search` view to your `resources/views/vendor` directory.

---

## Usage

### Step 1: Extend the `SearchComponent`

To create your searchable dropdown component, extend the abstract `SearchComponent` class and define the required `builder` and `configure` methods.

```php
namespace App\Http\Livewire;

use Rishadblack\WireTomselect\SearchComponent;
use App\Models\User;

class UserSearch extends SearchComponent
{
    public function builder(): Builder
    {
        return User::query(); // Base query for fetching data
    }

    public function configure(): void
    {
        $this->isSearchable();
        $this->setSearchField(['name', 'email']); // Fields to search in
    }
}
```

### Step 2: Use the Component in a Blade File

Include your component in a Blade file as follows:

```blade
<livewire:user-search />
```

---

## Customization

### Configure Fields

- **Value Field**: Field used for the dropdown value (default: `id`).
- **Label Field**: Field used for the dropdown label (default: `name`).

Set these fields in your `configure` method:

```php
$this->setValueField('id');
$this->setLabelField('name');
```

### Search Fields

Specify the fields for performing searches using:

```php
$this->setSearchField(['name', 'email']);
```

### Maximum Options

Set the maximum number of options to display using:

```php
$this->setMaxOptions(10);
```

---

## Example

Here’s a complete example for creating a searchable product dropdown:

```php
namespace App\Http\Livewire;

use Rishadblack\WireTomselect\SearchComponent;
use App\Models\Product;

class ProductSearch extends SearchComponent
{
    public function builder(): Builder
    {
        return Product::query();
    }

    public function configure(): void
    {
        $this->isSearchable();
        $this->setSearchField(['name', 'sku']);
        $this->setMaxOptions(15);
    }
}
```

In your Blade template:

```blade
<livewire:product-search />
```

---

## Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository.
2. Create a feature branch.
3. Submit a pull request with a detailed description of your changes.

---

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

---

Feel free to adapt this documentation based on your repository's specific needs!
