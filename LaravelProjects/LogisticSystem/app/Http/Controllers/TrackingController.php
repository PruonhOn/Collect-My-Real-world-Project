<?php

namespace App\Http\Controllers;

use App\Services\TrackingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackingController extends Controller
{
    public function __construct(
        protected TrackingService $trackingService
    ) {}

    public function track(Request $request): View
    {
        $trackingNumber = $request->get('tracking_number');
        $shipment = null;
        $details = null;

        if ($trackingNumber) {
            $shipment = $this->trackingService->track($trackingNumber);
            $details = $shipment ? $this->trackingService->getTrackingDetails($trackingNumber) : null;
        }

        return view('tracking.index', compact('shipment', 'details', 'trackingNumber'));
    }

    public function show(string $trackingNumber): View
    {
        $shipment = $this->trackingService->track($trackingNumber);
        $details = $this->trackingService->getTrackingDetails($trackingNumber);

        if (!$shipment) {
            abort(404, 'Tracking number not found.');
        }

        return view('tracking.show', compact('shipment', 'details'));
    }
}
