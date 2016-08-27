# Column sorting for Laravel 5.*
[![Latest Version](https://img.shields.io/github/release/Kyslik/column-sortable.svg?style=flat-square)](https://github.com/Kyslik/column-sortable/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/Kyslik/column-sortable.svg?style=flat-square)](https://packagist.org/packages/Kyslik/column-sortable)

Package for handling column sorting in Laravel 5.1, 5.2 and 5.3.

Simply put: [this hack](http://hack.swic.name/laravel-column-sorting-made-easy/) in package with blade extension and Font Awesome icon support.

>This is my shot at universal and easy to use model sorting in Laravel. The end result allows you to sort an Eloquent model using any column by clicking the column name. Everything is done in PHP, no JS involved.

# Setup

## Composer

Pull this package in through Composer. (development/latest version `dev-master`)

```
{
    "require": {
        "kyslik/column-sortable": "~5.0.0"
    }
}
```

    $ composer update


Add the package to your application service providers in `config/app.php`

```
'providers' => [

    App\Providers\RouteServiceProvider::class,

    /*
     * Third Party Service Providers...
     */
    Kyslik\ColumnSortable\ColumnSortableServiceProvider::class,
],
```
## Publish configuration

Publish the package configuration file to your application.

    $ php artisan vendor:publish --provider="Kyslik\ColumnSortable\ColumnSortableServiceProvider" --tag="columnsortable"

See configuration file (`config/columnsortable.php`) yourself and make adjustments as you wish.

### Config in few words

Sortablelink blade extension distinguishes between "types" (numeric, amount and alpha) and applies different class for each of them. See following snippet:

```
'columns' => [
        'numeric'  => [
            'rows' => ['created_at', 'updated_at', 'level', 'id'],
            'class' => 'fa fa-sort-numeric'
        ],
        'amount'   => [
            'rows' => ['price'],
            'class' => 'fa fa-sort-amount'
        ],
        'alpha'    => [
            'rows' => ['name', 'description', 'email', 'slug'],
            'class' => 'fa fa-sort-alpha',
        ],
    ],
```

Rest of the [config file](https://github.com/Kyslik/column-sortable/blob/master/src/config/columnsortable.php) should be crystal clear and I advise you to read it.

### Font Awesome (default font classes)

Install [Font-Awesome](https://github.com/FortAwesome/Font-Awesome) for visual joy. Search "sort" in [cheatsheet](http://fortawesome.github.io/Font-Awesome/cheatsheet/) and see used icons (12) yourself.

## Blade Extension

There is one blade extension for you to use
```
@sortablelink('column', 'Title')
```

**Column** (1st) parameter is `order by` and **Title** (2nd) parameter is displayed inside anchor tags.
You can omit **Title** parameter.

# Usage

Use `Sortable` trait inside your `Eloquent` model(s). Define `$sortable` array (see example code below).

>`Scheme::hasColumn()` is run only when `$sortable` is not defined - less DB hits per request.


```
use Kyslik\ColumnSortable\Sortable;

class User extends Model implements AuthenticatableContract, CanResetPasswordContract {

	use Authenticatable, CanResetPassword, Sortable;
	...

	protected $sortable = ['id',
	                       'name',
	                       'email',
	                       'created_at',
	                       'updated_at'];
```

You're set to go.

Sortable trait adds Sortable scope to the models so you can use it with paginate.

## Full Example

### Routes

```
Route::get('users', ['as' => 'users.index', 'uses' => 'HomeController@index']);
```

### Controller's `index()` method

```
public function index(User $user)
{
    $users = $user->sortable()->paginate(10);

	return view('user.index')->withUsers($users);
}
```

You can set default sort (when nothing is in (URL) query strings yet).

```
//generate ->orderBy('name', 'asc')
$users = $user->sortable(['name'])->paginate(10); //default order is asc

//generate ->orderBy('id', 'desc')
$users = $user->sortable(['id' => 'desc'])->paginate(10);
```

### View

In Laravel 5.2 and 5.3 **\Input** facade is not aliased by default. To do so, open `config/app.php` and add `'Input'     => Illuminate\Support\Facades\Input::class,` to *aliases* array.

_pagination included_

```
@sortablelink('id', 'Id')
@sortablelink('name')

@foreach ($users as $user)
    {{ $user->name }}
@endforeach
{!! $users->appends(\Input::except('page'))->render() !!}
```

# One To One Relation sorting

## Define HasOne relation

In order to make relation sorting work, you have to define **hasOne()** relation in your model in question.

```
/**
* Get the user_detail record associated with the user.
*/
public function detail()    
{
    return $this->hasOne('App\UserDetail');
}
```

In *User* model we define **hasOne** relation to *UserDetail* model (which holds phone number and address details).

## Define `$sortable` array

Define `$sortable` array in both models (else, package uses `Scheme::hasColumn()` which is extra database query).


for *User*

```
protected $sortable = ['id', 'name', 'email', 'created_at', 'updated_at'];
```

for *UserDetail*

```
public $sortable = ['address', 'phone_number'];
```

>note that `$sortable` array in *UserDetail* is declared as **public** and not protected because we need to access it inside *User* model.

## Blade and relation sorting

In order to tell package to sort using relation:

```
@sortablelink ('detail.phone_number', 'phone')
```
>package works with relation "name" that you define in model instead of table name.

In config file you can set your own separator if `.` (dot) is not what you want.

```
'uri_relation_column_separator' => '.'
```

# Exception to catch

#### Package throws custom exception `ColumnSortableException` with three codes (0, 1, 2).

Code **0** means that `explode()` fails to explode URI parameter "sort" in to two values.
For example: `sort=detail..phone_number` - produces array with size of 3, which causes package to throw exception with code **0**.

Code **1** means that `$query->getRelation()` method fails, that means when relation name is invalid (does not exists, is not declared in model).

Code **2** means that provided relation through sort argument is not instance of **hasOne**.

Example how to catch:

```
try {
    $users = $user->with('detail')->sortable(['detail.phone_number'])->paginate(5);    
    } catch (ColumnSortableException $e) {
    dd($e);
}
```

>I strongly recommend to catch **ColumnSortableException** because there is a user input in question (GET parameter) and any user can modify it in such way that package throws ColumnSortableException with code 0.
