<?php namespace Kyslik\ColumnSortable;

use Illuminate\Support\ServiceProvider;

class ColumnSortableServiceProvider extends ServiceProvider
{
    
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $config_path = __DIR__ . '/../config/columnsortable.php';
        $this->publishes([$config_path => config_path('columnsortable.php')], 'columnsortable');
        $this->registerBladeExtensions();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $config_path = __DIR__ . '/../config/columnsortable.php';
        $this->mergeConfigFrom($config_path, 'columnsortable');
    }

    /**
     * Register Blade extensions.
     *
     * @return void
     */
    protected function registerBladeExtensions()
    {
        $blade = $this->app['view']->getEngineResolver()->resolve('blade')->getCompiler();

        $blade->directive('sortablelink', function ($expression) {
            if ($expression[0] === '(') {
                return "<?php echo \Kyslik\ColumnSortable\Sortable::link(array {$expression});?>";
            }
            return "<?php echo \Kyslik\ColumnSortable\Sortable::link(array ({$expression}));?>";
        });
    }
}
