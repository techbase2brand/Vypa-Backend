<?php

namespace Marvel\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Dompdf\Options;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Marvel\Database\Models\DownloadToken;
use Marvel\Database\Models\Order;
use Marvel\Database\Models\Settings;
use Marvel\Database\Repositories\OrderRepository;
use Marvel\Enums\PaymentGatewayType;
use Marvel\Enums\Permission;
use Marvel\Exceptions\MarvelException;
use Marvel\Exports\OrderExport;
use Marvel\Http\Requests\OrderCreateRequest;
use Marvel\Http\Requests\OrderUpdateRequest;
use Marvel\Traits\OrderManagementTrait;
use Marvel\Traits\PaymentStatusManagerWithOrderTrait;
use Marvel\Traits\PaymentTrait;
use Marvel\Traits\TranslationTrait;
use Marvel\Traits\WalletsTrait;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Support\Facades\Cache;


class OrderController extends CoreController
{
    use WalletsTrait,
        OrderManagementTrait,
        TranslationTrait,
        PaymentStatusManagerWithOrderTrait,
        PaymentTrait;

    public OrderRepository $repository;
    public Settings $settings;

    public function __construct(OrderRepository $repository)
    {
        $this->repository = $repository;
        $this->settings = Settings::first();
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Collection|Order[]
     */
    public function index(Request $request)
    {
        $limit = $request->limit ?: 10;
        return $this->fetchOrders($request)
            ->paginate($limit)
            ->withQueryString();
    }

    /**
     * fetchOrders
     *
     * @param mixed $request
     * @return object
     */
    public function fetchOrders(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            throw new AuthorizationException(NOT_AUTHORIZED);
        }

        // Base query with selective loading
        $query = $this->repository->select([
            'id',
            'tracking_number',
            'order_status',
            'payment_gateway',
            'shop_id',
            'customer_id',
            'created_at',
            'parent_id',
            'id',
            'customer_contact',
            'customer_name',
            'paid_total',
            'total',
            'payment_status',
            'billing_address',
            'amount',
        ]);

        // Add shop_id condition if it exists in the request
        if ($request->has('shop_id') && $request->shop_id) {
            $query->where('shop_id', '=', $request->shop_id);
        }

        // Add date filtering based on the request parameter
        if ($request->has('days')) {
            $days = $request->days;
            $dateCondition = now()->subDays($days)->startOfDay();
            $query->where('created_at', '>=', $dateCondition);
        }

        if ($request->has('type') && $request->type) {
            $query->where('payment_gateway', '=', $request->type);
        }

        if ($request->has('status') && $request->status) {
            $query->where('order_status', '=', $request->status);
        }

        // Optimize permission-based queries
        switch (true) {
            case $user->hasPermissionTo(Permission::SUPER_ADMIN):
                return $query->where('parent_id', '=', null);
                break;

            case $user->hasPermissionTo(Permission::STORE_OWNER):
                if ($this->repository->hasPermission($user, $request->shop_id)) {
                    return $query->where('parent_id', '!=', null);
                } else {
                    return $query->whereIn('shop_id', $user->shops->pluck('id'));
                }
                break;

            case $user->hasPermissionTo(Permission::STAFF):
                if ($this->repository->hasPermission($user, $request->shop_id)) {
                    return $query->where('parent_id', '!=', null);
                } else {
                    return $query->where('shop_id', '=', $user->shop_id);
                }
                break;

            default:
                return $query->where('customer_id', '=', $user->id);
                break;
        }
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param OrderCreateRequest $request
     * @return LengthAwarePaginator|\Illuminate\Support\Collection|mixed
     * @throws MarvelException
     */
    public function store(OrderCreateRequest $request)
    {
        return DB::transaction(fn () => $this->repository->storeOrder($request, $this->settings));
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param $params
     * @return JsonResponse
     * @throws MarvelException
     */
    public function show(Request $request, $params)
    {
        $request["tracking_number"] = $params;
        try {
            return $this->fetchSingleOrder($request);
        } catch (MarvelException $e) {
            throw new MarvelException($e->getMessage());
        }
    }

    /**
     * fetchSingleOrder
     *
     * @param mixed $request
     * @return void
     * @throws MarvelException
     */
    public function fetchSingleOrder(Request $request)
    {
        $user = $request->user() ?? null;
        $language = $request->language ?? DEFAULT_LANGUAGE;
        $orderParam = $request->tracking_number ?? $request->id;

        try {
            $order = $this->repository->select([
                'id',
                'tracking_number',
                'order_status',
                'payment_gateway',
                'shop_id',
                'customer_id',
                'created_at',
                'parent_id',
                'language',
                'id',
                'customer_contact',
                'customer_name',
                'paid_total',
                'total',
                'payment_status',
                'billing_address',
                'amount',
                'discount',
                'sales_tax',
                'note'
            ])
            ->with([
                'products' => function($query) {
                    $query->select(['products.id', 'products.name', 'products.price',  'products.image', 'products.slug', 'order_id']);
                },
                'shop' => function($query) {
                    $query->select(['id', 'name', 'slug']);
                },
                'children.shop' => function($query) {
                    $query->select(['id', 'name', 'slug']);
                },
                'wallet_point' => function($query) {
                    $query->select(['id', 'amount', 'order_id']);
                }
            ])
            ->where('language', $language)
            ->where(function($query) use ($orderParam) {
                $query->where('id', $orderParam)
                      ->orWhere('tracking_number', $orderParam);
            })
            ->firstOrFail();

            if (!in_array($order->payment_gateway, [
                PaymentGatewayType::CASH,
                PaymentGatewayType::CASH_ON_DELIVERY,
                PaymentGatewayType::FULL_WALLET_PAYMENT
            ])) {
                $order['payment_intent'] = $this->attachPaymentIntent($orderParam);
            }

            if (!$order->customer_id) {
                return $order;
            }

            if ($user && $user->hasPermissionTo(Permission::SUPER_ADMIN)) {
                return $order;
            }

            if (isset($order->shop_id)) {
                if ($user && ($this->repository->hasPermission($user, $order->shop_id) || $user->id == $order->customer_id)) {
                    return $order;
                }
            } elseif ($user && $user->id == $order->customer_id) {
                return $order;
            }

            throw new AuthorizationException(NOT_AUTHORIZED);
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException(NOT_FOUND);
        }
    }

    /**
     * findByTrackingNumber
     *
     * @param mixed $request
     * @param mixed $tracking_number
     * @return void
     */
    public function findByTrackingNumber(Request $request, $tracking_number)
    {
        $user = $request->user() ?? null;
        try {
            $order = $this->repository->with(['products', 'children.shop', 'wallet_point', 'payment_intent'])
                ->findOneByFieldOrFail('tracking_number', $tracking_number);

            if ($order->customer_id === null) {
                return $order;
            }
            if ($user && ($user->id === $order->customer_id || $user->can('super_admin'))) {
                return $order;
            } else {
                throw new AuthorizationException(NOT_AUTHORIZED);
            }
        } catch (MarvelException $e) {
            throw new MarvelException(NOT_FOUND);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param OrderUpdateRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(OrderUpdateRequest $request, $id)
    {
        try {
            $request["id"] = $id;
            return $this->updateOrder($request);
        } catch (MarvelException $e) {
            throw new MarvelException(COULD_NOT_UPDATE_THE_RESOURCE, $e->getMessage());
        }
    }

    public function updateOrder(OrderUpdateRequest $request)
    {
        return $this->repository->updateOrder($request);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        try {
            return $this->repository->findOrFail($id)->delete();
        } catch (MarvelException $e) {
            throw new MarvelException(NOT_FOUND);
        }
    }

    /**
     * Export order dynamic url
     *
     * @param Request $request
     * @param int $shop_id
     * @return string
     */
    public function exportOrderUrl(Request $request, $shop_id = null)
    {
        try {
            $user = $request->user();

            if ($user && !$this->repository->hasPermission($user, $request->shop_id)) {
                throw new AuthorizationException(NOT_AUTHORIZED);
            }

            $dataArray = [
                'user_id' => $user->id,
                'token' => Str::random(16),
                'payload' => $request->shop_id
            ];
            $newToken = DownloadToken::create($dataArray);

            return route('export_order.token', ['token' => $newToken->token]);
        } catch (MarvelException $e) {
            throw new MarvelException(SOMETHING_WENT_WRONG."298", $e->getMessage());
        }
    }

    /**
     * Export order to excel sheet
     *
     * @param string $token
     * @return void
     */
    public function exportOrder($token)
    {
        $shop_id = 0;
        try {
            $downloadToken = DownloadToken::where('token', $token)->first();

            $shop_id = $downloadToken->payload;
            $downloadToken->delete();
        } catch (MarvelException $e) {
            throw new MarvelException(TOKEN_NOT_FOUND);
        }

        try {
            return Excel::download(new OrderExport($this->repository, $shop_id), 'orders.xlsx');
        } catch (MarvelException $e) {
            throw new MarvelException(NOT_FOUND);
        }
    }

    /**
     * Export order dynamic url
     *
     * @param Request $request
     * @param int $shop_id
     * @return string
     */
    public function downloadInvoiceUrl(Request $request)
    {

        try {
            $user = $request->user();
            if ($user && !$this->repository->hasPermission($user, $request->shop_id)) {
                throw new AuthorizationException(NOT_AUTHORIZED);
            }
            if (empty($request->order_id)) {
                throw new NotFoundHttpException(NOT_FOUND);
            }
            $language = $request->language ?? DEFAULT_LANGUAGE;
            $isRTL = $request->is_rtl ?? false;

            $translatedText = $this->formatInvoiceTranslateText($request->translated_text);

            $payload = [
                'user_id' => $user->id,
                'order_id' => intval($request->order_id),
                'language' => $language,
                'translated_text' => $translatedText,
                'is_rtl' => $isRTL
            ];

            $data = [
                'user_id' => $user->id,
                'token' => Str::random(16),
                'payload' => serialize($payload)
            ];

            $newToken = DownloadToken::create($data);

            return route('download_invoice.token', ['token' => $newToken->token]);
        } catch (MarvelException $e) {
            throw new MarvelException($e->getMessage());
        }
    }

    /**
     * Export order to excel sheet
     *
     * @param string $token
     * @return void
     */
    public function downloadInvoice($token)
    {
        $payloads = [];
        try {
            $downloadToken = DownloadToken::where('token', $token)->firstOrFail();
            $payloads = unserialize($downloadToken->payload);
            $downloadToken->delete();
        } catch (MarvelException $e) {
            throw new MarvelException(TOKEN_NOT_FOUND);
        }

        try {
            $settings = Settings::getData($payloads['language']);
            $order = $this->repository->with(['products', 'children.shop', 'wallet_point', 'parent_order'])->where('id', $payloads['order_id'])->orWhere('tracking_number', $payloads['order_id'])->firstOrFail();

            $invoiceData = [
                'order' => $order,
                'settings' => $settings,
                'translated_text' => $payloads['translated_text'],
                'is_rtl' => $payloads['is_rtl'],
                'language' => $payloads['language'],
            ];
            $pdf = PDF::loadView('pdf.order-invoice', $invoiceData);
            $options = new Options();
            $options->setIsPhpEnabled(true);
            $options->setIsJavascriptEnabled(true);
            $pdf->getDomPDF()->setOptions($options);

            $filename = 'invoice-order-' . $payloads['order_id'] . '.pdf';

            return $pdf->download($filename);
        } catch (MarvelException $e) {
            throw new MarvelException(NOT_FOUND);
        }
    }

    /**
     * submitPayment
     *
     * @param mixed $request
     * @return void
     * @throws Exception
     */
    public function submitPayment(Request $request): void
    {
        $tracking_number = $request->tracking_number ?? null;
        try {
            $order = $this->repository->with(['products', 'children.shop', 'wallet_point', 'payment_intent'])
                ->findOneByFieldOrFail('tracking_number', $tracking_number);

            $isFinal = $this->checkOrderStatusIsFinal($order);
            if ($isFinal) return;

            switch ($order->payment_gateway) {

                case PaymentGatewayType::STRIPE:
                    $this->stripe($order, $request, $this->settings);
                    break;

                case PaymentGatewayType::PAYPAL:
                    $this->paypal($order, $request, $this->settings);
                    break;

                case PaymentGatewayType::MOLLIE:
                    $this->mollie($order, $request, $this->settings);
                    break;

                case PaymentGatewayType::RAZORPAY:
                    $this->razorpay($order, $request, $this->settings);
                    break;

                case PaymentGatewayType::SSLCOMMERZ:
                    $this->sslcommerz($order, $request, $this->settings);
                    break;

                case PaymentGatewayType::PAYSTACK:
                    $this->paystack($order, $request, $this->settings);
                    break;

                case PaymentGatewayType::PAYMONGO:
                    $this->paymongo($order, $request, $this->settings);
                    break;

                case PaymentGatewayType::XENDIT:
                    $this->xendit($order, $request, $this->settings);
                    break;

                case PaymentGatewayType::IYZICO:
                    $this->iyzico($order, $request, $this->settings);
                    break;

                case PaymentGatewayType::BKASH:
                    $this->bkash($order, $request, $this->settings);
                    break;

                case PaymentGatewayType::FLUTTERWAVE:
                    $this->flutterwave($order, $request, $this->settings);
                    break;
            }
        } catch (MarvelException $e) {
            throw new MarvelException(SOMETHING_WENT_WRONG."478", $e->getMessage());
        }
    }
}
