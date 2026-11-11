@extends('layouts.admin')

@section('title', 'Shipping Label - ' . $shipment->tracking_number)

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-2 border-primary">
                <div class="card-header bg-primary text-white text-center py-3">
                    <h4 class="mb-0 fw-bold">{{ config('app.name', 'LogiPro') }}</h4>
                    <small>Shipping Label</small>
                </div>
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <h5 class="fw-bold text-primary">{{ $shipment->tracking_number }}</h5>
                        {!! QrCode::size(120)->generate($shipment->tracking_number) !!}
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <small class="text-muted d-block">FROM</small>
                            <strong>{{ $shipment->shipper_name ?? $shipment->origin_address ?? 'N/A' }}</strong>
                            <small class="d-block">{{ $shipment->origin_city ?? '' }}{{ $shipment->origin_province ? ', ' . $shipment->origin_province : '' }}</small>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">TO</small>
                            <strong>{{ $shipment->consignee_name ?? $shipment->destination_address ?? 'N/A' }}</strong>
                            <small class="d-block">{{ $shipment->destination_city ?? '' }}{{ $shipment->destination_province ? ', ' . $shipment->destination_province : '' }}</small>
                        </div>
                    </div>

                    <hr>

                    <div class="row text-center">
                        <div class="col-4">
                            <small class="text-muted d-block">Weight</small>
                            <strong>{{ number_format($shipment->weight ?? 0, 2) }} kg</strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Pieces</small>
                            <strong>{{ $shipment->pieces ?? 0 }}</strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Date</small>
                            <strong>{{ $shipment->shipment_date?->format('d M Y') ?? date('d M Y') }}</strong>
                        </div>
                    </div>

                    @if($shipment->is_express)
                        <div class="text-center mt-3">
                            <span class="badge bg-danger fs-6 px-3 py-2">EXPRESS</span>
                        </div>
                    @endif

                    @if($shipment->description)
                        <hr>
                        <small class="text-muted">Description:</small>
                        <p class="mb-0">{{ $shipment->description }}</p>
                    @endif
                </div>
                <div class="card-footer text-center text-muted small py-2">
                    {{ $shipment->tracking_number }} | {{ date('d M Y H:i') }}
                </div>
            </div>
        </div>
    </div>
    <div class="text-center mt-3">
        <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer"></i> Print Label</button>
    </div>
@endsection
