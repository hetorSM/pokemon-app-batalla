<?php

try {
    echo "Checking DB connection...\n";
    $count = \App\Models\Move::count();
    echo "Moves count: $count\n";

    echo "Checking 'tackle'...\n";
    $tackle = \App\Models\Move::where('name', 'tackle')->first();
    if ($tackle) {
        echo "Tackle found in DB.\n";
    }
    else {
        echo "Tackle NOT found in DB.\n";
    }

    echo "Checking 'splash'...\n";
    $splash = \App\Models\Move::where('name', 'splash')->first();
    if ($splash) {
        echo "Splash found in DB.\n";
    }
    else {
        echo "Splash NOT found in DB.\n";
    }

}
catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}