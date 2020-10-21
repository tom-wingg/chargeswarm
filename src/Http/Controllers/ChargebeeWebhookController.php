<?php

namespace Rennokki\Chargeswarm\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;

class ChargebeeWebhookController extends Controller
{
    /**
     * Handle the incoming webhook.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function handleWebhook(Request $request)
    {
        $payload = json_decode(file_get_contents('php://input'));
        $event = Str::studly($payload->event_type);

        $subscription = optional($payload->content)->subscription;
        $storedSubscription = null;
        $plan = null;

        $subscriptionModel = config('chargeswarm.models.subscription');

        if ($subscription) {
            $storedSubscription = $subscriptionModel::find($subscription->id);

            if ($storedSubscription) {
                $plan = $storedSubscription->plan();
            }
        }

        $eventClass = '\Rennokki\Chargeswarm\Events\/'.$event;

        if (class_exists($eventClass)) {
            event(new $eventClass($payload, $storedSubscription, $subscription, $plan));
        }

        event(new \Rennokki\Chargeswarm\Events\WebhookReceived($payload, $storedSubscription, $subscription, $plan));

        if (method_exists($this, 'handle'.$event)) {
            $this->{ 'handle'.$event}($payload, $storedSubscription, $subscription, $plan);
        }

        return response('The webhook was handled for '.$request->event_type, 200);
    }

    /**
     * Handle a cancelled subscription.
     *
     * @param $payload The payload, in JSON Object, from the webhook.
     * @param $storedSubscription The stored subscription in the database, if any.
     * @param $subscription The subscription came from the webhook (same as $payload->content->subscription)
     * @param $plan The plan of the subscription, if any.
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function handleSubscriptionCancelled($payload, $storedSubscription, $subscription, $plan)
    {
        if ($storedSubscription) {
            $storedSubscription->update([
                'billing_period' => $subscription->billing_period,
                'billing_period_unit' => $subscription->billing_period_unit,
                'plan_quantity' => $subscription->plan_quantity,
                'plan_free_quantity' => $subscription->plan_free_quantity,
                'starts_at' => $subscription->started_at,
                'ends_at' => $subscription->cancelled_at,
                'trial_starts_at' => $subscription->trial_start,
                'trial_ends_at' => $subscription->trial_end,
                'status' => $subscription->status,
            ]);
        }

        return response('Webhook handled successfully.', 200);
    }

    /**
     * Handle a successfully paid subscription.
     *
     * @param $payload The payload, in JSON Object, from the webhook.
     * @param $storedSubscription The stored subscription in the database, if any.
     * @param $subscription The subscription came from the webhook (same as $payload->content->subscription)
     * @param $plan The plan of the subscription, if any.
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function handlePaymentSucceeded($payload, $storedSubscription, $subscription, $plan)
    {
        if ($storedSubscription) {
            $storedSubscription->update([
                'billing_period' => $subscription->billing_period,
                'billing_period_unit' => $subscription->billing_period_unit,
                'plan_quantity' => $subscription->plan_quantity,
                'plan_free_quantity' => $subscription->plan_free_quantity,
                'starts_at' => $subscription->started_at,
                'ends_at' => $subscription->current_term_end,
                'trial_starts_at' => $subscription->trial_start,
                'trial_ends_at' => $subscription->trial_end,
                'next_billing_at' => $subscription->next_billing_at,
                'status' => $subscription->status,
            ]);
        }

        return response('Webhook handled successfully.', 200);
    }

    /**
     * Handle a refunded payment.
     *
     * @param $payload The payload, in JSON Object, from the webhook.
     * @param $storedSubscription The stored subscription in the database, if any.
     * @param $subscription The subscription came from the webhook (same as $payload->content->subscription)
     * @param $plan The plan of the subscription, if any.
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function handlePaymentRefunded($payload, $storedSubscription, $subscription, $plan)
    {
        if ($storedSubscription) {
            $storedSubscription->update([
                'billing_period' => $subscription->billing_period,
                'billing_period_unit' => $subscription->billing_period_unit,
                'plan_quantity' => $subscription->plan_quantity,
                'plan_free_quantity' => $subscription->plan_free_quantity,
                'starts_at' => $subscription->started_at,
                'ends_at' => $subscription->current_term_end,
                'trial_starts_at' => $subscription->trial_start,
                'trial_ends_at' => $subscription->trial_end,
                'next_billing_at' => $subscription->next_billing_at,
                'status' => $subscription->status,
            ]);
        }

        return response('Webhook handled successfully.', 200);
    }

    /**
     * Handle subscription removal.
     *
     * @param $payload The payload, in JSON Object, from the webhook.
     * @param $storedSubscription The stored subscription in the database, if any.
     * @param $subscription The subscription came from the webhook (same as $payload->content->subscription)
     * @param $plan The plan of the subscription, if any.
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function handleSubscriptionDeleted($payload, $storedSubscription, $subscription, $plan)
    {
        if ($storedSubscription) {
            $storedSubscription->usages()->delete();

            $storedSubscription->delete();
        }

        return response('Webhook handled successfully.', 200);
    }

    /**
     * Handle subscription removal.
     *
     * @param $payload The payload, in JSON Object, from the webhook.
     * @param $storedSubscription The stored subscription in the database, if any.
     * @param $subscription The subscription came from the webhook (same as $payload->content->subscription)
     * @param $plan The plan of the subscription, if any.
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function handleSubscriptionRenewed($payload, $storedSubscription, $subscription, $plan)
    {
        if ($storedSubscription) {
            $storedSubscription->update([
                'billing_period' => $subscription->billing_period,
                'billing_period_unit' => $subscription->billing_period_unit,
                'plan_quantity' => $subscription->plan_quantity,
                'plan_free_quantity' => $subscription->plan_free_quantity,
                'starts_at' => $subscription->started_at,
                'ends_at' => $subscription->current_term_end,
                'trial_starts_at' => $subscription->trial_start,
                'trial_ends_at' => $subscription->trial_end,
                'next_billing_at' => $subscription->next_billing_at,
                'status' => $subscription->status,
            ]);
        }

        return response('Webhook handled successfully.', 200);
    }
}
