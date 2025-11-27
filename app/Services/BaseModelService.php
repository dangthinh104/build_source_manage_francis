<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

class BaseModelService
{
    /**
     * @var Model
     */
    public Model $model;
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * @param array $request
     *
     */
    public function update(array $request)
    {
        $target = $this->model->find($request['id']);
        return $target->update($request);
    }

    /**
     * @param array $request
     *
     * @return bool
     */
    public function destroy(array $request): bool
    {
        return $this->model::update($request);
    }

}
