<?php

namespace App\Observers;

use App\Events\LeadEvent;
use App\Lead;
use App\Notifications\LeadAgentAssigned;
use App\UniversalSearch;
use App\User;
use Illuminate\Support\Facades\Notification;

class LeadObserver
{

    public function saving(Lead $lead)
    {
        // Cannot put in creating, because saving is fired before creating. And we need company id for check bellow
        if (company()) {
            $lead->company_id = company()->id;
        }
    }

    public function created(Lead $lead)
    {
        if (!isRunningInConsoleOrSeeding()) {
            if (request('agent_id') != '') {
                event(new LeadEvent($lead, $lead->lead_agent, 'LeadAgentAssigned'));
            } else {
                Notification::send(User::frontAllAdmins($lead->company_id), new LeadAgentAssigned($lead));
            }
        }
    }

    public function deleting(Lead $lead){
        $universalSearches = UniversalSearch::where('searchable_id', $lead->id)->where('module_type', 'lead')->get();
        if ($universalSearches){
            foreach ($universalSearches as $universalSearch){
                UniversalSearch::destroy($universalSearch->id);
            }
        }
    }

}
