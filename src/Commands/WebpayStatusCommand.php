<?php

namespace Crealab\WebpayPlusPaymentGateway\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Client;

class WebpayStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webpay:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Returns Webpay services status';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(){
        $response = $this->sendStatusRequest();
        if(is_null($response)){
            $this->error('Bad response from status server');
        }
        $info = $this->getInfoForOutputTable($response);
        $this->table(['name', 'status', 'description'], $info);
    }

    private function getInfoForOutputTable($response){
        return  array_map ( array($this, 'mapResponseComponentItem') , $response->components  );
    }

    private function mapResponseComponentItem($item){
        return [
            'name'          => $item->name,
            'status'        => $item->status,
            'description'   => $item->description
        ];
    }

    private static function sendStatusRequest()
    {
        try {
            $client = new Client([
                'base_uri' => 'https://b68xvwm7hg2w.statuspage.io' , 0,
            ]);
            $request = new GuzzleRequest('GET', '/api/v2/components.json');
            $response = $client->send($request);
            return json_decode($response->getBody());
        } catch (\Throwable $th) {
            return null;
        }
    }
}
