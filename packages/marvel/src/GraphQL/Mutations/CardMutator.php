<?php


namespace Marvel\GraphQL\Mutation;


use Exception;
use Marvel\Exceptions\MarvelException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Marvel\Facades\Shop;

class CardMutator
{
    /**
     * @throws MarvelException
     */
    public function delete($rootValue, array $args, GraphQLContext $context)
    {
        try {
            return Shop::call('Marvel\Http\Controllers\PaymentMethodController@deletePaymentMethod', $args);
        } catch (Exception $e) {
            throw new MarvelException(SOMETHING_WENT_WRONG."21");
        }
    }

    /**
     * @throws MarvelException
     */
    public function store($rootValue, array $args, GraphQLContext $context)
    {
        try {
            return Shop::call('Marvel\Http\Controllers\PaymentMethodController@store', $args);
        } catch (Exception $e) {
            throw new MarvelException(SOMETHING_WENT_WRONG."33");
        }
    }

    /**
     * @throws MarvelException
     */
    public function setDefaultPaymentMethod($rootValue, array $args, GraphQLContext $context)
    {
        try {
            return Shop::call('Marvel\Http\Controllers\PaymentMethodController@setDefaultPaymentMethod', $args);
        } catch (Exception $e) {
            throw new MarvelException(SOMETHING_WENT_WRONG."45");
        }
    }
}
