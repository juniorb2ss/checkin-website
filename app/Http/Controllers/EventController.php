<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Model\EventList;
use App\Model\EventType;
use App\Model\EventCheck;
use App\User;
use App\Model\State;
use Carbon\Carbon;
use App\Model\Graduate;
use DB;
use Validator;
use App\Http\Requests\StoreEventValidation;

class EventController extends Controller
{
    //
    // public function eventListFilter(Request $request, EventList $event)
    // {
    //     $eventList = $event->getEventList(auth()->user()->id, $request);
           
    //     $eventType = EventType::all();
    //     $state = State::all();
    //     $date = Carbon::now();
        
    //     // set 'old' value to view
    //     $request->flash();

    //     // dataForm to append on pagination links 
    //     $dataForm = $request->except('_token');

    //     return view('events.list', compact('eventList', 'eventType', 'date', 'dataForm', 'state'));
    // }
    
    /**
     * Dicas:
     *
     * 1 - SEMPRE filtre o que você espera no método.
     * px: Se este método recebe verbos GET/POST/PUT
     * faça a validação do que é esperado, de todos os campos possíveis.
     * Esse tipo de validação pode futuramente diminuir sua carga de requests inválidas.
     *
     * 2 - Utilize IOC, laravel vem preparado para isso e trabalha MUITO BEM.
     * Referência: https://laravel.com/docs/5.6/routing#route-model-binding
     * Saiba mais: https://stackoverflow.com/questions/24484782/what-is-laravel-ioc-container-in-simple-words
     *
     * 3 - Leia o método EventList::getEventList()
     *
     * 4 - $request->except('_token')
     * Eu não compreendi o motivo de você estar sempre removendo o _token do request.
     * 
     * @param  EventList $events [description]
     * @param  Carbon    $now    Quando é injetado a instância de carbon, sempre será Carbon::now()
     * @param  State     $states [description]
     * @param  Request   $user   [description]
     * @return [type]            [description]
     */
    public function eventListFilter(EventList $events, EventType $eventsType, Carbon $now, State $states, Request $user)
    {
        // se você fizer o model binding, não precisa executar
        // lógica de query no controller, pois o laravel já faz o IoC
        // é irá te retornar todos os eventos do usuário da requisição já filtrado.
            
        // get all avaiable states
        $allStates = $states->all();

        // get all type of events
        // 
        // Cuidado com querys com model::all()
        // Isso diminui e muito a performance da aplicação.
        // Recomendo sempre condicionar apenas o que você precisa.
        // 
        // Para consultas "genéricas", você pode utilizar o cache.
        // Imagine amanhã este método sendo invocado 1000 vezes por segundo?
        // Você precisará executar esta query 1000 vezes?
        // Por isso, temos o cache:
        // Documentação: https://laravel.com/docs/5.6/cache#cache-usage
        $allEventsTypes = Cache::remember('eventsType', 5, function () {
            return $eventsType
                        // Especifiques as colunas que irá utilizar na view
                        // descartando todo o resto.
                        ->select(['column1', 'column2', 'column3'])
                        // talvez seja necessário mesmo buscar todos os métodos
                        // mas, estes registros não pode existir nenhum tipo de filtro?
                        // por exemplo:
                        // 
                        // Impor um scope que retorna apenas os tipos de eventos ativos
                        // Documentação: https://laravel.com/docs/5.6/eloquent#local-scopes
                        // 
                        // p.x: Sempre utilize scope.
                        // Você me perguntou o "pq"
                        // Resposta: https://pt.stackoverflow.com/questions/148776/para-que-serve-um-scope-no-laravel
                        ->onlyEnabled()

                        // Me parece que aqui você pode utilizar cache, p.x:
                        // https://laravel.com/docs/5.6/cache
                        ->all();
        });

        // Não compreendi o motivo de utilizar este método, reveja a utilidade dele
        // se não for necessário, descarte-o.
        // 
        // Menos é sempre mais.
        //$request->flash();

        return view('events.list', compact(
                    'events', 
                    'now',
                    'allEventsTypes',                     
                    'allStates'
                ));
    }

    public function editTypes($id, Request $request, EventType $type)
    {
        dd($request->all());
        $validator = Validator::make(['id' => $id, 'name' => $name], [
            'name'              => 'required|min:4',
        ]);
    }

    // public function editGet($id, EventType $type)
    // {
    //     $info = $type->id($id)->get()->first();

    //     return response()->json(['info' => $info]);
    // }
    
    /**
     * Mais simples, não?
     * Como isso funciona?
     * Novamente, veja o model binding: https://laravel.com/docs/5.6/routing#route-model-binding
     *
     * Para ver em código como fica, siga os arquivos e linhas:
     * web.php:43
     * RouteServiceProvider:42-45
     * 
     * @param  EventType $type [description]
     * @return [type]          [description]
     */
    public function editGet(EventType $type)
    {
        return response()->json([
            'info' => $type
        ]);
    }

    public function types()
    {
        $type = EventType::all();

        return view('events.types', compact('type'));
    }

    public function deletEvent(Request $request, EventList $list)
    {
        if($list->deletEvent($request->event_id))
            return response()->json(['error' => -1, 'url' => route('eventos.listar')]);

        return response()->json(['error' => 1]);
    }

    public function eventView($name, $id)
    {
        // initialize object EventList 
        $event = new EventList;

        if(!$event->event($id)->exists())
            return redirect(route('eventos.listar'));
            
        // get eventInfo
        $eventInfo = $event->getEventInfo($id);

        $graduate = Graduate::all();
        return view('events.view', compact('eventInfo', 'graduate'));
    }

    public function createEvent()
    {
        $eventType = EventType::all();
        $state = State::all();

        return view('events.create', compact('state', 'eventType'));
    }

    public function createEventStore(StoreEventValidation $request, EventList $list)
    {
        $result = $list->insertEvent($request->except('_token', 'states'));

        if($result) {
            // initialize object EventList 
            $event = new EventList;
    
            $eventInfo = $event->getEventInfo($result->id);
    
            return redirect(route('eventos.visualizar', ['nome'=> kebab_case ($eventInfo->name), 'id'=>$eventInfo->id]));
        }
        else {
            
        return redirect()
                ->back();   
        }
    }

    public function doCheckin(Request $request, User $user, EventCheck $check)
    {
        // setamos as variáveis 
        $eventId    = $request->event_id;
        $userId     = $request->pai_id;
        $invitedId  = $request->convidado_id;
        $sell       = $request->sell;

        // chamamos a função responsável pelo checkin
        // e retornamos o valor para retn
        $retn = $check->doCheck($userId,$invitedId, $eventId, $sell);
        
        // Retornamos uma ARRAY e ess array é convertida
        // para json 
        return response()->json(json_encode($retn));
    }

    public function sold(Request $request, EventCheck $check)
    {
        $id     = $request->id;
        $type   = $request->type;

        $retn = $check->sold($id, $type);

        return response()->json(json_encode(
            $retn
        ));
    }

    public function removeCheckin(Request $request, EventCheck $check)
    {
        $id     = $request->id;

        $retn = $check->removeCheck($id);

        return response()->json(json_encode(
            ['error' => -1,
            'msg' => 'ok']
        ));
    }
}
