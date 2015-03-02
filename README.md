# Column sorting for Laravel 5

Package for handling column sorting in Laravel 5.

Simply put: [this hack](http://hack.swic.name/laravel-column-sorting-made-easy/) in package with blade extension and Font Awesome icon support.

## Setup

### Composer

Pull this package in through Composer.

```
{
    "require": {
        "kyslik/column-sortable": "dev-master"
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
    
See configuration file yourself and make adjustments as you wish.

### Font Awesome support

Install [Font-awesome](https://github.com/FortAwesome/Font-Awesome) for visual joy.
## Usage

First of all, include `Sortable` trait inside your `Eloquent` model(s).

```
use Kyslik\ColumnSortable\Sortable;

class User extends Model implements AuthenticatableContract, CanResetPasswordContract {

	use Authenticatable, CanResetPassword, Sortable;
	...
```

You're set to go.

Sortable trait adds Sortable scope to the models so you can use it with paginate e.g:


```
public function index(User $user)
{
    $users = $user->Sortable()->paginate(10);

	return view('user.index')->withUsers($users);
}
```

Simple example view with pagination links

```
@foreach ($users as $user)
    @sortablelink('name')
@endforeach
{!! $users->appends(\Input::except('page'))->render() !!}
```

## Blade Extension

There is one blade extension for you to use

```
@sortable('column', 'Title')
```

Column parameter is `order by` and Title parameter is displayed inside anchor tags.
You can ommit Title parameter.

