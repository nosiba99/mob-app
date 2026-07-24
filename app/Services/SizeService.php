<?php

namespace App\Services;

use App\Models\Size;

class SizeService
{
    public function getAll()
    {
        return Size::all();
    }

    public function create(array $data)
    {
        return Size::create([
            'name' => $data['name'],
        ]);
    }

    public function delete(Size $size)
    {
        $size->delete();
        return true;
    }
}
