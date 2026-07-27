<?php
/**
 * @file StripeWebhookController.php
 * @description Recibe y verifica los eventos que Stripe envía tras el pago de una Checkout Session,
 *              y es la fuente de verdad para confirmar o cancelar una Reserva.
 * @date 2026-07-27
 */

namespace App\Http\Controllers;

use App\Services\StripeCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function __construct(private readonly StripeCheckoutService $stripeCheckout)
    {
    }

    public function handle(Request $request): Response
    {
        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature', ''),
                config('services.stripe.webhook_secret')
            );
        } catch (SignatureVerificationException|\UnexpectedValueException $e) {
            Log::warning('Webhook de Stripe rechazado: firma inválida. ' . $e->getMessage());

            return response('Firma inválida', 400);
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                $this->stripeCheckout->confirmarPago($event->data->object);
                break;

            case 'checkout.session.expired':
                $this->stripeCheckout->liberarReserva($event->data->object);
                break;
        }

        return response('OK', 200);
    }
}
