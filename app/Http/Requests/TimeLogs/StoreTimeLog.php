<?php

namespace App\Http\Requests\TimeLogs;

use App\Company;
use App\Http\Requests\CoreRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreTimeLog extends CoreRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $setting = Company::with('currency', 'package')->withoutGlobalScope('active')->where('id', company()->id)->first();
        $rules =  [
            'start_time' => 'required',
            'end_time' =>  'required',
            'memo' => 'required',
            'task_id' => 'required',
            'user_id' => 'required',
            'end_date' => 'date_format:"' . $setting->date_format . '"|after_or_equal:start_date',

        ];
        return $rules;
    }

    public function messages()
    {
        return [
            'project_id.required' => __('messages.chooseProject')
        ];
    }
}
