<?php

namespace App\Http\Requests\TicketForm;

use App\Http\Requests\CoreRequest;

class StoreTicket extends CoreRequest
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
            'email'                 => 'required|email',
            'name'                  => 'required',
            'ticket_subject'        => 'required',
            'ticket_description'    => 'required',
        ];
    }
}
