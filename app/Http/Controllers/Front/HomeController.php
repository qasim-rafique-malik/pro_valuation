<?php

namespace App\Http\Controllers\Front;

use App\ClientDetails;
use App\Company;
use App\CreditNotes;
use App\Feature;
use App\FooterMenu;
use App\FrontClients;
use App\FrontDetail;
use App\FrontFaq;
use App\FrontFeature;
use App\GlobalSetting;
use App\Helper\Reply;
use App\Http\Requests\Front\ContactUs\ContactUsRequest;
use App\Http\Requests\Lead\StoreRequest;
use App\Http\Requests\TicketForm\StoreTicket;
use App\Invoice;
use App\InvoiceItems;
use App\Notifications\ContactUsMail;
use App\Package;
use App\PackageSetting;
use App\Project;
use App\Role;
use App\Scopes\CompanyScope;
use App\Setting;
use App\Task;
use App\Testimonials;
use App\Ticket;
use App\TicketCustomForm;
use App\TicketReply;
use App\TicketType;
use App\UniversalSearch;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use App\Module;
use App\InvoiceSetting;
use App\Lead;
use App\LeadCustomForm;
use App\LeadStatus;
use App\OfflinePaymentMethod;
use App\PaymentGatewayCredentials;
use App\SeoDetail;
use App\TaskFile;
use App\TrFrontDetail;
use Illuminate\Support\Facades\App;
use Stripe\Stripe;

class HomeController extends FrontBaseController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $trFrontDetailCount = TrFrontDetail::select('id', 'language_setting_id')->where('language_setting_id', $this->localeLanguage ? $this->localeLanguage->id : null)->count();

        $this->trFrontDetail = TrFrontDetail::where('language_setting_id', $trFrontDetailCount > 0 ? ( $this->localeLanguage ? $this->localeLanguage->id : null ) : null)->first();
        $this->defaultTrFrontDetail = TrFrontDetail::where('language_setting_id', null)->first();
    }

    /**
     * @param null $slug
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index($slug = null)
    {
        if ($this->global->setup_homepage == "custom") {
            return response(
                file_get_contents($this->global->custom_homepage_url)
            );
        } else if ($this->global->setup_homepage == "signup") {
           return $this->loadSignUpPage();
        } else if ($this->global->setup_homepage == "login") {
            return $this->loadLoginPage();
        } else {
            $this->seoDetail = SeoDetail::where('page_name', 'home')->first();

            $this->pageTitle = $this->seoDetail ? $this->seoDetail->seo_title : __('app.menu.home');
            $this->packages = Package::where('default', 'no')->where('is_private', 0)->orderBy('sort', 'ASC')->get();
    
            $imageFeaturesCount = Feature::select('id', 'language_setting_id', 'type')->where(['language_setting_id' => $this->localeLanguage ? $this->localeLanguage->id : null, 'type' => 'image'])->count();
            $iconFeaturesCount = Feature::select('id', 'language_setting_id', 'type')->where(['language_setting_id' => $this->localeLanguage ? $this->localeLanguage->id : null, 'type' => 'icon'])->count();
            $frontClientsCount = FrontClients::select('id', 'language_setting_id')->where('language_setting_id', $this->localeLanguage ? $this->localeLanguage->id : null)->count();
            $testimonialsCount = Testimonials::select('id', 'language_setting_id')->where('language_setting_id', $this->localeLanguage ? $this->localeLanguage->id : null)->count();

            $this->featureWithImages = Feature::where([
                'language_setting_id' => $imageFeaturesCount > 0 ? ( $this->localeLanguage ? $this->localeLanguage->id : null ) : null,
                'type' => 'image'
            ])->whereNull('front_feature_id')->get();
    
            $this->featureWithIcons = Feature::where([
                'language_setting_id' => $iconFeaturesCount > 0 ? ( $this->localeLanguage ? $this->localeLanguage->id : null ) : null,
                'type' => 'icon'
            ])->whereNull('front_feature_id')->get();

            $this->frontClients = FrontClients::where('language_setting_id', $frontClientsCount > 0 ? ( $this->localeLanguage ? $this->localeLanguage->id : null ) : null)->get();
            $this->testimonials = Testimonials::where('language_setting_id', $testimonialsCount > 0 ? ( $this->localeLanguage ? $this->localeLanguage->id : null ) : null)->get();
    
            $this->packageFeaturesModuleData = Module::get();
    
            $this->packageFeatures   = $this->packageFeaturesModuleData->pluck('module_name')->toArray();
            $this->packageModuleData = $this->packageFeaturesModuleData->pluck('module_name', 'id')->all();
    
            $moduleActive = [];
            foreach($this->packageFeatures as $key => $moduleData){
                foreach($this->packages as $packageData)
                {
                    $packageModules = (array)json_decode($packageData->module_in_package);
    
                    if(in_array($moduleData, $packageModules)){
                        $moduleActive[$key] = $moduleData;
                    }
                }
            }
    
            $this->activeModule = $moduleActive;
            // Check if trail is active
            $this->packageSetting = PackageSetting::where('status', 'active')->first();
            $this->trialPackage = Package::where('default','trial')->first();
    
    
            if ($slug) {
                $this->slugData = FooterMenu::where('slug', $slug)->first();
                $this->pageTitle = ucwords($this->slugData->name);
                return view('saas.footer-page', $this->data);
            }
            if ($this->setting->front_design == 1) {
                return view('saas.home', $this->data);
            }
            return view('front.home', $this->data);
        }
        
        
    }

    public function feature()
    {

        $this->seoDetail = SeoDetail::where('page_name', 'feature')->first();

        $this->pageTitle = isset($this->seoDetail) ? $this->seoDetail->seo_title : __('app.menu.features');
        $types = ['task', 'bills', 'team', 'apps'];

        foreach ($types as $type) {
            $featureCount = Feature::select('id', 'language_setting_id', 'type')->where(['language_setting_id' => $this->localeLanguage ? $this->localeLanguage->id : null, 'type' => $type])->count();
            $this->data['feature'.ucfirst(str_plural($type))] = Feature::where([
                'language_setting_id' => $featureCount > 0 ? ( $this->localeLanguage ? $this->localeLanguage->id : null ) : null,
                'type' => $type
            ])->get();
        }

        $frontClientsCount = FrontClients::select('id', 'language_setting_id')->where('language_setting_id', $this->localeLanguage ? $this->localeLanguage->id : null)->count();
        $this->frontClients = FrontClients::where('language_setting_id', $frontClientsCount > 0 ? ( $this->localeLanguage ? $this->localeLanguage->id : null ) : null)->get();
        $iconFeaturesCount = Feature::select('id', 'language_setting_id', 'type')->where(['language_setting_id' => $this->localeLanguage ? $this->localeLanguage->id : null, 'type' => 'icon'])->count();

        $this->frontFeatures = FrontFeature::with('features')->where([
            'language_setting_id' => $iconFeaturesCount > 0 ? ( $this->localeLanguage ? $this->localeLanguage->id : null ) : null,
        ])->get();
        if ($this->setting->front_design != 1) {
            abort(403);
        }

        return view('saas.feature', $this->data);
    }

    public function pricing()
    {
        $this->seoDetail = SeoDetail::where('page_name', 'pricing')->first();
        $this->pageTitle = isset($this->seoDetail) ? $this->seoDetail->seo_title : __('app.menu.pricing');
        $this->packages  = Package::where('default', 'no')->where('is_private', 0)
            ->orderBy('sort', 'ASC')
            ->get();

        $frontFaqsCount = FrontFaq::select('id', 'language_setting_id')->where('language_setting_id', $this->localeLanguage ? $this->localeLanguage->id : null)->count();
        
        $this->frontFaqs = FrontFaq::where('language_setting_id', $frontFaqsCount > 0 ? ( $this->localeLanguage ? $this->localeLanguage->id : null ) : null)->get();

        $this->packageFeaturesModuleData = Module::get();

        $this->packageFeatures   = $this->packageFeaturesModuleData->pluck('module_name')->toArray();
        $this->packageModuleData = $this->packageFeaturesModuleData->pluck('module_name', 'id')->all();

        $moduleActive = [];
        foreach($this->packageFeatures as $key => $moduleData){
            foreach($this->packages as $packageData)
            {
                $packageModules = (array)json_decode($packageData->module_in_package);

                if(in_array($moduleData, $packageModules)){
                    $moduleActive[$key] = $moduleData;
                }
            }
        }

        $this->activeModule = $moduleActive;
        // Check if trail is active
        $this->packageSetting = PackageSetting::where('status', 'active')->first();
        $this->trialPackage = Package::where('default','trial')->first();


        if ($this->setting->front_design != 1) {
            abort(403);
        }

        return view('saas.pricing', $this->data);
    }

    public function contact()
    {
        $this->seoDetail = SeoDetail::where('page_name', 'contact')->first();
        $this->pageTitle = $this->seoDetail ? $this->seoDetail->seo_title : __('app.menu.contact');

        if ($this->setting->front_design != 1) {
            abort(403);
        }
        return view('saas.contact', $this->data);
    }

    public function page($slug = null)
    {

        $this->slugData = FooterMenu::where('slug', $slug)->first();
        if(is_null($this->slugData)){
            abort(404);
        }
        $this->seoDetail = SeoDetail::where('page_name', $this->slugData->slug)->first();
        $this->pageTitle = isset($this->seoDetail) ? $this->seoDetail->seo_title : __('app.menu.contact');

        if ($this->setting->front_design == 1) {
            return view('saas.footer-page', $this->data);
        }
        return view('front.footer-page', $this->data);
    }

    public function contactUs(ContactUsRequest $request)
    {

        $this->pageTitle = 'app.menu.contact';
        $generatedBys = User::allSuperAdmin();
        $frontDetails = FrontDetail::first();
        $this->table = '<table><tbody style="color:#0000009c;">
        <tr>
            <td><p>Name : </p></td>
            <td><p>' . ucwords($request->name) . '</p></td>
        </tr>
        <tr>
            <td><p>Email : </p></td>
            <td><p>' . $request->email . '</p></td>
        </tr>
        <tr>
            <td style="font-family: Avenir, Helvetica, sans-serif;box-sizing: border-box;min-width: 98px;vertical-align: super;"><p style="font-family: Avenir, Helvetica, sans-serif; box-sizing: border-box; color: #74787E; font-size: 16px; line-height: 1.5em; margin-top: 0; text-align: left;">Message : </p></td>
            <td><p>' . $request->message . '</p></td>
        </tr>
</tbody>
        
</table><br>';

        if($frontDetails->email){
            Notification::route('mail', $frontDetails->email)
                ->notify(new ContactUsMail($this->data));
        }
        else{
            Notification::route('mail', $generatedBys)
                ->notify(new ContactUsMail($this->data));
        }


        return Reply::success('Thanks for contacting us. We will catch you soon.');
    }

    public function invoice($id)
    {
        $this->pageTitle = __('app.menu.invoices');
        $this->pageIcon = 'icon-people';

        $this->invoice = Invoice::whereRaw('md5(id) = ?', $id)->with('payment')->firstOrFail();
        App::setLocale(isset($this->invoice->company->locale) ? $this->invoice->company->locale : 'en');
        // public url company session set.
        session(['company' => $this->invoice->company]);
        $this->paidAmount = $this->invoice->getPaidAmount();

        $this->discount = 0;
        if ($this->invoice->discount > 0) {
            $this->discount = $this->invoice->discount;

            if ($this->invoice->discount_type == 'percent') {
                $this->discount = (($this->invoice->discount / 100) * $this->invoice->sub_total);
            }
        }

        $taxList = array();

        $items = InvoiceItems::whereNotNull('taxes')
            ->where('invoice_id', $this->invoice->id)
            ->get();

        foreach ($items as $item) {
            if ($this->invoice->discount > 0 && $this->invoice->discount_type == 'percent') {
                $item->amount = $item->amount - (($this->invoice->discount / 100) * $item->amount);
            }
            foreach (json_decode($item->taxes) as $tax) {
                $this->tax = InvoiceItems::taxbyid($tax)->first();
                if (!isset($taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'])) {
                    $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = ($this->tax->rate_percent / 100) * $item->amount;
                } else {
                    $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + (($this->tax->rate_percent / 100) * $item->amount);
                }
            }
        }

        $this->taxes = $taxList;

        $this->settings = Company::findOrFail($this->invoice->company_id);
        $this->credentials = PaymentGatewayCredentials::where('company_id', $this->invoice->company_id)->first();

        $this->methods = OfflinePaymentMethod::activeMethod();
        $this->invoiceSetting = InvoiceSetting::first();

        return view('invoice', [
            'companyName' => $this->settings->company_name,
            'pageTitle' => $this->pageTitle,
            'pageIcon' => $this->pageIcon,
            'global' => $this->settings,
            'setting' => $this->settings,
            'settings' => $this->settings,
            'invoice' => $this->invoice,
            'paidAmount' => $this->paidAmount,
            'discount' => $this->discount,
            'credentials' => $this->credentials,
            'taxes' => $this->taxes,
            'methods' => $this->methods,
            'invoiceSetting' => $this->invoiceSetting,
        ]);
    }

    public function stripeModal(Request $request){
        $id = $request->invoice_id;
        $this->invoice = Invoice::with('offline_invoice_payment', 'offline_invoice_payment.payment_method')->where([
            'id' => $id,
//            'credit_note' => 0
        ])->firstOrFail();

        $this->settings = $this->global;
        $this->credentials = PaymentGatewayCredentials::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->invoice->company_id)
            ->first();

        if($this->credentials->stripe_secret)
        {
            Stripe::setApiKey($this->credentials->stripe_secret);

            $total = $this->invoice->total;
            $totalAmount = $total;

            $customer = \Stripe\Customer::create([
                'email' => $this->invoice->client->email,
                'name' => $request->clientName,
                'address' => [
                    'line1' => $request->clientName,
                    'city' => $request->city,
                    'state' => $request->state,
                    'country' => $request->country,
                ],
            ]);

            $intent = \Stripe\PaymentIntent::create([
                'amount' => $totalAmount*100,
                'currency' => $this->invoice->currency->currency_code,
                'customer' => $customer->id,
                'setup_future_usage' => 'off_session',
                'payment_method_types' => ['card'],
                'description' => $this->invoice->invoice_number. ' Payment',
                'metadata' => ['integration_check' => 'accept_a_payment', 'invoice_id' => $id]
            ]);

            $this->intent = $intent;
        }
        $customerDetail = [
            'email' => $this->invoice->client->email,
            'name' => $request->clientName,
            'line1' => $request->clientName,
            'city' => $request->city,
            'state' => $request->state,
            'country' => $request->country,
        ];

        $this->customerDetail = $customerDetail;

        $view = view('front.stripe-payment', $this->data)->render();

        return Reply::dataOnly(['view' => $view]);
    }

    public function domPdfObjectForDownload($id)
    {
        $this->invoice = Invoice::whereRaw('md5(id) = ?', $id)->firstOrFail();
        App::setLocale(isset($this->invoice->company->locale) ? $this->invoice->company->locale : 'en');
        $this->paidAmount = $this->invoice->getPaidAmount();
        $this->creditNote = 0;
        if ($this->invoice->credit_note) {
            $this->creditNote = CreditNotes::where('invoice_id', $id)
                ->select('cn_number')
                ->first();
        }

        if ($this->invoice->discount > 0) {
            if ($this->invoice->discount_type == 'percent') {
                $this->discount = (($this->invoice->discount / 100) * $this->invoice->sub_total);
            } else {
                $this->discount = $this->invoice->discount;
            }
        } else {
            $this->discount = 0;
        }

        $taxList = array();

        $items = InvoiceItems::whereNotNull('taxes')
            ->where('invoice_id', $this->invoice->id)
            ->get();

        foreach ($items as $item) {
            if ($this->invoice->discount > 0 && $this->invoice->discount_type == 'percent') {
                $item->amount = $item->amount - (($this->invoice->discount / 100) * $item->amount);
            }
            foreach (json_decode($item->taxes) as $tax) {
                $this->tax = InvoiceItems::taxbyid($tax)->first();
                if (!isset($taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'])) {
                    $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = ($this->tax->rate_percent / 100) * $item->amount;
                } else {
                    $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + (($this->tax->rate_percent / 100) * $item->amount);
                }
            }
        }

        $this->taxes = $taxList;

        $this->settings = $this->global;

        $this->invoiceSetting = InvoiceSetting::where('company_id', $this->invoice->company_id)->first();
        //        return view('invoices.'.$this->invoiceSetting->template, $this->data);

        $pdf = app('dompdf.wrapper');
        $this->company = $this->invoice->company;
        // dd($this->company->address);
        $pdf->loadView('invoices.' . $this->invoiceSetting->template, $this->data);
        $filename = $this->invoice->invoice_number;

        return [
            'pdf' => $pdf,
            'fileName' => $filename
        ];
    }

    public function downloadInvoice($id)
    {

        $this->invoice = Invoice::whereRaw('md5(id) = ?', $id)->firstOrFail();
        App::setLocale(isset($this->invoice->company->locale) ? $this->invoice->company->locale : 'en');
        // Download file uploaded
        if ($this->invoice->file != null) {
            return response()->download(storage_path('app/public/invoice-files') . '/' . $this->invoice->file);
        }

        $pdfOption = $this->domPdfObjectForDownload($id);
        $pdf = $pdfOption['pdf'];
        $filename = $pdfOption['fileName'];

        return $pdf->download($filename . '.pdf');
    }

    public function app()
    {
        return ['data' => GlobalSetting::select('id', 'company_name')->first()];
    }

    public function gantt($ganttProjectId)
    {
        $this->project = Project::whereRaw('md5(id) = ?', $ganttProjectId)->firstOrFail();
        $this->settings = Setting::findOrFail($this->project->company_id);
        $this->ganttProjectId = $ganttProjectId;

        return view('gantt', [
            'ganttProjectId' => $this->ganttProjectId,
            'global' => $this->settings,
            'project' => $this->project
        ]);
    }

    public function ganttData($ganttProjectId)
    {

        $data = array();
        $links = array();

        $projects = Project::select('id', 'project_name', 'start_date', 'deadline', 'completion_percent')
            ->whereRaw('md5(id) = ?', $ganttProjectId)
            ->get();

        $id = 0; //count for gantt ids
        foreach ($projects as $project) {
            $id = $id + 1;
            $projectId = $id;

            // TODO::ProjectDeadline to do
            $projectDuration = 0;
            if ($project->deadline) {
                $projectDuration = $project->deadline->diffInDays($project->start_date);
            }

            $data[] = [
                'id' => $projectId,
                'text' => ucwords($project->project_name),
                'start_date' => $project->start_date->format('Y-m-d H:i:s'),
                'duration' => $projectDuration,
                'progress' => $project->completion_percent / 100,
                'project_id' => $project->id
            ];

            $tasks = Task::projectOpenTasks($project->id);

            foreach ($tasks as $key => $task) {
                $id = $id + 1;

                $taskDuration = $task->due_date->diffInDays($task->start_date);
                $taskDuration = $taskDuration + 1;

                $data[] = [
                    'id' => $task->id,
                    'text' => ucfirst($task->heading),
                    'start_date' => (!is_null($task->start_date)) ? $task->start_date->format('Y-m-d') : $task->due_date->format('Y-m-d'),
                    'duration' => $taskDuration,
                    'parent' => $projectId,
                    'taskid' => $task->id
                ];

                $links[] = [
                    'id' => $id,
                    'source' => $task->dependent_task_id != '' ? $task->dependent_task_id : $projectId,
                    'target' => $task->id,
                    'type' => $task->dependent_task_id != '' ? 0 : 1
                ];
            }
        }

        $ganttData = [
            'data' => $data,
            'links' => $links
        ];

        return response()->json($ganttData);
    }

    public function changeLanguage($lang)
    {
        $cookie = Cookie::forever('language', $lang);
        return redirect()->back()->withCookie($cookie);
    }

    public function taskShare($id)
    {
        $this->pageTitle = __('app.task');

        $this->task = Task::with('board_column', 'subtasks', 'project', 'users')->whereRaw('md5(id) = ?', $id)->firstOrFail();
        $this->settings = Setting::findOrFail($this->task->company_id);

        return view('task-share', [
            'task' => $this->task,
            'global' => $this->settings
        ]);
    }

    public function taskFiles($id)
    {
        $this->taskFiles = TaskFile::where('task_id', $id)->get();
        return view('task-files', ['taskFiles' => $this->taskFiles]);
    }

    /**
     * load signup page on home
     *
     * @return \Illuminate\Http\Response
     */
    public function loadSignUpPage()
    {
        if (\user()) {
            return redirect(getDomainSpecificUrl(route('login'), \user()->company));
        }
        $this->seoDetail = SeoDetail::where('page_name', 'home')->first();
        $this->pageTitle = 'Sign Up';

        $view = ($this->setting->front_design == 1) ? 'saas.register' : 'front.register';

        $global = GlobalSetting::first();
        
        if ($global->frontend_disable) {
            $view = 'auth.register';
        }
        $trFrontDetailCount = TrFrontDetail::select('id', 'language_setting_id')->where('language_setting_id', $this->localeLanguage ? $this->localeLanguage->id : null)->count();

        $this->trFrontDetail = TrFrontDetail::where('language_setting_id', $trFrontDetailCount > 0 ? ( $this->localeLanguage ? $this->localeLanguage->id : null ) : null)->first();
        $this->defaultTrFrontDetail = TrFrontDetail::where('language_setting_id', null)->first();
        return view($view, $this->data);
    }

    /**
     * show login page on home
     *
     * @return \Illuminate\Http\Response
     */
    public function loadLoginPage()
    {
        if (\user()) {
            return redirect(getDomainSpecificUrl(route('login'), \user()->company));
        }

        /*if (!$this->isLegal()) {
            return redirect('verify-purchase');
        }*/

        if ($this->global->frontend_disable) {
            return view('auth.login', $this->data);
        }

        if(module_enabled('Subdomain')){
            $this->pageTitle = __('subdomain::app.core.workspaceTitle');

            $view = ($this->setting->front_design == 1) ? 'subdomain::saas.workspace' : 'subdomain::workspace';
            return view($view, $this->data);
        }

        if ($this->setting->front_design == 1 && $this->setting->login_ui == 1) {
            return view('saas.login', $this->data);
        }
        $this->pageTitle = 'Login Page';
        return view('auth.login', $this->data);
    }

    /**
     * custom lead form
     *
     * @return \Illuminate\Http\Response
     */
    public function leadForm($id)
    {
        $this->pageTitle = 'modules.lead.leadForm';
        $this->leadFormFields = LeadCustomForm::where('status', 'active')
            ->whereRaw('md5(company_id) = ?', $id)
            ->orderBy('field_order', 'asc')
            ->get();

        $this->settings = Setting::whereRaw('md5(id) = ?', $id)->first();

        return view('lead-form', [
            'pageTitle' => $this->pageTitle,
            'leadFormFields' => $this->leadFormFields,
            'global' => $this->settings
        ]);
    }

    /**
     * save lead
     *
     * @return \Illuminate\Http\Response
     */
    public function leadStore(StoreRequest $request)
    {
        $leadStatus = LeadStatus::where('default', '1')->first();
        $settings = \App\Setting::find($request->company_id);

        $lead = new Lead();
        $lead->company_name = (request()->has('company_name') ? $request->company_name : '');
        $lead->website = (request()->has('website') ? $request->website : '');
        $lead->address = (request()->has('address') ? $request->address : '');
        $lead->client_name = (request()->has('client_name') ? $request->client_name : '');
        $lead->client_email = (request()->has('client_email') ? $request->client_email : '');
        $lead->mobile = (request()->has('mobile') ? $request->mobile : '');
        $lead->status_id = $leadStatus->id;
        $lead->value = 0;
        $lead->currency_id = $settings->currency->id;
        $lead->company_id = $request->company_id;
        $lead->save();

        return Reply::success(__('messages.LeadAddedUpdated'));
    }


    /**
     * custom lead form
     *
     * @return \Illuminate\Http\Response
     */
    public function ticketForm($id)
    {
        $this->pageTitle = 'app.ticketForm';
        $this->ticketFormFields = TicketCustomForm::where('status', 'active')
            ->whereRaw('md5(company_id) = ?', $id)
            ->orderBy('field_order', 'asc')
            ->get();
        $this->types = TicketType::whereRaw('md5(company_id) = ?', $id)->get();
        $this->settings = Setting::whereRaw('md5(id) = ?', $id)->first();

        return view('embed-forms.ticket-form', [
            'pageTitle' => $this->pageTitle,
            'ticketFormFields' => $this->ticketFormFields,
            'global' => $this->settings,
            'types' => $this->types
        ]);
    }

    /**
     * save lead
     *
     * @return \Illuminate\Http\Response
     */
    public function ticketStore(StoreTicket $request)
    {
        $existing_user = User::withoutGlobalScopes(['active', CompanyScope::class])->select('id', 'email')->where('email', $request->input('email'))->first();
        $newUser = $existing_user;
        if (!$existing_user) {
            $password = str_random(8);
            // create new user
            $client = new User();
            $client->name           = $request->input('name');
            $client->email          = $request->input('email');
            $client->password       = Hash::make($password);
            $client->company_id     = $request->company_id;;
            $client->save();

            // attach role
            $role = Role::where('name', 'client')->first();
            $client->attachRole($role->id);

            $clientDetail = new ClientDetails();
            $clientDetail->user_id      = $client->id;
            $clientDetail->name         = $request->input('name');
            $clientDetail->email        = $request->input('email');
            $clientDetail->company_id   = $request->company_id;;
            $clientDetail->save();

            // log search
            if (!is_null($client->company_name)) {
                $user_id = $existing_user ? $existing_user->id : $client->id;
                $this->logSearchEntry($user_id, $client->company_name, 'admin.clients.edit', 'client');
            }
            //log search
            $this->logSearchEntry($client->id, $request->name, 'admin.clients.edit', 'client');
            $this->logSearchEntry($client->id, $request->email, 'admin.clients.edit', 'client');
            $newUser = $client;
        }

        // Create New Ticket
        $ticket = new Ticket();
        $ticket->subject        = (request()->has('ticket_subject') ? $request->ticket_subject : '');;
        $ticket->status         = 'open';
        $ticket->user_id        = $newUser->id;
        $ticket->type_id        = (request()->has('type') ? $request->type : null);
        $ticket->priority       = (request()->has('priority') ? $request->priority : null);
        $ticket->company_id     = $request->company_id;
        $ticket->save();

        //save first message
        $reply = new TicketReply();
        $reply->message     = (request()->has('ticket_description') ? $request->ticket_description : '');
        $reply->ticket_id   = $ticket->id;
        $reply->user_id     = $newUser->id;; //current logged in user
        $reply->company_id  = $request->company_id;
        $reply->save();

        return Reply::success(__('messages.ticketAddSuccess'));
    }

    public function logSearchEntry($searchableId, $title, $route, $type)
    {
        $search = new UniversalSearch();
        $search->searchable_id  = $searchableId;
        $search->title          = $title;
        $search->route_name     = $route;
        $search->module_type    = $type;
        $search->save();
    }
}


