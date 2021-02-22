<?php

namespace App\Http\Requests\FollowUp;

use App\Lead;
use Froiden\LaravelInstaller\Request\CoreRequest;

class UpdateFollowUpRequest extends CoreRequest
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
        $lead = Lead::find($this->lead_id);
        if($this->has('type')){
            return [
                'next_follow_up_date' => 'required|date_format:"d/m/Y H:i"|after_or_equal:"' . $lead->created_at->format('d/m/Y H:i') . '"'
            ];
        }
        else{
            $setting = company_setting();
            return [
                'next_follow_up_date' => 'required|after_or_equal:'.$lead->created_at->format($setting->date_format),
            ];
        }
    }
}
