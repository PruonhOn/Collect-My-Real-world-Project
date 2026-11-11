<?php

return [

    /*
    | VIP test mode.
    |
    | When true the development-only "VIP Test Card" plan ($0.01 / 5 hours via
    | the normal Bakong KHQR flow) is exposed. Production MUST set
    | VIP_TEST_MODE=false: the test card is then hidden from listings and both
    | test purchases and test activation are rejected.
    */
    'test_mode' => (bool) env('VIP_TEST_MODE', false),

];
