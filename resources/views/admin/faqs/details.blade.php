<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h4 class="modal-title">{{ $faqDetails->title }}</h4>
</div>
<div class="modal-body">
    <div class="portlet-body">
        <div class="row">
            <div class="col-xs-12">
                {!! $faqDetails->description !!}
            </div>
            <div class="col-md-6">
                <img width="50%" height="50%" src="{{ $faqDetails->image_url }} " alt=""/>
            </div>
        </div>

    </div>
</div>