<?php

namespace Alphasky\Table\Http\Controllers;

use Alphasky\Base\Http\Controllers\BaseController;
use Alphasky\Base\Http\Responses\BaseHttpResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TableInlineEditController extends BaseController
{
    public function inlineEdit(Request $request, BaseHttpResponse $response)
    {
       
        $validator = Validator::make($request->all(), [
            'model' => 'required|string',
            'pk' => 'required',
            'name' => 'required|string',
            'value' => 'nullable',
        ]);

        if ($validator->fails()) {
            return $response->setError()->setMessage($validator->errors()->first());
        }

        $modelClass = $request->input('model');
        $id = $request->input('pk');
        $column = $request->input('name');
        $value = $request->input('value');

        if (! class_exists($modelClass)) {
            return $response->setError()->setMessage(trans('core/base::tables.model_not_found'));
        }

        $model = app($modelClass)->find($id);

        if (! $model) {
            return $response->setError()->setMessage(trans('core/base::tables.record_not_found'));
        }

        try {
            $model->{$column} = $value;
            $model->save();
        } catch (\Exception $e) {
            return $response->setError()->setMessage($e->getMessage());
        }

        return $response->setMessage(trans('core/base::tables.update_success'));
    }
}
