<?php

    namespace Polaris\Contracts;

    /**
     * Interface defining the must have methods for the abstract repository class.
     *
     * @package Polaris\Contracts
     */
    interface RepositoryInterface
    {
        /**
         * Returns all the records in the current constructed query.
         *
         * @param  array  $columns  array of columns to select from the model(s).
         * @return mixed
         */
        public function get(array $columns = ['*']);

        /**
         * Returns a paginated collection of models for the curren constructed query.
         *
         * @param  int  $numResults  number of results returned by this method.
         * @param  array  $columns  array of columns to select from the model(s).
         * @return mixed
         */
        public function paginate(int $numResults = 15, array $columns = ['*']);
    }