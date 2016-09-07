<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;

/**
 * Class ColumnSortableTraitTest
 */
class ColumnSortableTraitTest extends \Orchestra\Testbench\TestCase
{
    /**
     * @var
     */
    private $user;

    /**
     * @var
     */
    private $configDefaultOrder;

    /**
     * Method setUp() runs before each test.
     */
    public function setUp()
    {
        parent::setUp();

        $this->app['config']->set('database.default', 'sqlite');
        $this->app['config']->set('database.connections.sqlite.database', ':memory:');

        $this->user = new User();
        $this->configDefaultOrder = Config::get('columnsortable.default_direction', 'asc');
    }

    public function testSortableWithoutDefaultAndRequestParameters()
    {
        $query = $this->user->scopeSortable($this->user->newQuery());
        $this->assertEmpty($query->getQuery()->orders);
    }

    public function testSortableWithRequestParameters()
    {
        Input::replace(['sort' => 'name', 'order' => 'asc']);
        $resultArray = $this->user->scopeSortable($this->user->newQuery())->getQuery()->orders;
        $expected = ['column' => 'name', 'direction' => 'asc'];
        $this->assertEquals($expected, head($resultArray));

        Input::replace(['sort' => 'name', 'order' => 'desc']);
        $resultArray = $this->user->scopeSortable($this->user->newQuery())->getQuery()->orders;
        $expected = ['column' => 'name', 'direction' => 'desc'];
        $this->assertEquals($expected, head($resultArray));

        Input::replace(['sort' => 'name']);
        $result = $this->user->scopeSortable($this->user->newQuery())->getQuery()->orders;
        $this->assertNull($result);
    }

    public function testSortableWithDefaultAndWithoutRequestParameters()
    {
        $default = [
            'name' => 'desc',
        ];

        $resultArray = $this->user->scopeSortable($this->user->newQuery(), $default)->getQuery()->orders;
        $expected = ['column' => 'name', 'direction' => 'desc'];
        $this->assertEquals($expected, head($resultArray));
    }

    /**
     * This test might be useless, because testGetDefaultSortArray() does same
     */
    public function testSortableWithDefaultUsesConfig()
    {
        $default = 'name';

        $resultArray = $this->user->scopeSortable($this->user->newQuery(), $default)->getQuery()->orders;
        $expected = ['column' => 'name', 'direction' => $this->configDefaultOrder];
        $this->assertEquals($expected, head($resultArray));

        $default = ['name'];

        $resultArray = $this->user->scopeSortable($this->user->newQuery(), $default)->getQuery()->orders;
        $expected = ['column' => 'name', 'direction' => $this->configDefaultOrder];
        $this->assertEquals($expected, head($resultArray));
    }

    public function testGetDefaultSortArray()
    {
        $array = [];
        $resultArray = $this->invokeMethod($this->user, 'getDefaultSortArray', [$array]);
        $expected = [];
        $this->assertEquals($expected, $resultArray);

        $array = null;
        $resultArray = $this->invokeMethod($this->user, 'getDefaultSortArray', [$array]);
        $expected = [];
        $this->assertEquals($expected, $resultArray);

        $array = 'foo';
        $resultArray = $this->invokeMethod($this->user, 'getDefaultSortArray', [$array]);
        $expected = ['sort' => 'foo', 'order' => $this->configDefaultOrder];
        $this->assertEquals($expected, $resultArray);

        $array = ['foo'];
        $resultArray = $this->invokeMethod($this->user, 'getDefaultSortArray', [$array]);
        $expected = ['sort' => 'foo', 'order' => $this->configDefaultOrder];
        $this->assertEquals($expected, $resultArray);

        $array = ['foo' => 'desc'];
        $resultArray = $this->invokeMethod($this->user, 'getDefaultSortArray', [$array]);
        $expected = ['sort' => 'foo', 'order' => 'desc'];
        $this->assertEquals($expected, $resultArray);

        $array = ['foo' => 'desc', 'bar' => 'asc'];
        $resultArray = $this->invokeMethod($this->user, 'getDefaultSortArray', [$array]);
        $expected = ['sort' => 'foo', 'order' => 'desc'];
        $this->assertEquals($expected, $resultArray);
    }

    /**
     * Call protected/private method of a class.
     *
     * @param object &$object Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    protected function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    /**
     * @ignore
     *
     */
    public function dummy()
    {
        $this->markTestSkipped();
        $query = $this->user->newQuery()->with(['profile']); //query with relation
        Input::replace(['sort' => 'profile.phone', 'order' => 'asc']); //replace GET data in request

        $d = $this->user->scopeSortable($query); //
        dd($query->relation); // get orders that scopeSortable produced or ->toSql() to get raw SQL
    }
}

/**
 * Class User
 */
class User extends Model
{
    use \Kyslik\ColumnSortable\Sortable;

    /**
     * @var array
     */
    public $sortable = [
        'id',
        'name',
        'amount'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function profile()
    {
        return $this->hasOne(Profile::class, 'user_id', 'id');
    }
}

/**
 * Class Profile
 */
class Profile extends Model
{
    /**
     * @var array
     */
    public $sortable = [
        'phone'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
