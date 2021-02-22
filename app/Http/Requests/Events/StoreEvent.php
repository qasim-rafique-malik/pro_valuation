<?php

namespace App\Http\Requests\Events;

use Froiden\LaravelInstaller\Request\CoreRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreEvent extends CoreRequest
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
        return [
            'event_name' => 'required',
            'start_date' => 'required',
            'end_date' => 'required|date_format:"' . company()->date_format . '"|after_or_equal:start_date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'all_employees' => 'sometimes',
            'user_id.0' => 'required_unless:all_employees,true',
            'where' => 'required',
            'description' => 'required',
        ];
    }

    public function messages() {
        return [
            'user_id.0.required_unless' => __('messages.atleastOneValidation'),
            'end_time.after' => __('messages.endTimeGreaterThenStart')
        ];
    }
}
