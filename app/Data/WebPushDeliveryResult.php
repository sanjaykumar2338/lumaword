<?php

namespace App\Data;

readonly class WebPushDeliveryResult
{
    public function __construct(
        public bool $successful,
        public bool $expired = false,
    ) {}
}
