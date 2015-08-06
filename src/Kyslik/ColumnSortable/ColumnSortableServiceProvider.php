<?php namespace Kyslik\ColumnSortable;


use Illuminate\Support\ServiceProvider;

class ColumnSortableServiceProvider extends ServiceProvider {
    
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
        $this->publishes([__DIR__ . '/../../config/columnsortable.php' => config_path('columnsortable.php')]);
        $this->registerBladeExtensions();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/columnsortable.php', 'columnsortable');
    }

    /**
     * Register Blade extensions.
     *
     * @return void
     */
    protected function registerBladeExtensions()
    {

        $blade = $this->app['view']->getEngineResolver()->resolve('blade')->getCompiler();

        $blade->directive('sortablelink', function ($expression)
        {
            return "<?php echo \Kyslik\ColumnSortable\Sortable::link(array {$expression});?>";
        });

    }

}
