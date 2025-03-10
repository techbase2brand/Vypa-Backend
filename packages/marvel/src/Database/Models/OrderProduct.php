<?php
    namespace Marvel\Database\Models;

    use Illuminate\Database\Eloquent\Relations\Pivot;

    class OrderProduct extends Pivot
    {
            protected $casts = [
                'selectlogo' => 'json', // Cast the JSON column to an array
                'logoUrl' => 'json', // Cast the JSON column to an array
            ];
    }
?>
