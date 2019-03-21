<?php

namespace greenex\Excel\Transactions;

use Illuminate\Support\Manager;

class TransactionManager extends Manager
{
    /**
     * @return string
     */
    public function getDefaultDriver()
    {
        return config('excel2.transactions.handler');
    }

    /**
     * @return NullTransactionHandler
     */
    public function createNullDriver()
    {
        return new NullTransactionHandler();
    }

    /**
     * @return DbTransactionHandler
     */
    public function createDbDriver()
    {
        return new DbTransactionHandler(
            $this->app->get('db.connection')
        );
    }
}
