<?php
    
    namespace Polaris;
    
    use BadMethodCallException;
    use Closure;
    use Exception as RepositoryException;
    use Illuminate\Container\Container as App;
    use Illuminate\Database\Eloquent\Collection;
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
         * The model's query builder.
         *
         * @var
         */
        protected $model;
        
        /**
         * AbstractRepository constructor.
         *
         * @param App $app
         * @throws RepositoryException
         */
        public function __construct(App $app)
        {
            $this->app = $app;
            
            $this->makeModel();
        }
        
        /**
         * @return \Illuminate\Database\Eloquent\Builder
         * @throws RepositoryException
         */
        public function makeModel()
        {
            $model = $this->app->make($this->model);
            
            return $this->model = $model->newQuery();
        }
        
        /**
         * Returns all the records in the current constructed query.
         *
         * @param array $columns array of columns to select from the model(s).
         * @return mixed
         */
        public function get(array $columns = ['*'])
        {
            return $this->model->get($columns);
        }
        
        /**
         * @param int $numResults number of results returned by this method.
         * @param array $columns array of columns to select from the model(s).
         * @return Collection
         */
        public function paginate(int $perPage = 15, array $columns = ['*'], string $pageName = 'page', $page = null)
        {
            return $this->model->paginate($perPage, $columns, $pageName, $page);
        }
        
        public function all(array $columns = ['*'])
        {
            return $this->get($columns);
        }
        
        public function whereLike(string $column, $value)
        {
            $value = str_replace(' ', '%', $value);
            
            return $this->model->where($column, 'LIKE', '%' . $value . '%');
        }
        
        public function toSql()
        {
            return $this->model->toSql();
        }
        
        public function getBindings()
        {
            return $this->model->getBindings();
        }
        
        public function search($columns, $value)
        {
            if(!is_array($columns))
            {
                $columns = [$columns];
            }
            
            foreach($columns as $column)
            {
                $this->model->orWhere(function ($query) use ($column, $value)
                {
                    $query->where($column, 'LIKE', '%' . $value . '%');
                });
            }
            
            return $this->model;
        }
        
        public function where($column, $comparator = '=', $value = null)
        {
            return $this->model->where($column, $comparator, $value);
        }
    
        public function whereNotIn($column, array $values = [])
        {
            return $this->model->whereNotIn($column, $values);
        }
    
        public function whereIn($column, array $values = [])
        {
            return $this->model->whereIn($column, $values);
        }
        
        public function addScopeQuery(Closure $scope)
        {
            $this->model = $scope($this->model);
            
            return $this;
        }
    
        public function model()
        {
            return $this->model;
        }
    
        public function take($take)
        {
            return $this->model->take($take);
        }

        public function skip($skip)
        {
            return $this->model->skip($skip);
        }

        public function count()
        {
            return $this->model->count();
        }
        
        public function __call($method, $parameters)
        {
            // Check for scopes
            if(method_exists($this, $scope = 'scope' . ucfirst($method)))
            {
                return call_user_func_array([$this, $scope], $parameters);
            }
            
            $className = get_class($this);
            
            throw new BadMethodCallException("Call to undefined method {$className}::{$method}()");
        }
    }