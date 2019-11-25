<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Models\Transaction;
use App\Models\Merchant\Building;

class TransactionController extends Controller
{

    public function index($request, $response)
    {
        if (!isAdmin($this->auth->user()->role[0]->id))
            throw new \Slim\Exception\NotFoundException($request, $response);

        $transactions = Transaction::with('collector:id,email')
                        ->with(['building' => function($query) {
                            $query->with('market')
                                ->with(['price' => function($query_child) {
                                    $query_child->with('type');
                                }]);
                        }])->get();
        $data = [
            'path' => 'transaction',
            'transactions' => $transactions
        ];

        return $this->view->render($response, 'transactions/index.twig', compact('data'));
    }

    public function show($request, $response)
    {
        $register_number = $request->getParam('register_number');

        if (empty($register_number))
            throw new \Slim\Exception\NotFoundException($request, $response);

        $building = Building::with('owner', 'market')
                            ->with(['price' => function($query) {
                                $query->with('type');
                            }])->with(['transaction' => function($query) {
                                $query->with('collector');
                            }])->where(
                                'register_number',
                                $register_number
                            )->first();

        if (!$building)
            throw new \Slim\Exception\NotFoundException($request, $response);

        $data = [
            'path' => 'collecting',
            'building'  => $building
        ];

        return $this->view->render($response, 'transactions/show.twig', compact('data'));
    }

    public function find($request, $response, $args)
    {
        $building_id = $args['building_id'];
        $year_transaction = $args['transaction_year'];
        $transactions = Transaction::where([
                            'year' => $year_transaction,
                            'building_id' => $building_id
                        ])->first();

        $building = Building::with('owner', 'market')
                        ->with(['price' => function($query) {
                            $query->with('type');
                        }])->where('id', $building_id)->first();

        $data = [
            'status' => 200,
            'message' => (!$transactions) ? 'payment_available' : 'tahun ini sudah dibayar',
            'data_transaction'  => $transactions,
            'data_building' => $building
        ];

        return $response->withJson($data, 200);
    }

    public function store($request, $response)
    {
        $parsed_data = $request->getParsedBody();

        $url = $this->router->pathFor(
            'dash.transaction.collect',
            [],
            ['register_number' => $parsed_data['register_number']]
        );

        $arr = explode("/", $parsed_data['year'], 2);
        $register_year = $arr[0];

        if ($register_year != date('Y')) {
            $transaction = Transaction::where('year', $parsed_data['year'] - 1)
                ->where('building_id', $parsed_data['building_id'])
                ->first();

            if (!$transaction) {
                $this->flash->addMessage('error', "Failed add transaction, Unpayed bill  on ". ($parsed_data['year'] - 1) ."!");
                return $response->withRedirect($url);
            }
        }

        $invoice = date('Y').'/'.rand(10000,99999).'#'.date('m');

        $data = [
            'title' => 'Pembayaran ruko',
            'invoice' => $invoice,
            'building_id' => $parsed_data['building_id'],
            'year'  => $parsed_data['year'],
            'collector_id' => $this->auth->user()->id,
            'payable' => $parsed_data['payment_total'],
            'mulct' => 0,
            'total' => $parsed_data['payment_total'],
            'payment_type' => 'CASH',
            'payment_status' => 'FULL_PAY'
        ];

        $transactions = Transaction::create($data);

        if (!$transactions) {
            $this->flash->addMessage('error', "Failed make transaction!");
            return $response->withRedirect($url);
        }

        $this->flash->addMessage('info', "Success make transaction!");
        return $response->withRedirect($url);
    }

    public function invoice($request, $response)
    {
        $register_number = $request->getParam('register_number');
        $transaction_id = $request->getParam('transaction_id');

        $building = Building::with('owner', 'market')
            ->with(['price' => function($query) {
                $query->with('type');
            }])->where(
                'register_number',
                $register_number
            )->first();

        $transaction = Transaction::findOrFail($transaction_id);

        $data = [
            'path' => 'invoice',
            'building' => $building,
            'transaction' => $transaction
        ];

        // return $response->withJson($data, 200);
        return $this->view->render($response, 'transactions/invoice.twig', compact('data'));

    }

}