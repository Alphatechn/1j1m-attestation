@extends('layouts.front')

@section('title', 'Accueil')

@section('content')
 <!--begin::App Content Header-->
 <div class="app-content-header">
    <!--begin::Container-->
    <div class="container-fluid">
      <!--begin::Row-->
      <div class="row">
        <div class="col-sm-6"><h3 class="mb-0">Accueil</h3></div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Accueil</li>
          </ol>
        </div>
      </div>
      <!--end::Row-->
    </div>
    <!--end::Container-->
  </div>
  <!--end::App Content Header-->
  <!--begin::App Content-->
  <div class="app-content">
    <!--begin::Container-->
    <div class="container-fluid">
      <!--begin::Row-->
      <div class="row">
        <div class="col-12">
          <section class="card border-0 overflow-hidden">
            <div class="card-body p-4 p-md-5" style="background:linear-gradient(120deg,#172033,#2457d6);color:#fff">
              <div class="row align-items-center g-4">
                <div class="col-lg-8">
                  <span class="badge rounded-pill text-bg-light text-primary mb-3">ESPACE D’ADMINISTRATION</span>
                  <h1 class="display-6 fw-bold mb-3">Gérez vos attestations avec sérénité.</h1>
                  <p class="lead mb-4 opacity-75">Centralisez les participants, les périodes de formation et la délivrance des attestations depuis un seul espace.</p>
                  <a class="btn btn-light text-primary fw-semibold me-2 mb-2" href="{{ route('dashboard.manage') }}"><i class="bi bi-speedometer2 me-2"></i>Voir le tableau de bord</a>
                  <a class="btn btn-outline-light mb-2" href="{{ route('participants.index') }}"><i class="bi bi-people me-2"></i>Participants</a>
                </div>
                <div class="col-lg-4 text-center d-none d-lg-block"><i class="bi bi-patch-check-fill" style="font-size:9rem;opacity:.25"></i></div>
              </div>
            </div>
          </section>
        </div>
      </div>
      <!--end::Row-->
    </div>
    <!--end::Container-->
  </div>
  <!--end::App Content-->
@endsection
