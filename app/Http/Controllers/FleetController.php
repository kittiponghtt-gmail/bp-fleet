<?php

namespace App\Http\Controllers;

use App\Services\FleetDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FleetController extends Controller
{
    public function show(FleetDatabase $fleet): JsonResponse
    {
        return response()->json($fleet->snapshot());
    }

    public function saveVehicle(Request $request, FleetDatabase $fleet, ?string $vehicle = null): JsonResponse
    {
        $fleet->saveVehicle($request->all(), $vehicle);

        return $this->saved($fleet);
    }

    public function saveService(Request $request, FleetDatabase $fleet, ?string $service = null): JsonResponse
    {
        $fleet->saveService($request->all(), $service);

        return $this->saved($fleet);
    }

    public function deleteService(FleetDatabase $fleet, string $service): JsonResponse
    {
        $fleet->deleteService($service);

        return $this->saved($fleet);
    }

    public function saveRule(Request $request, FleetDatabase $fleet, ?string $rule = null): JsonResponse
    {
        $fleet->saveRule($request->all(), $rule);

        return $this->saved($fleet);
    }

    public function setRuleActive(Request $request, FleetDatabase $fleet, string $rule): JsonResponse
    {
        $fleet->setRuleActive($rule, $request->boolean('active'));

        return $this->saved($fleet);
    }

    public function saveOdometer(Request $request, FleetDatabase $fleet): JsonResponse
    {
        $fleet->saveOdometer($request->all());

        return $this->saved($fleet);
    }

    public function deleteOdometer(FleetDatabase $fleet, string $log): JsonResponse
    {
        $fleet->deleteOdometer($log);

        return $this->saved($fleet);
    }

    public function saveDowntime(Request $request, FleetDatabase $fleet): JsonResponse
    {
        $fleet->saveDowntime($request->all());

        return $this->saved($fleet);
    }

    public function deleteDowntime(FleetDatabase $fleet, string $downtime): JsonResponse
    {
        $fleet->deleteDowntime($downtime);

        return $this->saved($fleet);
    }

    public function resolveIssue(FleetDatabase $fleet, int $issue): JsonResponse
    {
        $fleet->resolveIssue($issue);

        return $this->saved($fleet);
    }

    public function setConfig(Request $request, FleetDatabase $fleet): JsonResponse
    {
        $fleet->setTrackingStart($request->input('trackingStart'));

        return $this->saved($fleet);
    }

    public function replace(Request $request, FleetDatabase $fleet): JsonResponse
    {
        $fleet->replace($request->input('data', []));

        return $this->saved($fleet);
    }

    private function saved(FleetDatabase $fleet): JsonResponse
    {
        return response()->json(['fleet' => $fleet->snapshot()]);
    }
}
