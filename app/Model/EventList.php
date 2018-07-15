<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class EventList extends Model
{
    //
    protected $casts = [
        'data' => 'datetime: Y'
    ];

    protected $fillable = ['data', 'name', 'description', 'eventtype_id', 'city_code'];
    protected $perPage = 10;

    public function checks()
    {
        return $this->hasMany(EventCheck::class, 'eventlist_id', 'id');
    }

    public function type()
    {
        return $this->belongsTo(EventType::class, 'eventtype_id', 'id');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_code', 'code');
    }

    // Scopes
    public function scopeEvent($query, $id)
    {
        return $query->where('id', $id);
    }

    public function deletEvent($id)
    {
        $event = auth()->user()->events()->event($id)->get()->first();

        if(!$event) 
            return false;

        return $event->delete();
    }

    public function getEventInfo($id) : EventList
    {
        return $this->where('id', $id)
                    ->get()
                    ->first();
    }

    public function insertEvent($data) 
    {
        $insert = auth()->user()->events()->create([
            'data'              => $data['data'],
            'name'              => $data['name'],
            'description'       => $data['description'],
            'eventtype_id'      => $data['eventtype_id'],
            'city_code'         => $data['cities']
        ]);

        return $insert;
    }  
    
    // Este código pode ser funcional, mas, está sujo.
    // Vamos melhorar ele.
    // public function getEventList($id, $request = null)
    // {
    //     if($request !== null)
    //     {
    //         $eventList = $this->where('user_id', $id)->where(function($query) use ($request) {
    //             if(isset($request->name))
    //                 $query->where('name', 'LIKE', "%{$request->name}%");

    //             if(isset($request->dateInitial))
    //                 $query->where('data', '>=', $request->dateInitial." 23:59:59");
                
    //             if(isset($request->dateInitial))
    //                 $query->where('data', '>=', $request->dateInitial." 23:59:59");
                
    //             if(isset($request->dateMax))
    //                 $query->where('data', '<=', $request->dateMax . " 23:59:59");

    //             if(isset($request->type) && $request->type != -1)
    //                 $query->where('eventtype_id', $request->type);

    //             if(isset($request->cities))
    //                 $query->where('city_code', $request->cities);
    //         })->orderBy('data', 'DESC')
    //           ->with('city')
    //           ->paginate($this->perPage);
    //     }
    //     else
    //         $eventList = $this->where('user_id', $id)
    //                           ->orderBy('data', 'DESC')
    //                           ->with('city')
    //                           ->paginate($this->perPage);
    
    //     return $eventList;
    // }
    // 
    
    public function getEventList($id, Illuminate\Http\Request $request = null)
    {
        // Primeiro vou instanciar uma nova query, para ir jogando os filtros
        $query = $this->newQuery();

        // Eu não preciso condicionar se há ou não request, fica feio.
        
        $query = $query->onUsers($id); // scope: método scopeOnUsers

        // Não vou exemplificar com todos os filtros que você fez
        // mas, um só deverá ser suficiente.
        // 
        // método helper, leia: https://laravel.com/docs/5.6/helpers#method-optional
        $dateInitial = optional($request)->get('dateInitial');
        $dateMax = optional($request)->get('dateMax');

        // aplica o filtro pela data de inicio
        $query = $query->onDateBetween($dateInitial, $dateMax);

        return $query;
    }

    /**
     * Esse scopo ele irá injetar na query a condição pelo user_id
     * CASO TENHA.
     * 
     * @param  mixed  $ids
     * @param  @param \Illuminate\Database\Eloquent\Builder $query $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOnUsers($ids, $query)
    {   
        // se for passado uma string, transforma em array
        $ids = is_array($ids) ? $ids : (array) $ids;

        // foi passado ids ou null?
        if(count($id)) {
            // Aqui aplico o filtro por usuário.
            // Bônus: em um só método, você pode buscar eventos
            // de um único usuário ou de vários :)
            $query->whereIn('user_id', $ids);
        }

        // retorno a query para continuação do código
        return $query;
    }

    /**
     * [scopeOnDateBetween description]
     * @param  [type] $dateInitial [description]
     * @param  [type] $dateFinal   [description]
     * @return [type]              [description]
     */
    public function scopeOnDateBetween($dateInitial, $dateFinal, $query)
    {
        // Por que apenas verificar se a data inicial não é null?
        // Pois você pode informar uma data de inicio e não uma final
        // mas, pode informar uma data final e não uma de inicio?
        if( !is_null($dateInitial) ) {

            $dateInitial = (($dateInitial instanceof Carbon)
                            ? $dateInitial
                            // Talvez o método parse não funcione aqui
                            // pois eu não vi qual padrão de data esta chegando aqui
                            // mas, se for por exemplo um padrão brasileiro
                            // você poderá utilizar o método createFromFormat
                            // 
                            // p.x: Carbon::createFromFormat('Y-m-d H', $dateInitial)
                            : Carbon::parse($dateInitial))
                                // muito melhor definir aqui, não é?
                                ->setTime(23, 59, 59);

            // como podemos passar apenas a data de inicio e não final, devemos prever isso
            $dateFinal = (($dateFinal instanceof Carbon)
                            ? $dateFinal
                            // Talvez o método parse não funcione aqui
                            // pois eu não vi qual padrão de data esta chegando aqui
                            // mas, se for por exemplo um padrão brasileiro
                            // você poderá utilizar o método createFromFormat
                            // 
                            // p.x: Carbon::createFromFormat('Y-m-d H', $dateInitial)
                            : ((is_string($dateFinal))
                                ? Carbon::parse($dateFinal)
                                : Carbon::now()))
                                // muito melhor definir aqui, não é?
                                ->setTime(23, 59, 59);

            $query->whereBetween('data', [$dateInitial, $dateFinal]);

        }
    }
}
