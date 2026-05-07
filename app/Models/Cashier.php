<?php

namespace App\Models;

// Deprecated compatibility wrapper: use `Caissier` (French) instead.
// Keep `Cashier` class so any existing references won't break.

class Cashier extends Caissier
{
    // intentionally empty - aliased to Caissier
}
