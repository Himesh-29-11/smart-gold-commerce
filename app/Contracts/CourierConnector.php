<?php

namespace App\Contracts;

use App\Models\Order;
use App\Models\Shipment;
use App\Services\Courier\CourierShipmentResult;

interface CourierConnector
{
    public function createShipment(Order $order, Shipment $shipment): CourierShipmentResult;

    public function refreshTracking(Shipment $shipment): CourierShipmentResult;
}
