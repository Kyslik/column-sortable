# Column sorting for Laravel 5
[![Latest Version](https://img.shields.io/github/release/Kyslik/column-sortable.svg?style=flat-square)](https://github.com/Kyslik/column-sortable/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/Kyslik/column-sortable.svg?style=flat-square)](https://packagist.org/packages/Kyslik/column-sortable)

Package for handling column sorting in Laravel 5.1

Simply put: [this hack](http://hack.swic.name/laravel-column-sorting-made-easy/) in package with blade extension and Font Awesome icon support.

>This is my shot at universal and easy to use model sorting in Laravel. The end result allows you to sort an Eloquent model using any column by clicking the column name. Everything is done in PHP, no JS involved.

## Setup

### Composer

Pull this package in through Composer.

```
{
    "require": {
        "kyslik/column-sortable": "~2.0.0"
    }
}
```

    $ composer update
    

Add the package to your application service providers in `config/app.php`

```
'providers' => [
    
    'Illuminate\Foundation\Providers\ArtisanServiceProvider',
    'Illuminate\Auth\AuthServiceProvider',
    ...
    
    'Kyslik\ColumnSortable\ColumnSortableServiceProvider',

],
```
### Publish configuration

Publish the package configuration file to your application.

    $ php artisan vendor:publish
    
See configuration file (`config/columnsortable.php`) yourself and make adjustments as you wish.

### Font Awesome support

Install [Font-Awesome](https://github.com/FortAwesome/Font-Awesome) for visual joy. Search "sort" in [cheatsheet](http://fortawesome.github.io/Font-Awesome/cheatsheet/) and see used icons (12) yourself.
## Usage

First of all, include `Sortable` trait inside your `Eloquent` model(s). Define `$sortable` array (see example code below).

>`Scheme::hasColumn()` is run only when `$sortable` is not defined. 

```
use Kyslik\ColumnSortable\Sortable;

class User extends Model implements AuthenticatableContract, CanResetPasswordContract {

	use Authenticatable, CanResetPassword, Sortable;
	...
	
	protected $sortable = ['id', 
	                       'name', 
	                       'email', 
	                       'created_at', 
	                       'updated_at']; //ommitable
	
```

You're set to go.

Sortable trait adds Sortable scope to the models so you can use it with paginate.

#### Example

Controller's index method.

```
public function index(User $user)
{
    $users = $user->Sortable()->paginate(10);

	return view('user.index')->withUsers($users);
}
```

View with pagination links

```
@sortablelink('name')
@foreach ($users as $user)
    {{ $user->name }}
@endforeach
{!! $users->appends(\Input::except('page'))->render() !!}
```

## Blade Extension

There is one blade extension for you to use

```
@sortablelink('column', 'Title')
```

Column parameter is `order by` and Title parameter is displayed inside anchor tags.
You can ommit Title parameter.

