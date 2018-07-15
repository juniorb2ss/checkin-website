<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['middleware' => ['auth']], function() {
    //
    Route::get('/', 'PageController@dashboard')->name('dashboard');
    Route::get('dashboard', 'PageController@dashboard')->name('dashboard');
});

Route::group(['middleware' => ['auth'], 'prefix' => 'eventos'], function(){
    // Particularmente eu não gosto do método ANY
    // é muito globalizado e futuramente pode gerar grande carga de verbo na aplicação
    // Se a rota recebe verbos multiplos, p.x:
    // Route::match(['GET', 'POST', ...])
    // 
    // API: https://laravel.com/api/5.6/Illuminate/Routing/RouteRegistrar.html#method_match
    // Documentação: https://laravel.com/docs/5.6/routing#basic-routing
    Route::any('listar', 'EventController@eventListFilter')
           ->name('eventos.listar');

    Route::get('visualizar/{name}/{id}', 'EventController@eventView')->where(['name' => '[A-Za-z0-9áàâãéèêíïóôõöúçñ-]+', 'id' => '[0-9]+'])->name('eventos.visualizar');

    Route::post('criar', 'EventController@createEventStore')->name('eventos.criar.enviar');
    Route::get('criar', 'EventController@createEvent')->name('eventos.criar');

    // ações
    Route::post('checkin', 'EventController@doCheckin')->name('eventos.checkin');
    Route::post('removerCheckin', 'EventController@removeCheckin')->name('eventos.removecheckin');
    Route::post('sold', 'EventController@sold')->name('eventos.sold');

    Route::get('tipos', 'EventController@types')->name('eventos.tipos');
    Route::get('tipos/{id}/editar', 'EventController@editTypes')->where(['id' => '[0-9]+'])->name('eventos.tipo.editar');

    // Nesse controler, esperando um ID de apenas número
    // e caso essa primeira validação seja respeitada
    // o laravel vai chamar o binding la de RouteServiceProvider:42-45
    // e injetar dentro do controller.
    // 
    // Novamente, essa é mágica de IoC.
    // 
    // Veja um video exemplificando: https://laracasts.com/series/laravel-from-scratch-2017/episodes/9
    // 
    // p.s: recomendo assinar o laracasts, são ótimas aulas e são do Jeffrey Way
    Route::get('tipos/get/{eventTypeId}', 'EventController@editGet')
                ->where(['id' => '[0-9]+'])
                ->name('eventos.tipo.get');

    // 
    Route::post('deletar', 'EventController@deletEvent')->name('eventos.deletar');
});

Route::group(['middleware' => ['auth'], 'prefix' => 'usuario'], function () {
    Route::post('cadastrar', 'UserController@cadastrar')->name('cadastrar.convidado');
});

Route::group(['middleware' => ['auth'], 'prefix' => 'report'], function() {
    Route::post('staffOnEventList', 'ReportController@staffOnEventList')->name('eventos.relatorio.equipe');
    Route::post('staffOnEventGraph', 'ReportController@staffOnEventGraph')->name('eventos.relatorio.graph');
});

Route::group(['prefix' => 'util'], function (){
    Route::get('getCities/{state}', 'UtilController@getCities')->where(['state' => '[A-Za-z]+'])->name('util.cities');
    Route::get('getMonitor', 'UtilController@getMonitor')->name('util.monitor');
    Route::post('listaConvidados', 'UtilController@listaConvidados')->name('util.listaconvidados');
    Route::post('graduadosContador', 'UtilController@graduadosContador')->name('util.graduadoscontador');
    Route::post('totalVendidos', 'UtilController@totalVendidos')->name('util.totalvendidos');
    Route::post('listaVendas', 'UtilController@listaVendas')->name('util.listavendas');
});

Auth::routes();
