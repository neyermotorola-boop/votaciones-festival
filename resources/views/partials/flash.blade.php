@if(session('ok'))
    <div class="alert alert-success" role="alert"><i class="bi bi-check-circle-fill"></i><div>{{ session('ok') }}</div></div>
@endif
@if(session('error'))
    <div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle-fill"></i><div>{{ session('error') }}</div></div>
@endif
@if($errors->any())
    <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-octagon-fill"></i>
        <div><b>Revisa el formulario</b>
            <ul class="mb-0 ps-3 mt-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    </div>
@endif
