<?php

namespace App\Providers;

use App\Model\EventType;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        //

        parent::boot();

        // Binding
        // 
        // O que isso afinal faz?
        // Toda vez que você utilizar o parametro eventTypeId em uma rota
        // o laravel vem aqui e tenta instanciar o model, se conseguir
        // ele injeta o model DENTRO do controler, pronto para você utilizar
        // já com o registro que você quer trabalhar, ao invés de você
        // dentro do controller ficar fazer model::where($id)->first()
        // 
        // Se o registro não for encontrado, o próprio laravel já
        // lança uma exception de ModelNotFoundException
        Route::bind('eventTypeId', function ($eventTypeId) {
            // API: https://laravel.com/api/5.6/Illuminate/Database/Eloquent/Builder.html#method_findOrFail
            return EventType::findOrFail($eventTypeId);
        });
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();

        $this->mapWebRoutes();

        //
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
             ->namespace($this->namespace)
             ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
             ->middleware('api')
             ->namespace($this->namespace)
             ->group(base_path('routes/api.php'));
    }
}
