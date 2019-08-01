<?php

    namespace Polaris;

    use BadMethodCallException;
    use Closure;
    use Exception as RepositoryException;
    use Illuminate\Container\Container as App;
    use Illuminate\Database\Eloquent\Collection;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Query\Builder;
    use Polaris\Contracts\RepositoryInterface;

    abstract class AbstractRepository implements RepositoryInterface
    {
        /**
         * Reference to the app container.
         *
         * @var App
         */
        protected $app;

        /**
         * The repository's model class.
         *
         * @var
         */
        protected $model;

        /**
         * Holds an intances of the model class.
         *
         * @var Model
         */
        protected $modelInstance;

        /**
         * The query builder reference.
         *
         * @var Builder
         */
        protected $query;

        /**
         * AbstractRepository constructor.
         *
         * @param  App  $app
         * @throws RepositoryException
         */
        public function __construct(App $app)
        {
            $this->app = $app;

            $this->makeModel();
        }

        /**
         * @return Model
         * @throws RepositoryException
         */
        public function makeModel(): Model
        {
            // Verify that the model property is set.
            if (empty($this->model)) {
                throw new RepositoryException('The model class must be set on the repository.');
            }

            // Make a new model instance of model type.
            $this->modelInstance = $this->app->make($this->model);

            // Create the QueryBuilder instance.
            $this->query = $this->modelInstance->newQuery();

            // Return the newly created instance.
            return $this->modelInstance;
        }

        /**
         * Creates a new query builder object for this repository's model.
         *
         * @return AbstractRepository
         */
        public function newQuery(): AbstractRepository
        {
            $this->query = $this->modelInstance->newQuery();

            return $this;
        }

        /**
         * Creates a new model instance and stores It in the database.
         *
         * @param  array  $attributes
         * @return mixed|null
         */
        public function create(array $attributes)
        {
            $instance = $this->newInstance($attributes);

            if ($instance->save()) {
                return $instance;
            }

            return null;
        }

        /**
         * Generate a new instance from the modelInstance class field.
         *
         * @param  array  $attributes
         * @return Model
         */
        public function newInstance(array $attributes = []): Model
        {
            return $this->modelInstance->newInstance($attributes);
        }

        /**
         * Find a model by its primary key.
         *
         * @param       $id
         * @param  array  $columns
         * @return Model|Collection|\Illuminate\Database\Eloquent\Builder[]|static|null
         */
        public function find($id, array $columns = ['*'])
        {
            return $this->where($this->modelInstance->getQualifiedKeyName(), '=', $id)->first($columns);
        }

        /**
         * Where wrapper for Database Query Builder.
         *
         * @param        $column
         * @param  string  $operator
         * @param  null  $value
         * @return Builder
         */
        public function where($column, $operator = '=', $value = null)
        {
            return $this->query->where($column, $operator, $value);
        }

        /**
         * @param  int  $numResults  number of results returned by this method.
         * @param  array  $columns  array of columns to select from the model(s).
         * @return Collection
         */
        public function paginate(int $perPage = 15, array $columns = ['*'], string $pageName = 'page', $page = null)
        {
            return $this->query->paginate($perPage, $columns, $pageName, $page);
        }

        public function all(array $columns = ['*'])
        {
            return $this->get($columns);
        }

        /**
         * Returns all the records in the current constructed query.
         *
         * @param  array  $columns  array of columns to select from the model(s).
         * @return mixed
         */
        public function get(array $columns = ['*'])
        {
            return $this->query->get($columns);
        }

        public function whereLike(string $column, $value)
        {
            $value = str_replace(' ', '%', $value);

            return $this->query->where($column, 'LIKE', '%'.$value.'%');
        }

        public function toSql()
        {
            return $this->query->toSql();
        }

        public function getBindings()
        {
            return $this->query->getBindings();
        }

        public function search($columns, $value)
        {
            if (!is_array($columns)) {
                $columns = [$columns];
            }

            foreach ($columns as $column) {
                $this->query->orWhere(function ($query) use ($column, $value)
                {
                    $query->where($column, 'LIKE', '%'.$value.'%');
                });
            }

            return $this->query;
        }

        public function whereNotIn($column, array $values = [])
        {
            return $this->query->whereNotIn($column, $values);
        }

        public function whereIn($column, array $values = [])
        {
            return $this->query->whereIn($column, $values);
        }

        public function addScopeQuery(Closure $scope)
        {
            $this->query = $scope($this->query);

            return $this;
        }

        public function model()
        {
            return $this->modelIntance;
        }

        public function take($take)
        {
            return $this->query->take($take);
        }

        public function skip($skip)
        {
            return $this->query->skip($skip);
        }

        public function count()
        {
            return $this->query->count();
        }

        /**
         * Set order by SQL clause.
         *
         * @param        $column
         * @param  string  $direction
         * @return mixed
         */
        public function orderBy($column, $direction = 'asc')
        {
            return $this->query->orderBy($column, $direction);
        }

        public function __call($method, $parameters)
        {
            // Check for scopes in repository class. Overrides scopes in model.
            if (method_exists($this, $scope = 'scope'.ucfirst($method))) {
                return call_user_func_array([$this, $scope], $parameters);
            }

            // Check for scopes in model class.
            if (method_exists($this->modelInstance, $scope = 'scope'.ucfirst($method))) {
                array_unshift($parameters, $this->query);

                return call_user_func_array([$this->modelInstance, $scope], $parameters);
            }

            $className = get_class($this);

            throw new BadMethodCallException("Call to undefined method {$className}::{$method}()");
        }
    }