<?php
    
    namespace Polaris;
    
    use Exception as RepositoryException;
    use Illuminate\Container\Container as App;
    use Illuminate\Database\Eloquent\Collection;
    use Illuminate\Database\Eloquent\Model;
    use Polaris\Contracts\RepositoryInterface;
    use RuntimeException;

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
            
            if(! $model instanceof Model)
            {
                throw new RuntimeException("Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model");
            }
            
            return $this->model = $model->newQuery();
        }
    
        /**
         * Returns all the records in the current constructed query.
         *
         * @param array $columns array of columns to select from the model(s).
         * @return mixed
         */
        public function get(array $columns = ['*']) : Collection
        {
            return $this->model->get($columns);
        }
    
        /**
         * @param int $numResults number of results returned by this method.
         * @param array $columns array of columns to select from the model(s).
         * @return Collection
         */
        public function paginate(int $numResults = 15, array $columns = ['*']) : Collection
        {
            return $this->model->paginate($columns);
        }
    }